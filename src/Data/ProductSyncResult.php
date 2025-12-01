<?php

namespace iabduul7\ThemeParkBooking\Data;

class ProductSyncResult
{
    public function __construct(
        public readonly bool $success,
        public readonly int $totalProducts,
        public readonly int $syncedProducts,
        public readonly int $skippedProducts,
        public readonly int $failedProducts,
        public readonly array $errors = [],
        public readonly array $warnings = [],
        public readonly ?int $syncDuration = null,
        public readonly array $metadata = []
    ) {}

    public static function success(
        int $totalProducts,
        int $syncedProducts,
        int $skippedProducts = 0,
        int $failedProducts = 0,
        array $warnings = [],
        ?int $syncDuration = null,
        array $metadata = []
    ): self {
        return new self(
            success: true,
            totalProducts: $totalProducts,
            syncedProducts: $syncedProducts,
            skippedProducts: $skippedProducts,
            failedProducts: $failedProducts,
            warnings: $warnings,
            syncDuration: $syncDuration,
            metadata: $metadata
        );
    }

    public static function failure(array $errors, array $metadata = []): self
    {
        return new self(
            success: false,
            totalProducts: 0,
            syncedProducts: 0,
            skippedProducts: 0,
            failedProducts: 0,
            errors: $errors,
            metadata: $metadata
        );
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'total_products' => $this->totalProducts,
            'synced_products' => $this->syncedProducts,
            'skipped_products' => $this->skippedProducts,
            'failed_products' => $this->failedProducts,
            'errors' => $this->errors,
            'warnings' => $this->warnings,
            'sync_duration' => $this->syncDuration,
            'metadata' => $this->metadata,
        ];
    }

    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    public function hasWarnings(): bool
    {
        return !empty($this->warnings);
    }

    public function getSuccessRate(): float
    {
        if ($this->totalProducts === 0) {
            return 0.0;
        }

        return ($this->syncedProducts / $this->totalProducts) * 100;
    }

    public function getSummary(): string
    {
        if (!$this->success) {
            return "Sync failed with " . count($this->errors) . " error(s)";
        }

        $summary = "Synced {$this->syncedProducts}/{$this->totalProducts} products";

        if ($this->skippedProducts > 0) {
            $summary .= ", skipped {$this->skippedProducts}";
        }

        if ($this->failedProducts > 0) {
            $summary .= ", failed {$this->failedProducts}";
        }

        if ($this->syncDuration) {
            $summary .= " in {$this->syncDuration}s";
        }

        return $summary;
    }
}