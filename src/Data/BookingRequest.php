<?php

namespace iabduul7\ThemeParkBooking\Data;

use Carbon\Carbon;

class BookingRequest
{
    public function __construct(
        public readonly string $productId,
        public readonly Carbon $date,
        public readonly int $quantity,
        public readonly array $customerInfo,
        public readonly ?string $rateId = null,
        public readonly ?string $availabilityId = null,
        public readonly ?string $timeSlot = null,
        public readonly ?Carbon $endDate = null,
        public readonly array $options = [],
        public readonly array $specialRequests = [],
        public readonly ?string $referenceId = null,
        public readonly array $guestInfo = [],
        public readonly array $paymentInfo = [],
        public readonly array $metadata = []
    ) {
    }

    public function toArray(): array
    {
        return [
            'product_id' => $this->productId,
            'rate_id' => $this->rateId,
            'availability_id' => $this->availabilityId,
            'date' => $this->date->toDateString(),
            'end_date' => $this->endDate?->toDateString(),
            'time_slot' => $this->timeSlot,
            'quantity' => $this->quantity,
            'customer_info' => $this->customerInfo,
            'guest_info' => $this->guestInfo,
            'payment_info' => $this->paymentInfo,
            'options' => $this->options,
            'special_requests' => $this->specialRequests,
            'reference_id' => $this->referenceId,
            'metadata' => $this->metadata,
        ];
    }

    public function toRedeamHoldFormat(): array
    {
        $holdItems = [];

        for ($i = 0; $i < $this->quantity; $i++) {
            $holdItems['items'][] = [
                'productId' => $this->productId,
                'rateId' => $this->rateId,
                'availabilityId' => $this->availabilityId,
                'at' => $this->date->toISOString(),
                'travelerType' => $this->getOption('age_group', 'adult'),
                'ext' => $this->options,
            ];
        }

        return $holdItems;
    }

    public function toRedeamBookingFormat(string $holdId): array
    {
        return [
            'holdId' => $holdId,
            'reference' => $this->referenceId ?? 'KBUG-' . uniqid(),
            'customer' => [
                'firstName' => $this->customerInfo['first_name'] ?? '',
                'lastName' => $this->customerInfo['last_name'] ?? '',
                'email' => $this->customerInfo['email'] ?? '',
                'phone' => $this->customerInfo['phone'] ?? '',
                'address' => [
                    'line1' => $this->customerInfo['address_line1'] ?? '',
                    'line2' => $this->customerInfo['address_line2'] ?? '',
                    'city' => $this->customerInfo['city'] ?? '',
                    'state' => $this->customerInfo['state'] ?? '',
                    'postcode' => $this->customerInfo['postcode'] ?? '',
                    'country' => $this->customerInfo['country'] ?? 'US',
                ],
            ],
            'ext' => $this->metadata,
        ];
    }

    public function toSmartOrderFormat(): array
    {
        return [
            'ProductID' => $this->productId,
            'EventDate' => $this->date->format('Y-m-d'),
            'Quantity' => $this->quantity,
            'CustomerName' => $this->getCustomerName(),
            'CustomerEmail' => $this->getCustomerEmail(),
            'CustomerPhone' => $this->getCustomerPhone(),
            'SpecialInstructions' => implode('; ', $this->specialRequests),
            'ReferenceNumber' => $this->referenceId,
        ];
    }

    public function getCustomerName(): string
    {
        return trim(($this->customerInfo['first_name'] ?? '') . ' ' . ($this->customerInfo['last_name'] ?? ''));
    }

    public function getCustomerEmail(): ?string
    {
        return $this->customerInfo['email'] ?? null;
    }

    public function getCustomerPhone(): ?string
    {
        return $this->customerInfo['phone'] ?? null;
    }

    public function hasTimeSlot(): bool
    {
        return ! empty($this->timeSlot);
    }

    public function hasSpecialRequests(): bool
    {
        return ! empty($this->specialRequests);
    }

    public function hasGuests(): bool
    {
        return ! empty($this->guestInfo);
    }

    public function getGuestCount(): int
    {
        return count($this->guestInfo);
    }

    public function getOption(string $key, $default = null)
    {
        return $this->options[$key] ?? $default;
    }

    public function getMetadata(string $key, $default = null)
    {
        return $this->metadata[$key] ?? $default;
    }

    public function isMultiDay(): bool
    {
        return $this->endDate !== null && ! $this->endDate->isSameDay($this->date);
    }

    public function getDuration(): int
    {
        if (! $this->endDate) {
            return 1;
        }

        return $this->date->diffInDays($this->endDate) + 1;
    }
}
