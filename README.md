# Laravel Theme Park Booking Adapters

[![Latest Version on Packagist](https://img.shields.io/packagist/v/iabduul7/laravel-themepark-booking-adapters.svg?style=flat-square)](https://packagist.org/packages/iabduul7/laravel-themepark-booking-adapters)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/iabduul7/laravel-themepark-booking-adapters/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/iabduul7/laravel-themepark-booking-adapters/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub PHPStan Action Status](https://img.shields.io/github/actions/workflow/status/iabduul7/laravel-themepark-booking-adapters/phpstan.yml?branch=dev&label=phpstan&style=flat-square)](https://github.com/iabduul7/laravel-themepark-booking-adapters/actions?query=workflow%3Aphpstan+branch%3Adev)
[![Total Downloads](https://img.shields.io/packagist/dt/iabduul7/laravel-themepark-booking-adapters.svg?style=flat-square)](https://packagist.org/packages/iabduul7/laravel-themepark-booking-adapters)

A Laravel package providing self-contained, drop-in booking adapters for major theme park
distribution APIs:

| Park | Provider | Adapter |
| --- | --- | --- |
| Walt Disney World | Redeam | `DisneyRedeamAdapter` |
| SeaWorld / United Parks | Redeam | `SeaWorldRedeamAdapter` |
| Universal Orlando | SmartOrder ("SmartOrder2") | `UniversalSmartOrder2Adapter` |

Each adapter exposes the provider's real API surface (catalog, rates, availability, pricing
schedules, holds/bookings for Redeam; events/orders for SmartOrder) and returns lightweight
DTOs over the raw responses. Auth, retries and OAuth token management are handled for you.

## Features

- 🎢 **Three production parks supported**: Disney (Redeam), SeaWorld/United Parks (Redeam), Universal (SmartOrder)
- 🧩 **Driver-based resolution** via a Laravel `Manager` + `ThemePark` facade
- 🔐 **Auth handled**: Redeam `X-API-Key`/`X-API-Secret`; SmartOrder OAuth2 client-credentials with token caching and 401 self-heal
- ♻️ **Resilient transport**: idempotent reads retried on connection drops / 5xx; writes never retried (no double-booking)
- 📦 **Typed result objects**: `Supplier`, `Product`, `Rate`, `PriceSchedule`, `RatePriceSchedule`, …
- 🧱 **No app coupling**: pure API integration; persistence, jobs and vouchers stay in your app (with opt-in helpers)
- 🧪 **Contract-tested** with `Http::fake()` and analysed with PHPStan

## Installation

```bash
composer require iabduul7/laravel-themepark-booking-adapters
```

Publish the config file:

```bash
php artisan vendor:publish --tag="themepark-adapters-config"
```

Add credentials to your `.env` (see [`.env.example`](.env.example) for the full list):

```env
THEMEPARK_DEFAULT_PROVIDER=disney

# Disney (Redeam)
REDEAM_DISNEY_SUPPLIER_ID=your_disney_supplier_id
REDEAM_DISNEY_API_KEY=your_disney_api_key
REDEAM_DISNEY_API_SECRET=your_disney_api_secret

# SeaWorld / United Parks (Redeam) — supplier is passed per call
REDEAM_UNITED_PARKS_API_KEY=your_seaworld_api_key
REDEAM_UNITED_PARKS_API_SECRET=your_seaworld_api_secret

# Universal (SmartOrder)
SMARTORDER_CUSTOMER_ID=134853
SMARTORDER_APPROVED_SUFFIX=-2KNOW
SMARTORDER_CLIENT_USERNAME=your_client_username
SMARTORDER_CLIENT_SECRET=your_client_secret
```

## Usage

### Resolving an adapter

```php
use Iabduul7\ThemeParkAdapters\Facades\ThemePark;

$disney    = ThemePark::provider('disney');     // DisneyRedeamAdapter
$seaworld  = ThemePark::provider('seaworld');   // SeaWorldRedeamAdapter
$universal = ThemePark::provider('universal');  // UniversalSmartOrder2Adapter
```

You can also resolve via the container (`app('themepark')` / `ThemeParkManager`) or construct an
adapter directly with a config array:

```php
use Iabduul7\ThemeParkAdapters\Providers\Disney\DisneyRedeamAdapter;

$disney = new DisneyRedeamAdapter(config('themepark-adapters.providers.disney'));
```

### Disney (Redeam) — supplier fixed from config

```php
$products = $disney->getAllProducts();                                  // Product[]
$product  = $disney->getProduct('PRODUCT_ID');                          // Product
$rates    = $disney->getProductRates('PRODUCT_ID');                     // Rate[]
$schedule = $disney->getProductPricingSchedule('PRODUCT_ID', '2026-06-01', '2026-06-30'); // PriceSchedule
$rateSched = $disney->getProductRatePricingSchedule('PRODUCT_ID', '2026-06-01', '2026-06-30', 'RATE_ID');
$avail    = $disney->checkAvailabilities('PRODUCT_ID', '2026-06-01', '2026-06-30');

// Hold → book → cancel
$hold    = $disney->createNewHold(['hold' => ['items' => [/* … */]]]);
$booking = $disney->createNewBooking(['booking' => [/* … */]]);
$disney->deleteBooking('BOOKING_ID');

echo $product->getName();
echo $product->getId();
```

### SeaWorld / United Parks (Redeam) — supplier passed per call

```php
$products = $seaworld->getAllProducts('SUPPLIER_ID');
$product  = $seaworld->getProduct('SUPPLIER_ID', 'PRODUCT_ID');
$rates    = $seaworld->getProductRates('SUPPLIER_ID', 'PRODUCT_ID');
$schedule = $seaworld->getProductPricingSchedule('SUPPLIER_ID', 'PRODUCT_ID', '2026-06-01', '2026-06-30');
```

### Universal (SmartOrder)

```php
$catalog = $universal->getAllProducts();                 // GET smartorder/MyProductCatalog
$months  = $universal->getAvailableMonths();             // next 12 months
$events  = $universal->findEvents([/* plu, dates, … */]); // POST smartorder/FindEvents
$order   = $universal->placeOrder([/* order lines, … */]);// POST smartorder/PlaceOrder

if ($universal->canCancelOrder(['ExternalOrderId' => 'E1'])) {
    $universal->cancelOrder(['ExternalOrderId' => 'E1']);
}
```

> The adapters mirror the method names and signatures of the production
> `LaravelRedeamForWaltDisney`, `LaravelRedeamForUnitedParks` and `SmartOrderClient` clients so
> they can serve as a drop-in replacement. A normalised, provider-agnostic interface is proposed
> for a future major version in [`guides/CLEANER_API_REFERENCE.md`](guides/CLEANER_API_REFERENCE.md).

## Capability interfaces

- `Contracts\Capabilities\SupportsHolds` — `createNewHold`, `getHold`, `deleteHold`, `createNewBooking`, `getBooking`, `deleteBooking` (Redeam adapters)
- `Contracts\Capabilities\SupportsEvents` — `findEvents`, `placeOrder`, `getExistingOrder`, `canCancelOrder`, `cancelOrder` (SmartOrder adapter)

Type-hint these when you only need a capability rather than a specific park.

## Optional building blocks

Business logic that is specific to a deployment is kept out of the core adapters and shipped as
opt-in helpers under `Support/`:

- `Support\Redeam\OptionCodeResolver` — maps a Walt Disney World ticket name to its Redeam
  option code (also exposed on the Redeam adapters as `getOptionCode()` for drop-in parity).

Persistence (Eloquent models, migrations), queue/sync jobs, commission/operator margins and voucher *rendering* (barcode images,
templates, PDF, delivery) are intentionally left to the consuming application. The package does expose
the provider-native voucher **data** — `tickets()` normalises each booking/order response into typed
`TicketArtifact`s (the redeemable identifier + format + validity). See `guides/VOUCHERS.md`.

## Authentication

- **Redeam** — `X-API-Key` / `X-API-Secret` headers; GET is form-encoded, writes are JSON.
- **SmartOrder** — OAuth2 client-credentials (`/connect/token`, `scope=SmartOrder`); bearer token
  cached via a `TokenRepository` (set `SMARTORDER_TOKEN_CACHE=false` to always refresh, matching
  upstream); `customerId` injected into every request; transparent refresh-and-retry on `401`.

## Error handling

```php
use Iabduul7\ThemeParkAdapters\Exceptions\ThemeParkApiException;

try {
    $products = ThemePark::provider('disney')->getAllProducts();
} catch (ThemeParkApiException $e) {
    report($e);
}
```

## Development

```bash
composer install
```

## Testing

```bash
# Adapter contract tests (Http::fake)
composer test:adapters

# Static analysis
composer analyse

# Code style
composer format        # fix
composer format:check  # check only
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Credits

- [Abdullah](https://github.com/iabduul7)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
