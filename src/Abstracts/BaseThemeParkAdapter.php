<?php

namespace Iabduul7\ThemeParkAdapters\Abstracts;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Iabduul7\ThemeParkAdapters\Contracts\ThemeParkAdapterInterface;
use Iabduul7\ThemeParkAdapters\Exceptions\ThemeParkApiException;
use Psr\Http\Message\ResponseInterface;

abstract class BaseThemeParkAdapter implements ThemeParkAdapterInterface
{
    protected Client $httpClient;

    public function __construct(
        protected array $config = []
    ) {
        $this->httpClient = new Client([
            'timeout' => $config['timeout'] ?? 30,
            'verify' => $config['verify_ssl'] ?? true,
        ]);
    }

    /**
     * Make an HTTP request to the API
     *
     * @param string $method
     * @param string $endpoint
     * @param array $options
     * @return ResponseInterface
     * @throws ThemeParkApiException
     */
    protected function makeRequest(string $method, string $endpoint, array $options = []): ResponseInterface
    {
        try {
            $response = $this->httpClient->request($method, $endpoint, $options);

            return $response;
        } catch (GuzzleException $e) {
            throw ThemeParkApiException::apiError(
                "API request failed: {$e->getMessage()}",
                ['endpoint' => $endpoint, 'method' => $method]
            );
        }
    }

    /**
     * Parse JSON response
     *
     * @param ResponseInterface $response
     * @return array
     * @throws ThemeParkApiException
     */
    protected function parseJsonResponse(ResponseInterface $response): array
    {
        $body = (string) $response->getBody();
        $data = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw ThemeParkApiException::apiError('Invalid JSON response: ' . json_last_error_msg());
        }

        return $data;
    }

    /**
     * Get configuration value
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    protected function getConfig(string $key, mixed $default = null): mixed
    {
        return $this->config[$key] ?? $default;
    }

    /**
     * Check if configuration has required keys
     *
     * @param array $requiredKeys
     * @return bool
     */
    protected function hasRequiredConfig(array $requiredKeys): bool
    {
        foreach ($requiredKeys as $key) {
            if (!isset($this->config[$key]) || empty($this->config[$key])) {
                return false;
            }
        }

        return true;
    }
}
