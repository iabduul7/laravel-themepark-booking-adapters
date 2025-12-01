<?php

namespace iabduul7\ThemeParkBooking\Data;

use Carbon\Carbon;
use Illuminate\Support\Collection;

class Product
{
    public function __construct(
        public readonly string $remoteId,
        public readonly string $name,
        public readonly string $description,
        public readonly string $provider,
        public readonly string $category,
        public readonly array $pricing,
        public readonly array $options,
        public readonly bool $isActive,
        public readonly ?string $imageUrl = null,
        public readonly ?array $location = null,
        public readonly ?int $duration = null,
        public readonly ?array $restrictions = null,
        public readonly ?Carbon $availableFrom = null,
        public readonly ?Carbon $availableUntil = null,
        public readonly ?array $metadata = null,
        public readonly ?Carbon $lastUpdated = null
    ) {}

    public function toArray(): array
    {
        return [
            'remote_id' => $this->remoteId,
            'name' => $this->name,
            'description' => $this->description,
            'provider' => $this->provider,
            'category' => $this->category,
            'pricing' => $this->pricing,
            'options' => $this->options,
            'is_active' => $this->isActive,
            'image_url' => $this->imageUrl,
            'location' => $this->location,
            'duration' => $this->duration,
            'restrictions' => $this->restrictions,
            'available_from' => $this->availableFrom?->toISOString(),
            'available_until' => $this->availableUntil?->toISOString(),
            'metadata' => $this->metadata,
            'last_updated' => $this->lastUpdated?->toISOString(),
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            remoteId: $data['remote_id'],
            name: $data['name'],
            description: $data['description'],
            provider: $data['provider'],
            category: $data['category'],
            pricing: $data['pricing'],
            options: $data['options'],
            isActive: $data['is_active'],
            imageUrl: $data['image_url'] ?? null,
            location: $data['location'] ?? null,
            duration: $data['duration'] ?? null,
            restrictions: $data['restrictions'] ?? null,
            availableFrom: isset($data['available_from']) ? Carbon::parse($data['available_from']) : null,
            availableUntil: isset($data['available_until']) ? Carbon::parse($data['available_until']) : null,
            metadata: $data['metadata'] ?? null,
            lastUpdated: isset($data['last_updated']) ? Carbon::parse($data['last_updated']) : null
        );
    }

    public function hasOption(string $option): bool
    {
        return isset($this->options[$option]);
    }

    public function getOptionValue(string $option, $default = null)
    {
        return $this->options[$option] ?? $default;
    }

    public function isAvailableOn(Carbon $date): bool
    {
        if ($this->availableFrom && $date->lt($this->availableFrom)) {
            return false;
        }

        if ($this->availableUntil && $date->gt($this->availableUntil)) {
            return false;
        }

        return true;
    }

    public function getBasePricing(): array
    {
        return $this->pricing['base'] ?? [];
    }

    public function hasLocation(): bool
    {
        return !empty($this->location);
    }

    public function getLocationName(): ?string
    {
        return $this->location['name'] ?? null;
    }

    public function getLocationCoordinates(): ?array
    {
        if (!isset($this->location['latitude'], $this->location['longitude'])) {
            return null;
        }

        return [
            'latitude' => $this->location['latitude'],
            'longitude' => $this->location['longitude']
        ];
    }
}