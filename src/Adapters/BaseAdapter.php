<?php

namespace iabduul7\ThemeParkBooking\Adapters;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use iabduul7\ThemeParkBooking\Contracts\BookingAdapterInterface;
use iabduul7\ThemeParkBooking\Data\BookingRequest;
use iabduul7\ThemeParkBooking\Data\BookingResponse;
use iabduul7\ThemeParkBooking\Data\Product;
use iabduul7\ThemeParkBooking\Exceptions\AdapterException;
use iabduul7\ThemeParkBooking\Exceptions\ConfigurationException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

abstract class BaseAdapter implements BookingAdapterInterface
{
    protected Client $httpClient;
    protected array $config;
    protected string $cachePrefix;

    public function __construct(array $config = [])
    {
        $this->config = $config;
        $this->cachePrefix = "booking_adapter_{$this->getName()}";
        $this->httpClient = new Client($this->getHttpClientConfig());
    }

    abstract protected function getHttpClientConfig(): array;

    public function getName(): string
    {
        return strtolower(class_basename(static::class));
    }

    public function getConfig(string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->config;
        }

        return data_get($this->config, $key, $default);
    }

    public function validateConfig(): array
    {
        $errors = [];
        $required = $this->getRequiredConfigKeys();

        foreach ($required as $key) {
            if (empty($this->getConfig($key))) {
                $errors[] = "Missing required configuration: {$key}";
            }
        }

        return $errors;
    }

    abstract protected function getRequiredConfigKeys(): array;

    public function testConnection(): bool
    {
        try {
            return $this->performConnectionTest();
        } catch (\Exception $e) {
            Log::warning("Connection test failed for adapter {$this->getName()}", [
                'adapter' => $this->getName(),
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    abstract protected function performConnectionTest(): bool;

    public function getLastSyncTimestamp(): ?int
    {
        return Cache::get($this->getCacheKey('last_sync'));
    }

    public function setLastSyncTimestamp(int $timestamp): void
    {
        Cache::put($this->getCacheKey('last_sync'), $timestamp, now()->addDays(30));
    }

    protected function getCacheKey(string $suffix): string
    {
        return "{$this->cachePrefix}_{$suffix}";
    }

    protected function makeHttpRequest(string $method, string $endpoint, array $options = []): array
    {
        try {
            $response = $this->httpClient->request($method, $endpoint, $options);
            $body = $response->getBody()->getContents();

            return json_decode($body, true) ?? [];
        } catch (GuzzleException $e) {
            Log::error("HTTP request failed for adapter {$this->getName()}", [
                'adapter' => $this->getName(),
                'method' => $method,
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);

            throw new AdapterException(
                "HTTP request failed: {$e->getMessage()}",
                $e->getCode(),
                $e
            );
        }
    }

    protected function logOperation(string $operation, array $context = []): void
    {
        Log::info("Adapter operation: {$operation}", array_merge([
            'adapter' => $this->getName(),
            'provider' => $this->getProvider(),
        ], $context));
    }

    protected function logError(string $operation, \Exception $e, array $context = []): void
    {
        Log::error("Adapter operation failed: {$operation}", array_merge([
            'adapter' => $this->getName(),
            'provider' => $this->getProvider(),
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ], $context));
    }

    protected function validateRequiredConfig(): void
    {
        $errors = $this->validateConfig();
        if (! empty($errors)) {
            throw new ConfigurationException(
                "Invalid configuration for adapter {$this->getName()}: " . implode(', ', $errors)
            );
        }
    }

    protected function transformProductData(array $rawData): Product
    {
        // This should be implemented by each adapter to transform their specific data format
        throw new AdapterException("transformProductData must be implemented by adapter");
    }

    protected function transformBookingRequestData(BookingRequest $request): array
    {
        // This should be implemented by each adapter to transform to their specific format
        throw new AdapterException("transformBookingRequestData must be implemented by adapter");
    }

    protected function transformBookingResponseData(array $rawData): BookingResponse
    {
        // This should be implemented by each adapter to transform their specific response format
        throw new AdapterException("transformBookingResponseData must be implemented by adapter");
    }

    protected function generateCacheKey(string $operation, array $params = []): string
    {
        $key = $this->getCacheKey($operation);

        if (! empty($params)) {
            $key .= '_' . md5(serialize($params));
        }

        return $key;
    }

    protected function getCachedResult(string $cacheKey, int $ttl = 300)
    {
        return Cache::get($cacheKey);
    }

    protected function setCachedResult(string $cacheKey, $data, int $ttl = 300): void
    {
        Cache::put($cacheKey, $data, $ttl);
    }
}
