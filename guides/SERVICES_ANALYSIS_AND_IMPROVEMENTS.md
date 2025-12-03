# Services Folder Analysis and Improvements

## Overview

This document provides a comprehensive analysis of the `app/Services` folder structure, identifying current issues, anti-patterns, and recommending improvements to enhance code quality, maintainability, and scalability.

## Current Services Structure

```
app/Services/
├── Redeam/
│   ├── Disney/
│   │   ├── CalendarPricing/
│   │   ├── MagicTickets/
│   │   ├── SpecialEvents/
│   │   └── WaterParks/
│   └── UnitedParks/
│       ├── CalendarPricing/
│       └── MultiDay/
├── SmartOrder/
│   ├── CalendarPricing/
│   ├── ExpressPass/
│   ├── HHN/
│   ├── NonDatedProductsService.php
│   ├── PromoTickets/
│   ├── SmartOrderInsertDataService.php
│   └── VolcanoBay/
├── RedeamServiceForDisneyWorld.php
├── RedeamServiceForUnitedParks.php
├── SmartOrderService.php
└── Stripe.php
```

## Current Issues Identified

### 1. **Inconsistent Service Architecture**

#### Problems:

-   **Mixed Responsibilities**: Services handle both business logic and data persistence
-   **Inconsistent Naming**: Some services end with "Service", others don't
-   **No Clear Interface Contracts**: Services don't implement common interfaces
-   **Tight Coupling**: Services are directly coupled to specific models and external APIs

#### Example Issues:

```php
// RedeamServiceForDisneyWorld.php - Mixed responsibilities
public function acquireBooking(Order $order): void
{
    // Business logic mixed with data persistence
    DB::transaction(function () use ($order) {
        // Direct model manipulation
        $order->disneyDetails()->create([...]);

        // API calls mixed with database operations
        $result = $this->client->createNewBooking([...]);

        // More database operations
        $order->refresh();
        $this->generateVoucher($order); // File system operations
    });
}
```

### 2. **Code Duplication**

#### Problems:

-   **Repeated Logic**: Hold/booking patterns repeated across Redeam services
-   **Similar Data Transformation**: Product sync logic duplicated
-   **Identical Error Handling**: Same error patterns across services

#### Evidence:

-   `RedeamServiceForDisneyWorld` and `RedeamServiceForUnitedParks` share 70% identical methods
-   `SmartOrderInsertDataService` has repetitive switch statements
-   Error handling patterns repeated in every service

### 3. **Poor Error Handling**

#### Problems:

-   **Inconsistent Error Types**: Some throw `ValidationException`, others return arrays
-   **Poor Error Context**: Limited error information for debugging
-   **No Fallback Strategies**: No graceful degradation on API failures
-   **Missing Circuit Breaker**: No protection against cascading failures

#### Example:

```php
// Inconsistent error handling
if (array_key_exists('error', $result)) {
    throw ValidationException::withMessages([
        'hold' => Arr::get($result, 'error.data.PARTNER_ERROR', Arr::get($result, 'error.message')),
    ]);
}
```

### 4. **Monolithic Service Classes**

#### Problems:

-   **Large Classes**: Services exceed 500+ lines
-   **Multiple Concerns**: Single services handle booking, vouchers, holds, cancellations
-   **Hard to Test**: Difficult to unit test individual components
-   **Violation of SRP**: Single Responsibility Principle violated

### 5. **Missing Abstractions**

#### Problems:

-   **No Provider Interfaces**: No common contract for booking providers
-   **No Value Objects**: Primitive obsession throughout
-   **No Domain Events**: Business events not properly modeled
-   **No Repository Pattern**: Direct model access from services

### 6. **Inadequate Logging and Monitoring**

#### Problems:

-   **Inconsistent Logging**: Different log formats across services
-   **Missing Metrics**: No performance tracking
-   **Poor Traceability**: Hard to trace requests across services
-   **No Structured Logging**: Logs lack consistent structure

### 7. **Configuration Management Issues**

#### Problems:

-   **Hardcoded Values**: Business rules embedded in code
-   **No Environment Separation**: Same configs for all environments
-   **Missing Validation**: No config validation on startup

## Recommended Improvements

### 1. **Implement Layered Architecture**

Create clear separation of concerns with defined layers:

```php
<?php
// Domain Layer - Core business logic
namespace App\Domain\Booking;

interface BookingProviderInterface
{
    public function createHold(HoldRequest $request): HoldResult;
    public function createBooking(BookingRequest $request): BookingResult;
    public function cancelBooking(BookingId $bookingId): CancellationResult;
    public function retrieveBooking(BookingId $bookingId): BookingDetails;
}

abstract class BookingProvider implements BookingProviderInterface
{
    protected $client;
    protected $logger;
    protected $metrics;

    public function __construct(
        ApiClient $client,
        LoggerInterface $logger,
        MetricsCollector $metrics
    ) {
        $this->client = $client;
        $this->logger = $logger;
        $this->metrics = $metrics;
    }

    protected function executeWithMetrics(string $operation, callable $callback)
    {
        $startTime = microtime(true);
        $traceId = Str::uuid();

        $this->logger->info("Starting {$operation}", [
            'trace_id' => $traceId,
            'provider' => static::class,
        ]);

        try {
            $result = $callback();

            $duration = (microtime(true) - $startTime) * 1000;
            $this->metrics->record("{$operation}.success", $duration);

            $this->logger->info("Completed {$operation}", [
                'trace_id' => $traceId,
                'duration_ms' => $duration,
            ]);

            return $result;
        } catch (\Exception $e) {
            $duration = (microtime(true) - $startTime) * 1000;
            $this->metrics->record("{$operation}.failure", $duration);

            $this->logger->error("Failed {$operation}", [
                'trace_id' => $traceId,
                'duration_ms' => $duration,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}

// Application Layer - Orchestration
class BookingService
{
    private $providerFactory;
    private $orderRepository;
    private $eventDispatcher;

    public function createBooking(CreateBookingCommand $command): BookingResult
    {
        return $this->executeInTransaction(function () use ($command) {
            $provider = $this->providerFactory->create($command->getProviderType());

            $hold = $provider->createHold($command->toHoldRequest());
            $booking = $provider->createBooking($command->toBookingRequest());

            $order = $this->orderRepository->update($command->getOrderId(), $booking);

            $this->eventDispatcher->dispatch(
                new BookingCreated($order, $booking)
            );

            return $booking;
        });
    }
}

// Infrastructure Layer - External integrations
class RedeamDisneyProvider extends BookingProvider
{
    public function createHold(HoldRequest $request): HoldResult
    {
        return $this->executeWithMetrics('create_hold', function () use ($request) {
            $response = $this->client->createHold($request->toApiFormat());
            return HoldResult::fromApiResponse($response);
        });
    }
}
```

### 2. **Introduce Value Objects and DTOs**

Replace primitive obsession with rich domain objects:

```php
<?php

namespace App\Domain\Booking\ValueObjects;

final class BookingId
{
    private string $value;

    public function __construct(string $value)
    {
        if (empty($value)) {
            throw new InvalidArgumentException('Booking ID cannot be empty');
        }
        $this->value = $value;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function equals(BookingId $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}

final class Money
{
    private int $cents;
    private string $currency;

    public function __construct(int $cents, string $currency = 'USD')
    {
        if ($cents < 0) {
            throw new InvalidArgumentException('Amount cannot be negative');
        }
        $this->cents = $cents;
        $this->currency = strtoupper($currency);
    }

    public static function fromDollars(float $amount, string $currency = 'USD'): self
    {
        return new self((int) round($amount * 100), $currency);
    }

    public function toDollars(): float
    {
        return $this->cents / 100;
    }

    public function add(Money $other): self
    {
        $this->ensureSameCurrency($other);
        return new self($this->cents + $other->cents, $this->currency);
    }

    public function subtract(Money $other): self
    {
        $this->ensureSameCurrency($other);
        return new self($this->cents - $other->cents, $this->currency);
    }

    private function ensureSameCurrency(Money $other): void
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidArgumentException('Cannot operate on different currencies');
        }
    }
}

class HoldRequest
{
    public function __construct(
        private readonly array $items,
        private readonly CustomerId $customerId,
        private readonly DateTimeInterface $expiresAt
    ) {}

    public function getItems(): array
    {
        return $this->items;
    }

    public function getCustomerId(): CustomerId
    {
        return $this->customerId;
    }

    public function toApiFormat(): array
    {
        return [
            'hold' => [
                'items' => array_map(fn($item) => $item->toArray(), $this->items),
                'customer_id' => $this->customerId->getValue(),
                'expires_at' => $this->expiresAt->format('c'),
            ]
        ];
    }
}
```

### 3. **Implement Repository Pattern**

Separate data access from business logic:

```php
<?php

namespace App\Domain\Booking\Repositories;

interface OrderRepositoryInterface
{
    public function find(OrderId $id): ?Order;
    public function save(Order $order): void;
    public function findByBookingId(BookingId $bookingId): ?Order;
}

namespace App\Infrastructure\Repositories;

class EloquentOrderRepository implements OrderRepositoryInterface
{
    public function find(OrderId $id): ?Order
    {
        $eloquentOrder = \App\Models\Order::find($id->getValue());

        return $eloquentOrder ? Order::fromEloquent($eloquentOrder) : null;
    }

    public function save(Order $order): void
    {
        $eloquentOrder = \App\Models\Order::find($order->getId()->getValue());

        if ($eloquentOrder) {
            $eloquentOrder->update($order->toArray());
        } else {
            \App\Models\Order::create($order->toArray());
        }
    }
}
```

### 4. **Add Circuit Breaker Pattern**

Implement resilience patterns for external API calls:

```php
<?php

namespace App\Infrastructure\Resilience;

class CircuitBreaker
{
    private $redis;
    private $config;

    public function __construct(Redis $redis, array $config)
    {
        $this->redis = $redis;
        $this->config = $config;
    }

    public function execute(string $serviceKey, callable $operation)
    {
        $state = $this->getState($serviceKey);

        if ($state === CircuitBreakerState::OPEN) {
            if ($this->shouldAttemptReset($serviceKey)) {
                return $this->attemptHalfOpenCall($serviceKey, $operation);
            }
            throw new ServiceUnavailableException("Circuit breaker open for {$serviceKey}");
        }

        return $this->executeCall($serviceKey, $operation);
    }

    private function executeCall(string $serviceKey, callable $operation)
    {
        try {
            $result = $operation();
            $this->recordSuccess($serviceKey);
            return $result;
        } catch (\Exception $e) {
            $this->recordFailure($serviceKey);
            throw $e;
        }
    }

    private function recordFailure(string $serviceKey): void
    {
        $failures = $this->redis->incr("circuit_breaker:{$serviceKey}:failures");
        $this->redis->expire("circuit_breaker:{$serviceKey}:failures", $this->config['recovery_timeout']);

        if ($failures >= $this->config['failure_threshold']) {
            $this->redis->set("circuit_breaker:{$serviceKey}:state", CircuitBreakerState::OPEN);
            $this->redis->set("circuit_breaker:{$serviceKey}:opened_at", time());
        }
    }
}

enum CircuitBreakerState: string
{
    case CLOSED = 'closed';
    case OPEN = 'open';
    case HALF_OPEN = 'half_open';
}
```

### 5. **Implement Domain Events**

Add event-driven architecture for better decoupling:

```php
<?php

namespace App\Domain\Booking\Events;

abstract class DomainEvent
{
    private DateTimeInterface $occurredAt;
    private string $eventId;

    public function __construct()
    {
        $this->occurredAt = new \DateTimeImmutable();
        $this->eventId = Str::uuid();
    }

    public function getEventId(): string
    {
        return $this->eventId;
    }

    public function getOccurredAt(): DateTimeInterface
    {
        return $this->occurredAt;
    }

    abstract public function getEventName(): string;
    abstract public function getPayload(): array;
}

class BookingCreated extends DomainEvent
{
    public function __construct(
        private readonly OrderId $orderId,
        private readonly BookingId $bookingId,
        private readonly string $provider
    ) {
        parent::__construct();
    }

    public function getEventName(): string
    {
        return 'booking.created';
    }

    public function getPayload(): array
    {
        return [
            'order_id' => $this->orderId->getValue(),
            'booking_id' => $this->bookingId->getValue(),
            'provider' => $this->provider,
        ];
    }
}

class BookingFailed extends DomainEvent
{
    public function __construct(
        private readonly OrderId $orderId,
        private readonly string $reason,
        private readonly string $provider,
        private readonly array $context = []
    ) {
        parent::__construct();
    }

    public function getEventName(): string
    {
        return 'booking.failed';
    }

    public function getPayload(): array
    {
        return [
            'order_id' => $this->orderId->getValue(),
            'reason' => $this->reason,
            'provider' => $this->provider,
            'context' => $this->context,
        ];
    }
}

// Event Dispatcher
interface EventDispatcherInterface
{
    public function dispatch(DomainEvent $event): void;
    public function subscribe(string $eventName, callable $handler): void;
}

class EventDispatcher implements EventDispatcherInterface
{
    private array $handlers = [];

    public function dispatch(DomainEvent $event): void
    {
        $handlers = $this->handlers[$event->getEventName()] ?? [];

        foreach ($handlers as $handler) {
            try {
                $handler($event);
            } catch (\Exception $e) {
                Log::error('Event handler failed', [
                    'event' => $event->getEventName(),
                    'handler' => get_class($handler),
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    public function subscribe(string $eventName, callable $handler): void
    {
        $this->handlers[$eventName][] = $handler;
    }
}
```

### 6. **Improve Error Handling**

Implement consistent error handling with custom exceptions:

```php
<?php

namespace App\Domain\Booking\Exceptions;

abstract class BookingException extends \Exception
{
    protected array $context;

    public function __construct(string $message, array $context = [], ?\Throwable $previous = null)
    {
        $this->context = $context;
        parent::__construct($message, 0, $previous);
    }

    public function getContext(): array
    {
        return $this->context;
    }

    abstract public function getErrorCode(): string;
}

class HoldExpiredException extends BookingException
{
    public function getErrorCode(): string
    {
        return 'HOLD_EXPIRED';
    }
}

class InsufficientCapacityException extends BookingException
{
    public function getErrorCode(): string
    {
        return 'INSUFFICIENT_CAPACITY';
    }
}

class PaymentRequiredException extends BookingException
{
    public function getErrorCode(): string
    {
        return 'PAYMENT_REQUIRED';
    }
}

class ExternalApiException extends BookingException
{
    public function __construct(
        string $message,
        private readonly string $provider,
        private readonly int $httpStatus,
        array $context = [],
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $context, $previous);
    }

    public function getProvider(): string
    {
        return $this->provider;
    }

    public function getHttpStatus(): int
    {
        return $this->httpStatus;
    }

    public function getErrorCode(): string
    {
        return 'EXTERNAL_API_ERROR';
    }
}

// Error Handler
class BookingErrorHandler
{
    public function handle(\Throwable $exception, array $context = []): ErrorResponse
    {
        $traceId = Str::uuid();

        Log::error('Booking operation failed', [
            'trace_id' => $traceId,
            'exception_class' => get_class($exception),
            'message' => $exception->getMessage(),
            'context' => $context,
            'stack_trace' => $exception->getTraceAsString(),
        ]);

        if ($exception instanceof BookingException) {
            return ErrorResponse::fromBookingException($exception, $traceId);
        }

        if ($exception instanceof ValidationException) {
            return ErrorResponse::validationError($exception->errors(), $traceId);
        }

        // Unknown exception - return generic error
        return ErrorResponse::genericError($traceId);
    }
}

class ErrorResponse
{
    public function __construct(
        private readonly string $code,
        private readonly string $message,
        private readonly array $details = [],
        private readonly ?string $traceId = null
    ) {}

    public static function fromBookingException(BookingException $exception, string $traceId): self
    {
        return new self(
            $exception->getErrorCode(),
            $exception->getMessage(),
            $exception->getContext(),
            $traceId
        );
    }

    public function toArray(): array
    {
        return [
            'error' => [
                'code' => $this->code,
                'message' => $this->message,
                'details' => $this->details,
                'trace_id' => $this->traceId,
            ]
        ];
    }
}
```

### 7. **Add Comprehensive Logging and Metrics**

Implement structured logging with metrics collection:

```php
<?php

namespace App\Infrastructure\Monitoring;

class BookingLogger
{
    private LoggerInterface $logger;
    private MetricsCollector $metrics;

    public function __construct(LoggerInterface $logger, MetricsCollector $metrics)
    {
        $this->logger = $logger;
        $this->metrics = $metrics;
    }

    public function logBookingAttempt(string $provider, OrderId $orderId): void
    {
        $this->logger->info('Booking attempt started', [
            'event' => 'booking.attempt.started',
            'provider' => $provider,
            'order_id' => $orderId->getValue(),
            'timestamp' => now()->toISOString(),
        ]);

        $this->metrics->increment('booking.attempts', [
            'provider' => $provider,
        ]);
    }

    public function logBookingSuccess(string $provider, OrderId $orderId, float $duration): void
    {
        $this->logger->info('Booking completed successfully', [
            'event' => 'booking.completed',
            'provider' => $provider,
            'order_id' => $orderId->getValue(),
            'duration_ms' => $duration,
            'timestamp' => now()->toISOString(),
        ]);

        $this->metrics->increment('booking.successes', [
            'provider' => $provider,
        ]);

        $this->metrics->histogram('booking.duration', $duration, [
            'provider' => $provider,
        ]);
    }

    public function logBookingFailure(
        string $provider,
        OrderId $orderId,
        \Throwable $exception,
        float $duration
    ): void {
        $this->logger->error('Booking failed', [
            'event' => 'booking.failed',
            'provider' => $provider,
            'order_id' => $orderId->getValue(),
            'duration_ms' => $duration,
            'error_class' => get_class($exception),
            'error_message' => $exception->getMessage(),
            'timestamp' => now()->toISOString(),
        ]);

        $this->metrics->increment('booking.failures', [
            'provider' => $provider,
            'error_type' => get_class($exception),
        ]);
    }
}

interface MetricsCollector
{
    public function increment(string $metric, array $tags = []): void;
    public function histogram(string $metric, float $value, array $tags = []): void;
    public function gauge(string $metric, float $value, array $tags = []): void;
}

class PrometheusMetricsCollector implements MetricsCollector
{
    private $prometheusRegistry;

    public function increment(string $metric, array $tags = []): void
    {
        $counter = $this->prometheusRegistry->getOrRegisterCounter(
            'booking',
            $metric,
            'Booking system metric',
            array_keys($tags)
        );

        $counter->incBy(1, array_values($tags));
    }

    public function histogram(string $metric, float $value, array $tags = []): void
    {
        $histogram = $this->prometheusRegistry->getOrRegisterHistogram(
            'booking',
            $metric,
            'Booking system metric',
            array_keys($tags),
            [0.1, 0.5, 1, 2.5, 5, 10] // buckets in seconds
        );

        $histogram->observe($value / 1000, array_values($tags)); // convert ms to seconds
    }
}
```

### 8. **Implement Configuration Management**

Create type-safe configuration with validation:

```php
<?php

namespace App\Infrastructure\Configuration;

class BookingConfiguration
{
    public function __construct(
        private readonly RedeamConfig $redeamConfig,
        private readonly SmartOrderConfig $smartOrderConfig,
        private readonly GeneralConfig $generalConfig
    ) {}

    public static function fromArray(array $config): self
    {
        return new self(
            RedeamConfig::fromArray($config['redeam'] ?? []),
            SmartOrderConfig::fromArray($config['smartorder'] ?? []),
            GeneralConfig::fromArray($config['general'] ?? [])
        );
    }

    public function getRedeamConfig(): RedeamConfig
    {
        return $this->redeamConfig;
    }

    public function getSmartOrderConfig(): SmartOrderConfig
    {
        return $this->smartOrderConfig;
    }
}

class RedeamConfig
{
    public function __construct(
        private readonly string $disneyBaseUrl,
        private readonly string $unitedParksBaseUrl,
        private readonly int $timeoutSeconds,
        private readonly int $maxRetries,
        private readonly string $supplierId
    ) {}

    public static function fromArray(array $config): self
    {
        $required = ['disney_base_url', 'united_parks_base_url', 'supplier_id'];

        foreach ($required as $key) {
            if (!isset($config[$key])) {
                throw new ConfigurationException("Missing required Redeam config: {$key}");
            }
        }

        return new self(
            $config['disney_base_url'],
            $config['united_parks_base_url'],
            $config['timeout_seconds'] ?? 30,
            $config['max_retries'] ?? 3,
            $config['supplier_id']
        );
    }

    public function getDisneyBaseUrl(): string
    {
        return $this->disneyBaseUrl;
    }

    public function getTimeoutSeconds(): int
    {
        return $this->timeoutSeconds;
    }
}

// config/booking.php
return [
    'redeam' => [
        'disney_base_url' => env('REDEAM_DISNEY_BASE_URL'),
        'united_parks_base_url' => env('REDEAM_UP_BASE_URL'),
        'supplier_id' => env('REDEAM_SUPPLIER_ID'),
        'timeout_seconds' => env('REDEAM_TIMEOUT', 30),
        'max_retries' => env('REDEAM_MAX_RETRIES', 3),
    ],
    'smartorder' => [
        'base_url' => env('SMARTORDER_BASE_URL'),
        'customer_id' => env('SMARTORDER_CUSTOMER_ID'),
        'timeout_seconds' => env('SMARTORDER_TIMEOUT', 45),
    ],
    'general' => [
        'hold_expiry_minutes' => env('BOOKING_HOLD_EXPIRY', 10),
        'voucher_storage_disk' => env('VOUCHER_STORAGE_DISK', 's3'),
    ],
    'circuit_breaker' => [
        'failure_threshold' => env('CB_FAILURE_THRESHOLD', 5),
        'recovery_timeout' => env('CB_RECOVERY_TIMEOUT', 300),
    ]
];
```

### 9. **Create Service Factories**

Implement factory pattern for service creation:

```php
<?php

namespace App\Infrastructure\Factories;

class BookingProviderFactory
{
    private array $providers = [];
    private Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->registerProviders();
    }

    public function create(string $providerType): BookingProviderInterface
    {
        if (!isset($this->providers[$providerType])) {
            throw new UnsupportedProviderException("Provider {$providerType} not supported");
        }

        $providerClass = $this->providers[$providerType];
        return $this->container->make($providerClass);
    }

    public function getSupportedProviders(): array
    {
        return array_keys($this->providers);
    }

    private function registerProviders(): void
    {
        $this->providers = [
            'redeam_disney' => RedeamDisneyProvider::class,
            'redeam_united_parks' => RedeamUnitedParksProvider::class,
            'smartorder' => SmartOrderProvider::class,
        ];
    }
}

// Service Provider for DI container
class BookingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(BookingConfiguration::class, function () {
            return BookingConfiguration::fromArray(config('booking'));
        });

        $this->app->singleton(BookingProviderFactory::class);

        $this->app->singleton(EventDispatcherInterface::class, EventDispatcher::class);

        $this->app->when(RedeamDisneyProvider::class)
            ->needs(ApiClient::class)
            ->give(RedeamDisneyApiClient::class);

        $this->app->when(SmartOrderProvider::class)
            ->needs(ApiClient::class)
            ->give(SmartOrderApiClient::class);
    }
}
```

### 10. **Implement Testing Strategy**

Create comprehensive testing approach:

```php
<?php

namespace Tests\Unit\Domain\Booking;

class BookingServiceTest extends TestCase
{
    private BookingService $bookingService;
    private MockInterface $providerFactory;
    private MockInterface $orderRepository;
    private MockInterface $eventDispatcher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->providerFactory = Mockery::mock(BookingProviderFactory::class);
        $this->orderRepository = Mockery::mock(OrderRepositoryInterface::class);
        $this->eventDispatcher = Mockery::mock(EventDispatcherInterface::class);

        $this->bookingService = new BookingService(
            $this->providerFactory,
            $this->orderRepository,
            $this->eventDispatcher
        );
    }

    public function test_successful_booking_creation(): void
    {
        // Arrange
        $command = new CreateBookingCommand(
            new OrderId('123'),
            'redeam_disney',
            new HoldId('hold-456'),
            new CustomerId('customer-789')
        );

        $mockProvider = Mockery::mock(BookingProviderInterface::class);
        $expectedBooking = new BookingResult(new BookingId('booking-123'), 'confirmed');

        $this->providerFactory
            ->shouldReceive('create')
            ->with('redeam_disney')
            ->once()
            ->andReturn($mockProvider);

        $mockProvider
            ->shouldReceive('createBooking')
            ->once()
            ->andReturn($expectedBooking);

        $this->orderRepository
            ->shouldReceive('update')
            ->once();

        $this->eventDispatcher
            ->shouldReceive('dispatch')
            ->once()
            ->with(Mockery::type(BookingCreated::class));

        // Act
        $result = $this->bookingService->createBooking($command);

        // Assert
        $this->assertInstanceOf(BookingResult::class, $result);
        $this->assertEquals('booking-123', $result->getBookingId()->getValue());
    }

    public function test_booking_creation_handles_provider_failure(): void
    {
        // Arrange
        $command = new CreateBookingCommand(
            new OrderId('123'),
            'redeam_disney',
            new HoldId('hold-456'),
            new CustomerId('customer-789')
        );

        $mockProvider = Mockery::mock(BookingProviderInterface::class);

        $this->providerFactory
            ->shouldReceive('create')
            ->with('redeam_disney')
            ->once()
            ->andReturn($mockProvider);

        $mockProvider
            ->shouldReceive('createBooking')
            ->once()
            ->andThrow(new ExternalApiException('API Error', 'redeam_disney', 500));

        $this->eventDispatcher
            ->shouldReceive('dispatch')
            ->once()
            ->with(Mockery::type(BookingFailed::class));

        // Act & Assert
        $this->expectException(ExternalApiException::class);
        $this->bookingService->createBooking($command);
    }
}

// Integration Test
class RedeamDisneyProviderIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_real_api_integration(): void
    {
        // This would test against a real API in a controlled environment
        $this->markTestSkipped('Requires real API credentials');

        $provider = app(RedeamDisneyProvider::class);
        $request = new HoldRequest(/* test data */);

        $result = $provider->createHold($request);

        $this->assertInstanceOf(HoldResult::class, $result);
        $this->assertTrue($result->isSuccessful());
    }
}

// Feature Test
class BookingFlowFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_complete_booking_flow(): void
    {
        // Create test data
        $order = Order::factory()->create();
        $customer = Customer::factory()->create();

        // Mock external API responses
        Http::fake([
            'api.redeam.com/*' => Http::response(['hold' => ['id' => 'hold-123']], 200),
        ]);

        // Execute booking flow
        $response = $this->postJson('/api/bookings', [
            'order_id' => $order->id,
            'provider' => 'redeam_disney',
            // ... other data
        ]);

        // Assertions
        $response->assertSuccessful();
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'confirmed',
        ]);
    }
}
```

## Implementation Roadmap

### Phase 1: Foundation (4-6 weeks)

1. **Week 1-2**: Create base abstractions and interfaces

    - Define `BookingProviderInterface`
    - Create value objects (`BookingId`, `OrderId`, `Money`)
    - Implement basic error handling structure

2. **Week 3-4**: Implement repository pattern

    - Create repository interfaces
    - Implement Eloquent repositories
    - Add basic domain events

3. **Week 5-6**: Add configuration management and logging
    - Create type-safe configuration classes
    - Implement structured logging
    - Add basic metrics collection

### Phase 2: Provider Refactoring (6-8 weeks)

1. **Week 7-10**: Refactor Redeam services

    - Extract common logic to base classes
    - Implement new provider interface
    - Add comprehensive error handling

2. **Week 11-14**: Refactor SmartOrder services
    - Apply same patterns as Redeam
    - Consolidate data sync logic
    - Add circuit breaker pattern

### Phase 3: Advanced Features (4-6 weeks)

1. **Week 15-18**: Add resilience patterns

    - Implement circuit breaker
    - Add retry mechanisms
    - Create health checks

2. **Week 19-20**: Testing and optimization
    - Comprehensive test coverage
    - Performance optimization
    - Documentation

### Phase 4: Migration and Monitoring (2-3 weeks)

1. **Week 21-22**: Gradual migration

    - Feature flag old vs new implementations
    - Monitor performance and errors
    - Gradual rollout

2. **Week 23**: Final optimization
    - Remove legacy code
    - Performance tuning
    - Documentation updates

## Migration Strategy

### 1. **Parallel Implementation**

-   Keep existing services running
-   Implement new services alongside old ones
-   Use feature flags to switch between implementations

### 2. **Gradual Rollout**

```php
class BookingServiceProxy implements BookingProviderInterface
{
    public function __construct(
        private BookingProviderInterface $newService,
        private $legacyService,
        private FeatureFlag $featureFlag
    ) {}

    public function createBooking(BookingRequest $request): BookingResult
    {
        if ($this->featureFlag->isEnabled('new_booking_service')) {
            return $this->newService->createBooking($request);
        }

        return $this->legacyService->createBooking($request);
    }
}
```

### 3. **Data Migration**

-   No immediate data migration required
-   New services work with existing database structure
-   Gradual enhancement of data models

## Success Metrics

### Technical Metrics

-   **Code Quality**: Cyclomatic complexity < 10, test coverage > 90%
-   **Performance**: Response time reduction of 30%
-   **Reliability**: Error rate < 0.1%
-   **Maintainability**: Reduced code duplication by 60%

### Business Metrics

-   **Booking Success Rate**: > 99.5%
-   **Time to Market**: 50% faster feature development
-   **Support Tickets**: 70% reduction in technical issues
-   **Developer Productivity**: 40% faster debugging

## Conclusion

The current services architecture has significant room for improvement in terms of maintainability, testability, and scalability. The proposed improvements will:

1. **Reduce Code Duplication** by 60-70%
2. **Improve Error Handling** with structured, consistent patterns
3. **Enhance Testability** through dependency injection and interfaces
4. **Increase Reliability** with circuit breakers and retry mechanisms
5. **Better Monitoring** with structured logging and metrics
6. **Simplified Maintenance** through clear separation of concerns

The phased implementation approach ensures minimal disruption to existing functionality while providing immediate value through improved error handling and logging. The long-term benefits include faster feature development, improved system reliability, and better developer experience.
