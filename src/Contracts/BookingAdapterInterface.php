<?php

namespace iabduul7\ThemeParkBooking\Contracts;

use iabduul7\ThemeParkBooking\Data\BookingRequest;
use iabduul7\ThemeParkBooking\Data\BookingResponse;
use iabduul7\ThemeParkBooking\Data\Product;
use iabduul7\ThemeParkBooking\Data\ProductSyncResult;
use iabduul7\ThemeParkBooking\Data\VoucherData;
use Illuminate\Support\Collection;

interface BookingAdapterInterface
{
    /**
     * Get the adapter name/identifier
     */
    public function getName(): string;

    /**
     * Get the provider name (e.g., 'redeam', 'smartorder')
     */
    public function getProvider(): string;

    /**
     * Test the connection to the booking service
     */
    public function testConnection(): bool;

    /**
     * Sync products from the remote service
     */
    public function syncProducts(): ProductSyncResult;

    /**
     * Get a specific product by its remote ID
     */
    public function getProduct(string $remoteId): ?Product;

    /**
     * Search for products using various criteria
     */
    public function searchProducts(array $criteria = []): Collection;

    /**
     * Create a booking reservation
     */
    public function createBooking(BookingRequest $request): BookingResponse;

    /**
     * Confirm a booking reservation
     */
    public function confirmBooking(string $reservationId, array $paymentData = []): BookingResponse;

    /**
     * Cancel a booking
     */
    public function cancelBooking(string $bookingId, string $reason = ''): BookingResponse;

    /**
     * Get booking status/details
     */
    public function getBooking(string $bookingId): ?BookingResponse;

    /**
     * Generate voucher for a confirmed booking
     */
    public function generateVoucher(string $bookingId): VoucherData;

    /**
     * Get available time slots for a product on a specific date
     */
    public function getAvailableTimeSlots(string $productId, string $date): Collection;

    /**
     * Check product availability for specific date/time
     */
    public function checkAvailability(string $productId, string $date, string $time = null, int $quantity = 1): bool;

    /**
     * Get pricing information for a product
     */
    public function getPricing(string $productId, string $date, array $options = []): array;

    /**
     * Get the last sync timestamp for this adapter
     */
    public function getLastSyncTimestamp(): ?int;

    /**
     * Set the last sync timestamp
     */
    public function setLastSyncTimestamp(int $timestamp): void;

    /**
     * Get adapter-specific configuration
     */
    public function getConfig(string $key = null, $default = null);

    /**
     * Validate adapter configuration
     */
    public function validateConfig(): array;
}