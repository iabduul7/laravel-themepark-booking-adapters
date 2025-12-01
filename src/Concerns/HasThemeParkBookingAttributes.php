<?php

namespace iabduul7\ThemeParkBooking\Concerns;

use iabduul7\ThemeParkBooking\Models\OrderDetailsRedeam;
use iabduul7\ThemeParkBooking\Models\OrderDetailsUniversal;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Arr;

trait HasThemeParkBookingAttributes
{
    /**
     * Boot the trait
     */
    protected static function bootHasThemeParkBookingAttributes()
    {
        // Add any boot logic if needed
    }

    /**
     * Check if order has SmartOrder items
     */
    public function getHasSmartorderItemsAttribute(): bool
    {
        return $this->universalDetails()->exists();
    }

    /**
     * Get SmartOrder items
     */
    public function getSmartorderItemsAttribute()
    {
        if (!$this->has_smartorder_items) {
            return [];
        }

        return $this->universalDetails;
    }

    /**
     * Get SmartOrder external order IDs
     */
    public function getSmartorderExternalOrderIdAttribute(): array
    {
        if (!$this->has_smartorder_items) {
            return [];
        }

        $details = $this->universalDetails;
        return $details ? [$details->external_order_id] : [];
    }

    /**
     * Get SmartOrder galaxy order IDs
     */
    public function getSmartorderGalaxyOrderIdAttribute(): array
    {
        if (!$this->has_smartorder_items) {
            return [];
        }

        $details = $this->universalDetails;
        return $details ? [$details->galaxy_order_id] : [];
    }

    /**
     * Get SmartOrder booking data
     */
    public function getSmartorderBookingDataAttribute(): array
    {
        if (!$this->has_smartorder_items) {
            return [];
        }

        $details = $this->universalDetails;
        return $details ? [$details->booking_data] : [];
    }

    /**
     * Check if order has Disney items
     */
    public function getHasDisneyItemsAttribute(): bool
    {
        return $this->disneyDetails()->exists();
    }

    /**
     * Get Disney items
     */
    public function getDisneyItemsAttribute(): ?array
    {
        if (!$this->has_disney_items) {
            return [];
        }

        $details = $this->disneyDetails;
        return $details?->toArray();
    }

    /**
     * Get Disney hold ID
     */
    public function getDisneyHoldIdAttribute(): ?string
    {
        if (!$this->has_disney_items) {
            return null;
        }

        return Arr::get($this->getDisneyItemsAttribute(), 'hold_id', null);
    }

    /**
     * Get Disney booking ID
     */
    public function getDisneyBookingIdAttribute(): ?string
    {
        if (!$this->has_disney_items) {
            return null;
        }

        return Arr::get($this->getDisneyItemsAttribute(), 'booking_id', null);
    }

    /**
     * Get Disney booking data
     */
    public function getDisneyBookingDataAttribute(): array
    {
        if (!$this->has_disney_items) {
            return [];
        }

        return Arr::get($this->getDisneyItemsAttribute(), 'booking_data', []);
    }

    /**
     * Get Disney reference number
     */
    public function getDisneyReferenceNumberAttribute(): ?string
    {
        if (!$this->has_disney_items) {
            return null;
        }

        return Arr::get($this->getDisneyItemsAttribute(), 'reference_number', null);
    }

    /**
     * Get Disney voucher
     */
    public function getDisneyVoucherAttribute(): ?string
    {
        if (!$this->has_disney_items) {
            return null;
        }

        return Arr::get($this->getDisneyItemsAttribute(), 'voucher', null);
    }

    /**
     * Get Disney reservation number
     */
    public function getDisneyReservationNumberAttribute(): ?string
    {
        if (!$this->has_disney_items || !$this->disney_booking_data) {
            return null;
        }

        return Arr::get($this->disney_booking_data, 'ext.supplier.reference');
    }

    /**
     * Check if order has United Parks items
     */
    public function getHasUnitedParksItemsAttribute(): bool
    {
        return $this->unitedParksDetails()->exists();
    }

    /**
     * Get United Parks items
     */
    public function getUnitedParksItemsAttribute(): ?array
    {
        if (!$this->has_united_parks_items) {
            return [];
        }

        $details = $this->unitedParksDetails;
        return $details?->toArray();
    }

    /**
     * Get United Parks hold ID
     */
    public function getUnitedParksHoldIdAttribute(): ?string
    {
        if (!$this->has_united_parks_items) {
            return null;
        }

        return Arr::get($this->getUnitedParksItemsAttribute(), 'hold_id', null);
    }

    /**
     * Get United Parks booking ID
     */
    public function getUnitedParksBookingIdAttribute(): ?string
    {
        if (!$this->has_united_parks_items) {
            return null;
        }

        return Arr::get($this->getUnitedParksItemsAttribute(), 'booking_id', null);
    }

    /**
     * Get United Parks booking data
     */
    public function getUnitedParksBookingDataAttribute(): array
    {
        if (!$this->has_united_parks_items) {
            return [];
        }

        return Arr::get($this->getUnitedParksItemsAttribute(), 'booking_data', []);
    }

    /**
     * Get United Parks reference number
     */
    public function getUnitedParksReferenceNumberAttribute(): ?string
    {
        if (!$this->has_united_parks_items) {
            return null;
        }

        return Arr::get($this->getUnitedParksItemsAttribute(), 'reference_number', null);
    }

    /**
     * Get United Parks voucher
     */
    public function getUnitedParksVoucherAttribute(): ?string
    {
        if (!$this->has_united_parks_items) {
            return null;
        }

        return Arr::get($this->getUnitedParksItemsAttribute(), 'voucher', null);
    }

    /**
     * Get United Parks reservation number
     */
    public function getUnitedParksReservationNumberAttribute(): ?string
    {
        if (!$this->has_united_parks_items || !$this->united_parks_booking_data) {
            return null;
        }

        return Arr::get($this->united_parks_booking_data, 'ext.supplier.reference');
    }

    /**
     * Relationship to Disney/Redeam order details
     */
    public function disneyDetails(): HasOne
    {
        return $this->hasOne(OrderDetailsRedeam::class, 'order_id');
    }

    /**
     * Relationship to United Parks order details (also Redeam)
     */
    public function unitedParksDetails(): HasOne
    {
        return $this->hasOne(OrderDetailsRedeam::class, 'order_id');
    }

    /**
     * Relationship to Universal/SmartOrder order details
     */
    public function universalDetails(): HasOne
    {
        return $this->hasOne(OrderDetailsUniversal::class, 'order_id');
    }

    /**
     * Check if order is cancelled based on theme park data
     */
    public function getIsCancelledByProviderAttribute(): bool
    {
        if ($this->has_disney_items) {
            return Arr::get($this->disney_booking_data, 'status') == 'CANCELLED';
        }

        if ($this->has_united_parks_items) {
            return Arr::get($this->united_parks_booking_data, 'status') == 'CANCELLED';
        }

        return false;
    }

    /**
     * Get all theme park booking references
     */
    public function getAllBookingReferencesAttribute(): array
    {
        $references = [];

        if ($this->has_disney_items && $this->disney_reference_number) {
            $references['disney'] = $this->disney_reference_number;
        }

        if ($this->has_united_parks_items && $this->united_parks_reference_number) {
            $references['united_parks'] = $this->united_parks_reference_number;
        }

        if ($this->has_smartorder_items && !empty($this->smartorder_external_order_id)) {
            $references['smartorder'] = $this->smartorder_external_order_id;
        }

        return $references;
    }
}
