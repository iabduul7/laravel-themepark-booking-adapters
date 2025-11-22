<?php

namespace Iabduul7\ThemeParkAdapters\Providers\Universal;

use Iabduul7\ThemeParkAdapters\Abstracts\BaseThemeParkAdapter;
use Iabduul7\ThemeParkAdapters\DataTransferObjects\Order;
use Iabduul7\ThemeParkAdapters\DataTransferObjects\Product;
use Iabduul7\ThemeParkAdapters\Exceptions\ThemeParkApiException;

class UniversalSmartOrder2Adapter extends BaseThemeParkAdapter
{
    protected string $baseUrl;

    public function __construct(array $config = [])
    {
        parent::__construct($config);

        if (!$this->hasRequiredConfig(['username', 'password', 'base_url'])) {
            throw ThemeParkApiException::invalidCredentials();
        }

        $this->baseUrl = $this->getConfig('base_url');
    }

    public function getProducts(array $filters = []): array
    {
        // TODO: Implement Universal SmartOrder2 API call
        // This is a stub implementation
        // Replace with actual SmartOrder2 API integration

        $response = $this->makeRequest('POST', $this->baseUrl . '/api/products', [
            'headers' => $this->getAuthHeaders(),
            'json' => $filters,
        ]);

        $data = $this->parseJsonResponse($response);

        return array_map(fn($item) => $this->mapToProduct($item), $data['data'] ?? []);
    }

    public function getProduct(string $productId): Product
    {
        // TODO: Implement Universal SmartOrder2 API call
        throw new \RuntimeException('Method not yet implemented. Awaiting SmartOrder2 API integration.');
    }

    public function createOrder(array $orderData): Order
    {
        // TODO: Implement Universal SmartOrder2 API call
        throw new \RuntimeException('Method not yet implemented. Awaiting SmartOrder2 API integration.');
    }

    public function getOrder(string $orderId): Order
    {
        // TODO: Implement Universal SmartOrder2 API call
        throw new \RuntimeException('Method not yet implemented. Awaiting SmartOrder2 API integration.');
    }

    public function cancelOrder(string $orderId): bool
    {
        // TODO: Implement Universal SmartOrder2 API call
        throw new \RuntimeException('Method not yet implemented. Awaiting SmartOrder2 API integration.');
    }

    public function getAvailability(string $productId, array $filters = []): array
    {
        // TODO: Implement Universal SmartOrder2 API call
        throw new \RuntimeException('Method not yet implemented. Awaiting SmartOrder2 API integration.');
    }

    public function validateCredentials(): bool
    {
        try {
            // SmartOrder2 uses basic auth
            $response = $this->makeRequest('POST', $this->baseUrl . '/api/auth/validate', [
                'headers' => $this->getAuthHeaders(),
            ]);

            return $response->getStatusCode() === 200;
        } catch (ThemeParkApiException $e) {
            return false;
        }
    }

    public function getProviderName(): string
    {
        return 'Universal (SmartOrder2)';
    }

    protected function getAuthHeaders(): array
    {
        $credentials = base64_encode(
            $this->getConfig('username') . ':' . $this->getConfig('password')
        );

        return [
            'Authorization' => 'Basic ' . $credentials,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
    }

    protected function mapToProduct(array $data): Product
    {
        // TODO: Map SmartOrder2 response to Product DTO
        // This is a placeholder mapping
        return Product::fromArray([
            'id' => $data['productId'] ?? $data['id'] ?? '',
            'name' => $data['productName'] ?? $data['name'] ?? '',
            'description' => $data['description'] ?? '',
            'price' => $data['unitPrice'] ?? $data['price'] ?? 0,
            'currency' => $data['currency'] ?? 'USD',
            'image_url' => $data['imageUrl'] ?? null,
            'metadata' => $data,
        ]);
    }
}
