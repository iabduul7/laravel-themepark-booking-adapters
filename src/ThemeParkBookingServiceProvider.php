<?php

namespace iabduul7\ThemeParkBooking;

use iabduul7\ThemeParkBooking\Adapters\RedeamAdapter;
use iabduul7\ThemeParkBooking\Adapters\SmartOrderAdapter;
use iabduul7\ThemeParkBooking\Commands\InstallCommand;
use iabduul7\ThemeParkBooking\Commands\SyncProductsCommand;
use iabduul7\ThemeParkBooking\Commands\TestConnectionCommand;
use iabduul7\ThemeParkBooking\Contracts\BookingAdapterInterface;
use iabduul7\ThemeParkBooking\Services\BookingManager;
use iabduul7\ThemeParkBooking\Services\VoucherGenerator;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class ThemeParkBookingServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('themepark-booking')
            ->hasConfigFile('themepark-booking')
            ->hasViews()
            ->hasMigrations([
                'create_booking_events_table',
                'create_booking_metrics_table',
                'create_order_details_redeam_table',
                'create_order_details_universal_table'
            ])
            ->hasCommands([
                SyncProductsCommand::class,
                InstallCommand::class,
                TestConnectionCommand::class
            ]);
    }

    public function packageRegistered(): void
    {
        // Register core services
        $this->app->singleton(BookingManager::class, function ($app) {
            return new BookingManager($app, config('themepark-booking.adapters', []));
        });

        $this->app->singleton(VoucherGenerator::class, function ($app) {
            return new VoucherGenerator(
                config('themepark-booking.voucher.storage_disk', 'public'),
                config('themepark-booking.voucher.templates_path', 'voucher-templates')
            );
        });

        // Register theme park booking manager
        $this->app->singleton(ThemeParkBookingManager::class, function ($app) {
            return new ThemeParkBookingManager($app);
        });

        // Register adapters
        $this->app->bind('booking.adapter.redeam.disney', function ($app) {
            return new RedeamAdapter(
                'disney',
                config('themepark-booking.adapters.redeam.disney', [])
            );
        });

        $this->app->bind('booking.adapter.redeam.united_parks', function ($app) {
            return new RedeamAdapter(
                'united_parks',
                config('themepark-booking.adapters.redeam.united_parks', [])
            );
        });

        $this->app->bind('booking.adapter.smartorder', function ($app) {
            return new SmartOrderAdapter(
                config('themepark-booking.adapters.smartorder', [])
            );
        });

        // Register aliases
        $this->app->alias(BookingManager::class, 'themepark-booking');
        $this->app->alias(ThemeParkBookingManager::class, 'themepark-booking-manager');
    }

    public function packageBooted(): void
    {
        // Load migrations from package directory
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // Publish migrations separately for flexibility
        $this->publishes([
            __DIR__ . '/../database/migrations' => database_path('migrations'),
        ], 'themepark-booking-migrations');

        // Publish additional assets
        $this->publishes([
            $this->package->basePath('/../resources/views') => resource_path('views/vendor/themepark-booking'),
        ], "{$this->package->shortName()}-views");

        $this->publishes([
            $this->package->basePath('/../config/themepark-booking.php') => config_path('themepark-booking.php'),
        ], "{$this->package->shortName()}-config");

        // Default config publishing
        $this->publishes([
            __DIR__ . '/../config/themepark-booking.php' => config_path('themepark-booking.php'),
        ], 'themepark-booking-config');
    }
}