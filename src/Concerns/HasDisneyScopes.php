<?php

namespace iabduul7\ThemeParkBooking\Concerns;

trait HasDisneyScopes
{
    /**
     * Scope for Disney World products.
     */
    public function scopeDisneyWorld($query, $negate = false)
    {
        return $query->whereHas('tickets', function ($query) use ($negate) {
            $query->where('name', $negate ? 'not like' : 'like', '%disney%world%');
        });
    }

    /**
     * Scope for Disney Magic Kingdom products.
     */
    public function scopeDisneyMagicKingdom($query, $negate = false)
    {
        return $query->whereHas('tickets', function ($query) use ($negate) {
            $query->where('name', $negate ? 'not like' : 'like', '%magic%kingdom%');
        });
    }

    /**
     * Scope for Disney EPCOT products.
     */
    public function scopeDisneyEpcot($query, $negate = false)
    {
        return $query->whereHas('tickets', function ($query) use ($negate) {
            $query->where('name', $negate ? 'not like' : 'like', '%epcot%');
        });
    }

    /**
     * Scope for Disney Hollywood Studios products.
     */
    public function scopeDisneyHollywoodStudios($query, $negate = false)
    {
        return $query->whereHas('tickets', function ($query) use ($negate) {
            $query->where('name', $negate ? 'not like' : 'like', '%hollywood%studios%');
        });
    }

    /**
     * Scope for Disney Animal Kingdom products.
     */
    public function scopeDisneyAnimalKingdom($query, $negate = false)
    {
        return $query->whereHas('tickets', function ($query) use ($negate) {
            $query->where('name', $negate ? 'not like' : 'like', '%animal%kingdom%');
        });
    }

    /**
     * Scope for Disney Genie+ products.
     */
    public function scopeDisneyGenie($query, $negate = false)
    {
        return $query->whereHas('tickets', function ($query) use ($negate) {
            $query->where('name', $negate ? 'not like' : 'like', '%genie%');
        });
    }

    /**
     * Scope for Disney Park Hopper products.
     */
    public function scopeDisneyParkHopper($query, $negate = false)
    {
        return $query->whereHas('tickets', function ($query) use ($negate) {
            $query->where('name', $negate ? 'not like' : 'like', '%hopper%');
        });
    }

    /**
     * Scope for Disney special events.
     */
    public function scopeDisneySpecialEvent($query, $negate = false)
    {
        return $query->whereHas('tickets', function ($query) use ($negate) {
            $query->where('is_special', $negate ? '!=' : '=', 1);
        });
    }

    /**
     * Scope for Disney water park products.
     */
    public function scopeDisneyWaterPark($query, $negate = false)
    {
        return $query->whereHas('tickets', function ($query) use ($negate) {
            $query->where('is_water_park', $negate ? '!=' : '=', 1)
                ->orWhere('name', $negate ? 'not like' : 'like', '%blizzard%beach%')
                ->orWhere('name', $negate ? 'not like' : 'like', '%typhoon%lagoon%');
        });
    }
}
