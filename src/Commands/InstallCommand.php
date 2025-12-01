<?php

namespace iabduul7\ThemeParkBooking\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'themepark-booking:install 
                            {--migrate : Run migrations immediately}
                            {--force : Force overwrite existing files}
                            {--no-config : Skip config file publishing}';

    /**
     * The console command description.
     */
    protected $description = 'Install the Theme Park Booking package';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🎢 Installing Theme Park Booking Adapters Package...');

        // Publish configuration files
        if (! $this->option('no-config')) {
            $this->publishConfig();
        }

        // Run migrations
        if ($this->option('migrate')) {
            $this->runMigrations();
        } else {
            $this->info('💡 Run migrations with: php artisan migrate');
        }

        $this->displayPostInstallInstructions();

        $this->info('✅ Theme Park Booking Adapters package installed successfully!');

        return self::SUCCESS;
    }

    /**
     * Publish configuration files
     */
    protected function publishConfig(): void
    {
        $this->info('📄 Publishing configuration files...');

        $force = $this->option('force');
        $params = ['--provider' => 'iabduul7\\ThemeParkBooking\\ThemeParkBookingServiceProvider'];

        if ($force) {
            $params['--force'] = true;
        }

        Artisan::call('vendor:publish', array_merge($params, ['--tag' => 'themepark-booking-config']));

        $this->line('   ✓ Config file published to config/themepark-booking.php');
    }

    /**
     * Run package migrations
     */
    protected function runMigrations(): void
    {
        $this->info('🗄️  Running database migrations...');

        try {
            Artisan::call('migrate', [
                '--path' => 'vendor/iabduul7/laravel-themepark-booking-adapters/database/migrations',
                '--force' => true,
            ]);

            $this->line('   ✓ Migrations completed successfully');
        } catch (\Exception $e) {
            $this->error('   ✗ Migration failed: ' . $e->getMessage());
            $this->warn('   You can run migrations manually with:');
            $this->warn('   php artisan migrate --path=vendor/iabduul7/laravel-themepark-booking-adapters/database/migrations');
        }
    }

    /**
     * Display post-installation instructions
     */
    protected function displayPostInstallInstructions(): void
    {
        $this->newLine();
        $this->info('📋 Next Steps:');
        $this->line('   1. Configure your API credentials in config/themepark-booking.php');
        $this->line('   2. Add the HasThemeParkBookingAttributes trait to your Order model:');
        $this->line('      use iabduul7\\ThemeParkBooking\\Concerns\\HasThemeParkBookingAttributes;');
        $this->line('   3. Update your Order model\'s $appends array with theme park attributes');
        $this->line('   4. Run migrations if you haven\'t already: php artisan migrate');

        $this->newLine();
        $this->info('🔧 Available Commands:');
        $this->line('   • php artisan themepark-booking:test-connection  - Test API connections');
        $this->line('   • php artisan vendor:publish --tag=themepark-booking-migrations  - Publish migrations');

        $this->newLine();
        $this->info('📖 Documentation:');
        $this->line('   Check the README.md for detailed usage instructions and examples.');
    }
}
