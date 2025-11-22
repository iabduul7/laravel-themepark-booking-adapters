# Laravel Theme Park Adapters

[![Latest Version on Packagist](https://img.shields.io/packagist/v/iabduul7/themepark-adapters.svg?style=flat-square)](https://packagist.org/packages/iabduul7/themepark-adapters)
[![Total Downloads](https://img.shields.io/packagist/dt/iabduul7/themepark-adapters.svg?style=flat-square)](https://packagist.org/packages/iabduul7/themepark-adapters)

A Laravel package for seamlessly integrating with major theme park APIs including Disney, SeaWorld, and Universal Studios. This package provides a unified interface for interacting with different theme park ticketing systems (Redeam and SmartOrder2).

## Features

- 🎢 **Multiple Theme Park Support**: Disney, SeaWorld, and Universal Studios
- 🔌 **Unified API Interface**: Work with different providers using the same methods
- 🎫 **Complete Ticketing Operations**: Search products, create orders, manage tickets
- 🔧 **Easy Configuration**: Simple .env-based configuration
- 🧩 **Extensible Architecture**: Easy to add new providers
- 📦 **Laravel Integration**: Native Laravel support with facades and service providers

## Supported Providers

| Theme Park | API System | Status |
|------------|------------|--------|
| Disney | Redeam | Ready for integration |
| SeaWorld | Redeam | Ready for integration |
| Universal Studios | SmartOrder2 | Ready for integration |

## Installation

You can install the package via composer:

```bash
composer require iabduul7/themepark-adapters
```

## Configuration

### Publish Configuration File

```bash
php artisan vendor:publish --tag="themepark-adapters-config"
```

### Environment Variables

Add the following to your `.env` file:

```env
# Default provider
THEMEPARK_DEFAULT_PROVIDER=disney

# Disney (Redeam)
DISNEY_ENABLED=true
DISNEY_API_BASE_URL=https://api.redeam.com/disney
DISNEY_API_KEY=your_disney_api_key
DISNEY_API_SECRET=your_disney_api_secret

# SeaWorld (Redeam)
SEAWORLD_ENABLED=true
SEAWORLD_API_BASE_URL=https://api.redeam.com/seaworld
SEAWORLD_API_KEY=your_seaworld_api_key
SEAWORLD_API_SECRET=your_seaworld_api_secret

# Universal Studios (SmartOrder2)
UNIVERSAL_ENABLED=true
UNIVERSAL_API_BASE_URL=https://api.universalstudios.com/smartorder2
UNIVERSAL_API_USERNAME=your_universal_username
UNIVERSAL_API_PASSWORD=your_universal_password

# Cache Configuration
THEMEPARK_CACHE_ENABLED=true
THEMEPARK_CACHE_TTL=3600

# Logging
THEMEPARK_LOGGING_ENABLED=false
THEMEPARK_LOG_CHANNEL=stack
```

## Usage

### Using the Facade

```php
use Iabduul7\ThemeParkAdapters\Facades\ThemePark;

// Use default provider (from config)
$products = ThemePark::getProducts();

// Use specific provider
$products = ThemePark::provider('disney')->getProducts();
$products = ThemePark::provider('seaworld')->getProducts();
$products = ThemePark::provider('universal')->getProducts();
```

### Using Dependency Injection

```php
use Iabduul7\ThemeParkAdapters\ThemeParkManager;

class TicketController extends Controller
{
    public function __construct(
        protected ThemeParkManager $themePark
    ) {}

    public function index()
    {
        $products = $this->themePark->provider('disney')->getProducts();

        return view('tickets.index', compact('products'));
    }
}
```

### Direct Adapter Usage

```php
use Iabduul7\ThemeParkAdapters\Providers\Disney\DisneyRedeamAdapter;

$adapter = new DisneyRedeamAdapter([
    'api_key' => 'your_api_key',
    'api_secret' => 'your_api_secret',
    'base_url' => 'https://api.redeam.com/disney',
]);

$products = $adapter->getProducts();
```

## API Methods

All adapters implement the following methods:

### Get Products

```php
// Get all products
$products = ThemePark::getProducts();

// Get products with filters
$products = ThemePark::getProducts([
    'category' => 'admission',
    'date' => '2024-12-25',
]);
```

### Get Single Product

```php
$product = ThemePark::getProduct('product-id-123');

// Access product details
echo $product->name;
echo $product->price;
echo $product->description;
```

### Check Availability

```php
$availability = ThemePark::getAvailability('product-id-123', [
    'start_date' => '2024-12-01',
    'end_date' => '2024-12-31',
]);
```

### Create Order

```php
$order = ThemePark::createOrder([
    'product_id' => 'product-id-123',
    'quantity' => 2,
    'visit_date' => '2024-12-25',
    'customer' => [
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ],
]);

// Access order details
echo $order->id;
echo $order->confirmationNumber;
echo $order->totalAmount;
```

### Get Order

```php
$order = ThemePark::getOrder('order-id-456');

foreach ($order->tickets as $ticket) {
    echo $ticket->ticketNumber;
    echo $ticket->barcode;
}
```

### Cancel Order

```php
$cancelled = ThemePark::cancelOrder('order-id-456');

if ($cancelled) {
    echo "Order cancelled successfully";
}
```

### Validate Credentials

```php
if (ThemePark::provider('disney')->validateCredentials()) {
    echo "Disney credentials are valid";
}
```

## Data Transfer Objects (DTOs)

The package uses DTOs for type-safe data handling:

### Product

```php
$product = ThemePark::getProduct('product-id');

$product->id;           // string
$product->name;         // string
$product->description;  // string
$product->price;        // float
$product->currency;     // string
$product->imageUrl;     // ?string
$product->metadata;     // array

// Convert to array
$array = $product->toArray();
```

### Order

```php
$order = ThemePark::getOrder('order-id');

$order->id;                    // string
$order->status;                // string
$order->tickets;               // array
$order->totalAmount;           // float
$order->currency;              // string
$order->confirmationNumber;    // ?string
$order->createdAt;            // ?string
$order->metadata;             // array

// Convert to array
$array = $order->toArray();
```

### Ticket

```php
$ticket = $order->tickets[0];

$ticket->id;            // string
$ticket->productId;     // string
$ticket->productName;   // string
$ticket->ticketNumber;  // string
$ticket->barcode;       // ?string
$ticket->qrCode;        // ?string
$ticket->validFrom;     // ?string
$ticket->validUntil;    // ?string
$ticket->metadata;      // array

// Convert to array
$array = $ticket->toArray();
```

## Error Handling

The package throws `ThemeParkApiException` for API errors:

```php
use Iabduul7\ThemeParkAdapters\Exceptions\ThemeParkApiException;

try {
    $product = ThemePark::getProduct('invalid-id');
} catch (ThemeParkApiException $e) {
    echo $e->getMessage();

    // Get response data if available
    $responseData = $e->getResponseData();
}
```

## Example Integration

### Controller Example

```php
namespace App\Http\Controllers;

use Iabduul7\ThemeParkAdapters\Facades\ThemePark;
use Iabduul7\ThemeParkAdapters\Exceptions\ThemeParkApiException;

class ThemeParkTicketController extends Controller
{
    public function index(string $provider = 'disney')
    {
        try {
            $products = ThemePark::provider($provider)->getProducts();

            return view('tickets.index', [
                'products' => $products,
                'provider' => $provider,
            ]);
        } catch (ThemeParkApiException $e) {
            return back()->withError('Failed to fetch tickets: ' . $e->getMessage());
        }
    }

    public function show(string $provider, string $productId)
    {
        try {
            $product = ThemePark::provider($provider)->getProduct($productId);
            $availability = ThemePark::provider($provider)->getAvailability($productId);

            return view('tickets.show', compact('product', 'availability'));
        } catch (ThemeParkApiException $e) {
            return back()->withError('Product not found');
        }
    }

    public function store(string $provider)
    {
        try {
            $order = ThemePark::provider($provider)->createOrder([
                'product_id' => request('product_id'),
                'quantity' => request('quantity'),
                'visit_date' => request('visit_date'),
                'customer' => request('customer'),
            ]);

            return redirect()
                ->route('orders.show', $order->id)
                ->withSuccess('Order created successfully!');
        } catch (ThemeParkApiException $e) {
            return back()
                ->withInput()
                ->withError('Failed to create order: ' . $e->getMessage());
        }
    }
}
```

## Testing

```bash
composer test
```

## Contributing

Contributions are welcome! Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

### Adding a New Provider

1. Create a new adapter class extending `BaseThemeParkAdapter`
2. Implement all methods from `ThemeParkAdapterInterface`
3. Add configuration in `config/themepark-adapters.php`
4. Add driver creation method in `ThemeParkManager`
5. Write tests for the new provider

## Security

If you discover any security-related issues, please email the maintainer instead of using the issue tracker.

## Roadmap

- [ ] Complete Redeam API integration for Disney
- [ ] Complete Redeam API integration for SeaWorld
- [ ] Complete SmartOrder2 API integration for Universal
- [ ] Add response caching support
- [ ] Add webhook support for order updates
- [ ] Add more theme park providers (Six Flags, Busch Gardens, etc.)

## Credits

- [iabduul7](https://github.com/iabduul7)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## Acknowledgements

This package was created to simplify theme park API integrations for the [KnowBeforeUGo](https://github.com/iabduul7/knowbeforeugo-backend) project.
