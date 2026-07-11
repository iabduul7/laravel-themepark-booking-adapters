<?php

namespace Iabduul7\ThemeParkAdapters\Tests\Adapters;

use Iabduul7\ThemeParkAdapters\DataTransferObjects\Results\PriceSchedule;
use Iabduul7\ThemeParkAdapters\DataTransferObjects\Results\Product;
use Iabduul7\ThemeParkAdapters\DataTransferObjects\Results\Rate;
use Iabduul7\ThemeParkAdapters\DataTransferObjects\Results\RatePriceSchedule;
use Iabduul7\ThemeParkAdapters\Exceptions\ThemeParkApiException;
use Iabduul7\ThemeParkAdapters\Providers\Disney\DisneyRedeamAdapter;
use Iabduul7\ThemeParkAdapters\Tests\AdapterTestCase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

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

    public function test_pricing_schedule_without_rate_id_returns_the_full_multi_rate_payload(): void
    {
        Http::fake([
            'booking.redeam.io/v1.2/suppliers/20/products/p1/pricing/schedule*' => Http::response([
                'rate-a' => ['x' => 1],
                'rate-b' => ['y' => 2],
            ]),
        ]);

        $rateSchedule = $this->adapter()->getProductRatePricingSchedule('p1', '2026-06-15', '2026-06-20');

        $this->assertInstanceOf(RatePriceSchedule::class, $rateSchedule);
        $this->assertSame(['rate-a' => ['x' => 1], 'rate-b' => ['y' => 2]], $rateSchedule->getPriceData());
    }

    public function test_check_availabilities_accepts_base_carbon_class_instances(): void
    {
        Http::fake([
            'booking.redeam.io/v1.2/suppliers/20/products/p1/availabilities*' => Http::response(['available' => true]),
        ]);

        $this->adapter()->checkAvailabilities(
            'p1',
            new Carbon('2026-06-15 09:30:00'),
            new Carbon('2026-06-20 09:30:00')
        );

        Http::assertSent(fn (Request $r) => str_contains(urldecode($r->url()), '2026-06-15T09:30'));
    }

    public function test_get_product_pricing_schedule_accepts_base_carbon_class_instances(): void
    {
        Http::fake([
            'booking.redeam.io/v1.2/suppliers/20/products/p1/pricing/schedule*' => Http::response([]),
        ]);

        $this->adapter()->getProductPricingSchedule(
            'p1',
            new Carbon('2026-06-15 09:30:00'),
            new Carbon('2026-06-20 09:30:00')
        );

        Http::assertSent(fn (Request $r) => str_contains(urldecode($r->url()), 'start_date=2026-06-15'));
    }

    public function test_product_get_rates_delegates_to_the_adapter(): void
    {
        Http::fake([
            // The rates pattern must be registered before the broader products
            // pattern below — Http::fake() applies the first matching pattern, and
            // "products*" would otherwise also match "products/p1/rates".
            'booking.redeam.io/v1.2/suppliers/20/products/p1/rates*' => Http::response([
                'rates' => [['id' => 'r1', 'name' => 'Adult', 'optionId' => 'opt-1']],
            ]),
            'booking.redeam.io/v1.2/suppliers/20/products*' => Http::response([
                'products' => [['id' => 'p1', 'name' => 'Magic Kingdom 1-Day']],
            ]),
        ]);

        $products = $this->adapter()->getAllProducts();
        $rates = $products[0]->getRates();

        $this->assertCount(1, $rates);
        $this->assertInstanceOf(Rate::class, $rates[0]);
        $this->assertSame('opt-1', $rates[0]->getOptionId());

        Http::assertSent(fn (Request $r) => $r->method() === 'GET'
            && str_starts_with($r->url(), 'https://booking.redeam.io/v1.2/suppliers/20/products/p1/rates'));
    }

    public function test_get_park_availability_data_returns_the_raw_feed_untouched(): void
    {
        $feed = [
            '2026-06-15' => [
                'status' => 'full',
                'parks' => [
                    'EPCOT®' => 'available',
                    'Magic Kingdom® Park' => 'available',
                ],
            ],
        ];

        Http::fake(['dis-obs.redeam.io/disney/park/availability*' => Http::response($feed)]);

        $this->assertSame($feed, $this->adapter()->getParkAvailabilityData('2026-06-15', '2026-06-20'));
    }

    public function test_get_park_availability_strips_registered_trademark_and_reverses_park_order(): void
    {
        // Real feed shape: each entry keys the parks map BY PARK NAME
        // ("Magic Kingdom® Park" => "available"), so the ® strip must hit the keys.
        Http::fake([
            'dis-obs.redeam.io/disney/park/availability*' => Http::response([
                '2026-06-15' => ['parks' => [
                    'EPCOT®' => 'available',
                    'Magic Kingdom® Park' => 'available',
                ]],
                '2026-06-16' => ['parks' => [
                    'EPCOT®' => 'notAvailable',
                    'Magic Kingdom® Park' => 'available',
                ]],
            ]),
        ]);

        $availability = $this->adapter()->getParkAvailability('2026-06-15', '2026-06-20');

        $this->assertArrayHasKey('2026-06-15', $availability);
        $entry = $availability['2026-06-15'];
        $this->assertSame('full', $entry['availability']);
        $this->assertSame('2026-06-15', $entry['date']);
        $this->assertSame([
            'Magic Kingdom Park' => 'available',
            'EPCOT' => 'available',
        ], $entry['parks']);

        $this->assertSame('partial', $availability['2026-06-16']['availability']);
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
