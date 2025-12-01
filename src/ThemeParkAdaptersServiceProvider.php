<?php

namespace Iabduul7\ThemeParkAdapters;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class ThemeParkAdaptersServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('themepark-adapters')
            ->hasConfigFile('themepark-adapters');
    }

    public function packageRegistered(): void
    {
        $this->app->singleton('themepark', function ($app) {
            return new ThemeParkManager($app);
        });

        $this->app->alias('themepark', ThemeParkManager::class);
    }
}
