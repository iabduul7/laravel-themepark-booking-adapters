# KnowBeforeUGo - Package Analysis & Migration Guide

## Executive Summary

This document analyzes the current implementation of two internal Laravel packages (`laravel-redeam` and `laravel-smartorder`) and provides a comprehensive guide for converting them into open-source repositories while improving their architecture and standardization.

## Table of Contents

1. [Current Architecture](#current-architecture)
2. [Package Analysis](#package-analysis)
3. [Integration Patterns](#integration-patterns)
4. [Identified Issues](#identified-issues)
5. [Improvement Recommendations](#improvement-recommendations)
6. [Migration Strategy](#migration-strategy)
7. [Best Practices](#best-practices)

---

## Current Architecture

### Package Overview

The project currently uses two internal packages stored in the `packages/` directory:

1. **laravel-redeam** - API client for Redeam booking services (Disney & United Parks)
2. **laravel-smartorder** - API client for Universal SmartOrder system

### Directory Structure

```
packages/
├── laravel-redeam/
│   ├── src/
│   │   ├── Commands/
│   │   ├── Facades/
│   │   ├── Result/
│   │   ├── LaravelRedeamForWaltDisney.php
│   │   ├── LaravelRedeamForUnitedParks.php
│   │   ├── RedeamApiClientForDisney.php
│   │   ├── RedeamApiClientForUnitedParks.php
│   │   └── LaravelRedeamServiceProvider.php
│   ├── config/
│   │   ├── redeam.php
│   │   └── walt_disney.php
│   └── composer.json
│
└── laravel-smartorder/
    ├── src/
    │   ├── Commands/
    │   ├── Facades/
    │   ├── SmartOrderClient.php
    │   ├── SmartOrderApiClient.php
    │   └── LaravelSmartOrderServiceProvider.php
    ├── config/
    │   └── smartorder.php
    └── composer.json
```

### Composer Integration

Both packages are loaded as local path repositories in the main `composer.json`:

```json
"repositories": [
    {
        "type": "path",
        "url": "./packages/laravel-redeam",
        "options": { "symlink": false }
    },
    {
        "type": "path",
        "url": "./packages/laravel-smartorder",
        "options": { "symlink": false }
    }
]
```

---

## Package Analysis

### 1. Laravel Redeam Package

#### Purpose
Provides integration with the Redeam API for booking theme park tickets (Disney & United Parks).

#### Key Components

**Service Classes:**
- `LaravelRedeamForWaltDisney` - Disney-specific booking logic
- `LaravelRedeamForUnitedParks` - United Parks-specific booking logic

**API Clients:**
- `RedeamApiClientForDisney` - HTTP client for Disney API
- `RedeamApiClientForUnitedParks` - HTTP client for United Parks API

**Result Models:**
- `Product` - Product data structure
- `Rate` - Rate information
- `Supplier` - Supplier details
- `Availability` - Availability data
- `PriceSchedule` - Pricing schedules
- `Booking` - Booking information
- `Hold` - Hold/reservation data

#### Configuration

**Package Config (`packages/laravel-redeam/config/redeam.php`):**
```php
return [
    'disney' => [
        'supplier_id' => env('REDEAM_DISNEY_SUPPLIER_ID'),
        'host' => env('REDEAM_API_HOST', 'booking.redeam.io'),
        'version' => env('REDEAM_API_VERSION', 'v1.2'),
        'api_key' => env('REDEAM_DISNEY_API_KEY'),
        'api_secret' => env('REDEAM_DISNEY_API_SECRET'),
    ],
    'united_parks' => [
        'supplier_id' => null,
        'host' => env('REDEAM_API_HOST', 'booking.redeam.io'),
        'version' => env('REDEAM_API_VERSION', 'v1.2'),
        'api_key' => env('REDEAM_UNITED_PARKS_API_KEY'),
        'api_secret' => env('REDEAM_UNITED_PARKS_API_SECRET'),
    ],
];
```

**App-Level Config (`config/walt_disney.php`):**
- Commission percentages by ticket type and duration
- Special events configuration
- Theme park access types
- Florida resident discount percentages

#### API Methods

**Product Management:**
- `getAllProducts()` - Fetch all products for a supplier
- `getProduct($product_id)` - Get specific product details
- `getProductRates($product_id)` - Get available rates for a product
- `getProductRate($product_id, $rate_id)` - Get specific rate details

**Availability:**
- `checkAvailability($product_id, $at, $qty)` - Check single date availability
- `checkAvailabilities($product_id, $start, $end)` - Check date range availability
- `getProductPricingSchedule($product_id, $start_date, $end_date)` - Get pricing schedule

**Booking:**
- `createNewHold($data)` - Create reservation hold
- `getHold($hold_id)` - Retrieve hold details
- `deleteHold($hold_id)` - Release hold
- `createNewBooking($data)` - Create confirmed booking
- `getBooking($booking_id)` - Retrieve booking details
- `deleteBooking($booking_id)` - Cancel booking

**Business Logic:**
- `getOptionCode($days, $name)` - Parse ticket option codes from ticket names
- `getCommissionPercentage($days, $optionCode)` - Calculate commission rates
- `getParkAvailability($start_date, $end_date)` - Disney-specific park availability

#### Dependencies
- `guzzlehttp/guzzle: ^7.5`
- `spatie/laravel-package-tools: ^1.15`
- `illuminate/contracts: ^10.9`

---

### 2. Laravel SmartOrder Package

#### Purpose
Provides integration with Universal's SmartOrder API for ticket bookings.

#### Key Components

**Service Classes:**
- `SmartOrderClient` - Main client for SmartOrder operations
- `SmartOrderApiClient` - Low-level HTTP client with OAuth token management

#### Configuration

**Package Config (`packages/laravel-smartorder/config/smartorder.php`):**
```php
return [
    'customer_id' => 134853,
    'approved_suffix' => '-2KNOW',
    'client_username' => env('SMARTORDER_CLIENT_USERNAME'),
    'client_secret' => env('SMARTORDER_CLIENT_SECRET'),
    'host' => env('SMARTORDER_API_HOST', 'QACorpAPI.ucdp.net'),
];
```

#### API Methods

**Product Catalog:**
- `getAllProducts($parameters)` - Get product catalog
- `getAvailableMonths()` - Get next 12 months for booking

**Events:**
- `findEvents($parameters)` - Search for available events

**Orders:**
- `placeOrder($parameters)` - Create new order
- `getExistingOrder($parameters)` - Retrieve order details
- `canCancelOrder($parameters)` - Check if order can be cancelled
- `cancelOrder($parameters)` - Cancel an order

#### Authentication
- OAuth 2.0 client credentials flow
- Automatic token refresh
- Token storage in `UniversalSmartOrderAuthToken` model

#### Dependencies
- `guzzlehttp/guzzle: ^7.5`
- `spatie/laravel-package-tools: ^1.15`
- `illuminate/contracts: ^10.9`

---

## Integration Patterns

### How Packages Are Used in the Application

#### 1. Service Layer Pattern

Both packages are wrapped in application-level service classes:

**Redeam Services:**
- `App\Services\RedeamServiceForDisneyWorld`
- `App\Services\RedeamServiceForUnitedParks`

**SmartOrder Service:**
- `App\Services\SmartOrderService`

#### 2. Dependency Injection

Services are injected via constructor:

```php
// RedeamServiceForDisneyWorld.php
public function __construct()
{
    $this->client = app(LaravelRedeamForWaltDisney::class);
}

// SmartOrderService.php
public function __construct()
{
    $this->client = app(SmartOrderClient::class);
}
```

#### 3. Controller Usage

Controllers use the service layer:

```php
// CheckoutController.php
use App\Services\RedeamServiceForDisneyWorld;
use App\Services\SmartOrderService;

// Acquire holds, create bookings, etc.
```

#### 4. Job Integration

Background jobs use the services for:
- Syncing product data
- Updating pricing schedules
- Processing bookings

**Example Jobs:**
- `App\Jobs\Redeam\WaltDisney\SyncProductRatesJob`
- `App\Jobs\Redeam\UnitedParks\LoopSyncProductScheduleJob`

---

## Identified Issues

### Critical Issues

#### 1. **Breaking Package Isolation** ⚠️
**Location:** `SmartOrderApiClient.php:5`

```php
use App\Models\UniversalSmartOrderAuthToken;
```

**Problem:** The package directly depends on an application model, breaking encapsulation and making it non-reusable.

**Impact:**
- Cannot be used outside this specific Laravel application
- Tight coupling prevents package reuse
- Violates separation of concerns

---

#### 2. **Code Duplication** 🔄
**Locations:**
- `RedeamApiClientForDisney.php`
- `RedeamApiClientForUnitedParks.php`

**Problem:** Both API clients contain nearly identical code (90%+ similarity).

**Duplicated Code:**
```php
// Both classes have identical methods:
public function get(string $uri, array $payload = []): array
public function post(string $uri, array $payload = []): array
public function delete(string $uri, array $payload = [])
public function put(string $uri)
public function getHeaders(array $headers = []): array
public function getUrl(string $uri): string
public function getDomain(): string
public function getHost(): string
public function getVersion(): string
```

**Impact:**
- Maintenance burden (changes must be made twice)
- Risk of inconsistencies
- Violates DRY principle

---

#### 3. **Business Logic in Package** 🏢
**Location:** `LaravelRedeamForWaltDisney.php:329-458`, `LaravelRedeamForUnitedParks.php:328-384`

**Problem:** Business-specific logic embedded in package:

```php
public function getOptionCode(int $days, ?string $name = null): ?string
{
    // Complex string parsing for Disney ticket types
    // ~130 lines of business logic
}

public function getCommissionPercentage(int $days, ?string $optionCode = null): float
{
    // Commission calculation logic specific to this business
}
```

**Impact:**
- Package is not generic/reusable
- Business rules embedded in infrastructure code
- Makes open-sourcing difficult

---

### Moderate Issues

#### 4. **Inconsistent Configuration Management** ⚙️

**Problem:** Configuration split between package and application:
- `packages/laravel-redeam/config/redeam.php` - API credentials
- `config/walt_disney.php` - Business logic configuration
- `packages/laravel-redeam/config/walt_disney.php` - Duplicate?

**Impact:**
- Confusing configuration structure
- Unclear separation of concerns
- Difficult to manage

---

#### 5. **Missing Interfaces** 📋

**Problem:** No interfaces defined for core functionality:
- No `BookingClientInterface`
- No `ApiClientInterface`
- No contracts for Result classes

**Impact:**
- Difficult to mock for testing
- Cannot easily swap implementations
- Poor extensibility

---

#### 6. **Incomplete Package Configuration** 📝
**Location:** `packages/laravel-smartorder/config/smartorder.php`

**Problem:** SmartOrder config contains hardcoded values:

```php
return [
    'customer_id' => 134853,  // Hardcoded
    'approved_suffix' => '-2KNOW',  // Hardcoded
];
```

**Impact:**
- Not configurable for different environments/users
- Cannot be open-sourced as-is
- Violates configuration best practices

---

#### 7. **No Abstract Base Classes** 🏗️

**Problem:** Similar functionality not abstracted:
- `LaravelRedeamForWaltDisney` and `LaravelRedeamForUnitedParks` have identical methods
- No shared base class for common operations

**Impact:**
- Code duplication
- Inconsistent behavior risk
- Harder to maintain

---

### Minor Issues

#### 8. **Inconsistent Error Handling** ❌

**Problem:** API error responses handled differently:
- Sometimes returns error arrays
- Sometimes throws exceptions
- No standardized error format

**Impact:**
- Unpredictable error handling
- More complex client code
- Harder to debug

---

#### 9. **Timeout Hardcoded** ⏱️
**Location:** All API client methods

```php
Http::timeout('600')  // 10 minutes hardcoded
```

**Problem:** Timeout not configurable.

**Impact:**
- Cannot adjust for different environments
- All requests have same timeout

---

#### 10. **Missing Documentation** 📚

**Problem:**
- README files are template boilerplate
- No usage examples
- No API documentation
- Minimal code comments

**Impact:**
- Steep learning curve
- Difficult for new developers
- Not open-source ready

---

## Improvement Recommendations

### Phase 1: Refactoring for Reusability

#### 1.1 Create Base API Client

**Create:** `packages/laravel-redeam/src/ApiClient/BaseRedeamApiClient.php`

```php
<?php

namespace CodeCreatives\LaravelRedeam\ApiClient;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

abstract class BaseRedeamApiClient
{
    protected array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    abstract protected function getConfigKey(): string;

    public function get(string $uri, array $payload = []): array
    {
        return $this->buildRequest()
            ->get($this->getUrl($uri), $payload)
            ->json();
    }

    public function post(string $uri, array $payload = []): array
    {
        return $this->buildRequest()
            ->asJson()
            ->post($this->getUrl($uri), $payload)
            ->json();
    }

    public function delete(string $uri, array $payload = []): array
    {
        return $this->buildRequest()
            ->delete($this->getUrl($uri), $payload)
            ->json();
    }

    public function put(string $uri): mixed
    {
        return $this->buildRequest()
            ->send('PUT', $this->getUrl($uri));
    }

    protected function buildRequest(): PendingRequest
    {
        return Http::asForm()
            ->timeout($this->getTimeout())
            ->withHeaders($this->getHeaders());
    }

    protected function getHeaders(array $headers = []): array
    {
        $configKey = $this->getConfigKey();

        return array_merge([
            'X-API-Key' => config("redeam.{$configKey}.api_key"),
            'X-API-Secret' => config("redeam.{$configKey}.api_secret"),
        ], $headers);
    }

    protected function getUrl(string $uri): string
    {
        return "{$this->getDomain()}/{$this->getVersion()}/$uri";
    }

    protected function getDomain(): string
    {
        return "https://{$this->getHost()}";
    }

    protected function getHost(): string
    {
        $configKey = $this->getConfigKey();
        return config("redeam.{$configKey}.host");
    }

    protected function getVersion(): string
    {
        $configKey = $this->getConfigKey();
        return config("redeam.{$configKey}.version");
    }

    protected function getTimeout(): int
    {
        $configKey = $this->getConfigKey();
        return config("redeam.{$configKey}.timeout", 600);
    }
}
```

**Then update specific clients:**

```php
<?php

namespace CodeCreatives\LaravelRedeam\ApiClient;

class RedeamApiClientForDisney extends BaseRedeamApiClient
{
    protected function getConfigKey(): string
    {
        return 'disney';
    }
}

class RedeamApiClientForUnitedParks extends BaseRedeamApiClient
{
    protected function getConfigKey(): string
    {
        return 'united_parks';
    }
}
```

**Benefits:**
- Eliminates ~90% code duplication
- Single source of truth for HTTP logic
- Easier to maintain and test
- Consistent behavior across clients

---

#### 1.2 Extract Business Logic from Package

**Move business logic to application layer:**

**Create:** `app/Services/Disney/TicketOptionCodeResolver.php`

```php
<?php

namespace App\Services\Disney;

use Illuminate\Support\Str;

class TicketOptionCodeResolver
{
    public function resolve(int $days, ?string $name = null): ?string
    {
        // Move getOptionCode() logic here
    }
}
```

**Create:** `app/Services\Disney\CommissionCalculator.php`

```php
<?php

namespace App\Services\Disney;

class CommissionCalculator
{
    public function calculate(int $days, ?string $optionCode = null): float
    {
        // Move getCommissionPercentage() logic here
    }
}
```

**Update package class:**

```php
// Remove getOptionCode() and getCommissionPercentage() methods
// Keep only API interaction methods
```

**Benefits:**
- Package becomes generic and reusable
- Business logic stays in application
- Clear separation of concerns
- Package can be open-sourced

---

#### 1.3 Fix SmartOrder Token Management

**Problem:** Direct dependency on `App\Models\UniversalSmartOrderAuthToken`

**Solution: Create a Token Repository Interface**

**Create:** `packages/laravel-smartorder/src/Contracts/TokenRepositoryInterface.php`

```php
<?php

namespace CodeCreatives\LaravelSmartOrder\Contracts;

interface TokenRepositoryInterface
{
    public function getValidToken(): ?string;

    public function storeToken(string $token, int $expiresIn): void;
}
```

**Create:** `packages/laravel-smartorder/src/TokenRepository/CacheTokenRepository.php`

```php
<?php

namespace CodeCreatives\LaravelSmartOrder\TokenRepository;

use CodeCreatives\LaravelSmartOrder\Contracts\TokenRepositoryInterface;
use Illuminate\Support\Facades\Cache;

class CacheTokenRepository implements TokenRepositoryInterface
{
    public function getValidToken(): ?string
    {
        return Cache::get('smartorder_token');
    }

    public function storeToken(string $token, int $expiresIn): void
    {
        Cache::put('smartorder_token', $token, $expiresIn);
    }
}
```

**Create:** `app/Repositories/SmartOrderTokenRepository.php` (in main app)

```php
<?php

namespace App\Repositories;

use App\Models\UniversalSmartOrderAuthToken;
use CodeCreatives\LaravelSmartOrder\Contracts\TokenRepositoryInterface;

class SmartOrderTokenRepository implements TokenRepositoryInterface
{
    public function getValidToken(): ?string
    {
        $token = UniversalSmartOrderAuthToken::query()
            ->latest()
            ->first();

        return ($token && $token->valid()) ? $token->token : null;
    }

    public function storeToken(string $token, int $expiresIn): void
    {
        UniversalSmartOrderAuthToken::create([
            'token' => $token,
            'expires_at' => now()->addSeconds($expiresIn),
        ]);
    }
}
```

**Update:** `SmartOrderApiClient.php`

```php
<?php

namespace CodeCreatives\LaravelSmartOrder;

use CodeCreatives\LaravelSmartOrder\Contracts\TokenRepositoryInterface;
use Illuminate\Support\Facades\Http;

class SmartOrderApiClient
{
    protected TokenRepositoryInterface $tokenRepository;

    public function __construct(TokenRepositoryInterface $tokenRepository)
    {
        $this->tokenRepository = $tokenRepository;
    }

    private function getToken(): string
    {
        $token = $this->tokenRepository->getValidToken();

        if ($token) {
            return $token;
        }

        return $this->refreshToken();
    }

    private function refreshToken(): string
    {
        $response = Http::asForm()
            ->timeout(config('smartorder.timeout', 600))
            ->post($this->getUrl('connect/token'), [
                'grant_type' => 'client_credentials',
                'client_id' => config('smartorder.client_username'),
                'client_secret' => config('smartorder.client_secret'),
                'scope' => 'SmartOrder',
            ]);

        $token = $response->json('access_token');
        $expiresIn = $response->json('expires_in');

        $this->tokenRepository->storeToken($token, $expiresIn);

        return $token;
    }
}
```

**Register in Service Provider:**

```php
// app/Providers/AppServiceProvider.php
use CodeCreatives\LaravelSmartOrder\Contracts\TokenRepositoryInterface;
use App\Repositories\SmartOrderTokenRepository;

public function register()
{
    $this->app->bind(
        TokenRepositoryInterface::class,
        SmartOrderTokenRepository::class
    );
}
```

**Benefits:**
- Package no longer depends on application models
- Fully decoupled and reusable
- Can use any storage mechanism
- Easy to test with mock repository

---

#### 1.4 Standardize Configuration

**Update:** `packages/laravel-redeam/config/redeam.php`

```php
<?php

return [
    'default_provider' => env('REDEAM_DEFAULT_PROVIDER', 'disney'),

    'timeout' => env('REDEAM_TIMEOUT', 600),

    'providers' => [
        'disney' => [
            'supplier_id' => env('REDEAM_DISNEY_SUPPLIER_ID'),
            'host' => env('REDEAM_API_HOST', 'booking.redeam.io'),
            'version' => env('REDEAM_API_VERSION', 'v1.2'),
            'api_key' => env('REDEAM_DISNEY_API_KEY'),
            'api_secret' => env('REDEAM_DISNEY_API_SECRET'),
        ],

        'united_parks' => [
            'supplier_id' => env('REDEAM_UNITED_PARKS_SUPPLIER_ID'),
            'host' => env('REDEAM_API_HOST', 'booking.redeam.io'),
            'version' => env('REDEAM_API_VERSION', 'v1.2'),
            'api_key' => env('REDEAM_UNITED_PARKS_API_KEY'),
            'api_secret' => env('REDEAM_UNITED_PARKS_API_SECRET'),
        ],
    ],
];
```

**Update:** `packages/laravel-smartorder/config/smartorder.php`

```php
<?php

return [
    'customer_id' => env('SMARTORDER_CUSTOMER_ID'),
    'approved_suffix' => env('SMARTORDER_APPROVED_SUFFIX', ''),
    'client_username' => env('SMARTORDER_CLIENT_USERNAME'),
    'client_secret' => env('SMARTORDER_CLIENT_SECRET'),
    'host' => env('SMARTORDER_API_HOST', 'QACorpAPI.ucdp.net'),
    'timeout' => env('SMARTORDER_TIMEOUT', 600),
];
```

**Benefits:**
- All sensitive data moved to environment variables
- Configurable timeouts
- Cleaner configuration structure
- Production-ready

---

### Phase 2: Adding Interfaces and Contracts

#### 2.1 Create Core Interfaces

**Create:** `packages/laravel-redeam/src/Contracts/BookingProviderInterface.php`

```php
<?php

namespace CodeCreatives\LaravelRedeam\Contracts;

use Carbon\Carbon;

interface BookingProviderInterface
{
    public function getAllProducts(array $parameters = []): array;

    public function getProduct(string $product_id, array $parameters = []);

    public function checkAvailability(
        string $product_id,
        Carbon|string $at,
        int $qty,
        array $parameters = []
    ): array;

    public function createNewHold(array $data): array;

    public function getHold(string $hold_id): array;

    public function deleteHold(string $hold_id): array;

    public function createNewBooking(array $data): array;

    public function getBooking(string $booking_id): array;

    public function deleteBooking(string $booking_id);
}
```

**Implement in existing classes:**

```php
class LaravelRedeamForWaltDisney implements BookingProviderInterface
{
    // Implementation
}

class LaravelRedeamForUnitedParks implements BookingProviderInterface
{
    // Implementation
}
```

**Benefits:**
- Enforces consistent API
- Enables dependency injection
- Improves testability
- Documents expected behavior

---

#### 2.2 Add Result Interfaces

**Create:** `packages/laravel-redeam/src/Contracts/ResultInterface.php`

```php
<?php

namespace CodeCreatives\LaravelRedeam\Contracts;

interface ResultInterface
{
    public function getData(): array;

    public function get(string $key, $default = null);

    public function has(string $key): bool;

    public function toArray(): array;
}
```

---

### Phase 3: Standardization & Best Practices

#### 3.1 Add Comprehensive Error Handling

**Create:** `packages/laravel-redeam/src/Exceptions/RedeamException.php`

```php
<?php

namespace CodeCreatives\LaravelRedeam\Exceptions;

use Exception;

class RedeamException extends Exception
{
    protected array $context;

    public function __construct(
        string $message,
        int $code = 0,
        array $context = [],
        ?Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->context = $context;
    }

    public function getContext(): array
    {
        return $this->context;
    }
}
```

**Create specific exceptions:**

```php
class RedeamApiException extends RedeamException {}
class RedeamAuthenticationException extends RedeamException {}
class RedeamValidationException extends RedeamException {}
class RedeamBookingException extends RedeamException {}
```

**Update API clients to throw exceptions:**

```php
public function get(string $uri, array $payload = []): array
{
    $response = $this->buildRequest()
        ->get($this->getUrl($uri), $payload);

    if ($response->failed()) {
        throw new RedeamApiException(
            'API request failed',
            $response->status(),
            [
                'uri' => $uri,
                'payload' => $payload,
                'response' => $response->json(),
            ]
        );
    }

    return $response->json();
}
```

---

#### 3.2 Add Logging

**Create:** `packages/laravel-redeam/src/Traits/LogsApiRequests.php`

```php
<?php

namespace CodeCreatives\LaravelRedeam\Traits;

use Illuminate\Support\Facades\Log;

trait LogsApiRequests
{
    protected function logRequest(string $method, string $uri, array $payload = []): void
    {
        if (config('redeam.logging.enabled', false)) {
            Log::channel(config('redeam.logging.channel', 'stack'))
                ->info("Redeam API Request: {$method} {$uri}", [
                    'payload' => $payload,
                ]);
        }
    }

    protected function logResponse(string $uri, array $response): void
    {
        if (config('redeam.logging.enabled', false)) {
            Log::channel(config('redeam.logging.channel', 'stack'))
                ->info("Redeam API Response: {$uri}", [
                    'response' => $response,
                ]);
        }
    }
}
```

---

#### 3.3 Add Testing Support

**Create:** `packages/laravel-redeam/src/Testing/RedeamFake.php`

```php
<?php

namespace CodeCreatives\LaravelRedeam\Testing;

use CodeCreatives\LaravelRedeam\Contracts\BookingProviderInterface;

class RedeamFake implements BookingProviderInterface
{
    protected array $products = [];
    protected array $holds = [];
    protected array $bookings = [];

    public function fakeProducts(array $products): self
    {
        $this->products = $products;
        return $this;
    }

    public function getAllProducts(array $parameters = []): array
    {
        return $this->products;
    }

    // Implement other methods with fake responses
}
```

**Usage in tests:**

```php
use CodeCreatives\LaravelRedeam\Testing\RedeamFake;

$fake = new RedeamFake();
$fake->fakeProducts([/* test data */]);

$this->app->instance(BookingProviderInterface::class, $fake);

// Test your code
```

---

#### 3.4 Add Comprehensive Documentation

**Update:** `packages/laravel-redeam/README.md`

```markdown
# Laravel Redeam

A Laravel package for integrating with the Redeam Booking API.

## Installation

```bash
composer require code-creatives/laravel-redeam
```

## Configuration

Publish the config file:

```bash
php artisan vendor:publish --tag=redeam-config
```

Add to your `.env`:

```env
REDEAM_DISNEY_SUPPLIER_ID=your-supplier-id
REDEAM_DISNEY_API_KEY=your-api-key
REDEAM_DISNEY_API_SECRET=your-api-secret
```

## Usage

### Basic Usage

```php
use CodeCreatives\LaravelRedeam\LaravelRedeamForWaltDisney;

$redeam = app(LaravelRedeamForWaltDisney::class);

// Get all products
$products = $redeam->getAllProducts();

// Check availability
$availability = $redeam->checkAvailability(
    'product-id',
    '2024-12-01',
    2
);

// Create a hold
$hold = $redeam->createNewHold([
    'hold' => [
        'items' => [/* items */]
    ]
]);

// Create a booking
$booking = $redeam->createNewBooking([
    'booking' => [/* booking data */]
]);
```

### Advanced Usage

[More examples...]

## Testing

```bash
composer test
```

## License

MIT
```

---

## Migration Strategy

### Step-by-Step Migration Plan

#### Stage 1: Refactor Without Breaking (Week 1-2)

**Goals:**
- Eliminate code duplication
- Fix critical issues
- Maintain backward compatibility

**Tasks:**
1. ✅ Create `BaseRedeamApiClient`
2. ✅ Update Disney and United Parks clients to extend base
3. ✅ Create `TokenRepositoryInterface` for SmartOrder
4. ✅ Add cache-based implementation
5. ✅ Update configuration files
6. ✅ Run existing tests to ensure no breakage

**Verification:**
```bash
php artisan test
```

---

#### Stage 2: Extract Business Logic (Week 2-3)

**Goals:**
- Separate business logic from infrastructure
- Make packages reusable

**Tasks:**
1. ✅ Create `TicketOptionCodeResolver` in app layer
2. ✅ Create `CommissionCalculator` in app layer
3. ✅ Remove business logic from package classes
4. ✅ Update service classes to use new resolvers
5. ✅ Update tests

**Files to Update:**
- `app/Services/RedeamServiceForDisneyWorld.php`
- `app/Services/RedeamServiceForUnitedParks.php`
- `packages/laravel-redeam/src/LaravelRedeamForWaltDisney.php`
- `packages/laravel-redeam/src/LaravelRedeamForUnitedParks.php`

---

#### Stage 3: Add Interfaces & Contracts (Week 3-4)

**Goals:**
- Define clear contracts
- Improve testability
- Enable dependency injection

**Tasks:**
1. ✅ Create `BookingProviderInterface`
2. ✅ Create `ResultInterface`
3. ✅ Implement interfaces in existing classes
4. ✅ Update service layer to depend on interfaces
5. ✅ Create fake implementations for testing

---

#### Stage 4: Enhance Error Handling & Logging (Week 4-5)

**Goals:**
- Standardize error handling
- Add comprehensive logging
- Improve debugging

**Tasks:**
1. ✅ Create exception hierarchy
2. ✅ Update API clients to throw exceptions
3. ✅ Add logging trait
4. ✅ Configure logging channels
5. ✅ Update error handling in service layer

---

#### Stage 5: Documentation & Testing (Week 5-6)

**Goals:**
- Complete documentation
- Achieve high test coverage
- Prepare for open-source release

**Tasks:**
1. ✅ Write comprehensive README files
2. ✅ Add inline documentation
3. ✅ Create usage examples
4. ✅ Write integration tests
5. ✅ Write unit tests for all components
6. ✅ Set up CI/CD pipeline

---

#### Stage 6: Extract to Separate Repositories (Week 6-7)

**Goals:**
- Create standalone repositories
- Publish to Packagist
- Set up versioning

**Tasks:**
1. ✅ Create GitHub repositories
   - `code-creatives/laravel-redeam`
   - `code-creatives/laravel-smartorder`
2. ✅ Copy package code to new repos
3. ✅ Set up GitHub Actions
4. ✅ Publish to Packagist
5. ✅ Update main app to use Packagist versions
6. ✅ Remove local packages directory

**Update composer.json:**

```json
{
    "require": {
        "code-creatives/laravel-redeam": "^1.0",
        "code-creatives/laravel-smartorder": "^1.0"
    }
}
```

**Remove:**

```json
"repositories": [
    // Remove path repositories
]
```

---

## Best Practices for Open Source

### 1. Repository Structure

```
laravel-redeam/
├── .github/
│   └── workflows/
│       ├── tests.yml
│       └── code-style.yml
├── src/
├── tests/
├── config/
├── docs/
├── CHANGELOG.md
├── CONTRIBUTING.md
├── LICENSE.md
├── README.md
└── composer.json
```

### 2. Semantic Versioning

Follow [SemVer](https://semver.org/):
- **MAJOR**: Breaking changes (2.0.0)
- **MINOR**: New features, backward compatible (1.1.0)
- **PATCH**: Bug fixes (1.0.1)

### 3. Changelog Management

Use [Keep a Changelog](https://keepachangelog.com/) format:

```markdown
# Changelog

## [Unreleased]

### Added
- New feature X

### Changed
- Updated Y

### Fixed
- Bug Z

## [1.0.0] - 2024-01-15

### Added
- Initial release
```

### 4. Contribution Guidelines

Create `CONTRIBUTING.md`:

```markdown
# Contributing

## Development Setup

1. Clone the repository
2. Run `composer install`
3. Copy `.env.example` to `.env` and configure
4. Run tests: `composer test`

## Code Style

We use Laravel Pint:

```bash
composer format
```

## Pull Requests

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests
5. Ensure tests pass
6. Submit PR
```

### 5. GitHub Actions Workflow

**Create:** `.github/workflows/tests.yml`

```yaml
name: Tests

on:
  push:
    branches: [main, develop]
  pull_request:
    branches: [main, develop]

jobs:
  test:
    runs-on: ubuntu-latest

    strategy:
      matrix:
        php: [8.1, 8.2, 8.3]
        laravel: [10.x, 11.x]

    steps:
      - uses: actions/checkout@v3

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: ${{ matrix.php }}
          coverage: xdebug

      - name: Install dependencies
        run: composer install --prefer-dist --no-progress

      - name: Run tests
        run: composer test

      - name: Upload coverage
        uses: codecov/codecov-action@v3
```

### 6. Security Policy

**Create:** `SECURITY.md`

```markdown
# Security Policy

## Supported Versions

| Version | Supported          |
| ------- | ------------------ |
| 1.x     | :white_check_mark: |
| < 1.0   | :x:                |

## Reporting a Vulnerability

Please report security vulnerabilities to security@code-creatives.com
```

---

## Post-Migration Checklist

### Package Quality Checklist

- [ ] No hardcoded credentials or sensitive data
- [ ] All configuration via environment variables
- [ ] Comprehensive README with examples
- [ ] Full test coverage (>80%)
- [ ] PSR-12 code style compliance
- [ ] No application-specific dependencies
- [ ] Clear interfaces and contracts
- [ ] Proper error handling
- [ ] Logging support
- [ ] GitHub Actions CI/CD setup
- [ ] CHANGELOG.md maintained
- [ ] LICENSE file (MIT)
- [ ] CONTRIBUTING.md guide
- [ ] Security policy
- [ ] Version tagging

### Integration Checklist

- [ ] Main app updated to use package interfaces
- [ ] Service layer properly injecting dependencies
- [ ] Configuration migrated to .env
- [ ] Tests updated and passing
- [ ] Documentation updated
- [ ] Team trained on new structure

---

## Estimated Timeline

| Phase | Duration | Description |
|-------|----------|-------------|
| Phase 1 | 1-2 weeks | Refactor base classes, fix duplication |
| Phase 2 | 1 week | Extract business logic |
| Phase 3 | 1 week | Add interfaces and contracts |
| Phase 4 | 1 week | Error handling and logging |
| Phase 5 | 1-2 weeks | Documentation and testing |
| Phase 6 | 1 week | Extract to repositories |
| **Total** | **6-7 weeks** | Complete migration |

---

## Benefits Summary

### Technical Benefits

1. **Maintainability**
   - Reduced code duplication (90%+ reduction)
   - Clear separation of concerns
   - Easier to debug and test

2. **Reusability**
   - Packages can be used in other projects
   - No application-specific dependencies
   - Clean interfaces

3. **Testability**
   - Interface-based dependency injection
   - Fake implementations for testing
   - Higher test coverage

4. **Scalability**
   - Easy to add new providers
   - Extensible architecture
   - Plugin-based system

### Business Benefits

1. **Open Source Credibility**
   - Professional package structure
   - Community contributions
   - Portfolio enhancement

2. **Developer Experience**
   - Clear documentation
   - Easy to onboard new developers
   - Consistent patterns

3. **Code Quality**
   - CI/CD automation
   - Code style enforcement
   - Security best practices

---

## Support & Resources

### Documentation

- [Spatie Package Tools](https://github.com/spatie/laravel-package-tools)
- [Laravel Package Development](https://laravel.com/docs/10.x/packages)
- [PHP Package Development](https://phptherightway.com/)

### Community

- Laravel News
- Laracasts
- Laravel.io

---

## Conclusion

This migration will transform two internal packages into professional, reusable, open-source libraries. The phased approach ensures minimal disruption while steadily improving code quality, maintainability, and reusability.

The key improvements include:
- ✅ Eliminating code duplication
- ✅ Fixing package isolation issues
- ✅ Extracting business logic
- ✅ Adding comprehensive interfaces
- ✅ Standardizing configuration
- ✅ Improving error handling
- ✅ Adding extensive documentation

Following this guide will result in high-quality, community-ready packages that can be shared publicly and reused across multiple projects.

---

**Document Version:** 1.0
**Last Updated:** 2024-01-15
**Author:** Code Creatives Team
**Status:** Ready for Implementation
