<?php

namespace Iabduul7\ThemeParkAdapters\Providers\SeaWorld;

use Iabduul7\ThemeParkAdapters\Abstracts\BaseThemeParkAdapter;
use Iabduul7\ThemeParkAdapters\DataTransferObjects\Order;
use Iabduul7\ThemeParkAdapters\DataTransferObjects\Product;
use Iabduul7\ThemeParkAdapters\Exceptions\ThemeParkApiException;

class SeaWorldRedeamAdapter extends BaseThemeParkAdapter
{
    protected string $baseUrl;

    public function __construct(array $config = [])
    {
        parent::__construct($config);

        if (! $this->hasRequiredConfig(['api_key', 'api_secret', 'base_url'])) {
            throw ThemeParkApiException::invalidCredentials();
        }

        $this->baseUrl = $this->getConfig('base_url');
    }

    public function getProducts(array $filters = []): array
    {
        // TODO: Implement SeaWorld Redeam API call
        // This is a stub implementation
        // Replace with actual Redeam API integration

        $response = $this->makeRequest('GET', $this->baseUrl . '/products', [
            'headers' => $this->getAuthHeaders(),
            'query' => $filters,
        ]);

        $data = $this->parseJsonResponse($response);

        return array_map(fn ($item) => $this->mapToProduct($item), $data['products'] ?? []);
    }

    public function getProduct(string $productId): Product
    {
        // TODO: Implement SeaWorld Redeam API call
        throw new \RuntimeException('Method not yet implemented. Awaiting Redeam API integration.');
    }

    public function createOrder(array $orderData): Order
    {
        // TODO: Implement SeaWorld Redeam API call
        throw new \RuntimeException('Method not yet implemented. Awaiting Redeam API integration.');
    }

    public function getOrder(string $orderId): Order
    {
        // TODO: Implement SeaWorld Redeam API call
        throw new \RuntimeException('Method not yet implemented. Awaiting Redeam API integration.');
    }

    public function cancelOrder(string $orderId): bool
    {
        // TODO: Implement SeaWorld Redeam API call
        throw new \RuntimeException('Method not yet implemented. Awaiting Redeam API integration.');
    }

    public function getAvailability(string $productId, array $filters = []): array
    {
        // TODO: Implement SeaWorld Redeam API call
        throw new \RuntimeException('Method not yet implemented. Awaiting Redeam API integration.');
    }

    public function validateCredentials(): bool
    {
        try {
            $response = $this->makeRequest('GET', $this->baseUrl . '/validate', [
                'headers' => $this->getAuthHeaders(),
            ]);

            return $response->getStatusCode() === 200;
        } catch (ThemeParkApiException $e) {
            return false;
        }
    }

    public function getProviderName(): string
    {
        return 'SeaWorld (Redeam)';
    }

    protected function getAuthHeaders(): array
    {
        return [
            'X-API-Key' => $this->getConfig('api_key'),
            'X-API-Secret' => $this->getConfig('api_secret'),
            'Accept' => 'application/json',
        ];
    }

    protected function mapToProduct(array $data): Product
    {
        // TODO: Map Redeam response to Product DTO
        // This is a placeholder mapping
        return Product::fromArray([
            'id' => $data['id'] ?? '',
            'name' => $data['name'] ?? '',
            'description' => $data['description'] ?? '',
            'price' => $data['price'] ?? 0,
            'currency' => $data['currency'] ?? 'USD',
            'image_url' => $data['image_url'] ?? null,
            'metadata' => $data,
        ]);
    }
}
