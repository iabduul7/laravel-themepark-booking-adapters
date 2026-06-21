<?php

namespace Iabduul7\ThemeParkAdapters\Tests\Adapters;

use Iabduul7\ThemeParkAdapters\Providers\Universal\UniversalSmartOrder2Adapter;
use Iabduul7\ThemeParkAdapters\Tests\AdapterTestCase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

class UniversalSmartOrder2AdapterTest extends AdapterTestCase
{
    private function adapter(): UniversalSmartOrder2Adapter
    {
        return new UniversalSmartOrder2Adapter([
            'client_username' => 'u',
            'client_secret' => 'sec',
            'customer_id' => 134853,
            'host' => 'qacorpapi.ucdp.net',
            'approved_suffix' => '-2KNOW',
            'token_cache' => false,
            'retry_sleep_ms' => 0,
        ]);
    }

    public function test_get_all_products_uses_bearer_token_and_injects_customer_id(): void
    {
        Http::fake([
            'qacorpapi.ucdp.net/connect/token' => Http::response(['access_token' => 'TOKEN123', 'expires_in' => 3600]),
            'qacorpapi.ucdp.net/smartorder/MyProductCatalog*' => Http::response(['productCatalogEntries' => []]),
        ]);

        $catalog = $this->adapter()->getAllProducts();

        $this->assertSame(['productCatalogEntries' => []], $catalog);

        Http::assertSent(fn (Request $r) => $r->method() === 'POST'
            && $r->url() === 'https://qacorpapi.ucdp.net/connect/token'
            && $r['grant_type'] === 'client_credentials'
            && $r['scope'] === 'SmartOrder');

        Http::assertSent(fn (Request $r) => str_starts_with($r->url(), 'https://qacorpapi.ucdp.net/smartorder/MyProductCatalog')
            && $r->hasHeader('Authorization', 'Bearer TOKEN123')
            && str_contains($r->url(), 'customerId=134853'));
    }

    public function test_place_order_posts_with_customer_id_in_body(): void
    {
        Http::fake([
            'qacorpapi.ucdp.net/connect/token' => Http::response(['access_token' => 'T', 'expires_in' => 3600]),
            'qacorpapi.ucdp.net/smartorder/PlaceOrder' => Http::response(['GalaxyOrderId' => 'G1']),
        ]);

        $result = $this->adapter()->placeOrder(['externalOrderId' => 'E1']);

        $this->assertSame('G1', $result['GalaxyOrderId']);
        Http::assertSent(fn (Request $r) => $r->method() === 'POST'
            && $r->url() === 'https://qacorpapi.ucdp.net/smartorder/PlaceOrder'
            && $r->data()['customerId'] === 134853
            && $r->data()['externalOrderId'] === 'E1');
    }

    public function test_self_heals_on_401_by_refreshing_token_and_retrying_once(): void
    {
        Http::fake([
            'qacorpapi.ucdp.net/connect/token' => Http::response(['access_token' => 'T', 'expires_in' => 3600]),
            'qacorpapi.ucdp.net/smartorder/MyProductCatalog*' => Http::sequence()
                ->push('', 401)
                ->push(['ok' => true], 200),
        ]);

        $catalog = $this->adapter()->getAllProducts();

        $this->assertSame(['ok' => true], $catalog);

        // The catalog endpoint is hit twice: the initial 401, then again after the
        // forced token refresh self-heals the request.
        $catalogCalls = Http::recorded(
            fn (Request $r) => str_contains($r->url(), 'smartorder/MyProductCatalog')
        );
        $this->assertCount(2, $catalogCalls);
    }

    public function test_can_cancel_order_uses_get(): void
    {
        Http::fake([
            'qacorpapi.ucdp.net/connect/token' => Http::response(['access_token' => 'T', 'expires_in' => 3600]),
            'qacorpapi.ucdp.net/smartorder/CanCancelOrder*' => Http::response(['canCancel' => true]),
        ]);

        $result = $this->adapter()->canCancelOrder(['ExternalOrderId' => 'E1']);

        $this->assertSame(['canCancel' => true], $result);
        Http::assertSent(fn (Request $r) => $r->method() === 'GET'
            && str_starts_with($r->url(), 'https://qacorpapi.ucdp.net/smartorder/CanCancelOrder'));
    }

    public function test_get_available_months_returns_twelve_structured_rows(): void
    {
        $months = $this->adapter()->getAvailableMonths();

        $this->assertCount(12, $months);
        $this->assertArrayHasKey('class', $months[0]);
        $this->assertArrayHasKey('text', $months[0]);
        $this->assertArrayHasKey('value', $months[0]);
    }
}
