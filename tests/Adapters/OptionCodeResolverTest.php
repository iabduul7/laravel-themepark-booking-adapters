<?php

namespace Iabduul7\ThemeParkAdapters\Tests\Adapters;

use Iabduul7\ThemeParkAdapters\Support\Redeam\OptionCodeResolver;
use Iabduul7\ThemeParkAdapters\Tests\AdapterTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class OptionCodeResolverTest extends AdapterTestCase
{
    public function test_null_name_returns_null(): void
    {
        $this->assertNull(OptionCodeResolver::resolve(1, null));
    }

    public function test_unmatched_name_returns_null(): void
    {
        $this->assertNull(OptionCodeResolver::resolve(1, 'General Parking Pass'));
    }

    public function test_strips_day_count_and_age_band_before_matching(): void
    {
        // "2-Day " prefix and " Child" suffix are removed before classification.
        $this->assertSame('base', OptionCodeResolver::resolve(2, '2-Day EPCOT Theme Park Ticket Child'));
    }

    #[DataProvider('baseOptionCodes')]
    public function test_resolves_base_option_codes(int $days, string $name, string $expected): void
    {
        $this->assertSame($expected, OptionCodeResolver::resolve($days, $name));
    }

    /**
     * @return array<string, array{int, string, string}>
     */
    public static function baseOptionCodes(): array
    {
        return [
            'base' => [1, 'EPCOT Theme Park Ticket', 'base'],
            'park-hopper' => [4, '4-Day Park Hopper Option', 'park-hopper'],
            'park-hopper-plus' => [4, '4-Day Park Hopper Plus Option', 'park-hopper-plus'],
            'water-park-and-sports' => [4, '4-Day Water Park and Sports Option', 'water-park-and-sports'],
            'water-park-without-blockout' => [1, 'Disney Water Park Ticket', 'water-park-without-blockout-dates'],
            'water-park-with-blockout' => [1, 'Disney Water Park Ticket with Blockout Dates', 'water-park-with-blockout-dates'],
        ];
    }

    #[DataProvider('prefixedOptionCodes')]
    public function test_resolves_prefixed_option_codes(int $days, string $name, string $expected): void
    {
        $this->assertSame($expected, OptionCodeResolver::resolve($days, $name));
    }

    /**
     * @return array<string, array{int, string, string}>
     */
    public static function prefixedOptionCodes(): array
    {
        return [
            'discover prefix' => [1, 'Discover Disney Ticket', 'discover_base'],
            'magic prefix' => [1, 'Magic Your Way Base Ticket', 'magic_base'],
            'fl_resident prefix' => [1, 'Florida Resident Base Ticket', 'fl_resident_base'],
            // Compound: day-count + age-band stripping + base classification + FL prefix.
            'fl_resident compound' => [4, 'Florida Resident 4-Day Park Hopper Option Adult', 'fl_resident_park-hopper'],
        ];
    }
}
