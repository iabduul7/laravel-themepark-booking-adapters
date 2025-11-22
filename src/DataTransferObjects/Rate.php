<?php

namespace Iabduul7\ThemeParkAdapters\DataTransferObjects;

class Rate
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly float $price,
        public readonly string $currency,
        public readonly array $metadata = [],
    ) {
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'price' => $this->price,
            'currency' => $this->currency,
            'metadata' => $this->metadata,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            name: $data['name'] ?? '',
            price: (float) $data['price'],
            currency: $data['currency'] ?? 'USD',
            metadata: $data,
        );
    }
}
