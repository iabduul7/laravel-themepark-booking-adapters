# Existing Package Architecture & Features

This document provides a comprehensive overview of the existing booking packages in the `knowbeforeugo-backend/packages` directory and how they're being transformed into the unified `laravel-themepark-booking-adapters` package.

## Current Package Structure

### 1. Laravel Redeam Package (`packages/laravel-redeam/`)

**Purpose**: Integration with Redeam API for Disney World and United Parks booking services.

**Key Features**:
- Dual park support (Disney World & United Parks)
- Product catalog management
- Availability checking and booking holds
- Booking creation and confirmation
- Voucher generation with PDF support
- Rate and price schedule management

**Core Classes**:
- `LaravelRedeamForWaltDisney` - Disney-specific implementation
- `LaravelRedeamForUnitedParks` - United Parks implementation  
- `RedeamApiClientForDisney` / `RedeamApiClientForUnitedParks` - HTTP clients
- `RedeamClientForDisney` / `RedeamClientForUnitedParks` - Business logic wrappers

**Data Models**:
- `Product` - Product information and metadata
- `Rate` - Pricing rate information
- `Supplier` - Park/supplier details
- `RatePriceSchedule` - Price scheduling data
- `PriceSchedule` - Pricing structure

**API Capabilities**:
```php
// Product Management
$redeam->getAllSuppliers()
$redeam->getAllProducts($supplierId)
$redeam->getProduct($productId)
$redeam->getProductRates($productId)

// Availability & Booking
$redeam->checkAvailabilities($productId, $startDate, $endDate)
$redeam->checkAvailability($productId, $date, $quantity)
$redeam->createNewHold($holdData)
$redeam->createNewBooking($bookingData)
$redeam->getBooking($bookingId)
$redeam->deleteBooking($bookingId)
```

### 2. Laravel SmartOrder Package (`packages/laravel-smartorder/`)

**Purpose**: Integration with SmartOrder API for Universal Studios and other attraction bookings.

**Key Features**:
- Universal Studios event booking
- Calendar-based product retrieval
- Event capacity management
- Guest information handling
- PDF voucher generation

**Core Classes**:
- `LaravelSmartOrder` - Main service class
- `SmartOrderApiClient` - HTTP client wrapper
- `SmartOrderClient` - Business logic implementation

**API Capabilities**:
```php
// Product Management
$smartOrder->getAllProducts()
$smartOrder->getAllCalendarProducts()
$smartOrder->getAllCalendarProductsWithPrices($code)

// Event Management
$smartOrder->findEvents($parameters)
$smartOrder->getProductAvailability($plu, $date)

// Booking Operations
$smartOrder->createBooking($bookingData)
$smartOrder->getBookingDetails($bookingId)
```

## Integration Points with Main Application

### Service Layer Integration

Both packages are integrated through dedicated service classes in the main application:

**RedeamServiceForDisneyWorld** (`app/Services/RedeamServiceForDisneyWorld.php`):
- Manages Disney-specific booking workflows
- Handles hold creation, booking confirmation
- Generates Disney-branded vouchers
- Integrates with Order management system

**RedeamServiceForUnitedParks** (`app/Services/RedeamServiceForUnitedParks.php`):
- Similar to Disney service but for United Parks
- Handles guest information requirements
- Different voucher generation logic

**SmartOrderService** (`app/Services/SmartOrderService.php`):
- Universal Studios booking management
- Event capacity validation
- Custom booking workflows for special events

### Database Integration

The services integrate with existing database models:
- `Order` - Main order entity
- `OrderTicketGuest` - Guest information for tickets
- `Customer` - Customer details
- Domain-specific detail models for each provider

### Job Processing

Background job processing for:
- Product synchronization
- Booking confirmations
- Voucher generation
- Data validation and cleanup

## Configuration Management

### Environment Variables

Current configuration uses provider-specific environment variables:
```env
# Redeam Configuration
REDEAM_DISNEY_API_KEY=
REDEAM_DISNEY_SUPPLIER_ID=
REDEAM_UNITED_PARKS_API_KEY=
REDEAM_ENVIRONMENT=sandbox

# SmartOrder Configuration
SMARTORDER_API_KEY=
SMARTORDER_API_SECRET=
SMARTORDER_BASE_URL=
```

### Config Files

Provider-specific configuration files:
- `config/redeam.php` - Redeam API settings
- `config/smartorder.php` - SmartOrder API settings

## Transformation to Unified Package

### Benefits of Unified Approach

1. **Consistent Interface**: Single interface for all booking providers
2. **Reduced Duplication**: Shared error handling, logging, and validation
3. **Better Testing**: Unified testing framework and mocking strategies  
4. **Easier Maintenance**: Single codebase for booking operations
5. **Enhanced Features**: Circuit breakers, rate limiting, comprehensive monitoring

### Migration Strategy

The new unified package provides:

1. **Adapter Pattern**: Each provider becomes an adapter implementing `BookingAdapterInterface`
2. **Standardized Data Models**: Common data structures for products, bookings, vouchers
3. **Centralized Configuration**: Single config file with provider-specific sections
4. **Enhanced Error Handling**: Custom exceptions with proper error categorization
5. **Improved Observability**: Comprehensive logging and metrics collection

### Backward Compatibility

The new package maintains compatibility by:
- Preserving existing API methods through adapter implementations
- Supporting existing configuration patterns
- Maintaining voucher generation capabilities
- Ensuring seamless integration with existing Order workflows

## Implementation Status

### Completed Features ✅

- Core package structure with Spatie tools
- Data transfer objects for all entities
- Base adapter interface and implementation
- Redeam adapter with Disney/United Parks support
- Configuration management system
- Custom exception hierarchy
- Artisan command for product synchronization
- Service provider with proper dependency injection

### In Progress 🚧

- SmartOrder adapter implementation
- Voucher generation service
- Enhanced error handling with circuit breakers
- Comprehensive test suite
- Documentation and usage examples

### Planned Enhancements 📋

- Rate limiting and API throttling
- Metrics collection and monitoring
- Queue-based background processing
- Webhook support for real-time updates
- Multi-tenant configuration support
- Advanced caching strategies

## Usage Examples

### Basic Booking Flow

```php
use iabduul7\ThemeParkBooking\Services\BookingManager;
use iabduul7\ThemeParkBooking\Data\BookingRequest;

$bookingManager = app(BookingManager::class);

// Check availability
$available = $bookingManager->checkAvailability(
    'redeam.disney',
    'PRODUCT_123',
    '2024-12-15',
    '10:00',
    2
);

// Create booking request
$request = new BookingRequest(
    productId: 'PRODUCT_123',
    date: Carbon::parse('2024-12-15'),
    quantity: 2,
    customerInfo: [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john@example.com'
    ],
    timeSlot: '10:00'
);

// Create booking
$response = $bookingManager->createBooking('redeam.disney', $request);

if ($response->success) {
    // Confirm with payment
    $confirmed = $bookingManager->confirmBooking(
        'redeam.disney',
        $response->reservationId,
        $paymentData
    );
    
    // Generate voucher
    $voucher = $bookingManager->generateVoucher(
        'redeam.disney',
        $confirmed->bookingId
    );
}
```

### Product Synchronization

```php
// Sync products for specific adapter
php artisan themepark:sync-products redeam.disney

// Sync all adapters
php artisan themepark:sync-products

// Force sync even if recent
php artisan themepark:sync-products --force

// Dry run to see what would be synced
php artisan themepark:sync-products --dry-run
```

This unified approach significantly improves the architecture while maintaining all existing functionality and providing a foundation for future enhancements.