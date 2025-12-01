<?php

namespace iabduul7\ThemeParkBooking\Data;

use Carbon\Carbon;

class BookingResponse
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $bookingId = null,
        public readonly ?string $reservationId = null,
        public readonly ?string $holdId = null,
        public readonly string $status = 'pending',
        public readonly ?array $customerInfo = null,
        public readonly ?array $productInfo = null,
        public readonly ?Carbon $bookingDate = null,
        public readonly ?Carbon $expiresAt = null,
        public readonly ?string $timeSlot = null,
        public readonly ?int $quantity = null,
        public readonly ?array $pricing = null,
        public readonly ?array $vouchers = null,
        public readonly ?string $confirmationCode = null,
        public readonly ?string $supplierReference = null,
        public readonly ?array $cancellationInfo = null,
        public readonly ?array $timeline = null,
        public readonly ?string $errorMessage = null,
        public readonly ?string $errorCode = null,
        public readonly string $provider = 'unknown',
        public readonly array $rawResponse = [],
        public readonly array $metadata = [],
        public readonly ?Carbon $createdAt = null,
        public readonly ?Carbon $updatedAt = null
    ) {}

    public static function success(array $data): self
    {
        return new self(
            success: true,
            bookingId: $data['booking_id'] ?? null,
            reservationId: $data['reservation_id'] ?? null,
            holdId: $data['hold_id'] ?? null,
            status: $data['status'] ?? 'confirmed',
            customerInfo: $data['customer_info'] ?? null,
            productInfo: $data['product_info'] ?? null,
            bookingDate: isset($data['booking_date']) ? Carbon::parse($data['booking_date']) : null,
            expiresAt: isset($data['expires_at']) ? Carbon::parse($data['expires_at']) : null,
            timeSlot: $data['time_slot'] ?? null,
            quantity: $data['quantity'] ?? null,
            pricing: $data['pricing'] ?? null,
            vouchers: $data['vouchers'] ?? null,
            confirmationCode: $data['confirmation_code'] ?? null,
            supplierReference: $data['supplier_reference'] ?? null,
            timeline: $data['timeline'] ?? null,
            provider: $data['provider'] ?? 'unknown',
            rawResponse: $data['raw_response'] ?? [],
            metadata: $data['metadata'] ?? [],
            createdAt: isset($data['created_at']) ? Carbon::parse($data['created_at']) : null,
            updatedAt: isset($data['updated_at']) ? Carbon::parse($data['updated_at']) : null
        );
    }

    public static function error(string $message, ?string $code = null, array $metadata = []): self
    {
        return new self(
            success: false,
            errorMessage: $message,
            errorCode: $code,
            metadata: $metadata
        );
    }

    public static function cancelled(array $data): self
    {
        return new self(
            success: true,
            bookingId: $data['booking_id'] ?? null,
            status: 'cancelled',
            cancellationInfo: $data['cancellation_info'] ?? null,
            timeline: $data['timeline'] ?? null,
            provider: $data['provider'] ?? 'unknown',
            rawResponse: $data['raw_response'] ?? [],
            metadata: $data['metadata'] ?? []
        );
    }

    public static function hold(array $data): self
    {
        return new self(
            success: true,
            holdId: $data['hold_id'] ?? null,
            reservationId: $data['reservation_id'] ?? $data['hold_id'] ?? null,
            status: 'hold',
            expiresAt: isset($data['expires_at']) ? Carbon::parse($data['expires_at']) : null,
            productInfo: $data['product_info'] ?? null,
            bookingDate: isset($data['booking_date']) ? Carbon::parse($data['booking_date']) : null,
            timeSlot: $data['time_slot'] ?? null,
            quantity: $data['quantity'] ?? null,
            pricing: $data['pricing'] ?? null,
            provider: $data['provider'] ?? 'unknown',
            rawResponse: $data['raw_response'] ?? [],
            metadata: $data['metadata'] ?? []
        );
    }

    public static function fromRedeamHold(array $holdData): self
    {
        $hold = $holdData['hold'] ?? $holdData;
        
        return self::hold([
            'hold_id' => $hold['id'] ?? null,
            'expires_at' => $hold['expires'] ?? null,
            'quantity' => count($hold['items'] ?? []),
            'provider' => 'redeam',
            'raw_response' => $holdData
        ]);
    }

    public static function fromRedeamBooking(array $bookingData): self
    {
        $booking = $bookingData['booking'] ?? $bookingData;
        
        return self::success([
            'booking_id' => $booking['id'] ?? null,
            'status' => 'confirmed',
            'supplier_reference' => $booking['ext']['supplier']['reference'] ?? null,
            'customer_info' => $booking['customer'] ?? null,
            'timeline' => $booking['timeline'] ?? null,
            'provider' => 'redeam',
            'raw_response' => $bookingData
        ]);
    }

    public static function fromSmartOrderBooking(array $orderData): self
    {
        return self::success([
            'booking_id' => $orderData['OrderID'] ?? $orderData['order_id'] ?? null,
            'confirmation_code' => $orderData['ConfirmationNumber'] ?? null,
            'status' => 'confirmed',
            'quantity' => $orderData['Quantity'] ?? null,
            'pricing' => [
                'total' => $orderData['TotalPrice'] ?? null,
                'currency' => $orderData['Currency'] ?? 'USD'
            ],
            'provider' => 'smartorder',
            'raw_response' => $orderData
        ]);
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'booking_id' => $this->bookingId,
            'reservation_id' => $this->reservationId,
            'hold_id' => $this->holdId,
            'status' => $this->status,
            'customer_info' => $this->customerInfo,
            'product_info' => $this->productInfo,
            'booking_date' => $this->bookingDate?->toISOString(),
            'expires_at' => $this->expiresAt?->toISOString(),
            'time_slot' => $this->timeSlot,
            'quantity' => $this->quantity,
            'pricing' => $this->pricing,
            'vouchers' => $this->vouchers,
            'confirmation_code' => $this->confirmationCode,
            'supplier_reference' => $this->supplierReference,
            'cancellation_info' => $this->cancellationInfo,
            'timeline' => $this->timeline,
            'error_message' => $this->errorMessage,
            'error_code' => $this->errorCode,
            'provider' => $this->provider,
            'raw_response' => $this->rawResponse,
            'metadata' => $this->metadata,
            'created_at' => $this->createdAt?->toISOString(),
            'updated_at' => $this->updatedAt?->toISOString(),
        ];
    }

    public function isSuccessful(): bool
    {
        return $this->success;
    }

    public function isConfirmed(): bool
    {
        return $this->success && $this->status === 'confirmed';
    }

    public function isPending(): bool
    {
        return $this->success && $this->status === 'pending';
    }

    public function isHold(): bool
    {
        return $this->success && $this->status === 'hold';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function isExpired(): bool
    {
        return $this->expiresAt && $this->expiresAt->isPast();
    }

    public function hasVouchers(): bool
    {
        return !empty($this->vouchers);
    }

    public function getTotalPrice(): ?float
    {
        return $this->pricing['total'] ?? null;
    }

    public function getCurrency(): string
    {
        return $this->pricing['currency'] ?? 'USD';
    }

    public function getCustomerName(): ?string
    {
        if (!$this->customerInfo) {
            return null;
        }

        return trim(($this->customerInfo['first_name'] ?? $this->customerInfo['firstName'] ?? '') . ' ' . 
                   ($this->customerInfo['last_name'] ?? $this->customerInfo['lastName'] ?? ''));
    }

    public function getProductName(): ?string
    {
        return $this->productInfo['name'] ?? null;
    }

    public function getReservationId(): ?string
    {
        return $this->reservationId ?? $this->holdId ?? $this->bookingId;
    }
}