<?php

namespace iabduul7\ThemeParkBooking\Tests\Unit\Commands;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
iabduul7\ThemeParkBooking\Tests\TestCase;

class TestConnectionCommandTest extends TestCase
{
    /** @test */
    public function test_connection_command_tests_all_providers_by_default()
    {
        Artisan::call('themepark-booking:test-connection');

        $output = Artisan::output();
        
        $this->assertStringContainsString('Testing Theme Park Booking API Connections', $output);
        $this->assertStringContainsString('Testing Redeam API Connection', $output);
        $this->assertStringContainsString('Testing SmartOrder API Connection', $output);
        $this->assertStringContainsString('Test Results', $output);
    }

    /** @test */
    public function test_connection_command_tests_specific_provider()
    {
        Artisan::call('themepark-booking:test-connection', ['provider' => 'redeam']);

        $output = Artisan::output();
        
        $this->assertStringContainsString('Testing Redeam API Connection', $output);
        $this->assertStringNotContainsString('Testing SmartOrder API Connection', $output);
    }

    /** @test */
    public function test_connection_command_handles_missing_configuration()
    {
        // Clear configuration
        Config::set('themepark-booking.redeam.api_key', null);
        Config::set('themepark-booking.redeam.api_secret', null);
        
        Artisan::call('themepark-booking:test-connection', ['provider' => 'redeam']);

        $output = Artisan::output();
        
        $this->assertStringContainsString('Configuration missing', $output);
        $this->assertStringContainsString('FAILED', $output);
    }

    /** @test */
    public function test_connection_command_respects_timeout_option()
    {
        $startTime = microtime(true);
        
        Artisan::call('themepark-booking:test-connection', [
            'provider' => 'redeam',
            '--timeout' => 1
        ]);

        $duration = microtime(true) - $startTime;
        
        // Should complete quickly due to short timeout
        $this->assertLessThan(10, $duration);
    }

    /** @test */
    public function test_connection_command_provides_detailed_feedback()
    {
        Artisan::call('themepark-booking:test-connection');

        $output = Artisan::output();
        
        // Should show provider-specific results
        $this->assertStringContainsString('redeam:', $output);
        $this->assertStringContainsString('smartorder:', $output);
        
        // Should show success/failure status
        $this->assertMatchesRegularExpression('/(SUCCESS|FAILED)/', $output);
        
        // Should provide configuration guidance
        $this->assertStringContainsString('check your configuration', $output);
    }

    /** @test */
    public function test_connection_command_returns_appropriate_exit_code()
    {
        // Test with missing config (should fail)
        Config::set('themepark-booking.redeam.api_key', null);
        
        $exitCode = Artisan::call('themepark-booking:test-connection', ['provider' => 'redeam']);
        
        // Should return failure exit code
        $this->assertEquals(1, $exitCode);
    }
}