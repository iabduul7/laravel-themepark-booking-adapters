<?php

namespace iabduul7\ThemeParkBooking\Tests\Unit\Services;

use Carbon\Carbon;
use iabduul7\ThemeParkBooking\Data\BookingRequest;
use iabduul7\ThemeParkBooking\Data\BookingResponse;
use iabduul7\ThemeParkBooking\Http\RedeamHttpClient;
use iabduul7\ThemeParkBooking\Services\RedeamBookingService;
use iabduul7\ThemeParkBooking\Tests\TestCase;
use Mockery;

class RedeamBookingServiceTest extends TestCase
{
    private RedeamBookingService $service;
    private $mockHttpClient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockHttpClient = Mockery::mock(RedeamHttpClient::class);
        $this->service = new RedeamBookingService($this->mockHttpClient);
    }

    /** @test */
    public function it_can_get_all_products()
    {
        $mockResponse = [
            'products' => [
                [
                    'id' => 'disney-magic-kingdom-1day',
                    'name' => 'Magic Kingdom 1-Day Ticket',
                    'supplier_id' => '20',
                    'description' => 'One day access to Magic Kingdom',
                ],
                [
                    'id' => 'disney-epcot-1day',
                    'name' => 'EPCOT 1-Day Ticket',
                    'supplier_id' => '20',
                    'description' => 'One day access to EPCOT',
                ],
            ],
        ];

        $this->mockHttpClient
            ->shouldReceive('get')
            ->with('/products')
            ->once()
            ->andReturn($mockResponse);

        $products = $this->service->getAllProducts();

        $this->assertIsArray($products);
        $this->assertCount(2, $products);
        $this->assertEquals('Magic Kingdom 1-Day Ticket', $products[0]['name']);
    }

    /** @test */
    public function it_can_get_suppliers()
    {
        $mockResponse = [
            'suppliers' => [
                [
                    'id' => '20',
                    'name' => 'Walt Disney World',
                    'octo_id' => 'disney_world',
                    'active' => true,
                ],
            ],
        ];

        $this->mockHttpClient
            ->shouldReceive('get')
            ->with('/suppliers')
            ->once()
            ->andReturn($mockResponse);

        $suppliers = $this->service->getSuppliers();

        $this->assertIsArray($suppliers);
        $this->assertEquals('Walt Disney World', $suppliers[0]['name']);
    }

    /** @test */
    public function it_can_check_availability()
    {
        $mockResponse = [
            'availability' => [
                [
                    'id' => 'avail-1',
                    'start' => '2024-12-25T09:00:00Z',
                    'capacity' => 100,
                    'available' => 85,
                ],
            ],
        ];

        $this->mockHttpClient
            ->shouldReceive('get')
            ->with('/products/disney-magic-kingdom-1day/availability', [
                'start_date' => '2024-12-25',
                'end_date' => '2024-12-25',
            ])
            ->once()
            ->andReturn($mockResponse);

        $availability = $this->service->getAvailability(
            'disney-magic-kingdom-1day',
            Carbon::parse('2024-12-25'),
            Carbon::parse('2024-12-25')
        );

        $this->assertIsArray($availability);
        $this->assertEquals(85, $availability[0]['available']);
    }

    /** @test */
    public function it_can_hold_booking()
    {
        $bookingRequest = new BookingRequest(
            productId: 'disney-magic-kingdom-1day',
            rateId: 'adult',
            startDate: Carbon::parse('2024-12-25'),
            endDate: Carbon::parse('2024-12-25'),
            quantity: 2
        );

        $mockResponse = [
            'hold_id' => 'HOLD123456',
            'expires_at' => '2024-12-25T15:00:00Z',
            'status' => 'HELD',
            'booking_data' => [
                'product_id' => 'disney-magic-kingdom-1day',
                'quantity' => 2,
            ],
        ];

        $this->mockHttpClient
            ->shouldReceive('post')
            ->with('/bookings/hold', Mockery::type('array'))
            ->once()
            ->andReturn($mockResponse);

        $response = $this->service->holdBooking($bookingRequest);

        $this->assertInstanceOf(BookingResponse::class, $response);
        $this->assertEquals('HOLD123456', $response->holdId);
        $this->assertTrue($response->isSuccessful());
    }

    /** @test */
    public function it_can_confirm_booking()
    {
        $bookingRequest = new BookingRequest(
            productId: 'disney-magic-kingdom-1day',
            rateId: 'adult',
            startDate: Carbon::parse('2024-12-25'),
            endDate: Carbon::parse('2024-12-25'),
            quantity: 2,
            guestInfo: [['name' => 'John Doe', 'age' => 35]]
        );

        $mockResponse = [
            'booking_id' => 'BOOK789',
            'reference_number' => 'REF123456',
            'status' => 'CONFIRMED',
            'voucher_url' => 'https://vouchers.redeam.io/voucher_123.pdf',
        ];

        $this->mockHttpClient
            ->shouldReceive('post')
            ->with('/bookings', Mockery::type('array'))
            ->once()
            ->andReturn($mockResponse);

        $response = $this->service->makeBooking($bookingRequest);

        $this->assertInstanceOf(BookingResponse::class, $response);
        $this->assertEquals('BOOK789', $response->bookingId);
        $this->assertEquals('REF123456', $response->referenceNumber);
        $this->assertTrue($response->isSuccessful());
    }

    /** @test */
    public function it_can_cancel_booking()
    {
        $mockResponse = [
            'booking_id' => 'BOOK789',
            'status' => 'CANCELLED',
            'cancelled_at' => '2024-12-24T10:00:00Z',
        ];

        $this->mockHttpClient
            ->shouldReceive('delete')
            ->with('/bookings/BOOK789')
            ->once()
            ->andReturn($mockResponse);

        $response = $this->service->cancelBooking('BOOK789');

        $this->assertInstanceOf(BookingResponse::class, $response);
        $this->assertEquals('CANCELLED', $response->status);
    }

    /** @test */
    public function it_handles_api_errors_gracefully()
    {
        $this->mockHttpClient
            ->shouldReceive('get')
            ->with('/products')
            ->once()
            ->andThrow(new \Exception('API Error: Network timeout'));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('API Error: Network timeout');

        $this->service->getAllProducts();
    }

    /** @test */
    public function it_transforms_booking_request_to_redeam_format()
    {
        $bookingRequest = new BookingRequest(
            productId: 'disney-magic-kingdom-1day',
            rateId: 'adult',
            startDate: Carbon::parse('2024-12-25'),
            endDate: Carbon::parse('2024-12-25'),
            quantity: 2,
            guestInfo: [['name' => 'John Doe', 'age' => 35]]
        );

        $expectedData = [
            'product_id' => 'disney-magic-kingdom-1day',
            'rate_id' => 'adult',
            'start_date' => '2024-12-25',
            'end_date' => '2024-12-25',
            'quantity' => 2,
            'guests' => [['name' => 'John Doe', 'age' => 35]],
        ];

        $this->mockHttpClient
            ->shouldReceive('post')
            ->with('/bookings', $expectedData)
            ->once()
            ->andReturn(['booking_id' => 'BOOK123', 'status' => 'CONFIRMED']);

        $this->service->makeBooking($bookingRequest);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
