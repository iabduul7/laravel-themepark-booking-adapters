<?php

namespace iabduul7\ThemeParkBooking\Services;

use iabduul7\ThemeParkBooking\Contracts\BookingAdapterInterface;
use iabduul7\ThemeParkBooking\Data\BookingRequest;
use iabduul7\ThemeParkBooking\Data\BookingResponse;
use iabduul7\ThemeParkBooking\Data\Product;
use iabduul7\ThemeParkBooking\Data\ProductSyncResult;
use iabduul7\ThemeParkBooking\Exceptions\AdapterException;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class BookingManager
{
    protected Container $app;
    protected array $adapters = [];
    protected array $config;

    public function __construct(Container $app, array $config = [])
    {
        $this->app = $app;
        $this->config = $config;
        $this->loadAdapters();
    }

    protected function loadAdapters(): void
    {
        foreach ($this->config as $name => $adapterConfig) {
            if (! isset($adapterConfig['enabled']) || ! $adapterConfig['enabled']) {
                continue;
            }

            $this->adapters[$name] = $adapterConfig;
        }
    }

    public function getAdapter(string $name): BookingAdapterInterface
    {
        if (! isset($this->adapters[$name])) {
            throw new AdapterException("Adapter not found or not enabled: {$name}");
        }

        $bindingKey = "booking.adapter.{$name}";

        if (! $this->app->bound($bindingKey)) {
            throw new AdapterException("Adapter binding not found: {$bindingKey}");
        }

        return $this->app->make($bindingKey);
    }

    public function getAvailableAdapters(): array
    {
        return array_keys($this->adapters);
    }

    public function testConnection(string $adapterName): bool
    {
        try {
            $adapter = $this->getAdapter($adapterName);

            return $adapter->testConnection();
        } catch (\Exception $e) {
            Log::warning("Connection test failed for adapter {$adapterName}", [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function testAllConnections(): array
    {
        $results = [];

        foreach ($this->getAvailableAdapters() as $adapterName) {
            $results[$adapterName] = $this->testConnection($adapterName);
        }

        return $results;
    }

    public function syncProducts(string $adapterName): ProductSyncResult
    {
        $adapter = $this->getAdapter($adapterName);

        Log::info('Starting product sync', [
            'adapter' => $adapterName,
            'provider' => $adapter->getProvider(),
        ]);

        $result = $adapter->syncProducts();

        Log::info('Product sync completed', [
            'adapter' => $adapterName,
            'success' => $result->success,
            'summary' => $result->getSummary(),
        ]);

        return $result;
    }

    public function syncAllProducts(): array
    {
        $results = [];

        foreach ($this->getAvailableAdapters() as $adapterName) {
            try {
                $results[$adapterName] = $this->syncProducts($adapterName);
            } catch (\Exception $e) {
                Log::error("Product sync failed for adapter {$adapterName}", [
                    'error' => $e->getMessage(),
                ]);

                $results[$adapterName] = ProductSyncResult::failure([
                    $e->getMessage(),
                ]);
            }
        }

        return $results;
    }

    public function getProduct(string $adapterName, string $productId): ?Product
    {
        $adapter = $this->getAdapter($adapterName);

        return $adapter->getProduct($productId);
    }

    public function searchProducts(string $adapterName, array $criteria = []): Collection
    {
        $adapter = $this->getAdapter($adapterName);

        return $adapter->searchProducts($criteria);
    }

    public function checkAvailability(
        string $adapterName,
        string $productId,
        string $date,
        ?string $time = null,
        int $quantity = 1
    ): bool {
        $adapter = $this->getAdapter($adapterName);

        return $adapter->checkAvailability($productId, $date, $time, $quantity);
    }

    public function getAvailableTimeSlots(string $adapterName, string $productId, string $date): Collection
    {
        $adapter = $this->getAdapter($adapterName);

        return $adapter->getAvailableTimeSlots($productId, $date);
    }

    public function getPricing(string $adapterName, string $productId, string $date, array $options = []): array
    {
        $adapter = $this->getAdapter($adapterName);

        return $adapter->getPricing($productId, $date, $options);
    }

    public function createBooking(string $adapterName, BookingRequest $request): BookingResponse
    {
        $adapter = $this->getAdapter($adapterName);

        Log::info('Creating booking', [
            'adapter' => $adapterName,
            'product_id' => $request->productId,
            'date' => $request->date->format('Y-m-d'),
            'quantity' => $request->quantity,
        ]);

        $response = $adapter->createBooking($request);

        Log::info('Booking creation result', [
            'adapter' => $adapterName,
            'success' => $response->success,
            'booking_id' => $response->bookingId,
            'reservation_id' => $response->reservationId,
            'status' => $response->status,
        ]);

        return $response;
    }

    public function confirmBooking(string $adapterName, string $reservationId, array $paymentData = []): BookingResponse
    {
        $adapter = $this->getAdapter($adapterName);

        Log::info('Confirming booking', [
            'adapter' => $adapterName,
            'reservation_id' => $reservationId,
        ]);

        $response = $adapter->confirmBooking($reservationId, $paymentData);

        Log::info('Booking confirmation result', [
            'adapter' => $adapterName,
            'reservation_id' => $reservationId,
            'success' => $response->success,
            'booking_id' => $response->bookingId,
            'status' => $response->status,
        ]);

        return $response;
    }

    public function cancelBooking(string $adapterName, string $bookingId, string $reason = ''): BookingResponse
    {
        $adapter = $this->getAdapter($adapterName);

        Log::info('Cancelling booking', [
            'adapter' => $adapterName,
            'booking_id' => $bookingId,
            'reason' => $reason,
        ]);

        $response = $adapter->cancelBooking($bookingId, $reason);

        Log::info('Booking cancellation result', [
            'adapter' => $adapterName,
            'booking_id' => $bookingId,
            'success' => $response->success,
        ]);

        return $response;
    }

    public function getBooking(string $adapterName, string $bookingId): ?BookingResponse
    {
        $adapter = $this->getAdapter($adapterName);

        return $adapter->getBooking($bookingId);
    }

    public function generateVoucher(string $adapterName, string $bookingId)
    {
        $adapter = $this->getAdapter($adapterName);

        Log::info('Generating voucher', [
            'adapter' => $adapterName,
            'booking_id' => $bookingId,
        ]);

        return $adapter->generateVoucher($bookingId);
    }

    public function getAdapterStatuses(): array
    {
        $statuses = [];

        foreach ($this->getAvailableAdapters() as $adapterName) {
            try {
                $adapter = $this->getAdapter($adapterName);
                $statuses[$adapterName] = [
                    'name' => $adapter->getName(),
                    'provider' => $adapter->getProvider(),
                    'connected' => $adapter->testConnection(),
                    'last_sync' => $adapter->getLastSyncTimestamp(),
                    'config_valid' => empty($adapter->validateConfig()),
                ];
            } catch (\Exception $e) {
                $statuses[$adapterName] = [
                    'name' => $adapterName,
                    'connected' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $statuses;
    }
}
