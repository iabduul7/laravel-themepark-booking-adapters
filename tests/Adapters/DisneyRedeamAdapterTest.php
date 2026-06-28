<?php

namespace Iabduul7\ThemeParkAdapters\Tests\Adapters;

use Iabduul7\ThemeParkAdapters\DataTransferObjects\Results\PriceSchedule;
use Iabduul7\ThemeParkAdapters\DataTransferObjects\Results\Product;
use Iabduul7\ThemeParkAdapters\DataTransferObjects\Results\RatePriceSchedule;
use Iabduul7\ThemeParkAdapters\Exceptions\ThemeParkApiException;
use Iabduul7\ThemeParkAdapters\Providers\Disney\DisneyRedeamAdapter;
use Iabduul7\ThemeParkAdapters\Tests\AdapterTestCase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

class DisneyRedeamAdapterTest extends AdapterTestCase
{
    private function adapter(): DisneyRedeamAdapter
    {
        return new DisneyRedeamAdapter([
            'api_key' => 'k',
            'api_secret' => 's',
            'supplier_id' => '20',
            'host' => 'booking.redeam.io',
            'version' => 'v1.2',
            'retry_sleep_ms' => 0,
        ]);
    }

    public function test_get_all_products_hits_supplier_products_endpoint_with_auth_headers(): void
    {
        Http::fake([
            'booking.redeam.io/v1.2/suppliers/20/products*' => Http::response([
                'products' => [['id' => 'p1', 'name' => 'Magic Kingdom 1-Day']],
            ]),
        ]);

        $products = $this->adapter()->getAllProducts();

        $this->assertCount(1, $products);
        $this->assertInstanceOf(Product::class, $products[0]);
        $this->assertSame('p1', $products[0]->getId());
        $this->assertSame('Magic Kingdom 1-Day', $products[0]->getName());

        Http::assertSent(fn (Request $r) => $r->method() === 'GET'
            && str_starts_with($r->url(), 'https://booking.redeam.io/v1.2/suppliers/20/products')
            && $r->hasHeader('X-API-Key', 'k')
            && $r->hasHeader('X-API-Secret', 's'));
    }

    public function test_pricing_schedule_returns_dto_and_extracts_rate_by_id(): void
    {
        Http::fake([
            'booking.redeam.io/v1.2/suppliers/20/products/p1/pricing/schedule*' => Http::response([
                'rate-7' => ['2026-06-15' => ['net' => ['amount' => 100]]],
            ]),
        ]);

        $adapter = $this->adapter();

        $schedule = $adapter->getProductPricingSchedule('p1', '2026-06-15', '2026-06-20');
        $this->assertInstanceOf(PriceSchedule::class, $schedule);

        $rateSchedule = $adapter->getProductRatePricingSchedule('p1', '2026-06-15', '2026-06-20', 'rate-7');
        $this->assertInstanceOf(RatePriceSchedule::class, $rateSchedule);
        $this->assertSame(['2026-06-15' => ['net' => ['amount' => 100]]], $rateSchedule->getPriceData());
    }

    public function test_create_new_hold_posts_to_holds_endpoint(): void
    {
        Http::fake(['booking.redeam.io/v1.2/holds' => Http::response(['hold' => ['id' => 'H1']])]);

        $result = $this->adapter()->createNewHold(['hold' => ['items' => []]]);

        $this->assertSame('H1', $result['hold']['id']);
        Http::assertSent(fn (Request $r) => $r->method() === 'POST'
            && $r->url() === 'https://booking.redeam.io/v1.2/holds');
    }

    public function test_delete_booking_cancels_via_put(): void
    {
        Http::fake(['booking.redeam.io/v1.2/bookings/cancel/B1' => Http::response('', 200)]);

        $this->adapter()->deleteBooking('B1');

        Http::assertSent(fn (Request $r) => $r->method() === 'PUT'
            && $r->url() === 'https://booking.redeam.io/v1.2/bookings/cancel/B1');
    }

    public function test_reads_are_retried_on_server_error(): void
    {
        Http::fake([
            'booking.redeam.io/v1.2/suppliers/20/products*' => Http::sequence()
                ->push('', 500)
                ->push(['products' => [['id' => 'p9', 'name' => 'Recovered']]], 200),
        ]);

        $products = $this->adapter()->getAllProducts();

        $this->assertCount(1, $products);
        $this->assertSame('p9', $products[0]->getId());
        Http::assertSentCount(2);
    }

    public function test_get_product_throws_product_not_found_on_404(): void
    {
        Http::fake([
            'booking.redeam.io/v1.2/suppliers/20/products/missing*' => Http::response(['error' => 'gone'], 404),
        ]);

        $this->expectException(ThemeParkApiException::class);
        $this->expectExceptionMessage("Product with ID 'missing' not found.");

        $this->adapter()->getProduct('missing');
    }

    public function test_write_failure_throws_with_status_and_body(): void
    {
        Http::fake([
            'booking.redeam.io/v1.2/bookings' => Http::response(['message' => 'price mismatch'], 422),
        ]);

        try {
            $this->adapter()->createNewBooking(['booking' => []]);
            $this->fail('Expected ThemeParkApiException was not thrown.');
        } catch (ThemeParkApiException $e) {
            $this->assertSame(422, $e->getCode());
            $this->assertSame(['message' => 'price mismatch'], $e->getResponseData());
        }
    }

    public function test_reads_throw_after_exhausting_retries_on_persistent_server_error(): void
    {
        Http::fake([
            'booking.redeam.io/v1.2/suppliers/20/products*' => Http::response('', 500),
        ]);

        $this->expectException(ThemeParkApiException::class);

        $this->adapter()->getAllProducts();
    }

    public function test_validate_credentials_returns_false_on_unauthorized(): void
    {
        Http::fake([
            'booking.redeam.io/v1.2/suppliers/20/products*' => Http::response(['error' => 'bad key'], 401),
        ]);

        $this->assertFalse($this->adapter()->validateCredentials());
    }
}
