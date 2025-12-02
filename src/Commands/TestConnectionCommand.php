<?php

namespace iabduul7\ThemeParkBooking\Commands;

use iabduul7\ThemeParkBooking\Services\RedeamBookingService;
use iabduul7\ThemeParkBooking\Services\SmartOrderBookingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;

class TestConnectionCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'themepark-booking:test-connection 
                            {provider? : The provider to test (redeam, smartorder, or all)}
                            {--timeout=30 : Request timeout in seconds}';

    /**
     * The console command description.
     */
    protected $description = 'Test API connections to theme park booking providers';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $provider = $this->argument('provider') ?? 'all';
        $timeout = (int) $this->option('timeout');

        $this->info('🧪 Testing Theme Park Booking API Connections...');
        $this->newLine();

        $results = [];

        if ($provider === 'all' || $provider === 'redeam') {
            $results['redeam'] = $this->testRedeamConnection($timeout);
        }

        if ($provider === 'all' || $provider === 'smartorder') {
            $results['smartorder'] = $this->testSmartOrderConnection($timeout);
        }

        $this->displayResults($results);

        // Return error code if any tests failed
        $hasFailures = collect($results)->contains('success', false);

        return $hasFailures ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Test Redeam API connection.
     */
    protected function testRedeamConnection(int $timeout): array
    {
        $this->info('🎯 Testing Redeam API Connection...');

        try {
            $config = Config::get('themepark-booking.redeam');

            if (! $config || ! isset($config['api_key'], $config['api_secret'])) {
                return [
                    'success' => false,
                    'message' => 'Redeam configuration missing - check config/themepark-booking.php',
                    'details' => 'API key and secret are required',
                ];
            }

            $service = new RedeamBookingService();

            // Test with a simple supplier list call
            $response = $service->getSuppliers();

            if ($response && isset($response['suppliers'])) {
                $supplierCount = count($response['suppliers']);

                return [
                    'success' => true,
                    'message' => "Connection successful - Found {$supplierCount} suppliers",
                    'details' => 'API credentials are valid and working',
                ];
            }

            return [
                'success' => false,
                'message' => 'Invalid response from Redeam API',
                'details' => 'Check API credentials and network connectivity',
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Connection failed: ' . $e->getMessage(),
                'details' => 'Check API credentials, network, and endpoint URL',
            ];
        }
    }

    /**
     * Test SmartOrder API connection.
     */
    protected function testSmartOrderConnection(int $timeout): array
    {
        $this->info('🎯 Testing SmartOrder API Connection...');

        try {
            $config = Config::get('themepark-booking.smartorder');

            if (! $config || ! isset($config['client_username'], $config['client_secret'])) {
                return [
                    'success' => false,
                    'message' => 'SmartOrder configuration missing - check config/themepark-booking.php',
                    'details' => 'Client username and secret are required',
                ];
            }

            $service = new SmartOrderBookingService();

            // Test with authentication
            $token = $service->authenticate();

            if ($token) {
                return [
                    'success' => true,
                    'message' => 'Connection successful - Authentication token received',
                    'details' => 'API credentials are valid and working',
                ];
            }

            return [
                'success' => false,
                'message' => 'Authentication failed',
                'details' => 'Check client credentials and customer ID',
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Connection failed: ' . $e->getMessage(),
                'details' => 'Check client credentials, network, and endpoint URL',
            ];
        }
    }

    /**
     * Display test results.
     */
    protected function displayResults(array $results): void
    {
        $this->newLine();
        $this->info('📊 Test Results:');
        $this->newLine();

        foreach ($results as $provider => $result) {
            $icon = $result['success'] ? '✅' : '❌';
            $status = $result['success'] ? 'SUCCESS' : 'FAILED';

            $this->line("   {$icon} {$provider}: {$status}");
            $this->line("      {$result['message']}");

            if (isset($result['details'])) {
                $this->line("      {$result['details']}");
            }

            $this->newLine();
        }

        $successful = collect($results)->where('success', true)->count();
        $total = count($results);

        if ($successful === $total) {
            $this->info("🎉 All {$total} provider(s) connected successfully!");
        } else {
            $failed = $total - $successful;
            $this->warn("⚠️  {$failed} of {$total} provider(s) failed connection tests.");
            $this->warn('Please check your configuration and network connectivity.');
        }
    }
}
