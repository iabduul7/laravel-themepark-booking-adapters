<?php

namespace iabduul7\ThemeParkBooking;

use Illuminate\Contracts\Foundation\Application;
use iabduul7\ThemeParkBooking\Contracts\BookingAdapterInterface;
use iabduul7\ThemeParkBooking\Services\RedeamBookingService;
use iabduul7\ThemeParkBooking\Services\SmartOrderBookingService;
use InvalidArgumentException;

class ThemeParkBookingManager
{
    protected Application $app;
    protected array $adapters = [];

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Get booking adapter by provider name
     */
    public function adapter(string $provider): BookingAdapterInterface
    {
        if (isset($this->adapters[$provider])) {
            return $this->adapters[$provider];
        }

        $adapter = $this->createAdapter($provider);
        
        return $this->adapters[$provider] = $adapter;
    }

    /**
     * Create adapter instance
     */
    protected function createAdapter(string $provider): BookingAdapterInterface
    {
        switch (strtolower($provider)) {
            case 'redeam':
            case 'disney':
            case 'united_parks':
                return $this->app->make(RedeamBookingService::class);
                
            case 'smartorder':
            case 'universal':
                return $this->app->make(SmartOrderBookingService::class);
                
            default:
                throw new InvalidArgumentException("Unsupported booking provider: {$provider}");
        }
    }

    /**
     * Get Redeam adapter
     */
    public function redeam(): BookingAdapterInterface
    {
        return $this->adapter('redeam');
    }

    /**
     * Get SmartOrder adapter
     */
    public function smartorder(): BookingAdapterInterface
    {
        return $this->adapter('smartorder');
    }

    /**
     * Get Disney adapter (uses Redeam)
     */
    public function disney(): BookingAdapterInterface
    {
        return $this->adapter('redeam');
    }

    /**
     * Get Universal adapter (uses SmartOrder)
     */
    public function universal(): BookingAdapterInterface
    {
        return $this->adapter('smartorder');
    }

    /**
     * Get all available providers
     */
    public function getAvailableProviders(): array
    {
        return ['redeam', 'smartorder', 'disney', 'universal', 'united_parks'];
    }

    /**
     * Check if provider is supported
     */
    public function hasProvider(string $provider): bool
    {
        return in_array(strtolower($provider), $this->getAvailableProviders());
    }
}
