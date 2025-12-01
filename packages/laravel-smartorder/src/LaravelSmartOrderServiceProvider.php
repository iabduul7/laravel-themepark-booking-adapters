<?php

namespace CodeCreatives\LaravelSmartOrder;

use CodeCreatives\LaravelSmartOrder\Commands\LaravelSmartOrderCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LaravelSmartOrderServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-smartorder')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_laravel-smartorder_table')
            ->hasCommand(LaravelSmartOrderCommand::class);
    }
}
