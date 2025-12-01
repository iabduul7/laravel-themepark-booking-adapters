<?php

namespace iabduul7\ThemeParkBooking\Adapters;

use Carbon\Carbon;
use iabduul7\LaravelThemeparkBookingAdapters\Http\SmartOrderHttpClient;
use iabduul7\ThemeParkBooking\Data\BookingRequest;
use iabduul7\ThemeParkBooking\Data\BookingResponse;
use iabduul7\ThemeParkBooking\Data\Product;
use iabduul7\ThemeParkBooking\Data\ProductSyncResult;
use iabduul7\ThemeParkBooking\Data\VoucherData;
use iabduul7\ThemeParkBooking\Exceptions\AdapterException;
use iabduul7\ThemeParkBooking\Exceptions\BookingException;
use iabduul7\ThemeParkBooking\Exceptions\ConfigurationException;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SmartOrderAdapter extends BaseAdapter
{
    protected $client;
    protected SmartOrderHttpClient $httpClient;
    protected int $customerId;
    protected string $approvedSuffix;

    public function __construct(array $config = [])
    {
        parent::__construct($config);
        $this->customerId = $this->getConfig('customer_id') ?? throw new ConfigurationException('customer_id is required for SmartOrderAdapter');
        $this->approvedSuffix = $this->getConfig('approved_suffix', '-2KNOW');
        $this->initializeClient();
    }

    protected function initializeClient(): void
    {
        // First try to use the new independent HTTP client
        try {
            $this->initializeIndependentClient();

            return;
        } catch (\Exception $e) {
            Log::info('Failed to initialize independent SmartOrder client, falling back to legacy client', [
                'error' => $e->getMessage(),
            ]);
        }

        // Fallback to legacy client for backward compatibility
        $legacyClientClass = '\CodeCreatives\LaravelSmartOrder\SmartOrderClient';

        if (class_exists($legacyClientClass)) {
            $this->client = app($legacyClientClass);

            return;
        }

        throw new ConfigurationException(
            "Neither independent HTTP client nor legacy SmartOrder client could be initialized. " .
            "Please ensure proper configuration is provided for the SmartOrder API."
        );
    }

    protected function initializeIndependentClient(): void
    {
        $baseUrl = $this->getConfig('base_url') ?? 'https://QACorpAPI.ucdp.net';
        $clientId = $this->getConfig('client_username');
        $clientSecret = $this->getConfig('client_secret');
        $timeout = $this->getConfig('timeout', 600);

        if (! $clientId || ! $clientSecret) {
            throw new ConfigurationException('client_username and client_secret are required for independent SmartOrder client');
        }

        $this->httpClient = new SmartOrderHttpClient(
            baseUrl: $baseUrl,
            clientId: $clientId,
            clientSecret: $clientSecret,
            customerId: $this->customerId,
            timeout: $timeout
        );

        // Create a wrapper client that mimics the legacy client interface
        $this->client = $this->createClientWrapper();
    }

    protected function createClientWrapper(): object
    {
        $httpClient = $this->httpClient;
        $customerId = $this->customerId;
        $approvedSuffix = $this->approvedSuffix;

        return new class ($httpClient, $customerId, $approvedSuffix) {
            public function __construct(
                private SmartOrderHttpClient $httpClient,
                private int $customerId,
                private string $approvedSuffix
            ) {
            }

            public function getAllProducts(array $parameters = []): array
            {
                return $this->httpClient->get('smartorder/MyProductCatalog', $parameters);
            }

            public function getAllCalendarProducts(): array
            {
                return $this->httpClient->get('smartorder/MyProductCatalog', []);
            }

            public function getAllCalendarProductsWithPrices(string $code): array
            {
                return $this->httpClient->get('smartorder/MyProductCatalog', ['ProductCode' => $code]);
            }

            public function findEvents(array $parameters): array
            {
                return $this->httpClient->post('smartorder/FindEvents', $parameters);
            }

            public function getFindEventsData(string $plu, string $date): array
            {
                return $this->httpClient->post('smartorder/FindEvents', [
                    'ProductID' => $plu,
                    'EventDate' => $date,
                ]);
            }

            public function getProductAvailability(string $productId, string $date): array
            {
                return $this->httpClient->post('smartorder/FindEvents', [
                    'ProductID' => $productId,
                    'EventDate' => $date,
                ]);
            }

            public function createBooking(array $bookingData): array
            {
                $orderData = array_merge([
                    'CustomerID' => $this->customerId,
                    'ApprovedSuffix' => $this->approvedSuffix,
                ], $bookingData);

                return $this->httpClient->post('smartorder/PlaceOrder', $orderData);
            }

            public function getBookingDetails(string $bookingId): array
            {
                return $this->httpClient->get('smartorder/GetExistingOrderId', [
                    'OrderID' => $bookingId,
                ]);
            }

            public function cancelBooking(string $bookingId, string $reason = ''): array
            {
                // First check if the order can be cancelled
                $canCancel = $this->httpClient->get('smartorder/CanCancelOrder', [
                    'OrderID' => $bookingId,
                ]);

                if (! ($canCancel['CanCancel'] ?? false)) {
                    return ['error' => ['message' => 'Order cannot be cancelled']];
                }

                // Cancel the order
                return $this->httpClient->get('smartorder/CancelOrder', [
                    'OrderID' => $bookingId,
                ]);
            }

            public function getAvailableMonths(): array
            {
                return collect(range(0, 11))
                    ->map(function ($i) {
                        $month = now()->startOfMonth()->addMonths($i);

                        return [
                            'class' => $month->format('Y-m'),   // \"2024-07\"
                            'text' => $month->format('F, Y'),   // \"July 2024\"
                            'value' => $month->format('Y-m-d'), // \"2024-07-01\"
                        ];
                    })
                    ->toArray();
            }
        };
    }

    public function getName(): string
    {
        return 'smartorder';
    }

    public function getProvider(): string
    {
        return 'smartorder';
    }

    protected function getHttpClientConfig(): array
    {
        return [
            'base_uri' => $this->getConfig('base_url', 'https://api.smartorder.com'),
            'timeout' => $this->getConfig('timeout', 30),
            'connect_timeout' => $this->getConfig('connect_timeout', 5),
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->getConfig('api_key'),
            ],
        ];
    }

    protected function getRequiredConfigKeys(): array
    {
        return ['api_key', 'api_secret', 'base_url'];
    }

    protected function performConnectionTest(): bool
    {
        try {
            // Test by fetching calendar products
            $result = $this->client->getAllCalendarProducts();

            return ! empty($result) || is_array($result);
        } catch (\Exception $e) {
            return false;
        }
    }

    public function syncProducts(): ProductSyncResult
    {
        $this->validateRequiredConfig();
        $startTime = time();

        try {
            $this->logOperation('sync_products_start');

            // Get all calendar products from SmartOrder
            $products = $this->client->getAllProducts();

            $syncedCount = 0;
            $skippedCount = 0;
            $failedCount = 0;
            $warnings = [];

            foreach ($products as $productData) {
                try {
                    // Filter for specific sales program if configured
                    $salesProgramId = $this->getConfig('sales_program_id', 4638);
                    if (isset($productData['salesProgramId']) && $productData['salesProgramId'] != $salesProgramId) {
                        $skippedCount++;

                        continue;
                    }

                    $product = $this->transformProductData($productData);

                    // Here you would save to your local database
                    // This is application-specific logic
                    $syncedCount++;

                } catch (\Exception $e) {
                    $failedCount++;
                    Log::warning("Failed to sync SmartOrder product", [
                        'adapter' => $this->getName(),
                        'product_id' => $productData['id'] ?? 'unknown',
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $duration = time() - $startTime;
            $this->setLastSyncTimestamp(time());

            $this->logOperation('sync_products_complete', [
                'synced' => $syncedCount,
                'skipped' => $skippedCount,
                'failed' => $failedCount,
                'duration' => $duration,
            ]);

            return ProductSyncResult::success(
                totalProducts: count($products),
                syncedProducts: $syncedCount,
                skippedProducts: $skippedCount,
                failedProducts: $failedCount,
                warnings: $warnings,
                syncDuration: $duration
            );

        } catch (\Exception $e) {
            $this->logError('sync_products', $e);

            return ProductSyncResult::failure([$e->getMessage()]);
        }
    }

    public function getProduct(string $remoteId): ?Product
    {
        try {
            // SmartOrder doesn't have a direct get product endpoint
            // We need to search through all products
            $products = $this->client->getAllProducts();

            foreach ($products as $productData) {
                if (($productData['id'] ?? '') === $remoteId || ($productData['plu'] ?? '') === $remoteId) {
                    return $this->transformProductData($productData);
                }
            }

            return null;

        } catch (\Exception $e) {
            $this->logError('get_product', $e, ['product_id' => $remoteId]);

            return null;
        }
    }

    public function searchProducts(array $criteria = []): Collection
    {
        try {
            $products = $this->client->getAllProducts();
            $results = new Collection();

            foreach ($products as $productData) {
                $product = $this->transformProductData($productData);

                // Apply search criteria
                $matches = true;

                if (isset($criteria['code']) && ! Str::startsWith($product->remoteId, $criteria['code'])) {
                    $matches = false;
                }

                if (isset($criteria['category']) && $product->category !== $criteria['category']) {
                    $matches = false;
                }

                if (isset($criteria['active']) && $product->isActive !== $criteria['active']) {
                    $matches = false;
                }

                if ($matches) {
                    $results->push($product);
                }
            }

            return $results;

        } catch (\Exception $e) {
            $this->logError('search_products', $e, $criteria);

            return new Collection();
        }
    }

    public function getAvailableTimeSlots(string $productId, string $date): Collection
    {
        try {
            // For SmartOrder, time slots are typically event-based
            $events = $this->client->findEvents([
                'plu' => $productId,
                'date' => $date,
            ]);

            $timeSlots = new Collection();

            if (isset($events['eventResults'])) {
                foreach ($events['eventResults'] as $event) {
                    $timeSlots->push([
                        'time' => Carbon::parse($event['startTime'])->format('H:i'),
                        'event_id' => $event['id'],
                        'capacity' => $event['capacityAvailable'],
                        'price' => $event['price'] ?? null,
                    ]);
                }
            }

            return $timeSlots;

        } catch (\Exception $e) {
            $this->logError('get_available_time_slots', $e, [
                'product_id' => $productId,
                'date' => $date,
            ]);

            throw new AdapterException("Failed to get time slots: {$e->getMessage()}");
        }
    }

    public function checkAvailability(string $productId, string $date, string $time = null, int $quantity = 1): bool
    {
        try {
            // Check event capacity for special events (Universal Studios pattern)
            if (Str::startsWith($productId, '1701') || Str::startsWith($productId, '11011700')) {
                $event = $this->client->getFindEventsData($productId, $date);

                if (! Arr::get($event, 'success')) {
                    return false;
                }

                $capacityAvailable = Arr::get($event, 'eventResults.0.capacityAvailable', 0);

                return $capacityAvailable >= $quantity;
            }

            // For regular products, check general availability
            $availability = $this->client->getProductAvailability($productId, $date);

            return Arr::get($availability, 'available', false);

        } catch (\Exception $e) {
            $this->logError('check_availability', $e, [
                'product_id' => $productId,
                'date' => $date,
                'time' => $time,
                'quantity' => $quantity,
            ]);

            return false;
        }
    }

    public function getPricing(string $productId, string $date, array $options = []): array
    {
        try {
            $products = $this->client->getAllCalendarProductsWithPrices($productId);
            $pricing = [];

            foreach ($products as $product) {
                if (Str::startsWith($product['plu'], $productId)) {
                    $pricing[] = [
                        'plu' => $product['plu'],
                        'price' => $product['price'] ?? null,
                        'currency' => 'USD',
                        'date' => $date,
                    ];
                }
            }

            return $pricing;

        } catch (\Exception $e) {
            $this->logError('get_pricing', $e, [
                'product_id' => $productId,
                'date' => $date,
                'options' => $options,
            ]);

            throw new AdapterException("Failed to get pricing: {$e->getMessage()}");
        }
    }

    public function createBooking(BookingRequest $request): BookingResponse
    {
        try {
            $this->validateRequiredConfig();

            // Check availability before booking
            if (! $this->checkAvailability($request->productId, $request->date->format('Y-m-d'), $request->timeSlot, $request->quantity)) {
                throw new BookingException('Product not available for selected date/time/quantity');
            }

            // Build booking data for SmartOrder
            $bookingData = $this->transformBookingRequestData($request);

            $result = $this->client->createBooking($bookingData);

            if (isset($result['error'])) {
                throw new BookingException($result['error']['message'] ?? 'Booking creation failed');
            }

            $bookingId = Arr::get($result, 'booking.id') ?? Arr::get($result, 'id');

            return BookingResponse::success([
                'booking_id' => $bookingId,
                'status' => 'confirmed',
                'booking_date' => $request->date,
                'time_slot' => $request->timeSlot,
                'quantity' => $request->quantity,
                'customer_info' => $request->customerInfo,
                'metadata' => [
                    'booking_details' => $result,
                    'provider' => 'smartorder',
                ],
            ]);

        } catch (\Exception $e) {
            $this->logError('create_booking', $e, [
                'product_id' => $request->productId,
                'date' => $request->date->format('Y-m-d'),
            ]);

            return BookingResponse::error($e->getMessage());
        }
    }

    public function confirmBooking(string $reservationId, array $paymentData = []): BookingResponse
    {
        // SmartOrder typically creates confirmed bookings directly
        // This method would handle any confirmation steps if needed
        try {
            $booking = $this->getBooking($reservationId);

            if (! $booking) {
                throw new BookingException('Booking not found');
            }

            return BookingResponse::success([
                'booking_id' => $reservationId,
                'status' => 'confirmed',
                'metadata' => $booking->metadata,
            ]);

        } catch (\Exception $e) {
            $this->logError('confirm_booking', $e, [
                'reservation_id' => $reservationId,
            ]);

            return BookingResponse::error($e->getMessage());
        }
    }

    public function cancelBooking(string $bookingId, string $reason = ''): BookingResponse
    {
        try {
            // SmartOrder cancellation logic
            $result = $this->client->cancelBooking($bookingId, $reason);

            if (isset($result['error'])) {
                throw new BookingException($result['error']['message'] ?? 'Cancellation failed');
            }

            return BookingResponse::cancelled([
                'booking_id' => $bookingId,
                'cancellation_info' => [
                    'reason' => $reason,
                    'cancelled_at' => Carbon::now()->toISOString(),
                ],
            ]);

        } catch (\Exception $e) {
            $this->logError('cancel_booking', $e, [
                'booking_id' => $bookingId,
                'reason' => $reason,
            ]);

            return BookingResponse::error($e->getMessage());
        }
    }

    public function getBooking(string $bookingId): ?BookingResponse
    {
        try {
            $result = $this->client->getBookingDetails($bookingId);

            if (isset($result['error']) || empty($result)) {
                return null;
            }

            return BookingResponse::success([
                'booking_id' => $bookingId,
                'status' => 'confirmed',
                'metadata' => [
                    'booking_details' => $result,
                    'provider' => 'smartorder',
                ],
            ]);

        } catch (\Exception $e) {
            $this->logError('get_booking', $e, [
                'booking_id' => $bookingId,
            ]);

            return null;
        }
    }

    public function generateVoucher(string $bookingId): VoucherData
    {
        try {
            $booking = $this->getBooking($bookingId);
            if (! $booking) {
                throw new AdapterException("Booking not found: {$bookingId}");
            }

            $voucherNumber = $this->generateVoucherNumber($bookingId);
            $qrCode = $this->generateQRCode($bookingId);
            $barcodeData = $this->generateBarcodeData($bookingId);

            return new VoucherData(
                bookingId: $bookingId,
                voucherNumber: $voucherNumber,
                qrCode: $qrCode,
                barcodeData: $barcodeData,
                customerInfo: $booking->customerInfo ?? [],
                productInfo: $booking->productInfo ?? [],
                bookingDetails: [
                    'date' => $booking->bookingDate?->format('Y-m-d'),
                    'time_slot' => $booking->timeSlot,
                    'quantity' => $booking->quantity,
                ],
                metadata: [
                    'provider' => 'smartorder',
                    'generated_at' => Carbon::now()->toISOString(),
                ]
            );

        } catch (\Exception $e) {
            $this->logError('generate_voucher', $e, [
                'booking_id' => $bookingId,
            ]);

            throw new AdapterException("Failed to generate voucher: {$e->getMessage()}");
        }
    }

    protected function transformProductData(array $rawData): Product
    {
        return new Product(
            remoteId: $rawData['plu'] ?? $rawData['id'] ?? '',
            name: $rawData['name'] ?? $rawData['title'] ?? '',
            description: $rawData['description'] ?? '',
            provider: 'smartorder',
            category: $rawData['category'] ?? 'general',
            pricing: [
                'base' => [
                    'amount' => $rawData['price'] ?? null,
                    'currency' => 'USD',
                ],
            ],
            options: [
                'sales_program_id' => $rawData['salesProgramId'] ?? null,
                'event_type' => $rawData['eventType'] ?? null,
            ],
            isActive: $rawData['active'] ?? true,
            imageUrl: $rawData['image'] ?? null,
            metadata: [
                'provider' => 'smartorder',
                'raw_data' => $rawData,
            ],
            lastUpdated: Carbon::now()
        );
    }

    protected function transformBookingRequestData(BookingRequest $request): array
    {
        return [
            'plu' => $request->productId,
            'date' => $request->date->format('Y-m-d'),
            'time' => $request->timeSlot,
            'quantity' => $request->quantity,
            'customer' => $request->customerInfo,
            'options' => $request->options,
            'metadata' => $request->metadata,
        ];
    }

    protected function generateVoucherNumber(string $bookingId): string
    {
        return 'SO-' . strtoupper(Str::random(6)) . '-' . substr($bookingId, -4);
    }

    protected function generateQRCode(string $bookingId): string
    {
        return "QR-SO-{$bookingId}";
    }

    protected function generateBarcodeData(string $bookingId): string
    {
        return "BC-SO-{$bookingId}";
    }
}
