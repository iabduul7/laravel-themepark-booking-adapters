<?php

namespace Iabduul7\ThemeParkAdapters\Abstracts;

use Closure;
use Iabduul7\ThemeParkAdapters\Contracts\Capabilities\SupportsEvents;
use Iabduul7\ThemeParkAdapters\Contracts\TokenRepositoryInterface;
use Iabduul7\ThemeParkAdapters\Exceptions\ThemeParkApiException;
use Iabduul7\ThemeParkAdapters\TokenRepository\CacheTokenRepository;
use Illuminate\Http\Client\Response;

/**
 * Shared SmartOrder transport + event/order lifecycle for the Universal adapter.
 * Mirrors CodeCreatives\LaravelSmartOrder's SmartOrderApiClient + client:
 * OAuth2 client_credentials bearer auth, customerId injected into every request,
 * retried idempotent reads, non-retried writes, and 401 self-heal (refresh + retry once).
 *
 * The upstream client persists tokens in an app Eloquent model; this package
 * instead uses an injectable TokenRepository so it stays free of app coupling.
 */
abstract class AbstractSmartOrderAdapter extends BaseThemeParkAdapter implements SupportsEvents
{
    protected string $baseUrl;

    protected ?int $customerId;

    protected string $approvedSuffix;

    protected bool $useTokenCache;

    protected TokenRepositoryInterface $tokenRepository;

    /**
     * A token minted mid-request by the 401 self-heal retry in
     * {@see sendWithAuthRetry()}. Scoped to that single retry (reset in its
     * `finally`) so the retried request reuses it instead of {@see getToken()}
     * triggering yet another refresh when the token cache is disabled.
     */
    protected ?string $freshToken = null;

    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(array $config = [], ?TokenRepositoryInterface $tokenRepository = null)
    {
        parent::__construct($config);

        $host = (string) $this->getConfig('host', 'QACorpAPI.ucdp.net');
        $this->baseUrl = "https://{$host}";

        $customerId = $this->getConfig('customer_id');
        $this->customerId = $customerId !== null ? (int) $customerId : null;
        $this->approvedSuffix = (string) $this->getConfig('approved_suffix', '');

        // Upstream currently disables the local token cache and refreshes on every
        // call (a server-side invalidation otherwise looks like an empty catalog).
        // Default to caching here, but expose a toggle to exactly match upstream.
        $this->useTokenCache = (bool) $this->getConfig('token_cache', true);

        // Scope the cache key by a credentials fingerprint, not just the provider
        // name, so two same-provider adapter instances with different credentials
        // (e.g. two SmartOrder accounts) never share a cached token.
        $scope = substr(sha1(implode('|', [
            (string) $this->getConfig('host'),
            (string) $this->getConfig('client_username'),
            (string) $this->customerId,
        ])), 0, 12);

        $this->tokenRepository = $tokenRepository ?? new CacheTokenRepository($this->getProviderName() . '_' . $scope);
    }

    public function getProviderName(): string
    {
        return 'smartorder';
    }

    public function validateCredentials(): bool
    {
        try {
            return $this->getToken() !== '';
        } catch (ThemeParkApiException) {
            return false;
        }
    }

    public function getCustomerId(): ?int
    {
        return $this->customerId;
    }

    public function getApprovedSuffix(): string
    {
        return $this->approvedSuffix;
    }

    protected function url(string $uri): string
    {
        return "{$this->baseUrl}/{$uri}";
    }

    /**
     * @param  array<string, string>  $extra
     * @return array<string, string>
     */
    protected function authHeaders(array $extra = []): array
    {
        return array_merge([
            'Authorization' => "Bearer {$this->getToken()}",
        ], $extra);
    }

    protected function getToken(): string
    {
        if ($this->freshToken !== null) {
            return $this->freshToken;
        }

        if ($this->useTokenCache) {
            $token = $this->tokenRepository->getValidToken();
            if ($token !== null && $token !== '') {
                return $token;
            }
        }

        return $this->refreshToken();
    }

    protected function refreshToken(): string
    {
        $response = $this->http()->asForm()->post($this->url('connect/token'), [
            'grant_type' => 'client_credentials',
            'client_id' => $this->getConfig('client_username'),
            'client_secret' => $this->getConfig('client_secret'),
            'scope' => 'SmartOrder',
        ]);

        if ($response->failed()) {
            throw ThemeParkApiException::requestFailed($response->status(), $response->json() ?? []);
        }

        $token = $response->json('access_token');

        if (! is_string($token) || $token === '') {
            throw ThemeParkApiException::apiError('Failed to obtain SmartOrder OAuth token.');
        }

        $expiresIn = (int) ($response->json('expires_in') ?? 3600);
        $this->tokenRepository->storeToken($token, $expiresIn);

        return $token;
    }

    /**
     * Execute the request and, on a 401, force a token refresh and retry once so
     * a server-side-invalidated token self-heals instead of yielding an empty body.
     *
     * @param  Closure(): Response  $request
     * @return array<string, mixed>|null
     */
    protected function sendWithAuthRetry(Closure $request): ?array
    {
        $response = $request();

        if ($response->status() === 401) {
            // Mint the replacement token once and hand it to getToken() via
            // $freshToken, so the retried $request() call reuses it instead of
            // triggering a second, redundant refresh (notably when the token
            // cache is disabled and getToken() would otherwise refresh again).
            $this->freshToken = $this->refreshToken();

            try {
                $response = $request();
            } finally {
                $this->freshToken = null;
            }
        }

        if ($response->failed()) {
            throw ThemeParkApiException::requestFailed($response->status(), $response->json() ?? []);
        }

        return $response->json();
    }

    /**
     * Idempotent GET with customerId injection, transient-failure retry and 401 self-heal.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>|null
     */
    protected function getRequest(string $uri, array $payload = []): ?array
    {
        $payload['customerId'] = $this->customerId;

        return $this->sendWithAuthRetry(
            fn (): Response => $this->retryReads($this->http()->withHeaders($this->authHeaders()))
                ->get($this->url($uri), $payload)
        );
    }

    /**
     * Write request (no retry) with customerId injection and 401 self-heal.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>|null
     */
    protected function postRequest(string $uri, array $payload = []): ?array
    {
        $payload['customerId'] = $this->customerId;

        return $this->sendWithAuthRetry(
            fn (): Response => $this->http()->asJson()->withHeaders($this->authHeaders())
                ->post($this->url($uri), $payload)
        );
    }

    // --- SupportsEvents: SmartOrder business surface (matches upstream SmartOrderClient) ---

    public function findEvents(array $parameters): ?array
    {
        return $this->postRequest('smartorder/FindEvents', $parameters);
    }

    public function placeOrder(array $parameters): ?array
    {
        return $this->postRequest('smartorder/PlaceOrder', $parameters);
    }

    public function getExistingOrder(array $parameters): ?array
    {
        try {
            return $this->getRequest('smartorder/GetExistingOrderId', $parameters);
        } catch (ThemeParkApiException $e) {
            throw $e->getCode() === 404
                ? ThemeParkApiException::orderNotFound((string) ($parameters['ExternalOrderId'] ?? ''))
                : $e;
        }
    }

    public function canCancelOrder(array $parameters): ?array
    {
        return $this->getRequest('smartorder/CanCancelOrder', $parameters);
    }

    public function cancelOrder(array $parameters): ?array
    {
        return $this->getRequest('smartorder/CancelOrder', $parameters);
    }
}
