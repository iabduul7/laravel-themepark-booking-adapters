<?php

namespace Iabduul7\ThemeParkAdapters\DataTransferObjects;

class Booking
{
    public function __construct(
        public readonly string $id,
        public readonly string $status,
        public readonly array $items,
        public readonly float $totalAmount,
        public readonly string $currency,
        public readonly ?string $confirmationNumber = null,
        public readonly ?string $createdAt = null,
        public readonly array $metadata = [],
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'items' => $this->items,
            'total_amount' => $this->totalAmount,
            'currency' => $this->currency,
            'confirmation_number' => $this->confirmationNumber,
            'created_at' => $this->createdAt,
            'metadata' => $this->metadata,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            status: $data['status'],
            items: $data['items'] ?? [],
            totalAmount: (float) ($data['total_amount'] ?? 0),
            currency: $data['currency'] ?? 'USD',
            confirmationNumber: $data['confirmation_number'] ?? null,
            createdAt: $data['created_at'] ?? null,
            metadata: $data,
        );
    }
}
