<?php

namespace iabduul7\ThemeParkBooking\Tests\Unit\Commands;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
iabduul7\ThemeParkBooking\Tests\TestCase;

class InstallCommandTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function install_command_publishes_config_file()
    {
        // Clear any existing config
        if (File::exists(config_path('themepark-booking.php'))) {
            File::delete(config_path('themepark-booking.php'));
        }

        Artisan::call('themepark-booking:install', ['--no-config' => false]);

        $output = Artisan::output();
        
        $this->assertStringContainsString('Installing Theme Park Booking Adapters Package', $output);
        $this->assertStringContainsString('Publishing configuration files', $output);
    }

    /** @test */
    public function install_command_runs_migrations_when_requested()
    {
        Artisan::call('themepark-booking:install', ['--migrate' => true]);

        $output = Artisan::output();
        
        $this->assertStringContainsString('Running database migrations', $output);
        $this->assertStringContainsString('package installed successfully', $output);
        
        // Check that tables exist
        $this->assertTrue(\Schema::hasTable('order_details_redeam'));
        $this->assertTrue(\Schema::hasTable('order_details_universal'));
    }

    /** @test */
    public function install_command_skips_config_when_requested()
    {
        Artisan::call('themepark-booking:install', ['--no-config' => true]);

        $output = Artisan::output();
        
        $this->assertStringNotContainsString('Publishing configuration files', $output);
        $this->assertStringContainsString('package installed successfully', $output);
    }

    /** @test */
    public function install_command_shows_post_install_instructions()
    {
        Artisan::call('themepark-booking:install');

        $output = Artisan::output();
        
        $this->assertStringContainsString('Next Steps', $output);
        $this->assertStringContainsString('Configure your API credentials', $output);
        $this->assertStringContainsString('HasThemeParkBookingAttributes trait', $output);
        $this->assertStringContainsString('Available Commands', $output);
    }

    /** @test */
    public function install_command_handles_migration_errors_gracefully()
    {
        // Simulate migration error by using invalid database
        config(['database.connections.testing.database' => '/invalid/path/database.sqlite']);
        
        Artisan::call('themepark-booking:install', ['--migrate' => true]);

        $output = Artisan::output();
        
        // Should still complete installation even if migrations fail
        $this->assertStringContainsString('package installed successfully', $output);
    }
}