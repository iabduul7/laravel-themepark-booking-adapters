<?php

namespace Iabduul7\ThemeParkAdapters\Contracts;

use Iabduul7\ThemeParkAdapters\DataTransferObjects\Order;
use Iabduul7\ThemeParkAdapters\DataTransferObjects\Product;

interface ThemeParkAdapterInterface
{
    /**
     * Get available products/tickets.
     *
     * @return array<Product>
     */
    public function getProducts(array $filters = []): array;

    /**
     * Get a specific product by ID.
     */
    public function getProduct(string $productId): Product;

    /**
     * Create an order.
     */
    public function createOrder(array $orderData): Order;

    /**
     * Get order details.
     */
    public function getOrder(string $orderId): Order;

    /**
     * Cancel an order.
     */
    public function cancelOrder(string $orderId): bool;

    /**
     * Get available dates for a product.
     */
    public function getAvailability(string $productId, array $filters = []): array;

    /**
     * Validate API credentials.
     */
    public function validateCredentials(): bool;

    /**
     * Get the provider name.
     */
    public function getProviderName(): string;
}
