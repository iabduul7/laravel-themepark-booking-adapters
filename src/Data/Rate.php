<?php

namespace iabduul7\ThemeParkBooking\Data;

use Carbon\Carbon;

class Rate
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $code,
        public readonly ?string $optionId = null,
        public readonly ?string $productId = null,
        public readonly ?int $productDuration = null,
        public readonly ?Carbon $validFrom = null,
        public readonly ?Carbon $validUntil = null,
        public readonly ?string $description = null,
        public readonly array $pricing = [],
        public readonly array $restrictions = [],
        public readonly array $cancellationPolicy = [],
        public readonly bool $isActive = true,
        public readonly string $currency = 'USD',
        public readonly array $metadata = [],
        public readonly array $rawData = []
    ) {}

    public static function fromRedeamData(array $data): self
    {
        return new self(
            id: $data['id'] ?? '',
            name: $data['name'] ?? '',
            code: $data['code'] ?? '',
            optionId: $data['optionId'] ?? null,
            productId: $data['productId'] ?? null,
            productDuration: $data['ext']['disney-productDuration'] ?? null,
            validFrom: isset($data['valid']['from']) ? Carbon::parse($data['valid']['from']) : null,
            validUntil: isset($data['valid']['until']) ? Carbon::parse($data['valid']['until']) : null,
            description: $data['description'] ?? null,
            pricing: $data['pricing'] ?? [],
            restrictions: $data['restrictions'] ?? [],
            cancellationPolicy: $data['cancellationPolicy'] ?? [],
            isActive: $data['active'] ?? true,
            currency: $data['currency'] ?? 'USD',
            metadata: $data['ext'] ?? [],
            rawData: $data
        );
    }

    public static function fromSmartOrderData(array $data): self
    {
        return new self(
            id: $data['RateID'] ?? $data['rate_id'] ?? '',
            name: $data['RateName'] ?? $data['name'] ?? '',
            code: $data['RateCode'] ?? $data['code'] ?? '',
            productId: $data['ProductID'] ?? null,
            description: $data['Description'] ?? null,
            pricing: [
                'base_price' => $data['BasePrice'] ?? null,
                'total_price' => $data['TotalPrice'] ?? null,
            ],
            isActive: $data['IsActive'] ?? true,
            currency: $data['Currency'] ?? 'USD',
            rawData: $data
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'option_id' => $this->optionId,
            'product_id' => $this->productId,
            'product_duration' => $this->productDuration,
            'valid_from' => $this->validFrom?->toISOString(),
            'valid_until' => $this->validUntil?->toISOString(),
            'description' => $this->description,
            'pricing' => $this->pricing,
            'restrictions' => $this->restrictions,
            'cancellation_policy' => $this->cancellationPolicy,
            'is_active' => $this->isActive,
            'currency' => $this->currency,
            'metadata' => $this->metadata,
            'raw_data' => $this->rawData,
        ];
    }

    public function isValid(?Carbon $date = null): bool
    {
        $checkDate = $date ?? Carbon::now();
        
        if ($this->validFrom && $checkDate->isBefore($this->validFrom)) {
            return false;
        }
        
        if ($this->validUntil && $checkDate->isAfter($this->validUntil)) {
            return false;
        }
        
        return $this->isActive;
    }

    public function isValidForDateRange(Carbon $startDate, Carbon $endDate): bool
    {
        if ($this->validFrom && $endDate->isBefore($this->validFrom)) {
            return false;
        }
        
        if ($this->validUntil && $startDate->isAfter($this->validUntil)) {
            return false;
        }
        
        return $this->isActive;
    }

    public function getBasePrice(): ?float
    {
        return $this->pricing['base_price'] ?? $this->pricing['price'] ?? null;
    }

    public function getTotalPrice(): ?float
    {
        return $this->pricing['total_price'] ?? $this->pricing['total'] ?? $this->getBasePrice();
    }

    public function hasPricing(): bool
    {
        return !empty($this->pricing);
    }

    public function hasRestrictions(): bool
    {
        return !empty($this->restrictions);
    }

    public function hasCancellationPolicy(): bool
    {
        return !empty($this->cancellationPolicy);
    }

    public function getDurationInDays(): ?int
    {
        return $this->productDuration;
    }

    public function isMultiDay(): bool
    {
        return $this->productDuration && $this->productDuration > 1;
    }

    public function getValidityPeriod(): ?string
    {
        if (!$this->validFrom || !$this->validUntil) {
            return null;
        }
        
        return sprintf(
            '%s to %s',
            $this->validFrom->format('M j, Y'),
            $this->validUntil->format('M j, Y')
        );
    }

    public function getMetadata(string $key, $default = null)
    {
        return $this->metadata[$key] ?? $default;
    }
}