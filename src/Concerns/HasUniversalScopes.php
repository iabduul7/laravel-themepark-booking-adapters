<?php

namespace iabduul7\ThemeParkBooking\Concerns;

trait HasUniversalScopes
{
    /**
     * Scope for Universal promo products
     */
    public function scopeUniversalPromo($query, $negate = false)
    {
        return $query->whereHas('tickets', function ($query) use ($negate) {
            $query->where('is_promo', $negate ? '!=' : '=', 1);
        });
    }

    /**
     * Scope for Universal Express Pass products
     */
    public function scopeUniversalExpressPass($query, $negate = false)
    {
        return $query->whereHas('tickets', function ($query) use ($negate) {
            $query->where('is_express', $negate ? '!=' : '=', 1);
        });
    }

    /**
     * Scope for Universal dated products
     */
    public function scopeUniversalDated($query, $negate = false)
    {
        return $query->whereHas('tickets', function ($query) use ($negate) {
            $query->where('is_dated', $negate ? '!=' : '=', 1);
        });
    }

    /**
     * Scope for Universal Halloween Horror Nights products
     */
    public function scopeUniversalHHN($query, $negate = false)
    {
        return $query->whereHas('tickets', function ($query) use ($negate) {
            $query->where('api_identifier', $negate ? '!=' : '=', 150128265009);
        });
    }

    /**
     * Scope for Universal Volcano Bay products
     */
    public function scopeUniversalVolcanoBay($query, $negate = false)
    {
        return $query->whereHas('tickets', function ($query) use ($negate) {
            $query->where('name', $negate ? 'not like' : 'like', '%volcano%');
        });
    }

    /**
     * Scope for Universal Islands of Adventure products
     */
    public function scopeUniversalIslandsOfAdventure($query, $negate = false)
    {
        return $query->whereHas('tickets', function ($query) use ($negate) {
            $query->where('name', $negate ? 'not like' : 'like', '%islands%adventure%');
        });
    }

    /**
     * Scope for Universal Studios products
     */
    public function scopeUniversalStudios($query, $negate = false)
    {
        return $query->whereHas('tickets', function ($query) use ($negate) {
            $query->where('name', $negate ? 'not like' : 'like', '%universal%studios%');
        });
    }

    /**
     * Scope for Universal multi-day products
     */
    public function scopeUniversalMultiDay($query, $negate = false)
    {
        return $query->whereHas('tickets', function ($query) use ($negate) {
            $query->where('name', $negate ? 'not like' : 'like', '%day%');
        });
    }
}
