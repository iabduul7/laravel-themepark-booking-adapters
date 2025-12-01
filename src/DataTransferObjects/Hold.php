<?php

namespace Iabduul7\ThemeParkAdapters\DataTransferObjects;

class Hold
{
    public function __construct(
        public readonly string $id,
        public readonly string $status,
        public readonly array $items,
        public readonly ?string $expiresAt = null,
        public readonly array $metadata = [],
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'items' => $this->items,
            'expires_at' => $this->expiresAt,
            'metadata' => $this->metadata,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            status: $data['status'],
            items: $data['items'] ?? [],
            expiresAt: $data['expires_at'] ?? null,
            metadata: $data,
        );
    }
}
