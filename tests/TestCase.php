<?php

namespace iabduul7\ThemeParkBooking\Tests;

use iabduul7\ThemeParkBooking\ThemeParkBookingServiceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    use DatabaseTransactions;
    use SkipsTestsForMissingDependencies;

    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'iabduul7\\ThemeParkBooking\\Database\\Factories\\' . class_basename($modelName) . 'Factory'
        );

        $this->setUpDatabase();
    }

    protected function getPackageProviders($app)
    {
        return [
            ThemeParkBookingServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');
        config()->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Set up test configuration
        config()->set('themepark-booking.order_model', 'iabduul7\\ThemeParkBooking\\Tests\\Models\\Order');
        config()->set('themepark-booking.redeam', [
            'base_url' => 'https://booking.redeam.io/v1.2',
            'api_key' => 'test_api_key',
            'api_secret' => 'test_api_secret',
            'timeout' => 30,
            'disney' => [
                'supplier_id' => '20',
                'enabled' => true,
            ],
            'united_parks' => [
                'supplier_id' => '30',
                'enabled' => true,
            ],
        ]);

        config()->set('themepark-booking.smartorder', [
            'base_url' => 'https://QACorpAPI.ucdp.net',
            'customer_id' => '134853',
            'client_username' => 'test_username',
            'client_secret' => 'test_secret',
            'timeout' => 30,
            'enabled' => true,
        ]);
    }

    protected function setUpDatabase(): void
    {
        $this->artisan('migrate', ['--database' => 'testing']);
    }

    /**
     * Create a mock HTTP response for testing.
     */
    protected function mockHttpResponse(array $data, int $status = 200): array
    {
        return [
            'status' => $status,
            'data' => $data,
            'headers' => ['Content-Type' => 'application/json'],
        ];
    }

    /**
     * Create mock product data for testing.
     */
    protected function createMockProductData(string $provider = 'redeam'): array
    {
        if ($provider === 'redeam') {
            return [
                'id' => 'disney-magic-kingdom-1day',
                'name' => 'Magic Kingdom 1-Day Ticket',
                'supplier_id' => '20',
                'description' => 'One day access to Magic Kingdom',
                'rates' => [
                    [
                        'id' => 'adult',
                        'name' => 'Adult',
                        'price' => 109.00,
                        'currency' => 'USD',
                    ],
                ],
            ];
        }

        // SmartOrder format
        return [
            'ProductID' => 'UNIV_STUDIOS_1DAY',
            'ProductName' => 'Universal Studios 1-Day Ticket',
            'IsActive' => true,
            'Prices' => [
                [
                    'PriceID' => 'adult',
                    'BasePrice' => 109.00,
                    'Currency' => 'USD',
                ],
            ],
        ];
    }

    /**
     * Create mock booking response data.
     */
    protected function createMockBookingResponse(string $provider = 'redeam'): array
    {
        if ($provider === 'redeam') {
            return [
                'booking_id' => 'BOOK123456',
                'reference_number' => 'REF789012',
                'status' => 'CONFIRMED',
                'voucher_url' => 'https://vouchers.redeam.io/voucher_123.pdf',
                'ext' => [
                    'supplier' => [
                        'reference' => 'SUPPLIER_REF_123',
                    ],
                ],
            ];
        }

        // SmartOrder format
        return [
            'GalaxyOrderId' => 'GAL123456',
            'ExternalOrderId' => 'EXT789-2KNOW',
            'Status' => 'Confirmed',
            'CreatedTicketResponses' => [
                [
                    'TicketId' => 'TKT001',
                    'Barcode' => '123456789',
                    'ProductName' => 'Universal Studios 1-Day',
                    'GuestName' => 'John Doe',
                    'VisitDate' => '2024-12-25',
                ],
            ],
        ];
    }

    /**
     * Assert that database has order details record.
     */
    protected function assertHasOrderDetails(string $type, array $attributes): void
    {
        $table = $type === 'redeam' ? 'order_details_redeam' : 'order_details_universal';
        $this->assertDatabaseHas($table, $attributes);
    }

    /**
     * Create test order details record.
     */
    protected function createOrderDetails(string $type, array $attributes = []): \Illuminate\Database\Eloquent\Model
    {
        $modelClass = $type === 'redeam'
            ? \iabduul7\ThemeParkBooking\Models\OrderDetailsRedeam::class
            : \iabduul7\ThemeParkBooking\Models\OrderDetailsUniversal::class;

        $defaults = [
            'order_id' => 1,
            'status' => 'pending',
        ];

        if ($type === 'redeam') {
            $defaults['supplier_type'] = 'disney';
        }

        return $modelClass::create(array_merge($defaults, $attributes));
    }
}
