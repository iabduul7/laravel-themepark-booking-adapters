<?php

namespace Iabduul7\ThemeParkAdapters\Abstracts;

use Iabduul7\ThemeParkAdapters\Contracts\Capabilities\SupportsHolds;
use Iabduul7\ThemeParkAdapters\DataTransferObjects\Results\Result;
use Iabduul7\ThemeParkAdapters\Support\Redeam\OptionCodeResolver;
use Illuminate\Http\Client\Response;

/**
 * Shared Redeam transport + hold/booking lifecycle for the Disney and SeaWorld/
 * United Parks adapters. Mirrors CodeCreatives\LaravelRedeam's RedeamApiClient +
 * facade behaviour: X-API-Key/X-API-Secret auth, form-encoded reads with retry,
 * JSON writes without retry, and Result-object wrapping of read payloads.
 */
abstract class AbstractRedeamAdapter extends BaseThemeParkAdapter implements SupportsHolds
{
    protected string $baseUrl;

    protected ?string $supplierId = null;

    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(array $config = [])
    {
        parent::__construct($config);

        $host = (string) $this->getConfig('host', 'booking.redeam.io');
        $version = (string) $this->getConfig('version', 'v1.2');
        $this->baseUrl = "https://{$host}/{$version}";

        $supplierId = $this->getConfig('supplier_id');
        $this->supplierId = $supplierId !== null ? (string) $supplierId : null;
    }

    public function setSupplierId(string $supplierId): void
    {
        $this->supplierId = $supplierId;
    }

    public function getProviderName(): string
    {
        return 'redeam';
    }

    /**
     * @param  array<string, string>  $extra
     * @return array<string, string>
     */
    protected function authHeaders(array $extra = []): array
    {
        return array_merge([
            'X-API-Key' => (string) $this->getConfig('api_key'),
            'X-API-Secret' => (string) $this->getConfig('api_secret'),
        ], $extra);
    }

    protected function url(string $uri): string
    {
        return "{$this->baseUrl}/{$uri}";
    }

    /**
     * Idempotent, retried, form-encoded GET (matches the upstream client).
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function getRequest(string $uri, array $payload = []): array
    {
        return $this->retryReads(
            $this->http()->asForm()->withHeaders($this->authHeaders())
        )->get($this->url($uri), $payload)->json() ?? [];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function postRequest(string $uri, array $payload = []): array
    {
        return $this->http()->asJson()->withHeaders($this->authHeaders())
            ->post($this->url($uri), $payload)->json() ?? [];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function deleteRequest(string $uri, array $payload = []): array
    {
        return $this->http()->withHeaders($this->authHeaders())
            ->delete($this->url($uri), $payload)->json() ?? [];
    }

    protected function putRequest(string $uri): Response
    {
        return $this->http()->withHeaders($this->authHeaders())->send('PUT', $this->url($uri));
    }

    /**
     * @template T of Result
     *
     * @param  array<string, mixed>  $data
     * @param  class-string<T>  $class
     * @return T
     */
    protected function parseData(array $data, string $class): Result
    {
        /** @var T $result */
        $result = (new $class($data))->withAdapter($this);

        return $result;
    }

    /**
     * @template T of Result
     *
     * @param  array<int, array<string, mixed>>  $items
     * @param  class-string<T>  $class
     * @return array<int, T>
     */
    protected function parseArrayData(array $items, string $class): array
    {
        return array_map(fn (array $item) => $this->parseData($item, $class), $items);
    }

    // --- SupportsHolds: identical endpoints for Disney and SeaWorld/United Parks ---

    public function createNewHold(array $data): array
    {
        return $this->postRequest('holds', $data);
    }

    public function getHold(string $holdId): array
    {
        return $this->getRequest("holds/{$holdId}");
    }

    public function deleteHold(string $holdId): array
    {
        return $this->deleteRequest("holds/{$holdId}");
    }

    public function createNewBooking(array $data): array
    {
        return $this->postRequest('bookings', $data);
    }

    public function getBooking(string $bookingId): array
    {
        return $this->getRequest("bookings/{$bookingId}");
    }

    public function deleteBooking(string $bookingId): Response
    {
        return $this->putRequest("bookings/cancel/{$bookingId}");
    }

    // --- Optional Walt Disney World ticket business logic (opt-in building blocks) ---

    /**
     * Resolve a Disney ticket option code from its name. Delegates to the
     * {@see OptionCodeResolver} building block. Present for drop-in parity with
     * the upstream facades; irrelevant for non-Disney Redeam suppliers.
     */
    public function getOptionCode(int $days, ?string $name = null): ?string
    {
        return OptionCodeResolver::resolve($days, $name);
    }
}
