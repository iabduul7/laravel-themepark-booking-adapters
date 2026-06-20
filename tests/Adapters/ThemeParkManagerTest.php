<?php

namespace Iabduul7\ThemeParkAdapters\Tests\Adapters;

use Iabduul7\ThemeParkAdapters\Contracts\ThemeParkAdapterInterface;
use Iabduul7\ThemeParkAdapters\Facades\ThemePark;
use Iabduul7\ThemeParkAdapters\Providers\Disney\DisneyRedeamAdapter;
use Iabduul7\ThemeParkAdapters\Providers\SeaWorld\SeaWorldRedeamAdapter;
use Iabduul7\ThemeParkAdapters\Providers\Universal\UniversalSmartOrder2Adapter;
use Iabduul7\ThemeParkAdapters\Tests\AdapterTestCase;

class ThemeParkManagerTest extends AdapterTestCase
{
    /**
     * @param  \Illuminate\Foundation\Application  $app
     */
    protected function defineEnvironment($app): void
    {
        $app['config']->set('themepark-adapters.default', 'disney');
        $app['config']->set('themepark-adapters.providers.disney', [
            'api_key' => 'k', 'api_secret' => 's', 'supplier_id' => '20',
        ]);
        $app['config']->set('themepark-adapters.providers.seaworld', [
            'api_key' => 'k', 'api_secret' => 's',
        ]);
        $app['config']->set('themepark-adapters.providers.universal', [
            'client_username' => 'u', 'client_secret' => 'sec', 'customer_id' => 134853,
        ]);
    }

    public function test_facade_resolves_each_park_to_its_adapter(): void
    {
        $this->assertInstanceOf(DisneyRedeamAdapter::class, ThemePark::provider('disney'));
        $this->assertInstanceOf(SeaWorldRedeamAdapter::class, ThemePark::provider('seaworld'));
        $this->assertInstanceOf(UniversalSmartOrder2Adapter::class, ThemePark::provider('universal'));
    }

    public function test_default_provider_is_disney(): void
    {
        $adapter = ThemePark::provider();

        $this->assertInstanceOf(ThemeParkAdapterInterface::class, $adapter);
        $this->assertSame('disney', $adapter->getProviderName());
    }
}
