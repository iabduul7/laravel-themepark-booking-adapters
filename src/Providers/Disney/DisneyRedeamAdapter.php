<?php

namespace Iabduul7\ThemeParkAdapters\Providers\Disney;

use Iabduul7\ThemeParkAdapters\Abstracts\BaseThemeParkAdapter;
use Iabduul7\ThemeParkAdapters\DataTransferObjects\Booking;
use Iabduul7\ThemeParkAdapters\DataTransferObjects\Hold;
use Iabduul7\ThemeParkAdapters\DataTransferObjects\Product;
use Iabduul7\ThemeParkAdapters\Exceptions\ThemeParkApiException;

class DisneyRedeamAdapter extends BaseThemeParkAdapter
{
    protected string $baseUrl;
    protected string $supplierId;
    protected string $version;

    public function __construct(array $config = [])
    {
        parent::__construct($config);

        if (! $this->hasRequiredConfig(['api_key', 'api_secret', 'supplier_id'])) {
            throw ThemeParkApiException::invalidCredentials();
        }

        $host = $this->getConfig('host', 'booking.redeam.io');
        $this->version = $this->getConfig('version', 'v1.2');
        $this->supplierId = $this->getConfig('supplier_id');
        $this->baseUrl = "https://{$host}/{$this->version}";
    }

    /**
     * Get all products for the supplier.
     */
    public function getAllProducts(array $parameters = []): array
    {
        $response = $this->makeRequest('GET', "{$this->baseUrl}/suppliers/{$this->supplierId}/products", [
            'headers' => $this->getAuthHeaders(),
            'query' => $parameters,
        ]);

        return $this->parseJsonResponse($response);
    }

    /**
     * Get a specific product by ID.
     */
    public function getProduct(string $productId, array $parameters = []): array
    {
        $response = $this->makeRequest('GET', "{$this->baseUrl}/suppliers/{$this->supplierId}/products/{$productId}", [
            'headers' => $this->getAuthHeaders(),
            'query' => $parameters,
        ]);

        return $this->parseJsonResponse($response);
    }

    /**
     * Get available rates for a product.
     */
    public function getProductRates(string $productId, array $parameters = []): array
    {
        $response = $this->makeRequest('GET', "{$this->baseUrl}/suppliers/{$this->supplierId}/products/{$productId}/rates", [
            'headers' => $this->getAuthHeaders(),
            'query' => $parameters,
        ]);

        return $this->parseJsonResponse($response);
    }

    /**
     * Get a specific rate for a product.
     */
    public function getProductRate(string $productId, string $rateId, array $parameters = []): array
    {
        $response = $this->makeRequest('GET', "{$this->baseUrl}/suppliers/{$this->supplierId}/products/{$productId}/rates/{$rateId}", [
            'headers' => $this->getAuthHeaders(),
            'query' => $parameters,
        ]);

        return $this->parseJsonResponse($response);
    }

    /**
     * Check availability for a single date.
     */
    public function checkAvailability(string $productId, string $date, int $quantity, array $parameters = []): array
    {
        $params = array_merge($parameters, [
            'at' => $date,
            'qty' => $quantity,
        ]);

        $response = $this->makeRequest('GET', "{$this->baseUrl}/suppliers/{$this->supplierId}/products/{$productId}/availability", [
            'headers' => $this->getAuthHeaders(),
            'query' => $params,
        ]);

        return $this->parseJsonResponse($response);
    }

    /**
     * Check availability for a date range.
     */
    public function checkAvailabilities(string $productId, string $startDate, string $endDate, array $parameters = []): array
    {
        $params = array_merge($parameters, [
            'start' => $startDate,
            'end' => $endDate,
        ]);

        $response = $this->makeRequest('GET', "{$this->baseUrl}/suppliers/{$this->supplierId}/products/{$productId}/availabilities", [
            'headers' => $this->getAuthHeaders(),
            'query' => $params,
        ]);

        return $this->parseJsonResponse($response);
    }

    /**
     * Get pricing schedule for a product.
     */
    public function getProductPricingSchedule(string $productId, string $startDate, string $endDate, array $parameters = []): array
    {
        $params = array_merge($parameters, [
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);

        $response = $this->makeRequest('GET', "{$this->baseUrl}/suppliers/{$this->supplierId}/products/{$productId}/pricing-schedule", [
            'headers' => $this->getAuthHeaders(),
            'query' => $params,
        ]);

        return $this->parseJsonResponse($response);
    }

    /**
     * Create a new hold/reservation.
     */
    public function createNewHold(array $data): array
    {
        $response = $this->makeRequest('POST', "{$this->baseUrl}/holds", [
            'headers' => $this->getAuthHeaders(),
            'json' => $data,
        ]);

        return $this->parseJsonResponse($response);
    }

    /**
     * Get hold details.
     */
    public function getHold(string $holdId): array
    {
        $response = $this->makeRequest('GET', "{$this->baseUrl}/holds/{$holdId}", [
            'headers' => $this->getAuthHeaders(),
        ]);

        return $this->parseJsonResponse($response);
    }

    /**
     * Delete/release a hold.
     */
    public function deleteHold(string $holdId): array
    {
        $response = $this->makeRequest('DELETE', "{$this->baseUrl}/holds/{$holdId}", [
            'headers' => $this->getAuthHeaders(),
        ]);

        return $this->parseJsonResponse($response);
    }

    /**
     * Create a confirmed booking.
     */
    public function createNewBooking(array $data): array
    {
        $response = $this->makeRequest('POST', "{$this->baseUrl}/bookings", [
            'headers' => $this->getAuthHeaders(),
            'json' => $data,
        ]);

        return $this->parseJsonResponse($response);
    }

    /**
     * Get booking details.
     */
    public function getBooking(string $bookingId): array
    {
        $response = $this->makeRequest('GET', "{$this->baseUrl}/bookings/{$bookingId}", [
            'headers' => $this->getAuthHeaders(),
        ]);

        return $this->parseJsonResponse($response);
    }

    /**
     * Cancel a booking.
     */
    public function deleteBooking(string $bookingId)
    {
        $response = $this->makeRequest('DELETE', "{$this->baseUrl}/bookings/{$bookingId}", [
            'headers' => $this->getAuthHeaders(),
        ]);

        return $this->parseJsonResponse($response);
    }

    /**
     * Validate API credentials.
     */
    public function validateCredentials(): bool
    {
        try {
            // Try to fetch products list as a way to validate credentials
            $this->getAllProducts();

            return true;
        } catch (ThemeParkApiException $e) {
            return false;
        }
    }

    /**
     * Get the provider name.
     */
    public function getProviderName(): string
    {
        return 'Disney (Redeam)';
    }

    /**
     * Get authentication headers.
     */
    protected function getAuthHeaders(): array
    {
        return [
            'X-API-Key' => $this->getConfig('api_key'),
            'X-API-Secret' => $this->getConfig('api_secret'),
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];
    }
}
