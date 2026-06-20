<?php

namespace Iabduul7\ThemeParkAdapters\Support\Redeam;

use Illuminate\Support\Str;

/**
 * Resolves a Walt Disney World ticket "option code" (base / park-hopper /
 * water-park / fl_resident_* / magic_* …) from a product/rate name.
 *
 * This is Disney-specific BUSINESS logic, not part of the Redeam API. It is
 * provided as an opt-in building block so the Disney/SeaWorld adapters can stay
 * drop-in compatible with the upstream facades, while consumers integrating a
 * different Redeam supplier can ignore it. Ported verbatim from
 * CodeCreatives\LaravelRedeam's getOptionCode().
 */
class OptionCodeResolver
{
    public static function resolve(int $days, ?string $name = null): ?string
    {
        if ($name === null) {
            return null;
        }

        $formatedName = Str::replace([
            "$days-Day ",
            "$days-Park ",
            "$days Day ",
            "$days-Day Disney's ",
        ], '', $name);

        $formatedName = Str::replaceLast(' Adult', '', $formatedName);
        $formatedName = Str::replaceLast(' Child', '', $formatedName);

        $optionCode = null;

        if (Str::endsWith($formatedName, [
            'Animal Kingdom Theme Park Ticket',
            'Hollywood Studios Theme Park Ticket',
            'EPCOT Theme Park Ticket',
            'Magic Kingdom Theme Park Ticket',
            'Admission to 1 Park Per Day',
        ]) ||
            Str::startsWith($formatedName, 'Walt Disney World') ||
            Str::contains($formatedName, ['Base Ticket', 'Magic Ticket', 'Discover'])) {
            $optionCode = 'base';
        } elseif (Str::contains($formatedName, [
            'Park Hopper Option',
            'w/Park Hopper',
        ])) {
            $optionCode = 'park-hopper';
        } elseif (Str::contains($formatedName, [
            'Park Hopper Plus Option',
            'w/Park Hopper Plus',
        ])) {
            $optionCode = 'park-hopper-plus';
        } elseif (Str::contains($formatedName, [
            'Water Park and Sports Option',
            'w/Water Park and Sports',
        ])) {
            $optionCode = 'water-park-and-sports';
        } elseif (Str::endsWith($formatedName, ['Water Park Ticket'])) {
            $optionCode = 'water-park-without-blockout-dates';
        } elseif (Str::endsWith($formatedName, ['Water Park Ticket with Blockout Dates'])) {
            $optionCode = 'water-park-with-blockout-dates';
        }

        if (Str::endsWith($formatedName, 'Discover Disney Ticket')) {
            $optionCode = "discover_$optionCode";
        }
        if (Str::startsWith($formatedName, ['Florida Resident', 'FL Resident', 'FL Res.'])) {
            $optionCode = "fl_resident_$optionCode";
        }
        if (Str::startsWith($formatedName, ['Magic Your Way', 'Magic Ticket'])) {
            $optionCode = "magic_$optionCode";
        }

        return $optionCode;
    }
}
