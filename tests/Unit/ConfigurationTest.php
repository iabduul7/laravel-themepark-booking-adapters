<?php

namespace iabduul7\ThemeParkBooking\Tests\Unit;

use iabduul7\ThemeParkBooking\Tests\TestCase;
use Illuminate\Support\Facades\Config;

class ConfigurationTest extends TestCase
{
    /** @test */
    public function package_configuration_is_loaded_correctly()
    {
        // Test that our test configuration is loaded
        $this->assertEquals('https://booking.redeam.io/v1.2', config('themepark-booking.redeam.base_url'));
        $this->assertEquals('https://QACorpAPI.ucdp.net', config('themepark-booking.smartorder.base_url'));
        $this->assertEquals('134853', config('themepark-booking.smartorder.customer_id'));
    }

    /** @test */
    public function redeam_configuration_has_required_keys()
    {
        $redeamConfig = config('themepark-booking.redeam');

        $this->assertArrayHasKey('base_url', $redeamConfig);
        $this->assertArrayHasKey('api_key', $redeamConfig);
        $this->assertArrayHasKey('api_secret', $redeamConfig);
        $this->assertArrayHasKey('timeout', $redeamConfig);
        $this->assertArrayHasKey('disney', $redeamConfig);
        $this->assertArrayHasKey('united_parks', $redeamConfig);

        // Test nested disney config
        $this->assertArrayHasKey('supplier_id', $redeamConfig['disney']);
        $this->assertArrayHasKey('enabled', $redeamConfig['disney']);

        // Test nested united parks config
        $this->assertArrayHasKey('supplier_id', $redeamConfig['united_parks']);
        $this->assertArrayHasKey('enabled', $redeamConfig['united_parks']);
    }

    /** @test */
    public function smartorder_configuration_has_required_keys()
    {
        $smartOrderConfig = config('themepark-booking.smartorder');

        $this->assertArrayHasKey('base_url', $smartOrderConfig);
        $this->assertArrayHasKey('customer_id', $smartOrderConfig);
        $this->assertArrayHasKey('client_username', $smartOrderConfig);
        $this->assertArrayHasKey('client_secret', $smartOrderConfig);
        $this->assertArrayHasKey('timeout', $smartOrderConfig);
        $this->assertArrayHasKey('enabled', $smartOrderConfig);
    }

    /** @test */
    public function configuration_can_be_overridden_at_runtime()
    {
        // Test runtime configuration changes
        Config::set('themepark-booking.redeam.timeout', 60);
        Config::set('themepark-booking.smartorder.enabled', false);

        $this->assertEquals(60, config('themepark-booking.redeam.timeout'));
        $this->assertFalse(config('themepark-booking.smartorder.enabled'));
    }

    /** @test */
    public function configuration_supports_environment_variables()
    {
        // Test that configuration can be set via environment
        config([
            'themepark-booking.redeam.api_key' => env('REDEAM_API_KEY', 'test_api_key'),
            'themepark-booking.smartorder.client_username' => env('SMARTORDER_CLIENT_USERNAME', 'test_username'),
        ]);

        $this->assertEquals('test_api_key', config('themepark-booking.redeam.api_key'));
        $this->assertEquals('test_username', config('themepark-booking.smartorder.client_username'));
    }

    /** @test */
    public function timeout_configurations_are_numeric()
    {
        $this->assertIsNumeric(config('themepark-booking.redeam.timeout'));
        $this->assertIsNumeric(config('themepark-booking.smartorder.timeout'));
        $this->assertGreaterThan(0, config('themepark-booking.redeam.timeout'));
        $this->assertGreaterThan(0, config('themepark-booking.smartorder.timeout'));
    }

    /** @test */
    public function supplier_ids_are_strings()
    {
        $this->assertIsString(config('themepark-booking.redeam.disney.supplier_id'));
        $this->assertIsString(config('themepark-booking.redeam.united_parks.supplier_id'));
        $this->assertIsString(config('themepark-booking.smartorder.customer_id'));
    }

    /** @test */
    public function enabled_flags_are_boolean()
    {
        $this->assertIsBool(config('themepark-booking.redeam.disney.enabled'));
        $this->assertIsBool(config('themepark-booking.redeam.united_parks.enabled'));
        $this->assertIsBool(config('themepark-booking.smartorder.enabled'));
    }

    /** @test */
    public function base_urls_are_valid_formats()
    {
        $redeamUrl = config('themepark-booking.redeam.base_url');
        $smartOrderUrl = config('themepark-booking.smartorder.base_url');

        $this->assertStringStartsWith('https://', $redeamUrl);
        $this->assertStringStartsWith('https://', $smartOrderUrl);

        $this->assertTrue(filter_var($redeamUrl, FILTER_VALIDATE_URL) !== false);
        $this->assertTrue(filter_var($smartOrderUrl, FILTER_VALIDATE_URL) !== false);
    }
}
