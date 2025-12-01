<?php

declare(strict_types=1);

namespace iabduul7\LaravelThemeparkBookingAdapters\Http;

use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class SmartOrderHttpClient
{
    private ?string $accessToken = null;
    private ?string $tokenExpiresAt = null;

    public function __construct(
        private readonly string $baseUrl,
        private readonly string $clientId,
        private readonly string $clientSecret,
        private readonly int $customerId,
        private readonly int $timeout = 600
    ) {
    }

    /**
     * @param array<string, mixed> $parameters
     * @return array<string, mixed>
     * @throws GuzzleException
     */
    public function get(string $uri, array $parameters = []): array
    {
        $parameters['customerId'] = $this->customerId;

        $response = Http::timeout($this->timeout)
            ->withHeaders($this->getAuthHeaders())
            ->get($this->getUrl($uri), $parameters);

        return $response->json() ?? [];
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     * @throws GuzzleException
     */
    public function post(string $uri, array $data = []): array
    {
        $data['customerId'] = $this->customerId;

        $response = Http::timeout($this->timeout)
            ->withHeaders($this->getAuthHeaders())
            ->asJson()
            ->post($this->getUrl($uri), $data);

        return $response->json() ?? [];
    }

    /**
     * @return array<string, string>
     */
    private function getAuthHeaders(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->getAccessToken(),
        ];
    }

    private function getAccessToken(): string
    {
        // Check if we have a valid cached token
        if ($this->accessToken && $this->tokenExpiresAt && now()->isBefore($this->tokenExpiresAt)) {
            return $this->accessToken;
        }

        // Try to get from cache first
        $cacheKey = 'smartorder_token_' . md5($this->clientId . $this->customerId);
        $cachedToken = Cache::get($cacheKey);

        if ($cachedToken && isset($cachedToken['token'], $cachedToken['expires_at'])) {
            $expiresAt = \Carbon\Carbon::parse($cachedToken['expires_at']);
            if (now()->isBefore($expiresAt)) {
                $this->accessToken = $cachedToken['token'];
                $this->tokenExpiresAt = $expiresAt;

                return $this->accessToken;
            }
        }

        // Refresh token if not cached or expired
        $this->refreshAccessToken();

        return $this->accessToken;
    }

    private function refreshAccessToken(): void
    {
        $response = Http::timeout($this->timeout)
            ->asForm()
            ->post($this->getUrl('connect/token'), [
                'grant_type' => 'client_credentials',
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'scope' => 'SmartOrder',
            ]);

        $data = $response->json();
        $this->accessToken = $data['access_token'] ?? null;
        $expiresIn = $data['expires_in'] ?? 3600;

        if ($this->accessToken === null) {
            throw new \Exception('Failed to obtain access token from SmartOrder API');
        }

        // Calculate expiration time (subtract 5 minutes for safety)
        $this->tokenExpiresAt = now()->addSeconds($expiresIn - 300);

        // Cache the token
        $cacheKey = 'smartorder_token_' . md5($this->clientId . $this->customerId);
        Cache::put($cacheKey, [
            'token' => $this->accessToken,
            'expires_at' => $this->tokenExpiresAt->toISOString(),
        ], $this->tokenExpiresAt);
    }

    private function getUrl(string $uri): string
    {
        return rtrim($this->baseUrl, '/') . '/' . ltrim($uri, '/');
    }
}
