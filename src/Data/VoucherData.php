<?php

namespace iabduul7\ThemeParkBooking\Data;

class VoucherData
{
    public function __construct(
        public readonly string $bookingId,
        public readonly string $voucherNumber,
        public readonly string $qrCode,
        public readonly string $barcodeData,
        public readonly array $customerInfo,
        public readonly array $productInfo,
        public readonly array $bookingDetails,
        public readonly ?string $pdfPath = null,
        public readonly ?string $downloadUrl = null,
        public readonly array $instructions = [],
        public readonly array $metadata = []
    ) {}

    public function toArray(): array
    {
        return [
            'booking_id' => $this->bookingId,
            'voucher_number' => $this->voucherNumber,
            'qr_code' => $this->qrCode,
            'barcode_data' => $this->barcodeData,
            'customer_info' => $this->customerInfo,
            'product_info' => $this->productInfo,
            'booking_details' => $this->bookingDetails,
            'pdf_path' => $this->pdfPath,
            'download_url' => $this->downloadUrl,
            'instructions' => $this->instructions,
            'metadata' => $this->metadata,
        ];
    }

    public function getCustomerName(): string
    {
        return trim(($this->customerInfo['first_name'] ?? '') . ' ' . ($this->customerInfo['last_name'] ?? ''));
    }

    public function getProductName(): string
    {
        return $this->productInfo['name'] ?? '';
    }

    public function getBookingDate(): ?string
    {
        return $this->bookingDetails['date'] ?? null;
    }

    public function getTimeSlot(): ?string
    {
        return $this->bookingDetails['time_slot'] ?? null;
    }

    public function getQuantity(): int
    {
        return $this->bookingDetails['quantity'] ?? 1;
    }

    public function hasDownloadUrl(): bool
    {
        return ! empty($this->downloadUrl);
    }

    public function hasPdfFile(): bool
    {
        return ! empty($this->pdfPath);
    }

    public function hasInstructions(): bool
    {
        return ! empty($this->instructions);
    }
}
