<?php

namespace Iabduul7\ThemeParkAdapters\Tests;

use Iabduul7\ThemeParkAdapters\ThemeParkAdaptersServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Illuminate\Foundation\Application;

/**
 * Lightweight Testbench base for the independent-namespace adapter contract tests.
 * Boots only the ThemeParkAdapters service provider — no database/migrations — so
 * tests can assert HTTP behaviour via Http::fake() in isolation from the (retiring)
 * legacy ThemeParkBooking test harness.
 */
abstract class AdapterTestCase extends Orchestra
{
    /**
     * @param  Application  $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app)
    {
        return [
            ThemeParkAdaptersServiceProvider::class,
        ];
    }
}
