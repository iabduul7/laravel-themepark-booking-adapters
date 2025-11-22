<?php

namespace Iabduul7\ThemeParkAdapters\Contracts;

use Iabduul7\ThemeParkAdapters\DataTransferObjects\Ticket;
use Iabduul7\ThemeParkAdapters\DataTransferObjects\Order;
use Iabduul7\ThemeParkAdapters\DataTransferObjects\Product;

interface ThemeParkAdapterInterface
{
    /**
     * Get available products/tickets
     *
     * @param array $filters
     * @return array<Product>
     */
    public function getProducts(array $filters = []): array;

    /**
     * Get a specific product by ID
     *
     * @param string $productId
     * @return Product
     */
    public function getProduct(string $productId): Product;

    /**
     * Create an order
     *
     * @param array $orderData
     * @return Order
     */
    public function createOrder(array $orderData): Order;

    /**
     * Get order details
     *
     * @param string $orderId
     * @return Order
     */
    public function getOrder(string $orderId): Order;

    /**
     * Cancel an order
     *
     * @param string $orderId
     * @return bool
     */
    public function cancelOrder(string $orderId): bool;

    /**
     * Get available dates for a product
     *
     * @param string $productId
     * @param array $filters
     * @return array
     */
    public function getAvailability(string $productId, array $filters = []): array;

    /**
     * Validate API credentials
     *
     * @return bool
     */
    public function validateCredentials(): bool;

    /**
     * Get the provider name
     *
     * @return string
     */
    public function getProviderName(): string;
}
