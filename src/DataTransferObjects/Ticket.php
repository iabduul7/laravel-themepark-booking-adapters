<?php

namespace Iabduul7\ThemeParkAdapters\DataTransferObjects;

class Ticket
{
    public function __construct(
        public readonly string $id,
        public readonly string $productId,
        public readonly string $productName,
        public readonly string $ticketNumber,
        public readonly ?string $barcode = null,
        public readonly ?string $qrCode = null,
        public readonly ?string $validFrom = null,
        public readonly ?string $validUntil = null,
        public readonly array $metadata = [],
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->productId,
            'product_name' => $this->productName,
            'ticket_number' => $this->ticketNumber,
            'barcode' => $this->barcode,
            'qr_code' => $this->qrCode,
            'valid_from' => $this->validFrom,
            'valid_until' => $this->validUntil,
            'metadata' => $this->metadata,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            productId: $data['product_id'],
            productName: $data['product_name'],
            ticketNumber: $data['ticket_number'],
            barcode: $data['barcode'] ?? null,
            qrCode: $data['qr_code'] ?? null,
            validFrom: $data['valid_from'] ?? null,
            validUntil: $data['valid_until'] ?? null,
            metadata: $data['metadata'] ?? [],
        );
    }
}
