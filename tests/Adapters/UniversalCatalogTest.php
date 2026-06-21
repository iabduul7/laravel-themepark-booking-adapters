<?php

namespace Iabduul7\ThemeParkAdapters\Tests\Adapters;

use Iabduul7\ThemeParkAdapters\DataTransferObjects\Collections\CatalogEntryCollection;
use Iabduul7\ThemeParkAdapters\DataTransferObjects\Results\CatalogEntry;
use Iabduul7\ThemeParkAdapters\DataTransferObjects\Results\PricePoint;
use Iabduul7\ThemeParkAdapters\Providers\Universal\UniversalSmartOrder2Adapter;
use Iabduul7\ThemeParkAdapters\Tests\AdapterTestCase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

/**
 * Contract tests for the opt-in Universal catalog building block. Driven off a
 * trimmed-but-real MyProductCatalog sandbox capture (tests/Fixtures, all 52 entries
 * from sales programs 4638/4689 with pricesByDay trimmed to one row each), so the
 * filter counts asserted here are the real distribution, not synthetic data.
 */
class UniversalCatalogTest extends AdapterTestCase
{
    private function adapter(): UniversalSmartOrder2Adapter
    {
        return new UniversalSmartOrder2Adapter([
            'client_username' => 'u',
            'client_secret' => 'sec',
            'customer_id' => 134853,
            'host' => 'qacorpapi.ucdp.net',
            'token_cache' => false,
            'retry_sleep_ms' => 0,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function fixture(): array
    {
        return json_decode((string) file_get_contents(__DIR__ . '/../Fixtures/universal_catalog.json'), true);
    }

    private function fakeCatalog(): void
    {
        Http::fake([
            'qacorpapi.ucdp.net/connect/token' => Http::response(['access_token' => 'TOKEN', 'expires_in' => 3600]),
            'qacorpapi.ucdp.net/smartorder/MyProductCatalog*' => Http::response($this->fixture()),
        ]);
    }

    public function test_catalog_returns_a_typed_filterable_collection_with_real_counts(): void
    {
        $this->fakeCatalog();

        $catalog = $this->adapter()->catalog();

        $this->assertInstanceOf(CatalogEntryCollection::class, $catalog);
        $this->assertInstanceOf(CatalogEntry::class, $catalog->first());
        $this->assertCount(52, $catalog);

        // Provider-native predicates over real sandbox distribution.
        $this->assertCount(12, $catalog->expressPasses());
        $this->assertCount(40, $catalog->themeParkAccess());
        $this->assertCount(26, $catalog->parkToPark());
        $this->assertCount(26, $catalog->epicAccess());
        $this->assertCount(8, $catalog->days(3));
        $this->assertCount(20, $catalog->forAge('A'));
        $this->assertCount(6, $catalog->salesProgram(4689));

        // Predicates chain (express add-ons are never theme-park admission).
        $this->assertCount(0, $catalog->expressPasses()->themeParkAccess());
    }

    public function test_catalog_injects_parent_sales_program_onto_each_entry(): void
    {
        $this->fakeCatalog();

        $entry = $this->adapter()->catalog()->first();

        $this->assertSame(4638, $entry->getSalesProgramId());
        $this->assertSame('ABP-Guest Svs T2', $entry->getSalesProgramName());
    }

    public function test_catalog_entry_exposes_provider_native_fields_and_base_prices(): void
    {
        $this->fakeCatalog();

        $entry = $this->adapter()->catalog()
            ->first(fn (CatalogEntry $e): bool => $e->getPlu() === '185130321040');

        $this->assertNotNull($entry);
        $this->assertSame(3, $entry->getNumberOfDays());
        $this->assertSame('A', $entry->getAgeValue());
        $this->assertTrue($entry->isThemeParkAccess());
        $this->assertTrue($entry->isParkToPark());
        $this->assertFalse($entry->isExpressPass());
        $this->assertTrue($entry->requiresFindEvents());

        $base = $entry->futureBasePrices();
        $this->assertContainsOnlyInstancesOf(PricePoint::class, $base);
        $this->assertEqualsWithDelta(282.22, $base[0]->getTotal(), 0.001);
        $this->assertEqualsWithDelta(264.99, $base[0]->getPrice(), 0.001);
        $this->assertEqualsWithDelta(17.23, $base[0]->getTax(), 0.001); // tax1 15.90 + tax2 1.33
        $this->assertFalse($base[0]->isRange());
        $this->assertFalse($base[0]->isDiscounted());
    }

    public function test_catalog_entry_exposes_discount_metadata_on_discounted_prices(): void
    {
        $this->fakeCatalog();

        $entry = $this->adapter()->catalog()
            ->first(fn (CatalogEntry $e): bool => $e->getPlu() === '185130321040');

        $discounted = $entry->futureDiscountedPrices();
        $this->assertEqualsWithDelta(266.24, $discounted[0]->getTotal(), 0.001);
        $this->assertTrue($discounted[0]->isDiscounted());
        $this->assertSame(2, $discounted[0]->getDiscountType());
        $this->assertEqualsWithDelta(15.0, $discounted[0]->getDiscountAmount(), 0.001);
    }

    public function test_catalog_passes_native_params_through_formatting_dates_and_booleans(): void
    {
        $this->fakeCatalog();

        $this->adapter()->catalog([
            'startDateInclusive' => new \DateTimeImmutable('2026-07-01'),
            'endDateInclusive' => '2026-12-31',
            'retrieveOnly' => 'future',
            'pricing' => 'base',
            'collapseDates' => true,
        ]);

        Http::assertSent(function (Request $r): bool {
            if (! str_starts_with($r->url(), 'https://qacorpapi.ucdp.net/smartorder/MyProductCatalog')) {
                return false;
            }

            return str_contains($r->url(), 'customerId=134853')
                && str_contains($r->url(), 'startDateInclusive=2026-07-01')
                && str_contains($r->url(), 'endDateInclusive=2026-12-31')
                && str_contains($r->url(), 'retrieveOnly=future')
                && str_contains($r->url(), 'pricing=base')
                && str_contains($r->url(), 'collapseDates=true');
        });
    }

    public function test_sales_program_id_is_filtered_client_side(): void
    {
        // The endpoint ignores salesProgramId server-side (returns every program),
        // so catalog() must narrow it client-side.
        $this->fakeCatalog();

        $catalog = $this->adapter()->catalog(['salesProgramId' => 4689]);

        $this->assertCount(6, $catalog);
        $this->assertTrue($catalog->every(fn (CatalogEntry $e): bool => $e->getSalesProgramId() === 4689));
    }

    public function test_raw_returns_underlying_entry_payloads(): void
    {
        $this->fakeCatalog();

        $raw = $this->adapter()->catalog()->raw();

        $this->assertCount(52, $raw);
        $this->assertIsArray($raw[0]);
        // The injected sales-program context is present on the raw payload (added,
        // never hidden) so a consumer reading raw() still sees the program.
        $this->assertArrayHasKey('salesProgramId', $raw[0]);
        $this->assertArrayHasKey('plu', $raw[0]);
    }

    public function test_price_point_normalises_a_collapsed_date_range(): void
    {
        // A real collapseDates=true row carries pricingRangeEndDateTime.
        $point = new PricePoint([
            'priceWithTax' => ['totalPriceWithTax' => 234.29, 'price' => 219.99, 'tax1' => 13.20, 'tax2' => 1.10],
            'pricingDateTime' => '2026-07-01T04:00:00-04:00',
            'pricingRangeEndDateTime' => '2026-07-02T23:59:59-04:00',
        ]);

        $this->assertTrue($point->isRange());
        $this->assertSame('2026-07-01T04:00:00-04:00', $point->getDate());
        $this->assertSame('2026-07-02T23:59:59-04:00', $point->getEndDate());
        $this->assertEqualsWithDelta(14.30, $point->getTax(), 0.001);
    }
}
