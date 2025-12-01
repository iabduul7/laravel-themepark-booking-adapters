<?php

namespace CodeCreatives\LaravelRedeam;

use CodeCreatives\LaravelRedeam\Commands\LaravelRedeamCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LaravelRedeamServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-redeam')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_laravel-redeam_table')
            ->hasCommand(LaravelRedeamCommand::class);
    }
}
