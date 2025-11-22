<?php

namespace Iabduul7\ThemeParkAdapters\Tests;

use Iabduul7\ThemeParkAdapters\ThemeParkAdaptersServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app)
    {
        return [
            ThemeParkAdaptersServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('themepark-adapters.default', 'disney');
        config()->set('themepark-adapters.providers.disney', [
            'driver' => 'redeam',
            'enabled' => true,
            'base_url' => 'https://api.test.com/disney',
            'api_key' => 'test_key',
            'api_secret' => 'test_secret',
        ]);
    }
}
