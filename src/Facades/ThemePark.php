<?php

namespace Iabduul7\ThemeParkAdapters\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Iabduul7\ThemeParkAdapters\Contracts\ThemeParkAdapterInterface provider(?string $name = null)
 * @method static \Iabduul7\ThemeParkAdapters\Contracts\ThemeParkAdapterInterface driver(?string $driver = null)
 * @method static array getProducts(array $filters = [])
 * @method static \Iabduul7\ThemeParkAdapters\DataTransferObjects\Product getProduct(string $productId)
 * @method static \Iabduul7\ThemeParkAdapters\DataTransferObjects\Order createOrder(array $orderData)
 * @method static \Iabduul7\ThemeParkAdapters\DataTransferObjects\Order getOrder(string $orderId)
 * @method static bool cancelOrder(string $orderId)
 * @method static array getAvailability(string $productId, array $filters = [])
 * @method static bool validateCredentials()
 * @method static string getProviderName()
 *
 * @see \Iabduul7\ThemeParkAdapters\ThemeParkManager
 */
class ThemePark extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'themepark';
    }
}
