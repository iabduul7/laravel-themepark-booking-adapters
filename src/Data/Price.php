<?php

namespace iabduul7\ThemeParkBooking\Data;

use Carbon\Carbon;

class Price
{
    public function __construct(
        public readonly string $id,
        public readonly ?string $rateId = null,
        public readonly ?string $productId = null,
        public readonly ?Carbon $date = null,
        public readonly ?string $timeSlot = null,
        public readonly float $basePrice = 0.0,
        public readonly float $totalPrice = 0.0,
        public readonly array $taxes = [],
        public readonly array $fees = [],
        public readonly array $discounts = [],
        public readonly string $currency = 'USD',
        public readonly ?int $capacity = null,
        public readonly ?int $available = null,
        public readonly bool $isAvailable = true,
        public readonly ?string $priceType = null,
        public readonly array $ageGroups = [],
        public readonly array $metadata = [],
        public readonly array $rawData = []
    ) {
    }

    public static function fromRedeamPriceData(array $data): self
    {
        return new self(
            id: $data['id'] ?? uniqid('price_'),
            rateId: $data['rateId'] ?? null,
            productId: $data['productId'] ?? null,
            date: isset($data['date']) ? Carbon::parse($data['date']) : null,
            timeSlot: $data['timeSlot'] ?? null,
            basePrice: (float) ($data['basePrice'] ?? $data['price'] ?? 0),
            totalPrice: (float) ($data['totalPrice'] ?? $data['total'] ?? $data['basePrice'] ?? $data['price'] ?? 0),
            taxes: $data['taxes'] ?? [],
            fees: $data['fees'] ?? [],
            discounts: $data['discounts'] ?? [],
            currency: $data['currency'] ?? 'USD',
            capacity: $data['capacity'] ?? null,
            available: $data['available'] ?? null,
            isAvailable: $data['isAvailable'] ?? true,
            priceType: $data['priceType'] ?? 'standard',
            ageGroups: $data['ageGroups'] ?? [],
            metadata: $data['metadata'] ?? [],
            rawData: $data
        );
    }

    public static function fromSmartOrderPriceData(array $data): self
    {
        return new self(
            id: $data['PriceID'] ?? uniqid('price_'),
            productId: $data['ProductID'] ?? null,
            date: isset($data['Date']) ? Carbon::parse($data['Date']) : null,
            basePrice: (float) ($data['BasePrice'] ?? 0),
            totalPrice: (float) ($data['TotalPrice'] ?? $data['Price'] ?? $data['BasePrice'] ?? 0),
            currency: $data['Currency'] ?? 'USD',
            available: $data['AvailableQuantity'] ?? null,
            isAvailable: $data['IsAvailable'] ?? true,
            priceType: $data['PriceType'] ?? 'standard',
            rawData: $data
        );
    }

    public static function fromAvailabilityData(array $availabilityData, ?string $rateId = null): self
    {
        return new self(
            id: $availabilityData['id'] ?? uniqid('avail_'),
            rateId: $rateId,
            date: isset($availabilityData['start']) ? Carbon::parse($availabilityData['start']) : null,
            timeSlot: isset($availabilityData['start']) ? Carbon::parse($availabilityData['start'])->format('H:i') : null,
            capacity: $availabilityData['capacity'] ?? null,
            available: $availabilityData['available'] ?? $availabilityData['capacity'] ?? null,
            isAvailable: ($availabilityData['available'] ?? 0) > 0,
            rawData: $availabilityData
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'rate_id' => $this->rateId,
            'product_id' => $this->productId,
            'date' => $this->date?->toDateString(),
            'time_slot' => $this->timeSlot,
            'base_price' => $this->basePrice,
            'total_price' => $this->totalPrice,
            'taxes' => $this->taxes,
            'fees' => $this->fees,
            'discounts' => $this->discounts,
            'currency' => $this->currency,
            'capacity' => $this->capacity,
            'available' => $this->available,
            'is_available' => $this->isAvailable,
            'price_type' => $this->priceType,
            'age_groups' => $this->ageGroups,
            'metadata' => $this->metadata,
            'raw_data' => $this->rawData,
        ];
    }

    public function getFormattedPrice(bool $includeCurrency = true): string
    {
        $formatted = number_format($this->totalPrice, 2);
        if ($includeCurrency) {
            return $this->currency . ' ' . $formatted;
        }

        return $formatted;
    }

    public function getFormattedBasePrice(bool $includeCurrency = true): string
    {
        $formatted = number_format($this->basePrice, 2);

        if ($includeCurrency) {
            return $this->currency . ' ' . $formatted;
        }

        return $formatted;
    }

    public function getTaxAmount(): float
    {
        return array_sum(array_column($this->taxes, 'amount'));
    }

    public function getFeeAmount(): float
    {
        return array_sum(array_column($this->fees, 'amount'));
    }

    public function getDiscountAmount(): float
    {
        return array_sum(array_column($this->discounts, 'amount'));
    }

    public function hasTaxes(): bool
    {
        return ! empty($this->taxes);
    }

    public function hasFees(): bool
    {
        return ! empty($this->fees);
    }

    public function hasDiscounts(): bool
    {
        return ! empty($this->discounts);
    }

    public function isAvailableForQuantity(int $quantity): bool
    {
        if (! $this->isAvailable) {
            return false;
        }

        if ($this->available !== null) {
            return $this->available >= $quantity;
        }

        if ($this->capacity !== null) {
            return $this->capacity >= $quantity;
        }

        return true;
    }

    public function getRemainingCapacity(): ?int
    {
        if ($this->available !== null) {
            return $this->available;
        }

        return $this->capacity;
    }

    public function isForDate(Carbon $date): bool
    {
        return $this->date && $this->date->isSameDay($date);
    }

    public function isForTimeSlot(string $timeSlot): bool
    {
        return $this->timeSlot === $timeSlot;
    }

    public function getPriceBreakdown(): array
    {
        return [
            'base_price' => $this->basePrice,
            'tax_amount' => $this->getTaxAmount(),
            'fee_amount' => $this->getFeeAmount(),
            'discount_amount' => $this->getDiscountAmount(),
            'total_price' => $this->totalPrice,
            'currency' => $this->currency,
        ];
    }

    public function getDateTime(): ?Carbon
    {
        if (! $this->date) {
            return null;
        }

        if ($this->timeSlot) {
            return $this->date->copy()->setTimeFromTimeString($this->timeSlot);
        }

        return $this->date;
    }

    public function getMetadata(string $key, $default = null)
    {
        return $this->metadata[$key] ?? $default;
    }

    public function hasAgeGroupPricing(): bool
    {
        return ! empty($this->ageGroups);
    }

    public function getPriceForAgeGroup(string $ageGroup): ?float
    {
        return $this->ageGroups[$ageGroup] ?? null;
    }
}
