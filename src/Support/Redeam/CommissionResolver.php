<?php

namespace Iabduul7\ThemeParkAdapters\Support\Redeam;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * Resolves a commission percentage for a Walt Disney World ticket option, driven
 * by a config lookup table (defaults to the upstream "walt_disney.commission" key,
 * structured as commission.{normal|florida}.{days}.{option}-percentage).
 *
 * Opt-in business-logic building block; see {@see OptionCodeResolver}. Ported from
 * CodeCreatives\LaravelRedeam's getCommissionPercentage().
 */
class CommissionResolver
{
    public static function resolve(
        int $days,
        ?string $optionCode = null,
        string $configKey = 'walt_disney.commission'
    ): float {
        if ($optionCode === null) {
            return 0.0;
        }

        $commissions = config("{$configKey}.normal.{$days}");
        $floridaTicketCommissions = config("{$configKey}.florida.{$days}");

        if (Str::startsWith($optionCode, 'fl_resident')) {
            $optionCode = Str::replace('fl_resident_', '', $optionCode);

            return (float) Arr::get($floridaTicketCommissions ?? [], "{$optionCode}-percentage", 0.0);
        }

        return (float) Arr::get($commissions ?? [], "{$optionCode}-percentage", 0.0);
    }
}
