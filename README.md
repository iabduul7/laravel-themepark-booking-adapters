# Laravel Theme Park Adapters

[![Latest Version on Packagist](https://img.shields.io/packagist/v/iabduul7/themepark-adapters.svg?style=flat-square)](https://packagist.org/packages/iabduul7/themepark-adapters)
[![Total Downloads](https://img.shields.io/packagist/dt/iabduul7/themepark-adapters.svg?style=flat-square)](https://packagist.org/packages/iabduul7/themepark-adapters)

A Laravel package for seamlessly integrating with major theme park APIs including Disney, SeaWorld, and Universal Studios. This package provides a unified interface for interacting with different theme park ticketing systems (Redeam and SmartOrder2).

## Features

- 🎢 **Multiple Theme Park Support**: Disney, SeaWorld, and Universal Studios
- 🔌 **Unified API Interface**: Work with different providers using consistent methods
- 🎫 **Complete Ticketing Operations**: Products, rates, availability, holds, and bookings
- 🔧 **Easy Configuration**: Simple .env-based configuration
- 🔐 **OAuth 2.0 Support**: Automatic token management for SmartOrder2
- 🧩 **Extensible Architecture**: Easy to add new providers
- 📦 **Laravel Integration**: Native Laravel support with facades and service providers

## Supported Providers

| Theme Park | API System | Authentication | Status |
|------------|------------|----------------|--------|
| Disney | Redeam v1.2 | API Key + Secret | ✅ Production Ready |
| SeaWorld/United Parks | Redeam v1.2 | API Key + Secret | ✅ Production Ready |
| Universal Studios | SmartOrder2 | OAuth 2.0 | ✅ Production Ready |

## Table of Contents

- [Installation](#installation)
- [Configuration](#configuration)
- [Basic Usage](#basic-usage)
- [Redeam API (Disney & SeaWorld)](#redeam-api-disney--seaworld)
- [SmartOrder2 API (Universal)](#smartorder2-api-universal)
- [Advanced Usage](#advanced-usage)
- [Error Handling](#error-handling)
- [Testing](#testing)
- [Contributing](#contributing)

## Installation

### Requirements

- PHP 8.0 or higher
- Laravel 8.x, 9.x, 10.x, or 11.x
- Guzzle HTTP client 7.0+

### Install via Composer

```bash
composer require iabduul7/themepark-adapters
```

The package will automatically register its service provider.

## Configuration

### Step 1: Publish Configuration File

```bash
php artisan vendor:publish --tag="themepark-adapters-config"
```

This will create a `config/themepark-adapters.php` file in your Laravel application.

### Step 2: Set Environment Variables

Add the following to your `.env` file:

```env
# Default provider (disney, seaworld, universal)
THEMEPARK_DEFAULT_PROVIDER=disney

# Redeam API Configuration (Shared by Disney and SeaWorld/United Parks)
REDEAM_API_HOST=booking.redeam.io
REDEAM_API_VERSION=v1.2
REDEAM_TIMEOUT=600
REDEAM_VERIFY_SSL=true

# Disney Configuration (Redeam)
DISNEY_ENABLED=true
REDEAM_DISNEY_SUPPLIER_ID=your_disney_supplier_id_here
REDEAM_DISNEY_API_KEY=your_disney_api_key_here
REDEAM_DISNEY_API_SECRET=your_disney_api_secret_here

# SeaWorld/United Parks Configuration (Redeam)
SEAWORLD_ENABLED=true
REDEAM_UNITED_PARKS_SUPPLIER_ID=  # May be empty for United Parks
REDEAM_UNITED_PARKS_API_KEY=your_seaworld_api_key_here
REDEAM_UNITED_PARKS_API_SECRET=your_seaworld_api_secret_here

# Universal Studios Configuration (SmartOrder2)
UNIVERSAL_ENABLED=true
SMARTORDER_API_HOST=QACorpAPI.ucdp.net
SMARTORDER_CUSTOMER_ID=your_customer_id_here
SMARTORDER_APPROVED_SUFFIX=
SMARTORDER_CLIENT_USERNAME=your_client_username_here
SMARTORDER_CLIENT_SECRET=your_client_secret_here
SMARTORDER_TIMEOUT=600
SMARTORDER_VERIFY_SSL=true

# Cache Configuration
THEMEPARK_CACHE_ENABLED=true
THEMEPARK_CACHE_TTL=3600

# Logging Configuration
THEMEPARK_LOGGING_ENABLED=false
THEMEPARK_LOG_CHANNEL=stack
```

### Step 3: Clear Configuration Cache

```bash
php artisan config:clear
```

## Basic Usage

### Using the Facade

The easiest way to use the package is through the `ThemePark` facade:

```php
use Iabduul7\ThemeParkAdapters\Facades\ThemePark;

// Use default provider (from config)
$products = ThemePark::getAllProducts();

// Use specific provider
$disneyProducts = ThemePark::provider('disney')->getAllProducts();
$seaworldProducts = ThemePark::provider('seaworld')->getAllProducts();
$universalProducts = ThemePark::provider('universal')->getAllProducts();
```

### Using Dependency Injection

Inject the `ThemeParkManager` into your controllers or services:

```php
use Iabduul7\ThemeParkAdapters\ThemeParkManager;

class TicketController extends Controller
{
    public function __construct(
        protected ThemeParkManager $themePark
    ) {}

    public function index()
    {
        $products = $this->themePark->provider('disney')->getAllProducts();

        return view('tickets.index', compact('products'));
    }
}
```

### Direct Adapter Usage

You can also instantiate adapters directly:

```php
use Iabduul7\ThemeParkAdapters\Providers\Disney\DisneyRedeamAdapter;

$adapter = new DisneyRedeamAdapter([
    'supplier_id' => 'your_supplier_id',
    'host' => 'booking.redeam.io',
    'version' => 'v1.2',
    'api_key' => 'your_api_key',
    'api_secret' => 'your_api_secret',
    'timeout' => 600,
]);

$products = $adapter->getAllProducts();
```

## Redeam API (Disney & SeaWorld)

Both Disney and SeaWorld/United Parks use the Redeam API system. The methods are identical across both providers.

### Get All Products

Retrieve all available products for the supplier:

```php
$products = ThemePark::provider('disney')->getAllProducts();

// With filters
$products = ThemePark::provider('disney')->getAllProducts([
    'category' => 'tickets',
]);
```

### Get Specific Product

Get detailed information about a specific product:

```php
$productId = 'prod_12345';
$product = ThemePark::provider('disney')->getProduct($productId);

// Product details
echo $product['name'];
echo $product['description'];
echo $product['base_price'];
```

### Get Product Rates

Retrieve available rates for a product:

```php
$rates = ThemePark::provider('disney')->getProductRates($productId);

foreach ($rates as $rate) {
    echo $rate['name'];
    echo $rate['price'];
}
```

### Get Specific Rate

Get details of a specific rate:

```php
$rateId = 'rate_67890';
$rate = ThemePark::provider('disney')->getProductRate($productId, $rateId);
```

### Check Availability

Check availability for a single date:

```php
$availability = ThemePark::provider('disney')->checkAvailability(
    productId: $productId,
    date: '2024-12-25',
    quantity: 2
);

if ($availability['available']) {
    echo "Tickets available!";
}
```

### Check Date Range Availability

Check availability across multiple dates:

```php
$availabilities = ThemePark::provider('disney')->checkAvailabilities(
    productId: $productId,
    startDate: '2024-12-01',
    endDate: '2024-12-31'
);

foreach ($availabilities as $date => $availability) {
    echo "{$date}: " . ($availability['available'] ? 'Available' : 'Sold Out');
}
```

### Get Pricing Schedule

Retrieve pricing information for a date range:

```php
$pricingSchedule = ThemePark::provider('disney')->getProductPricingSchedule(
    productId: $productId,
    startDate: '2024-12-01',
    endDate: '2024-12-31'
);
```

### Create a Hold (Reservation)

Create a temporary hold before confirming a booking:

```php
$holdData = [
    'hold' => [
        'items' => [
            [
                'product_id' => $productId,
                'rate_id' => $rateId,
                'quantity' => 2,
                'date' => '2024-12-25',
            ],
        ],
        'customer' => [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
        ],
    ],
];

$hold = ThemePark::provider('disney')->createNewHold($holdData);
$holdId = $hold['id'];
```

### Get Hold Details

Retrieve information about an existing hold:

```php
$hold = ThemePark::provider('disney')->getHold($holdId);

echo $hold['status'];
echo $hold['expires_at'];
```

### Delete Hold

Release a hold:

```php
$result = ThemePark::provider('disney')->deleteHold($holdId);
```

### Create Booking

Convert a hold into a confirmed booking:

```php
$bookingData = [
    'booking' => [
        'hold_id' => $holdId,
        'payment' => [
            'method' => 'credit_card',
            'amount' => 150.00,
            'currency' => 'USD',
        ],
    ],
];

$booking = ThemePark::provider('disney')->createNewBooking($bookingData);
$bookingId = $booking['id'];
$confirmationNumber = $booking['confirmation_number'];
```

### Get Booking Details

Retrieve booking information:

```php
$booking = ThemePark::provider('disney')->getBooking($bookingId);

echo $booking['confirmation_number'];
echo $booking['status'];

foreach ($booking['tickets'] as $ticket) {
    echo $ticket['ticket_number'];
    echo $ticket['barcode'];
}
```

### Cancel Booking

Cancel a confirmed booking:

```php
$result = ThemePark::provider('disney')->deleteBooking($bookingId);
```

## SmartOrder2 API (Universal)

Universal Studios uses the SmartOrder2 API system with OAuth 2.0 authentication.

### Get All Products

Retrieve the product catalog:

```php
$products = ThemePark::provider('universal')->getAllProducts([
    'ProductTypeId' => 1, // Optional filter
]);
```

### Get Available Months

Get the next 12 months available for booking:

```php
$months = ThemePark::provider('universal')->getAvailableMonths();

// Returns: ['2024-11', '2024-12', '2025-01', ...]
```

### Find Events

Search for available events:

```php
$events = ThemePark::provider('universal')->findEvents([
    'ProductId' => 123,
    'EventDate' => '2024-12-25',
    'Quantity' => 2,
]);
```

### Place Order

Create a new order:

```php
$orderData = [
    'CustomerId' => config('themepark-adapters.providers.universal.customer_id'),
    'OrderItems' => [
        [
            'ProductId' => 123,
            'EventId' => 456,
            'Quantity' => 2,
            'UnitPrice' => 75.00,
        ],
    ],
    'Customer' => [
        'FirstName' => 'John',
        'LastName' => 'Doe',
        'Email' => 'john@example.com',
        'Phone' => '555-1234',
    ],
];

$order = ThemePark::provider('universal')->placeOrder($orderData);
$orderId = $order['OrderId'];
```

### Get Existing Order

Retrieve order details:

```php
$order = ThemePark::provider('universal')->getExistingOrder([
    'OrderId' => $orderId,
]);

echo $order['OrderStatus'];
echo $order['TotalAmount'];
```

### Check if Order Can Be Cancelled

Verify if an order is eligible for cancellation:

```php
$canCancel = ThemePark::provider('universal')->canCancelOrder([
    'OrderId' => $orderId,
]);

if ($canCancel) {
    echo "Order can be cancelled";
}
```

### Cancel Order

Cancel an existing order:

```php
$result = ThemePark::provider('universal')->cancelOrder([
    'OrderId' => $orderId,
    'Reason' => 'Customer request',
]);
```

### Access Customer ID and Suffix

Get configuration values:

```php
$adapter = ThemePark::provider('universal');

$customerId = $adapter->getCustomerId();
$suffix = $adapter->getApprovedSuffix();
```

## Advanced Usage

### Custom Token Repository

For SmartOrder2, you can implement your own token storage:

```php
use Iabduul7\ThemeParkAdapters\Contracts\TokenRepositoryInterface;

class DatabaseTokenRepository implements TokenRepositoryInterface
{
    public function getValidToken(): ?string
    {
        $token = DB::table('oauth_tokens')
            ->where('provider', 'smartorder')
            ->where('expires_at', '>', now())
            ->latest()
            ->value('token');

        return $token;
    }

    public function storeToken(string $token, int $expiresIn): void
    {
        DB::table('oauth_tokens')->insert([
            'provider' => 'smartorder',
            'token' => $token,
            'expires_at' => now()->addSeconds($expiresIn),
        ]);
    }
}

// Register in AppServiceProvider
use Iabduul7\ThemeParkAdapters\Contracts\TokenRepositoryInterface;

public function register()
{
    $this->app->bind(TokenRepositoryInterface::class, DatabaseTokenRepository::class);
}
```

### Validate Credentials

Test if your API credentials are valid:

```php
if (ThemePark::provider('disney')->validateCredentials()) {
    echo "Disney credentials are valid!";
} else {
    echo "Invalid credentials";
}
```

### Get Provider Name

Retrieve the provider's display name:

```php
$name = ThemePark::provider('disney')->getProviderName();
// Returns: "Disney (Redeam)"

$name = ThemePark::provider('universal')->getProviderName();
// Returns: "Universal (SmartOrder2)"
```

## Error Handling

The package throws `ThemeParkApiException` for API errors:

```php
use Iabduul7\ThemeParkAdapters\Exceptions\ThemeParkApiException;

try {
    $product = ThemePark::provider('disney')->getProduct('invalid-id');
} catch (ThemeParkApiException $e) {
    // Get error message
    echo $e->getMessage();

    // Get HTTP status code
    echo $e->getCode();

    // Get response data (if available)
    $responseData = $e->getResponseData();
    Log::error('Theme Park API Error', $responseData);
}
```

### Common Error Scenarios

```php
// Handle specific errors
try {
    $booking = ThemePark::provider('disney')->createNewBooking($data);
} catch (ThemeParkApiException $e) {
    if ($e->getCode() === 401) {
        // Invalid credentials
        return response()->json(['error' => 'Invalid API credentials'], 401);
    }

    if ($e->getCode() === 404) {
        // Resource not found
        return response()->json(['error' => 'Product not found'], 404);
    }

    if ($e->getCode() === 422) {
        // Validation error
        $errors = $e->getResponseData();
        return response()->json(['error' => 'Validation failed', 'details' => $errors], 422);
    }

    // Generic error
    return response()->json(['error' => 'An error occurred'], 500);
}
```

## Complete Example

Here's a complete example of a ticket booking flow:

```php
namespace App\Http\Controllers;

use Iabduul7\ThemeParkAdapters\Facades\ThemePark;
use Iabduul7\ThemeParkAdapters\Exceptions\ThemeParkApiException;
use Illuminate\Http\Request;

class DisneyTicketController extends Controller
{
    public function index()
    {
        try {
            $products = ThemePark::provider('disney')->getAllProducts();

            return view('tickets.index', compact('products'));
        } catch (ThemeParkApiException $e) {
            return back()->withError('Failed to load products: ' . $e->getMessage());
        }
    }

    public function show($productId)
    {
        try {
            $product = ThemePark::provider('disney')->getProduct($productId);
            $rates = ThemePark::provider('disney')->getProductRates($productId);

            return view('tickets.show', compact('product', 'rates'));
        } catch (ThemeParkApiException $e) {
            return back()->withError('Product not found');
        }
    }

    public function checkAvailability(Request $request, $productId)
    {
        try {
            $availability = ThemePark::provider('disney')->checkAvailability(
                productId: $productId,
                date: $request->date,
                quantity: $request->quantity
            );

            return response()->json($availability);
        } catch (ThemeParkApiException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function createHold(Request $request)
    {
        try {
            $holdData = [
                'hold' => [
                    'items' => [
                        [
                            'product_id' => $request->product_id,
                            'rate_id' => $request->rate_id,
                            'quantity' => $request->quantity,
                            'date' => $request->visit_date,
                        ],
                    ],
                    'customer' => [
                        'first_name' => $request->first_name,
                        'last_name' => $request->last_name,
                        'email' => $request->email,
                    ],
                ],
            ];

            $hold = ThemePark::provider('disney')->createNewHold($holdData);

            // Store hold ID in session
            session(['hold_id' => $hold['id']]);

            return redirect()->route('checkout');
        } catch (ThemeParkApiException $e) {
            return back()
                ->withInput()
                ->withError('Failed to create reservation: ' . $e->getMessage());
        }
    }

    public function confirmBooking(Request $request)
    {
        try {
            $holdId = session('hold_id');

            $bookingData = [
                'booking' => [
                    'hold_id' => $holdId,
                    'payment' => [
                        'method' => 'credit_card',
                        'amount' => $request->amount,
                        'currency' => 'USD',
                    ],
                ],
            ];

            $booking = ThemePark::provider('disney')->createNewBooking($bookingData);

            // Clear hold from session
            session()->forget('hold_id');

            return redirect()
                ->route('booking.confirmation', $booking['id'])
                ->with('success', 'Booking confirmed! Confirmation #' . $booking['confirmation_number']);
        } catch (ThemeParkApiException $e) {
            return back()
                ->withInput()
                ->withError('Booking failed: ' . $e->getMessage());
        }
    }

    public function showBooking($bookingId)
    {
        try {
            $booking = ThemePark::provider('disney')->getBooking($bookingId);

            return view('booking.confirmation', compact('booking'));
        } catch (ThemeParkApiException $e) {
            abort(404, 'Booking not found');
        }
    }
}
```

## Testing

Run the package tests:

```bash
composer test
```

### Testing with Fake Data

For testing, you can mock the adapters:

```php
use Iabduul7\ThemeParkAdapters\Facades\ThemePark;
use Mockery;

public function test_can_get_products()
{
    $mock = Mockery::mock('Iabduul7\ThemeParkAdapters\Providers\Disney\DisneyRedeamAdapter');
    $mock->shouldReceive('getAllProducts')
        ->once()
        ->andReturn([
            ['id' => '1', 'name' => 'Test Product'],
        ]);

    ThemePark::swap($mock);

    $products = ThemePark::getAllProducts();

    $this->assertCount(1, $products);
}
```

## Contributing

Contributions are welcome! Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

### Adding a New Provider

1. Create a new adapter class extending `BaseThemeParkAdapter`
2. Implement all required methods from `ThemeParkAdapterInterface`
3. Add configuration in `config/themepark-adapters.php`
4. Add driver creation method in `ThemeParkManager`
5. Write tests for the new provider
6. Update documentation

## Security

If you discover any security-related issues, please email the maintainer instead of using the issue tracker.

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Credits

- [iabduul7](https://github.com/iabduul7)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## Acknowledgements

This package was created to simplify theme park API integrations for the [KnowBeforeUGo](https://github.com/iabduul7/knowbeforeugo-backend) project.

## Support

For support, please open an issue on GitHub or contact the maintainer.
