<?php

namespace iabduul7\ThemeParkBooking\Facades;

use iabduul7\ThemeParkBooking\Services\BookingManager;
use Illuminate\Support\Facades\Facade;

/**
 * @method static \iabduul7\ThemeParkBooking\Contracts\BookingAdapterInterface getAdapter(string $name)
 * @method static array getAvailableAdapters()
 * @method static bool testConnection(string $adapterName)
 * @method static array testAllConnections()
 * @method static \iabduul7\ThemeParkBooking\Data\ProductSyncResult syncProducts(string $adapterName)
 * @method static array syncAllProducts()
 * @method static \iabduul7\ThemeParkBooking\Data\Product|null getProduct(string $adapterName, string $productId)
 * @method static \Illuminate\Support\Collection searchProducts(string $adapterName, array $criteria = [])
 * @method static bool checkAvailability(string $adapterName, string $productId, string $date, string $time = null, int $quantity = 1)
 * @method static \Illuminate\Support\Collection getAvailableTimeSlots(string $adapterName, string $productId, string $date)
 * @method static array getPricing(string $adapterName, string $productId, string $date, array $options = [])
 * @method static \iabduul7\ThemeParkBooking\Data\BookingResponse createBooking(string $adapterName, \iabduul7\ThemeParkBooking\Data\BookingRequest $request)
 * @method static \iabduul7\ThemeParkBooking\Data\BookingResponse confirmBooking(string $adapterName, string $reservationId, array $paymentData = [])
 * @method static \iabduul7\ThemeParkBooking\Data\BookingResponse cancelBooking(string $adapterName, string $bookingId, string $reason = '')
 * @method static \iabduul7\ThemeParkBooking\Data\BookingResponse|null getBooking(string $adapterName, string $bookingId)
 * @method static \iabduul7\ThemeParkBooking\Data\VoucherData generateVoucher(string $adapterName, string $bookingId)
 * @method static array getAdapterStatuses()
 *
 * @see \iabduul7\ThemeParkBooking\Services\BookingManager
 */
class ThemeParkBooking extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return BookingManager::class;
    }
}