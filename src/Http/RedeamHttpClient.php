<?php

declare(strict_types=1);

namespace iabduul7\LaravelThemeparkBookingAdapters\Http;

use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Http;

class RedeamHttpClient
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly string $apiKey,
        private readonly string $apiSecret,
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
        $response = Http::timeout($this->timeout)
            ->withHeaders($this->getHeaders())
            ->asForm()
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
        $response = Http::timeout($this->timeout)
            ->withHeaders($this->getHeaders())
            ->asJson()
            ->post($this->getUrl($uri), $data);

        return $response->json() ?? [];
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     * @throws GuzzleException
     */
    public function put(string $uri, array $data = []): array
    {
        $response = Http::timeout($this->timeout)
            ->withHeaders($this->getHeaders())
            ->asJson()
            ->put($this->getUrl($uri), $data);

        return $response->json() ?? [];
    }

    /**
     * @param array<string, mixed> $parameters
     * @return array<string, mixed>
     * @throws GuzzleException
     */
    public function delete(string $uri, array $parameters = []): array
    {
        $response = Http::timeout($this->timeout)
            ->withHeaders($this->getHeaders())
            ->delete($this->getUrl($uri), $parameters);

        return $response->json() ?? [];
    }

    /**
     * @return array<string, string>
     */
    private function getHeaders(): array
    {
        return [
            'X-API-Key' => $this->apiKey,
            'X-API-Secret' => $this->apiSecret,
        ];
    }

    private function getUrl(string $uri): string
    {
        return rtrim($this->baseUrl, '/') . '/' . ltrim($uri, '/');
    }
}
