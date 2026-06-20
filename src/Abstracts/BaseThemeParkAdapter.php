<?php

namespace Iabduul7\ThemeParkAdapters\Abstracts;

use Iabduul7\ThemeParkAdapters\Contracts\ThemeParkAdapterInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

abstract class BaseThemeParkAdapter implements ThemeParkAdapterInterface
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(protected array $config = []) {}

    protected function getConfig(string $key, mixed $default = null): mixed
    {
        return $this->config[$key] ?? $default;
    }

    /**
     * @param  array<int, string>  $requiredKeys
     */
    protected function hasRequiredConfig(array $requiredKeys): bool
    {
        foreach ($requiredKeys as $key) {
            if (! isset($this->config[$key]) || empty($this->config[$key])) {
                return false;
            }
        }

        return true;
    }

    /**
     * A base Laravel HTTP request configured with the provider timeout and TLS
     * verification. Using the Http facade (rather than a raw Guzzle client) keeps
     * the package's transport behaviour identical to the upstream clients and
     * makes responses fakeable in tests via Http::fake().
     */
    protected function http(): PendingRequest
    {
        return Http::timeout((int) $this->getConfig('timeout', 600))
            ->withOptions(['verify' => (bool) $this->getConfig('verify_ssl', true)]);
    }

    /**
     * Wrap a pending request with retry on transient failures (connection drops
     * and 5xx server errors). Use for idempotent reads ONLY — writes must never be
     * retried or a hold/booking/order could be duplicated. Mirrors the retry policy
     * of the upstream Redeam/SmartOrder API clients (3 attempts, 1s apart, no throw).
     */
    protected function retryReads(PendingRequest $request): PendingRequest
    {
        $attempts = max(1, (int) $this->getConfig('retry_attempts', 3));
        $sleepMs = max(0, (int) $this->getConfig('retry_sleep_ms', 1000));

        return $request->retry($attempts, $sleepMs, function ($exception) {
            return $exception instanceof ConnectionException
                || ($exception instanceof RequestException && $exception->response->serverError());
        }, throw: false);
    }

    /**
     * @return array<string, mixed>
     */
    protected function parseJsonResponse(Response $response): array
    {
        return $response->json() ?? [];
    }
}
