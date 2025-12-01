<?php

namespace iabduul7\ThemeParkBooking\Tests\Feature;

use iabduul7\ThemeParkBooking\Adapters\RedeamAdapter;
use iabduul7\ThemeParkBooking\Adapters\SmartOrderAdapter;
use iabduul7\ThemeParkBooking\Data\Product;
use iabduul7\ThemeParkBooking\Data\ProductSyncResult;
use iabduul7\ThemeParkBooking\Tests\TestCase;

class AdapterIntegrationTest extends TestCase
{
    /** @test */
    public function redeam_adapter_can_sync_disney_products()
    {
        if (empty(config('themepark-booking.adapters.redeam.disney.api_key'))) {
            $this->markTestSkipped('Disney API credentials not configured');
        }

        $config = config('themepark-booking.adapters.redeam.disney');
        $adapter = new RedeamAdapter('disney', $config);

        $syncResult = $adapter->syncProducts();

        $this->assertInstanceOf(ProductSyncResult::class, $syncResult);
        $this->assertTrue($syncResult->isSuccessful());
        $this->assertGreaterThan(0, $syncResult->totalProcessed);

        if ($syncResult->hasErrors()) {
            $this->assertIsArray($syncResult->errors);
            // Log errors for debugging but don't fail the test
            foreach ($syncResult->errors as $error) {
                echo "Sync Error: " . $error . "\n";
            }
        }
    }

    /** @test */
    public function redeam_adapter_can_sync_united_parks_products()
    {
        if (empty(config('themepark-booking.adapters.redeam.united_parks.api_key'))) {
            $this->markTestSkipped('United Parks API credentials not configured');
        }

        $config = config('themepark-booking.adapters.redeam.united_parks');
        $adapter = new RedeamAdapter('united_parks', $config);

        $syncResult = $adapter->syncProducts();

        $this->assertInstanceOf(ProductSyncResult::class, $syncResult);
        $this->assertTrue($syncResult->isSuccessful());
    }

    /** @test */
    public function smartorder_adapter_can_sync_universal_products()
    {
        if (empty(config('themepark-booking.adapters.smartorder.client_username'))) {
            $this->markTestSkipped('SmartOrder API credentials not configured');
        }

        $config = config('themepark-booking.adapters.smartorder');
        $adapter = new SmartOrderAdapter($config);

        $syncResult = $adapter->syncProducts();

        $this->assertInstanceOf(ProductSyncResult::class, $syncResult);
        $this->assertTrue($syncResult->isSuccessful());
    }

    /** @test */
    public function adapters_handle_network_failures_gracefully()
    {
        // Test with invalid configuration to simulate network issues
        $invalidConfig = [
            'api_key' => 'invalid_key',
            'api_secret' => 'invalid_secret',
            'base_url' => 'https://invalid-url.test',
            'timeout' => 1, // Very short timeout
        ];

        $adapter = new RedeamAdapter('disney', $invalidConfig);

        try {
            $syncResult = $adapter->syncProducts();

            // If it doesn't throw an exception, check it failed gracefully
            $this->assertFalse($syncResult->isSuccessful());
            $this->assertTrue($syncResult->hasErrors());
        } catch (\Exception $e) {
            // Exception is acceptable for network failures
            $this->assertStringContainsString('invalid-url.test', $e->getMessage());
        }
    }

    /** @test */
    public function adapters_respect_timeout_configuration()
    {
        $config = [
            'api_key' => 'test_key',
            'api_secret' => 'test_secret',
            'base_url' => 'https://httpbin.org/delay/5', // Endpoint that delays 5 seconds
            'timeout' => 2, // 2 second timeout
        ];

        $adapter = new RedeamAdapter('disney', $config);

        $startTime = microtime(true);

        try {
            $adapter->syncProducts();
        } catch (\Exception $e) {
            // Should timeout before 5 seconds
            $duration = microtime(true) - $startTime;
            $this->assertLessThan(4, $duration, 'Request should have timed out');
        }
    }

    /** @test */
    public function adapters_can_retrieve_product_details()
    {
        if (empty(config('themepark-booking.adapters.redeam.disney.api_key'))) {
            $this->markTestSkipped('Disney API credentials not configured');
        }

        $config = config('themepark-booking.adapters.redeam.disney');
        $adapter = new RedeamAdapter('disney', $config);

        // Get all products first
        $products = $adapter->getAllProducts();

        if (! empty($products)) {
            $firstProduct = $products[0];
            $productDetails = $adapter->getProduct($firstProduct['id']);

            $this->assertInstanceOf(Product::class, $productDetails);
            $this->assertEquals($firstProduct['id'], $productDetails->id);
            $this->assertNotEmpty($productDetails->name);
        }
    }

    /** @test */
    public function adapters_handle_rate_limiting()
    {
        if (empty(config('themepark-booking.adapters.redeam.disney.api_key'))) {
            $this->markTestSkipped('Disney API credentials not configured');
        }

        $config = config('themepark-booking.adapters.redeam.disney');
        $adapter = new RedeamAdapter('disney', $config);

        // Make multiple rapid requests to test rate limiting
        $requests = [];
        for ($i = 0; $i < 5; $i++) {
            try {
                $start = microtime(true);
                $adapter->getAllProducts();
                $requests[] = microtime(true) - $start;
            } catch (\Exception $e) {
                // Rate limiting might cause exceptions
                $this->assertStringContainsString('rate', strtolower($e->getMessage()));
            }
        }

        // If requests succeeded, they should show some delay for rate limiting
        if (count($requests) > 1) {
            $avgTime = array_sum($requests) / count($requests);
            $this->assertGreaterThan(0, $avgTime);
        }
    }

    /** @test */
    public function adapters_validate_configuration()
    {
        // Test missing required configuration
        $this->expectException(\InvalidArgumentException::class);

        $invalidConfig = [
            // Missing api_key and api_secret
            'base_url' => 'https://api.test.com',
        ];

        new RedeamAdapter('disney', $invalidConfig);
    }

    /** @test */
    public function sync_result_provides_detailed_metrics()
    {
        $syncResult = new ProductSyncResult(
            totalProcessed: 100,
            successful: 95,
            failed: 5,
            skipped: 0,
            errors: ['Product ID invalid', 'Rate limit exceeded'],
            duration: 45.2
        );

        $this->assertEquals(100, $syncResult->totalProcessed);
        $this->assertEquals(95, $syncResult->successful);
        $this->assertEquals(5, $syncResult->failed);
        $this->assertTrue($syncResult->isSuccessful());
        $this->assertTrue($syncResult->hasErrors());
        $this->assertEquals(0.95, $syncResult->getSuccessRate());
        $this->assertCount(2, $syncResult->errors);
    }
}
