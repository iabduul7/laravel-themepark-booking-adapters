<?php

namespace Iabduul7\ThemeParkAdapters\Tests\Adapters;

use Iabduul7\ThemeParkAdapters\DataTransferObjects\Results\Product;
use Iabduul7\ThemeParkAdapters\DataTransferObjects\Results\Rate;
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
}
