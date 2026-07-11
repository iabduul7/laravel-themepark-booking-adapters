<?php

namespace Iabduul7\ThemeParkAdapters\Tests\Adapters;

use Iabduul7\ThemeParkAdapters\DataTransferObjects\Results\Product;
use Iabduul7\ThemeParkAdapters\DataTransferObjects\Results\Rate;
use Iabduul7\ThemeParkAdapters\DataTransferObjects\Results\RatePriceSchedule;
use Iabduul7\ThemeParkAdapters\DataTransferObjects\Results\Supplier;
use Iabduul7\ThemeParkAdapters\Exceptions\ThemeParkApiException;
use Iabduul7\ThemeParkAdapters\Providers\SeaWorld\SeaWorldRedeamAdapter;
use Iabduul7\ThemeParkAdapters\Tests\AdapterTestCase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

class SeaWorldRedeamAdapterTest extends AdapterTestCase
{
    private function adapter(): SeaWorldRedeamAdapter
    {
        return new SeaWorldRedeamAdapter([
            'api_key' => 'k',
            'api_secret' => 's',
            'host' => 'booking.redeam.io',
            'version' => 'v1.2',
            'retry_sleep_ms' => 0,
        ]);
    }

    public function test_get_all_products_routes_through_the_supplier_passed_per_call(): void
    {
        Http::fake([
            'booking.redeam.io/v1.2/suppliers/30/products*' => Http::response([
                'products' => [['id' => 'sw1', 'name' => 'SeaWorld Orlando 1-Day']],
            ]),
        ]);

        $products = $this->adapter()->getAllProducts('30');

        $this->assertCount(1, $products);
        $this->assertInstanceOf(Product::class, $products[0]);
        $this->assertSame('sw1', $products[0]->getId());

        Http::assertSent(fn (Request $r) => $r->method() === 'GET'
            && str_starts_with($r->url(), 'https://booking.redeam.io/v1.2/suppliers/30/products')
            && $r->hasHeader('X-API-Key', 'k'));
    }

    public function test_get_product_rates_includes_supplier_and_product_in_path(): void
    {
        Http::fake([
            'booking.redeam.io/v1.2/suppliers/30/products/sw1/rates*' => Http::response([
                'rates' => [['id' => 'r1', 'name' => 'Adult', 'optionId' => 'opt-1']],
            ]),
        ]);

        $rates = $this->adapter()->getProductRates('30', 'sw1');

        $this->assertCount(1, $rates);
        $this->assertInstanceOf(Rate::class, $rates[0]);
        $this->assertSame('opt-1', $rates[0]->getOptionId());
    }

    public function test_product_get_rates_resolves_supplier_from_the_product_payload(): void
    {
        Http::fake([
            // The rates pattern must be registered before the broader products
            // pattern below — Http::fake() applies the first matching pattern, and
            // "products*" would otherwise also match "products/sw1/rates".
            'booking.redeam.io/v1.2/suppliers/30/products/sw1/rates*' => Http::response([
                'rates' => [['id' => 'r1', 'name' => 'Adult', 'optionId' => 'opt-1']],
            ]),
            'booking.redeam.io/v1.2/suppliers/30/products*' => Http::response([
                'products' => [['id' => 'sw1', 'name' => 'SeaWorld Orlando 1-Day', 'supplierId' => '30']],
            ]),
        ]);

        $products = $this->adapter()->getAllProducts('30');
        $rates = $products[0]->getRates();

        $this->assertCount(1, $rates);
        $this->assertInstanceOf(Rate::class, $rates[0]);
        $this->assertSame('opt-1', $rates[0]->getOptionId());

        Http::assertSent(fn (Request $r) => $r->method() === 'GET'
            && str_starts_with($r->url(), 'https://booking.redeam.io/v1.2/suppliers/30/products/sw1/rates'));
    }

    public function test_pricing_schedule_without_rate_id_returns_the_full_multi_rate_payload(): void
    {
        Http::fake([
            'booking.redeam.io/v1.2/suppliers/30/products/sw1/pricing/schedule*' => Http::response([
                'rate-a' => ['x' => 1],
                'rate-b' => ['y' => 2],
            ]),
        ]);

        $rateSchedule = $this->adapter()->getProductRatePricingSchedule('30', 'sw1', '2026-06-15', '2026-06-20');

        $this->assertInstanceOf(RatePriceSchedule::class, $rateSchedule);
        $this->assertSame(['rate-a' => ['x' => 1], 'rate-b' => ['y' => 2]], $rateSchedule->getPriceData());
    }

    public function test_get_all_suppliers_maps_supplier_envelope_with_auth_headers(): void
    {
        Http::fake([
            'booking.redeam.io/v1.2/suppliers*' => Http::response([
                'suppliers' => [
                    ['id' => 'sup-1', 'name' => 'SeaWorld Orlando', 'octoID' => 'octo-1'],
                    ['id' => 'sup-2', 'name' => 'Aquatica'],
                ],
            ]),
        ]);

        $suppliers = $this->adapter()->getAllSuppliers();

        $this->assertCount(2, $suppliers);
        $this->assertInstanceOf(Supplier::class, $suppliers[0]);
        $this->assertSame('sup-1', $suppliers[0]->getId());
        $this->assertSame('SeaWorld Orlando', $suppliers[0]->getName());

        Http::assertSent(fn (Request $r) => $r->method() === 'GET'
            && str_starts_with($r->url(), 'https://booking.redeam.io/v1.2/suppliers')
            && $r->hasHeader('X-API-Key', 'k')
            && $r->hasHeader('X-API-Secret', 's'));
    }

    public function test_get_supplier_hits_single_supplier_endpoint(): void
    {
        Http::fake([
            'booking.redeam.io/v1.2/suppliers/sup-1' => Http::response([
                'supplier' => ['id' => 'sup-1', 'name' => 'SeaWorld Orlando'],
            ]),
        ]);

        $supplier = $this->adapter()->getSupplier('sup-1');

        $this->assertInstanceOf(Supplier::class, $supplier);
        $this->assertSame('sup-1', $supplier->getId());
        $this->assertSame('SeaWorld Orlando', $supplier->getName());

        Http::assertSent(fn (Request $r) => $r->method() === 'GET'
            && $r->url() === 'https://booking.redeam.io/v1.2/suppliers/sup-1');
    }

    public function test_get_product_throws_product_not_found_on_404(): void
    {
        Http::fake([
            'booking.redeam.io/v1.2/suppliers/30/products/missing*' => Http::response(['error' => 'gone'], 404),
        ]);

        $this->expectException(ThemeParkApiException::class);
        $this->expectExceptionMessage("Product with ID 'missing' not found.");

        $this->adapter()->getProduct('30', 'missing');
    }

    public function test_validate_credentials_returns_false_on_unauthorized(): void
    {
        Http::fake([
            'booking.redeam.io/v1.2/suppliers*' => Http::response(['error' => 'bad key'], 401),
        ]);

        $this->assertFalse($this->adapter()->validateCredentials());
    }
}
