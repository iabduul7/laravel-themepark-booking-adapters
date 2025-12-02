# Laravel Theme Park Booking Adapters

[![Latest Version on Packagist](https://img.shields.io/packagist/v/iabduul7/laravel-themepark-booking-adapters.svg?style=flat-square)](https://packagist.org/packages/iabduul7/laravel-themepark-booking-adapters)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/iabduul7/laravel-themepark-booking-adapters/tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/iabduul7/laravel-themepark-booking-adapters/actions?query=workflow%3Atests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/iabduul7/laravel-themepark-booking-adapters/style-fix.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/iabduul7/laravel-themepark-booking-adapters/actions?query=workflow%3Astyle-fix+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/iabduul7/laravel-themepark-booking-adapters.svg?style=flat-square)](https://packagist.org/packages/iabduul7/laravel-themepark-booking-adapters)

A comprehensive Laravel package providing unified booking adapters for major theme park providers including Disney World (via Redeam), Universal Studios (via SmartOrder), and United Parks. This package provides a clean, consistent interface for handling theme park bookings, managing order details, and processing vouchers.

## Features

-   🎢 **Multiple Provider Support**: Disney World, Universal Studios, United Parks, SeaWorld
-   🔄 **Unified Interface**: Consistent API across all booking providers
-   📊 **Order Management**: Complete order details tracking with relationships
-   🎫 **Voucher Generation**: Automated voucher creation and management
-   ⚡ **Performance Optimized**: Built-in caching, rate limiting, and circuit breakers
-   🔍 **Query Scopes**: Pre-built scopes for Disney, Universal, and United Parks product filtering
-   📝 **Rich Documentation**: Comprehensive examples and configuration
-   🧪 **Full Test Coverage**: Reliable and well-tested codebase
-   🔧 **Easy Installation**: One-command setup with migrations and configuration

## Installation

You can install the package via composer:

```bash
composer require iabduul7/laravel-themepark-booking-adapters
```

For development work, you'll also need to install Node.js dependencies:

```bash
pnpm install
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="themepark-booking-config"
```

This is the contents of the published config file:

```php
return [
    'adapters' => [
        'redeam_disney' => [
            'driver' => 'redeam',
            'park_type' => 'disney',
            'base_url' => env('REDEAM_BASE_URL', 'https://booking.redeam.io/v1.2'),
            'api_key' => env('REDEAM_DISNEY_API_KEY'),
            'api_secret' => env('REDEAM_DISNEY_API_SECRET'),
            'supplier_id' => env('REDEAM_DISNEY_SUPPLIER_ID'),
            'timeout' => 600,
        ],
        'redeam_united' => [
            'driver' => 'redeam',
            'park_type' => 'united_parks',
            'base_url' => env('REDEAM_BASE_URL', 'https://booking.redeam.io/v1.2'),
            'api_key' => env('REDEAM_UNITED_API_KEY'),
            'api_secret' => env('REDEAM_UNITED_API_SECRET'),
            'timeout' => 600,
        ],
        'smartorder' => [
            'driver' => 'smartorder',
            'base_url' => env('SMARTORDER_BASE_URL', 'https://QACorpAPI.ucdp.net'),
            'client_username' => env('SMARTORDER_CLIENT_USERNAME'),
            'client_secret' => env('SMARTORDER_CLIENT_SECRET'),
            'customer_id' => env('SMARTORDER_CUSTOMER_ID', 134853),
            'approved_suffix' => env('SMARTORDER_APPROVED_SUFFIX', '-2KNOW'),
            'timeout' => 600,
        ],
    ],
    'default' => env('THEMEPARK_BOOKING_DEFAULT_ADAPTER', 'redeam_disney'),
];
```

## Usage

### Basic Configuration

Add these environment variables to your `.env` file:

```env
# Redeam API (Disney World)
REDEAM_DISNEY_API_KEY=your_disney_api_key
REDEAM_DISNEY_API_SECRET=your_disney_api_secret
REDEAM_DISNEY_SUPPLIER_ID=your_disney_supplier_id

# Redeam API (Universal Parks)
REDEAM_UNITED_API_KEY=your_united_api_key
REDEAM_UNITED_API_SECRET=your_united_api_secret

# SmartOrder API
SMARTORDER_CLIENT_USERNAME=your_smartorder_client_id
SMARTORDER_CLIENT_SECRET=your_smartorder_client_secret
SMARTORDER_CUSTOMER_ID=134853
```

### Using the Independent HTTP Clients

The package includes self-contained HTTP clients that don't depend on external packages:

#### Redeam HTTP Client

```php
use iabduul7\LaravelThemeparkBookingAdapters\Http\RedeamHttpClient;

$client = new RedeamHttpClient(
    baseUrl: 'https://booking.redeam.io/v1.2',
    apiKey: 'your_api_key',
    apiSecret: 'your_api_secret',
    timeout: 600
);

// Make API calls directly
$suppliers = $client->get('suppliers');
$products = $client->get('suppliers/123/products');
$booking = $client->post('suppliers/123/bookings', $bookingData);
```

#### SmartOrder HTTP Client

```php
use iabduul7\LaravelThemeparkBookingAdapters\Http\SmartOrderHttpClient;

$client = new SmartOrderHttpClient(
    baseUrl: 'https://QACorpAPI.ucdp.net',
    clientId: 'your_client_username',
    clientSecret: 'your_client_secret',
    customerId: 134853,
    timeout: 600
);

// Automatic OAuth2 token management
$catalog = $client->get('smartorder/MyProductCatalog');
$events = $client->post('smartorder/FindEvents', $searchParams);
$order = $client->post('smartorder/PlaceOrder', $orderData);
```

## API Authentication

### Redeam API Authentication

-   Uses **X-API-Key** and **X-API-Secret** headers
-   GET requests send data as form parameters
-   POST/PUT requests send data as JSON
-   600-second timeout by default

### SmartOrder API Authentication

-   Uses **OAuth2 Client Credentials** flow
-   Automatic token refresh and caching
-   Bearer token authentication
-   Customer ID embedded in requests

## Product Filtering Scopes

The package provides three traits with pre-built query scopes for different theme park providers:

### HasDisneyScopes

Add Disney-specific filtering to your Product model:

```php
use iabduul7\ThemeParkBooking\Concerns\HasDisneyScopes;

class Product extends Model
{
    use HasDisneyScopes;
}

// Usage examples
$disneyProducts = Product::disneyWorld()->get();
$magicKingdom = Product::disneyMagicKingdom()->get();
$epcot = Product::disneyEpcot()->get();
$hollywoodStudios = Product::disneyHollywoodStudios()->get();
$animalKingdom = Product::disneyAnimalKingdom()->get();
$waterParks = Product::disneyWaterPark()->get();
$parkHopper = Product::disneyParkHopper()->get();
$genie = Product::disneyGenie()->get();
$specialEvents = Product::disneySpecialEvent()->get();
```

### HasUniversalScopes

Add Universal Studios-specific filtering:

```php
use iabduul7\ThemeParkBooking\Concerns\HasUniversalScopes;

class Product extends Model
{
    use HasUniversalScopes;
}

// Usage examples
$promoProducts = Product::universalPromo()->get();
$expressPass = Product::universalExpressPass()->get();
$datedTickets = Product::universalDated()->get();
$hhn = Product::universalHHN()->get(); // Halloween Horror Nights
$volcanoBay = Product::universalVolcanoBay()->get();
$islandsOfAdventure = Product::universalIslandsOfAdventure()->get();
$universalStudios = Product::universalStudios()->get();
$multiDay = Product::universalMultiDay()->get();
```

### HasUnitedParksScopes

Add United Parks (SeaWorld, Busch Gardens) filtering:

```php
use iabduul7\ThemeParkBooking\Concerns\HasUnitedParksScopes;

class Product extends Model
{
    use HasUnitedParksScopes;
}

// Usage examples
$unitedParks = Product::unitedParks()->get();
$seaWorld = Product::seaWorld()->get();
$seaWorldOrlando = Product::seaWorldOrlando()->get();
$seaWorldSanDiego = Product::seaWorldSanDiego()->get();
$buschGardens = Product::buschGardens()->get();
$buschGardensTampa = Product::buschGardensTampa()->get();
$buschGardensWilliamsburg = Product::buschGardensWilliamsburg()->get();
$aquatica = Product::aquatica()->get();
$adventureIsland = Product::adventureIsland()->get();
$multiPark = Product::unitedParksMultiPark()->get();
$seasonPass = Product::unitedParksSeasonPass()->get();
$vip = Product::unitedParksVIP()->get();
$dining = Product::unitedParksDining()->get();
$parking = Product::unitedParksParking()->get();
$specialEvents = Product::unitedParksSpecialEvent()->get();
$waterParks = Product::unitedParksWaterPark()->get();
```

### Using Multiple Scopes

You can combine multiple scope traits for comprehensive filtering:

```php
class Product extends Model
{
    use HasDisneyScopes, HasUniversalScopes, HasUnitedParksScopes;
}

// Filter by park type
$disneyProducts = Product::disneyWorld()->get();
$universalProducts = Product::universalExpressPass()->get();
$unitedParksProducts = Product::seaWorld()->get();

// Negate scopes (exclude products)
$nonDisneyProducts = Product::disneyWorld(true)->get(); // negate = true
$nonPromo = Product::universalPromo(true)->get();
```

## Error Handling

The package provides comprehensive error handling:

```php
use iabduul7\ThemeParkBooking\Exceptions\AdapterException;
use iabduul7\ThemeParkBooking\Exceptions\BookingException;
use iabduul7\ThemeParkBooking\Exceptions\ConfigurationException;

try {
    $adapter = new RedeamAdapter('disney', $config);
    $products = $adapter->getAllProducts();
} catch (ConfigurationException $e) {
    // Handle configuration issues
    Log::error('Configuration error: ' . $e->getMessage());
} catch (AdapterException $e) {
    // Handle adapter-specific issues
    Log::error('Adapter error: ' . $e->getMessage());
} catch (BookingException $e) {
    // Handle booking-related errors
    Log::error('Booking error: ' . $e->getMessage());
}
```

## Development

### Initial Setup

After cloning the repository, install dependencies and set up git hooks:

```bash
# Install PHP dependencies
composer install

# Install Node.js dependencies (using pnpm)
pnpm install

# IMPORTANT: Manually install git hooks for code quality enforcement
pnpm run hooks:install
```

### Git Hooks

This package uses git hooks to maintain code quality:

-   **Pre-commit hook**: Automatically checks PHP code style using Laravel Pint
-   **Pre-push hook**: Runs tests before pushing to ensure code quality

If you encounter style issues during commit, fix them with:

```bash
composer format
```

To uninstall git hooks (if needed):

```bash
pnpm run hooks:uninstall
```

## Testing

Run the tests with:

```bash
composer test
```

Run specific test suites:

```bash
# Unit tests only
composer test:unit

# Feature tests only
composer test:feature

# With coverage report
composer test:coverage

# HTML coverage report
composer test:coverage-html
```

## Code Style

Check code style:

```bash
composer format:check
```

Fix code style issues:

```bash
composer format
```

**Note**: Code style is automatically enforced via git hooks. Commits will be blocked if style violations are detected.

## Static Analysis

Run static analysis:

```bash
composer analyse
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

-   [Abdullah](https://github.com/iabduul7)
-   [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
