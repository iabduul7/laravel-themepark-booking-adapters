<?php

namespace Iabduul7\ThemeParkAdapters\DataTransferObjects;

class Product
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $description,
        public readonly float $price,
        public readonly string $currency,
        public readonly ?string $imageUrl = null,
        public readonly array $metadata = [],
    ) {
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'currency' => $this->currency,
            'image_url' => $this->imageUrl,
            'metadata' => $this->metadata,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            name: $data['name'],
            description: $data['description'] ?? '',
            price: (float) $data['price'],
            currency: $data['currency'] ?? 'USD',
            imageUrl: $data['image_url'] ?? null,
            metadata: $data['metadata'] ?? [],
        );
    }
}
