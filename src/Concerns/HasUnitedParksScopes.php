<?php

namespace iabduul7\ThemeParkBooking\Concerns;

trait HasUnitedParksScopes
{
    /**
     * Scope for United Parks (SeaWorld, Busch Gardens, etc.) products.
     */
    public function scopeUnitedParks($query, $negate = false)
    {
        return $query->whereHas('tickets', function ($query) use ($negate) {
            $query->where('name', $negate ? 'not like' : 'like', '%united%parks%')
                ->orWhere('name', $negate ? 'not like' : 'like', '%seaworld%')
                ->orWhere('name', $negate ? 'not like' : 'like', '%busch%gardens%');
        });
    }

    /**
     * Scope for SeaWorld products.
     */
    public function scopeSeaWorld($query, $negate = false)
    {
        return $query->whereHas('tickets', function ($query) use ($negate) {
            $query->where('name', $negate ? 'not like' : 'like', '%seaworld%');
        });
    }

    /**
     * Scope for SeaWorld Orlando products.
     */
    public function scopeSeaWorldOrlando($query, $negate = false)
    {
        return $query->whereHas('tickets', function ($query) use ($negate) {
            $query->where('name', $negate ? 'not like' : 'like', '%seaworld%orlando%')
                ->orWhere('name', $negate ? 'not like' : 'like', '%seaworld%florida%');
        });
    }

    /**
     * Scope for SeaWorld San Diego products.
     */
    public function scopeSeaWorldSanDiego($query, $negate = false)
    {
        return $query->whereHas('tickets', function ($query) use ($negate) {
            $query->where('name', $negate ? 'not like' : 'like', '%seaworld%san%diego%')
                ->orWhere('name', $negate ? 'not like' : 'like', '%seaworld%california%');
        });
    }

    /**
     * Scope for Busch Gardens products.
     */
    public function scopeBuschGardens($query, $negate = false)
    {
        return $query->whereHas('tickets', function ($query) use ($negate) {
            $query->where('name', $negate ? 'not like' : 'like', '%busch%gardens%');
        });
    }

    /**
     * Scope for Busch Gardens Tampa products.
     */
    public function scopeBuschGardensTampa($query, $negate = false)
    {
        return $query->whereHas('tickets', function ($query) use ($negate) {
            $query->where('name', $negate ? 'not like' : 'like', '%busch%gardens%tampa%')
                ->orWhere('name', $negate ? 'not like' : 'like', '%busch%gardens%florida%');
        });
    }

    /**
     * Scope for Busch Gardens Williamsburg products.
     */
    public function scopeBuschGardensWilliamsburg($query, $negate = false)
    {
        return $query->whereHas('tickets', function ($query) use ($negate) {
            $query->where('name', $negate ? 'not like' : 'like', '%busch%gardens%williamsburg%')
                ->orWhere('name', $negate ? 'not like' : 'like', '%busch%gardens%virginia%');
        });
    }

    /**
     * Scope for Aquatica products (SeaWorld's water park).
     */
    public function scopeAquatica($query, $negate = false)
    {
        return $query->whereHas('tickets', function ($query) use ($negate) {
            $query->where('name', $negate ? 'not like' : 'like', '%aquatica%');
        });
    }

    /**
     * Scope for Adventure Island products (Busch Gardens' water park).
     */
    public function scopeAdventureIsland($query, $negate = false)
    {
        return $query->whereHas('tickets', function ($query) use ($negate) {
            $query->where('name', $negate ? 'not like' : 'like', '%adventure%island%');
        });
    }

    /**
     * Scope for United Parks multi-park products.
     */
    public function scopeUnitedParksMultiPark($query, $negate = false)
    {
        return $query->whereHas('tickets', function ($query) use ($negate) {
            $query->where(function ($q) use ($negate) {
                $condition = $negate ? 'not like' : 'like';
                $q->where('name', $condition, '%combo%')
                    ->orWhere('name', $condition, '%multi%park%')
                    ->orWhere('name', $condition, '%2%park%')
                    ->orWhere('name', $condition, '%two%park%');
            });
        });
    }

    /**
     * Scope for United Parks season pass products.
     */
    public function scopeUnitedParksSeasonPass($query, $negate = false)
    {
        return $query->whereHas('tickets', function ($query) use ($negate) {
            $query->where('name', $negate ? 'not like' : 'like', '%season%pass%')
                ->orWhere('name', $negate ? 'not like' : 'like', '%annual%pass%');
        });
    }

    /**
     * Scope for United Parks VIP experience products.
     */
    public function scopeUnitedParksVIP($query, $negate = false)
    {
        return $query->whereHas('tickets', function ($query) use ($negate) {
            $query->where('name', $negate ? 'not like' : 'like', '%vip%')
                ->orWhere('name', $negate ? 'not like' : 'like', '%behind%scenes%')
                ->orWhere('name', $negate ? 'not like' : 'like', '%exclusive%');
        });
    }

    /**
     * Scope for United Parks dining plan products.
     */
    public function scopeUnitedParksDining($query, $negate = false)
    {
        return $query->whereHas('tickets', function ($query) use ($negate) {
            $query->where('name', $negate ? 'not like' : 'like', '%dining%')
                ->orWhere('name', $negate ? 'not like' : 'like', '%meal%plan%')
                ->orWhere('name', $negate ? 'not like' : 'like', '%all%day%dining%');
        });
    }

    /**
     * Scope for United Parks parking products.
     */
    public function scopeUnitedParksParking($query, $negate = false)
    {
        return $query->whereHas('tickets', function ($query) use ($negate) {
            $query->where('name', $negate ? 'not like' : 'like', '%parking%')
                ->orWhere('name', $negate ? 'not like' : 'like', '%preferred%parking%');
        });
    }

    /**
     * Scope for United Parks special events.
     */
    public function scopeUnitedParksSpecialEvent($query, $negate = false)
    {
        return $query->whereHas('tickets', function ($query) use ($negate) {
            $query->where('is_special', $negate ? '!=' : '=', 1)
                ->orWhere('name', $negate ? 'not like' : 'like', '%howl%o%scream%')
                ->orWhere('name', $negate ? 'not like' : 'like', '%christmas%celebration%')
                ->orWhere('name', $negate ? 'not like' : 'like', '%summer%nights%');
        });
    }

    /**
     * Scope for United Parks water park products.
     */
    public function scopeUnitedParksWaterPark($query, $negate = false)
    {
        return $query->whereHas('tickets', function ($query) use ($negate) {
            $query->where('is_water_park', $negate ? '!=' : '=', 1)
                ->orWhere('name', $negate ? 'not like' : 'like', '%aquatica%')
                ->orWhere('name', $negate ? 'not like' : 'like', '%adventure%island%')
                ->orWhere('name', $negate ? 'not like' : 'like', '%water%park%');
        });
    }
}
