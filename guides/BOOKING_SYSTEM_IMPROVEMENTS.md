# Booking System Improvements

## Overview

This document outlines comprehensive improvements for the booking system to ensure better flow, enhanced standardization, and improved long-term codebase management for seamless customer booking experiences.

## Current Issues Identified

### 1. Code Duplication and Inconsistent Structure
- **Problem**: Multiple similar job classes (`Job.php` in both `UnitedParks` and `WaltDisney` folders) with nearly identical logic
- **Impact**: Maintenance overhead, inconsistent behavior, increased bug potential
- **Example**: Repeated job patterns (`SyncProductJob`, `SyncProductRatesJob`, `LoopSyncProductScheduleJob`) across different providers

### 2. Lack of Abstraction and Interfaces
- **Problem**: No common interfaces for different providers (Redeam, SmartOrder)
- **Impact**: Tight coupling, difficult testing, hard to add new providers
- **Current State**: Services and Jobs are tightly coupled to specific providers

### 3. Poor Error Handling and Resilience
- **Problem**: Basic error logging without structured recovery mechanisms
- **Impact**: Poor customer experience during API failures, manual intervention required
- **Missing Features**: Circuit breaker patterns, structured retry strategies

### 4. Queue Management Issues
- **Problem**: All jobs use the same queue (`sync-products`)
- **Impact**: No prioritization for critical booking operations
- **Missing**: Dead letter queue handling, priority-based processing

### 5. Data Synchronization Problems
- **Problem**: No conflict resolution for concurrent updates
- **Impact**: Data inconsistencies, race conditions
- **Missing**: Data validation, rollback mechanisms

## Recommended Improvements

### 1. Implement Provider Abstraction Layer

**Priority**: High
**Timeline**: 2-3 weeks

Create standardized interfaces for all booking providers:

```php
<?php

namespace App\Contracts\Booking;

interface BookingProviderInterface
{
    public function syncProducts(): void;
    public function syncRates(ProductInterface $product): void;
    public function syncSchedules(ProductInterface $product): void;
    public function createBooking(OrderInterface $order): BookingResult;
    public function cancelBooking(string $bookingId): bool;
    public function verifyBooking(string $bookingId): BookingStatus;
}

interface ProductInterface
{
    public function getId(): string;
    public function getTitle(): string;
    public function getProviderData(): array;
}

interface OrderInterface
{
    public function getId(): string;
    public function getCustomer(): CustomerInterface;
    public function getItems(): array;
}
```

**Benefits**:
- Standardized provider integration
- Easy addition of new booking providers
- Improved testability
- Consistent error handling

### 2. Standardize Job Structure with Abstract Base Classes

**Priority**: High
**Timeline**: 2 weeks

Create abstract job classes to reduce duplication:

```php
<?php

namespace App\Jobs\Base;

abstract class BaseProviderSyncJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    public $tries = 3;
    public $backoff = [30, 60, 120]; // Progressive backoff
    public $timeout = 300;
    public $maxExceptions = 3;

    abstract protected function getProvider(): BookingProviderInterface;
    abstract protected function processProduct($product): void;
    abstract protected function getJobType(): string;
    
    public function handle()
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        try {
            DB::transaction(function () {
                $this->processProduct($this->product);
            });
        } catch (\Exception $e) {
            $this->handleError($e);
            throw $e;
        }
    }

    public function failed($exception = null)
    {
        $this->logError($exception);
        $this->notifyAdministrators($exception);
    }

    protected function logError(\Throwable $exception): void
    {
        Log::channel('booking-jobs')->error("Job failed: {$this->getJobType()}", [
            'job_class' => get_class($this),
            'provider' => get_class($this->getProvider()),
            'product_id' => $this->product?->getId(),
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
            'domain_id' => get_domain_id(),
        ]);
    }
}
```

**Benefits**:
- Reduced code duplication
- Consistent error handling
- Standardized logging
- Progressive retry mechanism

### 3. Implement Circuit Breaker Pattern

**Priority**: High
**Timeline**: 1-2 weeks

Add resilience for external API calls:

```php
<?php

namespace App\Services\Resilience;

class CircuitBreaker
{
    private $redis;
    private $failureThreshold;
    private $timeout;
    private $recoveryTimeout;

    public function __construct(
        Redis $redis,
        int $failureThreshold = 5,
        int $timeout = 60,
        int $recoveryTimeout = 300
    ) {
        $this->redis = $redis;
        $this->failureThreshold = $failureThreshold;
        $this->timeout = $timeout;
        $this->recoveryTimeout = $recoveryTimeout;
    }

    public function call(callable $service, string $serviceKey, array $context = [])
    {
        $state = $this->getCircuitState($serviceKey);
        
        if ($state === 'open') {
            if ($this->shouldAttemptReset($serviceKey)) {
                return $this->attemptCall($service, $serviceKey, $context);
            }
            throw new ServiceUnavailableException("Circuit breaker open for {$serviceKey}");
        }

        return $this->attemptCall($service, $serviceKey, $context);
    }

    private function attemptCall(callable $service, string $serviceKey, array $context)
    {
        try {
            $result = $service();
            $this->recordSuccess($serviceKey);
            return $result;
        } catch (\Exception $e) {
            $this->recordFailure($serviceKey, $e, $context);
            throw $e;
        }
    }

    private function getCircuitState(string $serviceKey): string
    {
        $failures = $this->redis->get("circuit_breaker:{$serviceKey}:failures") ?: 0;
        $lastFailure = $this->redis->get("circuit_breaker:{$serviceKey}:last_failure");

        if ($failures >= $this->failureThreshold) {
            if ($lastFailure && (time() - $lastFailure) > $this->recoveryTimeout) {
                return 'half-open';
            }
            return 'open';
        }

        return 'closed';
    }

    private function recordSuccess(string $serviceKey): void
    {
        $this->redis->del("circuit_breaker:{$serviceKey}:failures");
        $this->redis->del("circuit_breaker:{$serviceKey}:last_failure");
    }

    private function recordFailure(string $serviceKey, \Exception $e, array $context): void
    {
        $failures = $this->redis->incr("circuit_breaker:{$serviceKey}:failures");
        $this->redis->set("circuit_breaker:{$serviceKey}:last_failure", time());
        $this->redis->expire("circuit_breaker:{$serviceKey}:failures", $this->recoveryTimeout);

        Log::warning("Circuit breaker failure recorded", [
            'service' => $serviceKey,
            'failures' => $failures,
            'exception' => $e->getMessage(),
            'context' => $context,
        ]);
    }
}
```

**Benefits**:
- Prevents cascade failures
- Improves system resilience
- Automatic recovery
- Better error tracking

### 4. Enhanced Queue Management

**Priority**: Medium
**Timeline**: 1 week

Implement priority-based queue system:

```php
<?php

namespace App\Services\Queue;

class QueueManager
{
    const CRITICAL_QUEUE = 'critical';
    const HIGH_PRIORITY_QUEUE = 'high-priority';
    const NORMAL_QUEUE = 'normal';
    const LOW_PRIORITY_QUEUE = 'low-priority';

    public static function dispatchBookingJob($job): void
    {
        $job->onQueue(self::CRITICAL_QUEUE);
        dispatch($job);
        
        Log::info('Critical booking job dispatched', [
            'job' => get_class($job),
            'queue' => self::CRITICAL_QUEUE,
        ]);
    }

    public static function dispatchSyncJob($job): void
    {
        $job->onQueue(self::NORMAL_QUEUE);
        dispatch($job);
    }

    public static function dispatchMaintenanceJob($job): void
    {
        $job->onQueue(self::LOW_PRIORITY_QUEUE);
        dispatch($job);
    }

    public static function getQueuePriorities(): array
    {
        return [
            self::CRITICAL_QUEUE => 1,
            self::HIGH_PRIORITY_QUEUE => 2,
            self::NORMAL_QUEUE => 3,
            self::LOW_PRIORITY_QUEUE => 4,
        ];
    }
}
```

**Queue Configuration** (config/queue.php):
```php
'connections' => [
    'redis' => [
        'driver' => 'redis',
        'connection' => 'default',
        'queue' => env('REDIS_QUEUE', 'default'),
        'retry_after' => 90,
        'block_for' => null,
        'after_commit' => false,
    ],
    'critical-redis' => [
        'driver' => 'redis',
        'connection' => 'default',
        'queue' => 'critical',
        'retry_after' => 30,
        'block_for' => null,
        'after_commit' => false,
    ],
],
```

**Benefits**:
- Prioritized job processing
- Better resource allocation
- Improved booking reliability
- Separated concerns

### 5. Implement Event Sourcing for Booking Flow

**Priority**: Medium
**Timeline**: 2-3 weeks

Create domain events for better tracking:

```php
<?php

namespace App\Events\Booking;

abstract class BookingEvent
{
    public $orderId;
    public $timestamp;
    public $metadata;
    public $eventId;

    public function __construct($orderId, array $metadata = [])
    {
        $this->orderId = $orderId;
        $this->timestamp = now();
        $this->metadata = $metadata;
        $this->eventId = Str::uuid();
    }

    public function toArray(): array
    {
        return [
            'event_id' => $this->eventId,
            'event_type' => get_class($this),
            'order_id' => $this->orderId,
            'timestamp' => $this->timestamp,
            'metadata' => $this->metadata,
        ];
    }
}

class BookingInitiated extends BookingEvent 
{
    public function __construct($orderId, $provider, $items)
    {
        parent::__construct($orderId, [
            'provider' => $provider,
            'items' => $items,
            'status' => 'initiated',
        ]);
    }
}

class BookingCompleted extends BookingEvent 
{
    public function __construct($orderId, $bookingId, $provider)
    {
        parent::__construct($orderId, [
            'booking_id' => $bookingId,
            'provider' => $provider,
            'status' => 'completed',
        ]);
    }
}

class BookingFailed extends BookingEvent 
{
    public function __construct($orderId, $reason, $provider)
    {
        parent::__construct($orderId, [
            'reason' => $reason,
            'provider' => $provider,
            'status' => 'failed',
        ]);
    }
}

class BookingCancelled extends BookingEvent 
{
    public function __construct($orderId, $reason, $provider)
    {
        parent::__construct($orderId, [
            'reason' => $reason,
            'provider' => $provider,
            'status' => 'cancelled',
        ]);
    }
}
```

**Event Store**:
```php
<?php

namespace App\Services\EventStore;

class BookingEventStore
{
    private $connection;

    public function __construct()
    {
        $this->connection = DB::connection();
    }

    public function store(BookingEvent $event): void
    {
        $this->connection->table('booking_events')->insert([
            'event_id' => $event->eventId,
            'order_id' => $event->orderId,
            'event_type' => get_class($event),
            'event_data' => json_encode($event->toArray()),
            'created_at' => $event->timestamp,
        ]);
    }

    public function getEventsForOrder(string $orderId): Collection
    {
        return $this->connection->table('booking_events')
            ->where('order_id', $orderId)
            ->orderBy('created_at')
            ->get()
            ->map(function ($row) {
                return json_decode($row->event_data, true);
            });
    }
}
```

**Benefits**:
- Complete audit trail
- Better debugging capabilities
- Event-driven architecture
- Historical data analysis

### 6. Standardize Error Handling and Recovery

**Priority**: High
**Timeline**: 1-2 weeks

Create comprehensive error handling system:

```php
<?php

namespace App\Services\ErrorHandling;

class BookingErrorHandler
{
    private $circuitBreaker;
    private $notificationService;

    public function __construct(
        CircuitBreaker $circuitBreaker,
        NotificationService $notificationService
    ) {
        $this->circuitBreaker = $circuitBreaker;
        $this->notificationService = $notificationService;
    }

    public function handle(\Throwable $exception, $context = []): ErrorHandlingResult
    {
        $traceId = Str::uuid();
        
        // Log structured error
        Log::channel('booking-errors')->error($exception->getMessage(), [
            'exception' => [
                'class' => get_class($exception),
                'message' => $exception->getMessage(),
                'code' => $exception->getCode(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
            ],
            'context' => $context,
            'trace_id' => $traceId,
            'timestamp' => now()->toISOString(),
        ]);

        // Determine recovery strategy
        $strategy = $this->getRecoveryStrategy($exception);
        
        return match($strategy) {
            'retry' => $this->scheduleRetry($context, $traceId),
            'manual_intervention' => $this->notifySupport($context, $traceId, $exception),
            'fallback' => $this->executeFallback($context, $traceId),
            'circuit_breaker' => $this->handleCircuitBreaker($context, $traceId),
            default => $this->fail($context, $traceId, $exception)
        };
    }

    private function getRecoveryStrategy(\Throwable $exception): string
    {
        return match(true) {
            $exception instanceof \GuzzleHttp\Exception\ConnectException => 'circuit_breaker',
            $exception instanceof \GuzzleHttp\Exception\RequestException && $exception->getCode() >= 500 => 'retry',
            $exception instanceof \GuzzleHttp\Exception\RequestException && $exception->getCode() === 429 => 'retry',
            $exception instanceof ValidationException => 'manual_intervention',
            $exception instanceof \PDOException => 'retry',
            default => 'fallback'
        };
    }

    private function scheduleRetry($context, $traceId): ErrorHandlingResult
    {
        $delay = $this->calculateRetryDelay($context);
        
        dispatch((new RetryBookingJob($context))
            ->delay($delay)
            ->onQueue(QueueManager::HIGH_PRIORITY_QUEUE));

        return ErrorHandlingResult::retry($traceId, $delay);
    }

    private function notifySupport($context, $traceId, \Throwable $exception): ErrorHandlingResult
    {
        $this->notificationService->notifySupport([
            'trace_id' => $traceId,
            'error_type' => 'booking_failure',
            'context' => $context,
            'exception' => $exception->getMessage(),
            'requires_manual_intervention' => true,
        ]);

        return ErrorHandlingResult::manualIntervention($traceId);
    }
}

class ErrorHandlingResult
{
    public $strategy;
    public $traceId;
    public $metadata;

    public static function retry($traceId, $delay): self
    {
        return new self('retry', $traceId, ['delay' => $delay]);
    }

    public static function manualIntervention($traceId): self
    {
        return new self('manual_intervention', $traceId, []);
    }

    // ... other factory methods
}
```

**Benefits**:
- Structured error handling
- Automatic recovery strategies
- Better observability
- Reduced manual intervention

### 7. Implement Saga Pattern for Complex Booking Flows

**Priority**: Medium
**Timeline**: 3-4 weeks

Handle multi-step booking processes with compensating actions:

```php
<?php

namespace App\Services\Saga;

class BookingSaga
{
    private $steps = [];
    private $compensations = [];
    private $sagaId;

    public function __construct()
    {
        $this->sagaId = Str::uuid();
    }

    public function addStep(BookingStep $step): self
    {
        $this->steps[] = $step;
        return $this;
    }

    public function execute(): BookingSagaResult
    {
        $executedSteps = [];
        
        Log::info("Starting booking saga", ['saga_id' => $this->sagaId]);
        
        try {
            foreach ($this->steps as $index => $step) {
                Log::info("Executing saga step", [
                    'saga_id' => $this->sagaId,
                    'step' => $index + 1,
                    'step_type' => get_class($step),
                ]);

                $result = $step->execute();
                $executedSteps[] = ['step' => $step, 'result' => $result];
            }
            
            Log::info("Booking saga completed successfully", ['saga_id' => $this->sagaId]);
            return BookingSagaResult::success($executedSteps, $this->sagaId);
            
        } catch (\Exception $e) {
            Log::error("Booking saga failed, starting compensation", [
                'saga_id' => $this->sagaId,
                'error' => $e->getMessage(),
                'executed_steps' => count($executedSteps),
            ]);
            
            $this->compensate($executedSteps);
            return BookingSagaResult::failed($e, $executedSteps, $this->sagaId);
        }
    }

    private function compensate(array $executedSteps): void
    {
        foreach (array_reverse($executedSteps) as $stepData) {
            try {
                $stepData['step']->compensate($stepData['result']);
                Log::info("Compensation successful", [
                    'saga_id' => $this->sagaId,
                    'step' => get_class($stepData['step']),
                ]);
            } catch (\Exception $e) {
                Log::error("Compensation failed", [
                    'saga_id' => $this->sagaId,
                    'step' => get_class($stepData['step']),
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}

abstract class BookingStep
{
    abstract public function execute();
    abstract public function compensate($result): void;
    abstract public function canCompensate($result): bool;
}

class CreateReservationStep extends BookingStep
{
    private $order;
    private $provider;

    public function __construct(Order $order, BookingProviderInterface $provider)
    {
        $this->order = $order;
        $this->provider = $provider;
    }

    public function execute()
    {
        return $this->provider->createReservation($this->order);
    }

    public function compensate($result): void
    {
        if ($this->canCompensate($result)) {
            $this->provider->cancelReservation($result['reservation_id']);
        }
    }

    public function canCompensate($result): bool
    {
        return isset($result['reservation_id']) && $result['status'] !== 'cancelled';
    }
}
```

**Benefits**:
- Handles complex multi-step processes
- Automatic rollback on failure
- Consistent state management
- Better error recovery

### 8. Package Structure Improvements

**Priority**: Medium
**Timeline**: 2-3 weeks

Reorganize packages with better separation of concerns:

```
packages/
├── booking-core/                   # Core booking abstractions
│   ├── src/
│   │   ├── Contracts/
│   │   │   ├── BookingProviderInterface.php
│   │   │   ├── ProductInterface.php
│   │   │   └── OrderInterface.php
│   │   ├── Events/
│   │   │   ├── BookingInitiated.php
│   │   │   ├── BookingCompleted.php
│   │   │   └── BookingFailed.php
│   │   ├── Exceptions/
│   │   │   ├── BookingException.php
│   │   │   └── ServiceUnavailableException.php
│   │   ├── Services/
│   │   │   ├── CircuitBreaker.php
│   │   │   ├── ErrorHandler.php
│   │   │   └── QueueManager.php
│   │   └── Jobs/
│   │       └── BaseProviderSyncJob.php
│   └── composer.json
├── provider-redeam/               # Redeam-specific implementation
│   ├── src/
│   │   ├── Services/
│   │   │   ├── RedeamDisneyService.php
│   │   │   └── RedeamUnitedParksService.php
│   │   ├── Jobs/
│   │   │   ├── SyncProductJob.php
│   │   │   └── SyncRatesJob.php
│   │   └── Clients/
│   │       ├── RedeamDisneyClient.php
│   │       └── RedeamUnitedParksClient.php
│   └── composer.json
├── provider-smartorder/           # SmartOrder-specific implementation
│   ├── src/
│   │   ├── Services/
│   │   │   └── SmartOrderService.php
│   │   ├── Jobs/
│   │   │   └── SmartOrderSyncJob.php
│   │   └── Clients/
│   │       └── SmartOrderClient.php
│   └── composer.json
└── booking-orchestrator/          # Booking flow orchestration
    ├── src/
    │   ├── Saga/
    │   │   ├── BookingSaga.php
    │   │   └── BookingStep.php
    │   ├── Orchestrators/
    │   │   └── BookingOrchestrator.php
    │   └── Services/
    │       └── BookingCoordinator.php
    └── composer.json
```

**Benefits**:
- Clear separation of concerns
- Reusable core components
- Better testability
- Easier maintenance

### 9. Configuration Management

**Priority**: Low
**Timeline**: 1 week

Centralize provider configurations:

```php
<?php
// config/booking-providers.php

return [
    'default_provider' => env('DEFAULT_BOOKING_PROVIDER', 'redeam'),
    
    'circuit_breaker' => [
        'default_failure_threshold' => 5,
        'default_recovery_timeout' => 300,
        'default_timeout' => 60,
    ],

    'providers' => [
        'redeam' => [
            'disney' => [
                'name' => 'Redeam Disney World',
                'api_base_url' => env('REDEAM_DISNEY_API_URL'),
                'timeout' => 30,
                'retry_attempts' => 3,
                'circuit_breaker' => [
                    'failure_threshold' => 5,
                    'recovery_timeout' => 300,
                ],
                'rate_limits' => [
                    'requests_per_minute' => 100,
                    'requests_per_hour' => 5000,
                ],
            ],
            'united_parks' => [
                'name' => 'Redeam United Parks',
                'api_base_url' => env('REDEAM_UP_API_URL'),
                'timeout' => 30,
                'retry_attempts' => 3,
                'circuit_breaker' => [
                    'failure_threshold' => 3,
                    'recovery_timeout' => 180,
                ],
                'rate_limits' => [
                    'requests_per_minute' => 80,
                    'requests_per_hour' => 3000,
                ],
            ],
        ],
        'smartorder' => [
            'name' => 'SmartOrder Universal',
            'api_base_url' => env('SMARTORDER_API_URL'),
            'timeout' => 45,
            'retry_attempts' => 5,
            'circuit_breaker' => [
                'failure_threshold' => 10,
                'recovery_timeout' => 600,
            ],
            'rate_limits' => [
                'requests_per_minute' => 150,
                'requests_per_hour' => 8000,
            ],
        ],
    ],

    'queues' => [
        'critical' => [
            'name' => 'critical',
            'connection' => 'redis',
            'priority' => 1,
            'timeout' => 30,
            'retry_after' => 45,
        ],
        'high' => [
            'name' => 'high-priority',
            'connection' => 'redis',
            'priority' => 2,
            'timeout' => 60,
            'retry_after' => 90,
        ],
        'normal' => [
            'name' => 'default',
            'connection' => 'redis',
            'priority' => 3,
            'timeout' => 120,
            'retry_after' => 180,
        ],
        'low' => [
            'name' => 'low-priority',
            'connection' => 'redis',
            'priority' => 4,
            'timeout' => 300,
            'retry_after' => 600,
        ],
    ],

    'monitoring' => [
        'enabled' => env('BOOKING_MONITORING_ENABLED', true),
        'metrics_driver' => env('METRICS_DRIVER', 'prometheus'),
        'log_channel' => 'booking',
        'alert_thresholds' => [
            'error_rate' => 0.05, // 5%
            'response_time_p95' => 5000, // 5 seconds
            'queue_depth' => 1000,
        ],
    ],
];
```

**Benefits**:
- Centralized configuration
- Environment-specific settings
- Easy provider management
- Better configuration validation

### 10. Monitoring and Observability

**Priority**: Medium
**Timeline**: 2-3 weeks

Add comprehensive monitoring:

```php
<?php

namespace App\Services\Monitoring;

class BookingMetrics
{
    private $metricsCollector;

    public function __construct(MetricsCollector $metricsCollector)
    {
        $this->metricsCollector = $metricsCollector;
    }

    public function recordBookingAttempt($provider, $type): void
    {
        $this->metricsCollector->increment('booking.attempts.total', [
            'provider' => $provider,
            'type' => $type,
            'domain' => get_domain_name(),
        ]);
    }

    public function recordBookingSuccess($provider, $duration, $amount = null): void
    {
        $this->metricsCollector->increment('booking.successes.total', [
            'provider' => $provider,
            'domain' => get_domain_name(),
        ]);

        $this->metricsCollector->histogram('booking.duration.seconds', $duration, [
            'provider' => $provider,
            'domain' => get_domain_name(),
        ]);

        if ($amount) {
            $this->metricsCollector->histogram('booking.amount.usd', $amount, [
                'provider' => $provider,
                'domain' => get_domain_name(),
            ]);
        }
    }

    public function recordBookingFailure($provider, $reason, $errorCode = null): void
    {
        $labels = [
            'provider' => $provider,
            'reason' => $reason,
            'domain' => get_domain_name(),
        ];

        if ($errorCode) {
            $labels['error_code'] = $errorCode;
        }

        $this->metricsCollector->increment('booking.failures.total', $labels);
    }

    public function recordQueueDepth($queue, $depth): void
    {
        $this->metricsCollector->gauge('queue.depth', $depth, [
            'queue' => $queue,
        ]);
    }

    public function recordCircuitBreakerState($service, $state): void
    {
        $this->metricsCollector->gauge('circuit_breaker.state', $state === 'open' ? 1 : 0, [
            'service' => $service,
        ]);
    }
}

class BookingHealthCheck
{
    private $providers;
    private $circuitBreaker;

    public function check(): array
    {
        $results = [];

        foreach ($this->providers as $provider) {
            $results[$provider] = $this->checkProvider($provider);
        }

        return [
            'status' => $this->calculateOverallStatus($results),
            'providers' => $results,
            'timestamp' => now()->toISOString(),
        ];
    }

    private function checkProvider(string $provider): array
    {
        try {
            $startTime = microtime(true);
            
            // Perform health check based on provider
            $healthy = $this->circuitBreaker->call(
                fn() => $this->performProviderHealthCheck($provider),
                "health_check_{$provider}"
            );

            $duration = (microtime(true) - $startTime) * 1000;

            return [
                'status' => $healthy ? 'healthy' : 'unhealthy',
                'response_time_ms' => round($duration, 2),
                'circuit_breaker_state' => $this->getCircuitBreakerState($provider),
            ];

        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
                'circuit_breaker_state' => 'open',
            ];
        }
    }
}
```

**Dashboard Configuration**:
```yaml
# monitoring/grafana-dashboard.json (example)
{
  "dashboard": {
    "title": "Booking System Metrics",
    "panels": [
      {
        "title": "Booking Success Rate",
        "type": "stat",
        "targets": [
          {
            "expr": "rate(booking_successes_total[5m]) / rate(booking_attempts_total[5m])",
            "legendFormat": "Success Rate"
          }
        ]
      },
      {
        "title": "Booking Response Time",
        "type": "graph",
        "targets": [
          {
            "expr": "histogram_quantile(0.95, booking_duration_seconds_bucket)",
            "legendFormat": "95th percentile"
          }
        ]
      },
      {
        "title": "Circuit Breaker Status",
        "type": "table",
        "targets": [
          {
            "expr": "circuit_breaker_state",
            "legendFormat": "{{service}}"
          }
        ]
      }
    ]
  }
}
```

**Benefits**:
- Real-time monitoring
- Proactive alerting
- Performance insights
- Better debugging capabilities

## Implementation Roadmap

### Phase 1: Foundation (4-6 weeks)
1. **Week 1-2**: Implement Provider Abstraction Layer
2. **Week 3-4**: Create Base Job Classes and Error Handling
3. **Week 5-6**: Add Circuit Breaker Pattern and Queue Management

### Phase 2: Advanced Features (6-8 weeks)
1. **Week 7-10**: Implement Event Sourcing and Saga Pattern
2. **Week 11-14**: Package Restructuring and Configuration Management

### Phase 3: Monitoring and Optimization (4-6 weeks)
1. **Week 15-18**: Add Comprehensive Monitoring
2. **Week 19-20**: Performance Optimization and Testing

## Migration Strategy

### 1. Backward Compatibility
- Keep existing implementations running
- Gradually migrate to new abstractions
- Feature flags for new vs old implementations

### 2. Testing Strategy
- Unit tests for all new abstractions
- Integration tests for provider implementations
- Load testing for queue management
- End-to-end testing for booking flows

### 3. Rollout Plan
- Start with non-critical sync jobs
- Gradually move to booking flows
- Monitor metrics during migration
- Rollback plan for each phase

## Success Metrics

### Technical Metrics
- **Error Rate**: < 1% for booking operations
- **Response Time**: 95th percentile < 3 seconds
- **Queue Processing**: < 10 seconds average
- **Circuit Breaker**: Recovery time < 5 minutes

### Business Metrics
- **Booking Success Rate**: > 99%
- **Customer Satisfaction**: Improved booking experience
- **Support Tickets**: Reduced by 50%
- **Revenue Impact**: Zero revenue loss from technical issues

## Risk Assessment

### High Risk
- **Data Migration**: Potential data loss during migration
  - **Mitigation**: Extensive testing, backup strategies
- **API Changes**: Provider API modifications
  - **Mitigation**: Version management, adapter patterns

### Medium Risk
- **Performance Impact**: New abstractions may impact performance
  - **Mitigation**: Performance testing, gradual rollout
- **Team Learning Curve**: New patterns and architecture
  - **Mitigation**: Training, documentation, pair programming

### Low Risk
- **Configuration Management**: Misconfigured providers
  - **Mitigation**: Validation, environment-specific configs

## Conclusion

These improvements will transform the booking system into a robust, scalable, and maintainable platform that ensures seamless customer experiences while providing excellent developer experience and operational visibility.

The phased approach allows for gradual implementation with minimal risk while delivering immediate value through improved error handling and resilience.