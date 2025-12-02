<?php

namespace iabduul7\ThemeParkBooking\Tests\Unit\Services;

use Carbon\Carbon;
use iabduul7\ThemeParkBooking\Data\BookingRequest;
use iabduul7\ThemeParkBooking\Data\BookingResponse;
use iabduul7\ThemeParkBooking\Http\SmartOrderHttpClient;
use iabduul7\ThemeParkBooking\Services\SmartOrderBookingService;
use iabduul7\ThemeParkBooking\Tests\TestCase;
use Mockery;

class SmartOrderBookingServiceTest extends TestCase
{
    private SmartOrderBookingService $service;
    private $mockHttpClient;

    protected function setUp(): void
    {
        parent::setUp();

        // Skip entire test file if API configurations are missing
        $this->skipIfApiConfigMissing([
            'themepark-booking.adapters.smartorder.api_key',
            'themepark-booking.adapters.smartorder.api_secret',
            'themepark-booking.adapters.smartorder.client_username'
        ], 'SmartOrder API configurations required for SmartOrderBookingService tests');

        $this->skipIfClassMissing(SmartOrderBookingService::class);

        $this->mockHttpClient = Mockery::mock(SmartOrderHttpClient::class);
        $this->service = new SmartOrderBookingService($this->mockHttpClient);
    }

    /** @test */
    public function it_can_get_all_products()
    {
        $mockResponse = [
            'products' => [
                [
                    'id' => 'UNIV_STUDIOS_1DAY',
                    'name' => 'Universal Studios 1-Day Ticket',
                    'price' => 109.99,
                    'currency' => 'USD',
                ],
                [
                    'id' => 'ISLANDS_ADV_1DAY',
                    'name' => 'Islands of Adventure 1-Day Ticket',
                    'price' => 109.99,
                    'currency' => 'USD',
                ],
            ]
        ];

        $this->mockHttpClient
            ->shouldReceive('get')
            ->with('products')
            ->once()
            ->andReturn($mockResponse);

        $result = $this->service->getAllProducts();

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals('UNIV_STUDIOS_1DAY', $result[0]['id']);
        $this->assertEquals('Universal Studios 1-Day Ticket', $result[0]['name']);
    }

    /** @test */
    public function it_can_get_product_details()
    {
        $productId = 'UNIV_STUDIOS_1DAY';
        $mockResponse = [
            'id' => $productId,
            'name' => 'Universal Studios 1-Day Ticket',
            'description' => 'Enjoy one day at Universal Studios theme park',
            'price' => 109.99,
            'currency' => 'USD',
            'availability' => true,
        ];

        $this->mockHttpClient
            ->shouldReceive('get')
            ->with("products/{$productId}")
            ->once()
            ->andReturn($mockResponse);

        $result = $this->service->getProduct($productId);

        $this->assertEquals($productId, $result['id']);
        $this->assertEquals('Universal Studios 1-Day Ticket', $result['name']);
        $this->assertEquals(109.99, $result['price']);
        $this->assertTrue($result['availability']);
    }

    /** @test */
    public function it_can_check_availability()
    {
        $productId = 'UNIV_STUDIOS_1DAY';
        $date = '2024-12-25';
        $quantity = 2;

        $mockResponse = [
            'available' => true,
            'remaining_capacity' => 500,
            'date' => $date,
            'product_id' => $productId,
        ];

        $this->mockHttpClient
            ->shouldReceive('get')
            ->with("products/{$productId}/availability", [
                'date' => $date,
                'quantity' => $quantity,
            ])
            ->once()
            ->andReturn($mockResponse);

        $result = $this->service->checkAvailability($productId, $date, $quantity);

        $this->assertTrue($result['available']);
        $this->assertEquals(500, $result['remaining_capacity']);
        $this->assertEquals($date, $result['date']);
    }

    /** @test */
    public function it_can_create_booking()
    {
        $request = new BookingRequest(
            productId: 'UNIV_STUDIOS_1DAY',
            date: Carbon::parse('2024-12-25'),
            quantity: 2,
            customerInfo: [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'email' => 'john@example.com',
            ],
            rateId: 'adult',
            timeSlot: '10:00'
        );

        $mockResponse = [
            'booking_id' => 'SMT_123456',
            'status' => 'confirmed',
            'confirmation_code' => 'CONF123',
            'total_amount' => 219.98,
            'currency' => 'USD',
            'tickets' => [
                ['id' => 'TKT_001', 'guest_name' => 'John Doe'],
                ['id' => 'TKT_002', 'guest_name' => 'Guest 2'],
            ],
        ];

        $this->mockHttpClient
            ->shouldReceive('post')
            ->with('bookings', Mockery::type('array'))
            ->once()
            ->andReturn($mockResponse);

        $result = $this->service->createBooking($request);

        $this->assertInstanceOf(BookingResponse::class, $result);
        $this->assertTrue($result->success);
        $this->assertEquals('SMT_123456', $result->bookingId);
        $this->assertEquals('confirmed', $result->status);
        $this->assertEquals('CONF123', $result->confirmationCode);
    }

    /** @test */
    public function it_can_cancel_booking()
    {
        $bookingId = 'SMT_123456';
        $reason = 'Customer requested cancellation';

        $mockResponse = [
            'booking_id' => $bookingId,
            'status' => 'cancelled',
            'cancellation_date' => now()->toISOString(),
            'refund_amount' => 219.98,
            'currency' => 'USD',
        ];

        $this->mockHttpClient
            ->shouldReceive('post')
            ->with("bookings/{$bookingId}/cancel", [
                'reason' => $reason,
            ])
            ->once()
            ->andReturn($mockResponse);

        $result = $this->service->cancelBooking($bookingId, $reason);

        $this->assertInstanceOf(BookingResponse::class, $result);
        $this->assertTrue($result->success);
        $this->assertEquals($bookingId, $result->bookingId);
        $this->assertEquals('cancelled', $result->status);
    }

    /** @test */
    public function it_can_get_booking_status()
    {
        $bookingId = 'SMT_123456';

        $mockResponse = [
            'booking_id' => $bookingId,
            'status' => 'confirmed',
            'confirmation_code' => 'CONF123',
            'total_amount' => 219.98,
            'currency' => 'USD',
            'booking_date' => '2024-12-25',
            'tickets' => [
                ['id' => 'TKT_001', 'status' => 'active'],
                ['id' => 'TKT_002', 'status' => 'active'],
            ],
        ];

        $this->mockHttpClient
            ->shouldReceive('get')
            ->with("bookings/{$bookingId}")
            ->once()
            ->andReturn($mockResponse);

        $result = $this->service->getBookingStatus($bookingId);

        $this->assertInstanceOf(BookingResponse::class, $result);
        $this->assertTrue($result->success);
        $this->assertEquals($bookingId, $result->bookingId);
        $this->assertEquals('confirmed', $result->status);
        $this->assertEquals('CONF123', $result->confirmationCode);
    }

    /** @test */
    public function it_handles_api_errors_gracefully()
    {
        $this->mockHttpClient
            ->shouldReceive('get')
            ->with('products')
            ->once()
            ->andThrow(new \Exception('API connection failed'));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('API connection failed');

        $this->service->getAllProducts();
    }

    /** @test */
    public function it_validates_booking_request_data()
    {
        // Test with invalid booking request data
        $this->expectException(\TypeError::class);

        new BookingRequest(
            productId: '',  // Invalid empty product ID
            date: Carbon::now(),
            quantity: 0,   // Invalid quantity
            customerInfo: [],
            rateId: 'adult'
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}