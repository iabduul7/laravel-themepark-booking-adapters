<?php

namespace iabduul7\ThemeParkBooking\Adapters;

use Carbon\Carbon;
use iabduul7\LaravelThemeparkBookingAdapters\Http\RedeamHttpClient;
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

class RedeamAdapter extends BaseAdapter
{
    protected string $parkType;
    protected $client;
    protected RedeamHttpClient $httpClient;

    public function __construct(string $parkType, array $config = [])
    {
        $this->parkType = $parkType;
        parent::__construct($config);
        $this->initializeClient();
    }

    protected function initializeClient(): void
    {
        // First try to use the new independent HTTP client
        try {
            $this->initializeIndependentClient();

            return;
        } catch (\Exception $e) {
            Log::info('Failed to initialize independent Redeam client, falling back to legacy client', [
                'error' => $e->getMessage(),
                'park_type' => $this->parkType,
            ]);
        }

        // Fallback to legacy client for backward compatibility
        $clientClass = $this->parkType === 'disney'
            ? '\CodeCreatives\LaravelRedeam\LaravelRedeamForWaltDisney'
            : '\CodeCreatives\LaravelRedeam\LaravelRedeamForUnitedParks';

        if (class_exists($clientClass)) {
            $this->client = app($clientClass);

            return;
        }

        throw new ConfigurationException(
            "Neither independent HTTP client nor legacy Redeam client could be initialized. " .
            "Please ensure proper configuration is provided for the Redeam API."
        );
    }

    protected function initializeIndependentClient(): void
    {
        $baseUrl = $this->getConfig('base_url') ?? 'https://booking.redeam.io/v1.2';
        $apiKey = $this->getConfig('api_key');
        $apiSecret = $this->getConfig('api_secret');
        $timeout = $this->getConfig('timeout', 600);

        if (! $apiKey || ! $apiSecret) {
            throw new ConfigurationException('api_key and api_secret are required for independent Redeam client');
        }

        $this->httpClient = new RedeamHttpClient(
            baseUrl: $baseUrl,
            apiKey: $apiKey,
            apiSecret: $apiSecret,
            timeout: $timeout
        );

        // Create a wrapper client that mimics the legacy client interface
        $this->client = $this->createClientWrapper();
    }

    protected function createClientWrapper(): object
    {
        $parkType = $this->parkType;
        $httpClient = $this->httpClient;
        $supplierId = $this->getConfig('supplier_id');

        return new class ($httpClient, $parkType, $supplierId) {
            public function __construct(
                private RedeamHttpClient $httpClient,
                private string $parkType,
                private ?string $supplierId
            ) {
            }

            public function getAllProducts(array $parameters = []): array
            {
                if ($this->parkType === 'disney' && $this->supplierId) {
                    $response = $this->httpClient->get("suppliers/{$this->supplierId}/products", $parameters);

                    return $response['products'] ?? [];
                }

                // For United Parks or when no supplier_id
                $response = $this->httpClient->get('suppliers', $parameters);

                return $response['suppliers'] ?? [];
            }

            public function checkAvailabilities(string $productId, string $startDate, string $endDate, array $parameters = []): array
            {
                if ($this->parkType === 'disney' && $this->supplierId) {
                    return $this->httpClient->get("suppliers/{$this->supplierId}/products/{$productId}/availability", array_merge([
                        'from' => $startDate,
                        'to' => $endDate,
                    ], $parameters));
                }

                // For United Parks - first parameter is supplierId
                $supplierId = $productId; // In United Parks, first param is supplierId
                $actualProductId = $startDate; // Second param is productId
                $actualStartDate = $endDate; // Third param is startDate
                $actualEndDate = $parameters[0] ?? $endDate; // Fourth param is endDate

                return $this->httpClient->get("suppliers/{$supplierId}/products/{$actualProductId}/availability", [
                    'from' => $actualStartDate,
                    'to' => $actualEndDate,
                ]);
            }

            public function createNewHold(array $holdData): array
            {
                if ($this->supplierId) {
                    return $this->httpClient->post("suppliers/{$this->supplierId}/holds", $holdData);
                }

                return $this->httpClient->post('holds', $holdData);
            }

            public function getHold(string $holdId): array
            {
                if ($this->supplierId) {
                    return $this->httpClient->get("suppliers/{$this->supplierId}/holds/{$holdId}");
                }

                return $this->httpClient->get("holds/{$holdId}");
            }

            public function createNewBooking(array $bookingData): array
            {
                if ($this->supplierId) {
                    return $this->httpClient->post("suppliers/{$this->supplierId}/bookings", $bookingData);
                }

                return $this->httpClient->post('bookings', $bookingData);
            }

            public function getBooking(string $bookingId): array
            {
                if ($this->supplierId) {
                    return $this->httpClient->get("suppliers/{$this->supplierId}/bookings/{$bookingId}");
                }

                return $this->httpClient->get("bookings/{$bookingId}");
            }

            public function deleteBooking(string $bookingId): array
            {
                if ($this->supplierId) {
                    return $this->httpClient->delete("suppliers/{$this->supplierId}/bookings/{$bookingId}");
                }

                return $this->httpClient->delete("bookings/{$bookingId}");
            }
        };
    }

    public function getName(): string
    {
        return "redeam_{$this->parkType}";
    }

    public function getProvider(): string
    {
        return 'redeam';
    }

    protected function getHttpClientConfig(): array
    {
        return [
            'timeout' => $this->getConfig('timeout', 30),
            'connect_timeout' => $this->getConfig('connect_timeout', 5),
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ];
    }

    protected function getRequiredConfigKeys(): array
    {
        $baseKeys = ['api_key', 'environment'];

        if ($this->parkType === 'disney') {
            $baseKeys[] = 'supplier_id';
        }

        return $baseKeys;
    }

    protected function performConnectionTest(): bool
    {
        // Test connection by checking availability for a known product
        $testProductId = $this->getConfig('test_product_id');
        if (! $testProductId) {
            return true; // Skip test if no test product configured
        }

        try {
            $result = $this->getAvailabilityProducts(
                $testProductId,
                Carbon::now()->format('Y-m-d'),
                Carbon::now()->addDay()->format('Y-m-d')
            );

            return ! isset($result['error']);
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

            // For Redeam, we would typically need to call their product catalog endpoint
            // This is a placeholder - the actual implementation depends on Redeam's API
            $products = $this->fetchProductCatalog();

            $syncedCount = 0;
            $skippedCount = 0;
            $failedCount = 0;
            $warnings = [];

            foreach ($products as $productData) {
                try {
                    $product = $this->transformProductData($productData);

                    // Here you would save to your local database
                    // This is application-specific logic
                    $syncedCount++;

                } catch (\Exception $e) {
                    $failedCount++;
                    Log::warning("Failed to sync product", [
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

    protected function fetchProductCatalog(): array
    {
        // This would be the actual API call to fetch products from Redeam
        // For now, returning empty array as this depends on Redeam's specific API
        return [];
    }

    public function getProduct(string $remoteId): ?Product
    {
        // Implementation would depend on Redeam's get product endpoint
        // This is a placeholder
        return null;
    }

    public function searchProducts(array $criteria = []): Collection
    {
        // Implementation would depend on Redeam's search capabilities
        return new Collection();
    }

    public function getAvailableTimeSlots(string $productId, string $date): Collection
    {
        try {
            $result = $this->getAvailabilityProducts(
                $productId,
                $date,
                $date
            );

            if (isset($result['error'])) {
                throw new AdapterException($result['error']['message']);
            }

            $timeSlots = new Collection();
            $availabilities = Arr::get($result, 'availabilities.byRate', []);

            foreach ($availabilities as $rateId => $rateData) {
                $availability = Arr::get($rateData, 'availability', []);

                foreach ($availability as $slot) {
                    if (Carbon::parse($slot['start'])->format('Y-m-d') === $date) {
                        $timeSlots->push([
                            'time' => Carbon::parse($slot['start'])->format('H:i'),
                            'availability_id' => $slot['id'],
                            'capacity' => $slot['capacity'],
                            'rate_id' => $rateId,
                        ]);
                    }
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
            $result = $this->getAvailabilityProducts(
                $productId,
                $date,
                $date
            );

            if (isset($result['error'])) {
                return false;
            }

            $availabilities = Arr::get($result, 'availabilities.byRate', []);

            foreach ($availabilities as $rateData) {
                $availability = Arr::get($rateData, 'availability', []);

                foreach ($availability as $slot) {
                    $slotDate = Carbon::parse($slot['start'])->format('Y-m-d');
                    $slotTime = Carbon::parse($slot['start'])->format('H:i');

                    if ($slotDate === $date && ($time === null || $slotTime === $time)) {
                        return $slot['capacity'] >= $quantity;
                    }
                }
            }

            return false;

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
            $result = $this->getAvailabilityProducts(
                $productId,
                $date,
                $date
            );

            if (isset($result['error'])) {
                throw new AdapterException($result['error']['message']);
            }

            $pricing = [];
            $availabilities = Arr::get($result, 'availabilities.byRate', []);

            foreach ($availabilities as $rateId => $rateData) {
                // Extract pricing information from rate data
                $pricing[$rateId] = [
                    'rate_id' => $rateId,
                    'price' => $rateData['price'] ?? null,
                    'currency' => $rateData['currency'] ?? 'USD',
                    'availability' => $rateData['availability'] ?? [],
                ];
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

            // First create a hold
            $holdItems = $this->getItemsForHold($request);
            $holdResult = $this->client->createNewHold(['hold' => $holdItems]);

            if (isset($holdResult['error'])) {
                throw new BookingException($holdResult['error']['message']);
            }

            $holdId = Arr::get($holdResult, 'hold.id');
            $holdExpires = Arr::get($holdResult, 'hold.expires');

            return BookingResponse::success([
                'reservation_id' => $holdId,
                'status' => 'pending',
                'booking_date' => $request->date,
                'time_slot' => $request->timeSlot,
                'quantity' => $request->quantity,
                'customer_info' => $request->customerInfo,
                'metadata' => [
                    'hold_expires_at' => $holdExpires,
                    'park_type' => $this->parkType,
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
        try {
            // Verify hold is still valid
            if (! $this->verifyHold($reservationId)) {
                throw new BookingException('Reservation has expired');
            }

            // Create booking data from hold
            $bookingData = $this->buildBookingDataFromHold($reservationId, $paymentData);

            $result = $this->client->createNewBooking(['booking' => $bookingData]);

            if (isset($result['error'])) {
                throw new BookingException($result['error']['message']);
            }

            $bookingId = Arr::get($result, 'booking.id');
            $bookingDetails = Arr::get($result, 'booking');

            return BookingResponse::success([
                'booking_id' => $bookingId,
                'reservation_id' => $reservationId,
                'status' => 'confirmed',
                'metadata' => [
                    'booking_details' => $bookingDetails,
                    'park_type' => $this->parkType,
                ],
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
            $result = $this->client->deleteBooking($bookingId);

            if (isset($result['error'])) {
                throw new BookingException($result['error']['message']);
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
            $result = $this->client->getBooking($bookingId);

            if (isset($result['error'])) {
                throw new AdapterException($result['error']['message']);
            }

            $booking = Arr::get($result, 'booking');
            if (! $booking) {
                return null;
            }

            return BookingResponse::success([
                'booking_id' => $bookingId,
                'status' => 'confirmed',
                'metadata' => [
                    'booking_details' => $booking,
                    'park_type' => $this->parkType,
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

            // Generate voucher data based on booking details
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
                    'park_type' => $this->parkType,
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

    protected function getAvailabilityProducts(string $productId, string $startDate, string $endDate): array
    {
        if ($this->parkType === 'disney') {
            return $this->client->checkAvailabilities(
                $productId,
                Carbon::parse($startDate)->toISOString(),
                Carbon::parse($endDate)->toISOString()
            );
        }

        // For United Parks, we need supplier ID
        $supplierId = $this->getConfig('supplier_id');
        if (! $supplierId) {
            throw new ConfigurationException('supplier_id is required for United Parks');
        }

        return $this->client->checkAvailabilities(
            $supplierId,
            $productId,
            Carbon::parse($startDate)->toISOString(),
            Carbon::parse($endDate)->toISOString()
        );
    }

    protected function getItemsForHold(BookingRequest $request): array
    {
        // This method would transform the BookingRequest into Redeam's hold format
        // Implementation depends on the specific requirements for each park type

        $holdItems = [];

        // Basic structure - would need to be enhanced based on actual requirements
        $holdItems['items'][] = [
            'productId' => $request->productId,
            'at' => $request->date->toISOString(),
            'quantity' => $request->quantity,
            'travelerType' => $request->getOption('age_group', 'adult'),
        ];

        return $holdItems;
    }

    protected function buildBookingDataFromHold(string $holdId, array $paymentData): array
    {
        // Implementation would depend on how hold data is retrieved and transformed
        // This is a placeholder
        return [
            'holdId' => $holdId,
            'customer' => $paymentData['customer'] ?? [],
        ];
    }

    protected function verifyHold(string $holdId): bool
    {
        try {
            $result = $this->client->getHold($holdId);

            if (isset($result['error'])) {
                return false;
            }

            $expires = Arr::get($result, 'hold.expires');

            return Carbon::parse($expires)->isFuture();

        } catch (\Exception $e) {
            return false;
        }
    }

    protected function generateVoucherNumber(string $bookingId): string
    {
        return 'VCH-' . strtoupper(Str::random(6)) . '-' . substr($bookingId, -4);
    }

    protected function generateQRCode(string $bookingId): string
    {
        // QR code generation logic
        return "QR-{$bookingId}";
    }

    protected function generateBarcodeData(string $bookingId): string
    {
        // Barcode generation logic
        return "BC-{$bookingId}";
    }

    protected function transformProductData(array $rawData): Product
    {
        // Transform Redeam product data to our standard Product format
        return new Product(
            remoteId: $rawData['id'],
            name: $rawData['name'],
            description: $rawData['description'] ?? '',
            provider: 'redeam',
            category: $rawData['category'] ?? 'general',
            pricing: $rawData['pricing'] ?? [],
            options: $rawData['options'] ?? [],
            isActive: $rawData['active'] ?? true,
            imageUrl: $rawData['image'] ?? null,
            metadata: [
                'park_type' => $this->parkType,
                'supplier_id' => $rawData['supplier_id'] ?? null,
            ],
            lastUpdated: Carbon::now()
        );
    }
}
