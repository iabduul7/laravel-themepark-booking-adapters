<?php

namespace Iabduul7\ThemeParkAdapters\Providers\Universal;

use Iabduul7\ThemeParkAdapters\Abstracts\BaseThemeParkAdapter;
use Iabduul7\ThemeParkAdapters\Contracts\TokenRepositoryInterface;
use Iabduul7\ThemeParkAdapters\Exceptions\ThemeParkApiException;
use Iabduul7\ThemeParkAdapters\TokenRepository\CacheTokenRepository;

class UniversalSmartOrder2Adapter extends BaseThemeParkAdapter
{
    protected string $baseUrl;
    protected int $customerId;
    protected string $approvedSuffix;
    protected TokenRepositoryInterface $tokenRepository;

    public function __construct(array $config = [], ?TokenRepositoryInterface $tokenRepository = null)
    {
        parent::__construct($config);

        if (! $this->hasRequiredConfig(['client_username', 'client_secret'])) {
            throw ThemeParkApiException::invalidCredentials();
        }

        $host = $this->getConfig('host', 'QACorpAPI.ucdp.net');
        $this->baseUrl = "https://{$host}";
        $this->customerId = (int) $this->getConfig('customer_id', 0);
        $this->approvedSuffix = $this->getConfig('approved_suffix', '');
        $this->tokenRepository = $tokenRepository ?? new CacheTokenRepository('smartorder');
    }

    /**
     * Get all products (catalog)
     */
    public function getAllProducts(array $parameters = []): array
    {
        $response = $this->makeRequest('POST', "{$this->baseUrl}/Product/GetAll", [
            'headers' => $this->getAuthHeaders(),
            'json' => $parameters,
        ]);

        return $this->parseJsonResponse($response);
    }

    /**
     * Get available months for booking (next 12 months)
     */
    public function getAvailableMonths(): array
    {
        $months = [];
        $currentDate = now();

        for ($i = 0; $i < 12; $i++) {
            $months[] = $currentDate->copy()->addMonths($i)->format('Y-m');
        }

        return $months;
    }

    /**
     * Find events/availability
     */
    public function findEvents(array $parameters = []): array
    {
        $response = $this->makeRequest('POST', "{$this->baseUrl}/Event/Find", [
            'headers' => $this->getAuthHeaders(),
            'json' => $parameters,
        ]);

        return $this->parseJsonResponse($response);
    }

    /**
     * Place an order
     */
    public function placeOrder(array $parameters = []): array
    {
        $response = $this->makeRequest('POST', "{$this->baseUrl}/Order/Place", [
            'headers' => $this->getAuthHeaders(),
            'json' => $parameters,
        ]);

        return $this->parseJsonResponse($response);
    }

    /**
     * Get existing order details
     */
    public function getExistingOrder(array $parameters = []): array
    {
        $response = $this->makeRequest('POST', "{$this->baseUrl}/Order/GetExistingOrder", [
            'headers' => $this->getAuthHeaders(),
            'json' => $parameters,
        ]);

        return $this->parseJsonResponse($response);
    }

    /**
     * Check if an order can be cancelled
     */
    public function canCancelOrder(array $parameters = []): bool
    {
        $response = $this->makeRequest('POST', "{$this->baseUrl}/Order/CanCancelOrder", [
            'headers' => $this->getAuthHeaders(),
            'json' => $parameters,
        ]);

        $data = $this->parseJsonResponse($response);

        return $data['canCancel'] ?? false;
    }

    /**
     * Cancel an order
     */
    public function cancelOrder(array $parameters = []): array
    {
        $response = $this->makeRequest('POST', "{$this->baseUrl}/Order/CancelOrder", [
            'headers' => $this->getAuthHeaders(),
            'json' => $parameters,
        ]);

        return $this->parseJsonResponse($response);
    }

    /**
     * Validate API credentials
     */
    public function validateCredentials(): bool
    {
        try {
            // Try to get a token
            $token = $this->getToken();

            return ! empty($token);
        } catch (ThemeParkApiException $e) {
            return false;
        }
    }

    /**
     * Get the provider name
     */
    public function getProviderName(): string
    {
        return 'Universal (SmartOrder2)';
    }

    /**
     * Get or refresh OAuth token
     */
    protected function getToken(): string
    {
        // Check if we have a valid cached token
        $token = $this->tokenRepository->getValidToken();

        if ($token) {
            return $token;
        }

        // Refresh the token
        return $this->refreshToken();
    }

    /**
     * Refresh OAuth token using client credentials
     */
    protected function refreshToken(): string
    {
        $response = $this->httpClient->request('POST', "{$this->baseUrl}/connect/token", [
            'form_params' => [
                'grant_type' => 'client_credentials',
                'client_id' => $this->getConfig('client_username'),
                'client_secret' => $this->getConfig('client_secret'),
                'scope' => 'SmartOrder',
            ],
            'timeout' => $this->getConfig('timeout', 600),
        ]);

        $data = json_decode((string) $response->getBody(), true);

        if (! isset($data['access_token'])) {
            throw ThemeParkApiException::apiError('Failed to obtain OAuth token');
        }

        $token = $data['access_token'];
        $expiresIn = $data['expires_in'] ?? 3600;

        // Store the token
        $this->tokenRepository->storeToken($token, $expiresIn);

        return $token;
    }

    /**
     * Get authentication headers with OAuth token
     */
    protected function getAuthHeaders(): array
    {
        $token = $this->getToken();

        return [
            'Authorization' => "Bearer {$token}",
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
    }

    /**
     * Get customer ID
     */
    public function getCustomerId(): int
    {
        return $this->customerId;
    }

    /**
     * Get approved suffix
     */
    public function getApprovedSuffix(): string
    {
        return $this->approvedSuffix;
    }
}
