<?php

namespace iabduul7\ThemeParkBooking\Tests\Unit\Data;

use Carbon\Carbon;
use iabduul7\ThemeParkBooking\Data\BookingRequest;
use iabduul7\ThemeParkBooking\Tests\TestCase;

class BookingRequestTest extends TestCase
{
    /** @test */
    public function it_can_be_created_with_required_fields()
    {
        $startDate = Carbon::parse('2024-12-25');
        $endDate = Carbon::parse('2024-12-25');

        $request = new BookingRequest(
            productId: 'disney-magic-kingdom-1day',
            rateId: 'adult',
            startDate: $startDate,
            endDate: $endDate,
            quantity: 2
        );

        $this->assertEquals('disney-magic-kingdom-1day', $request->productId);
        $this->assertEquals('adult', $request->rateId);
        $this->assertEquals(2, $request->quantity);
        $this->assertTrue($startDate->equalTo($request->startDate));
        $this->assertTrue($endDate->equalTo($request->endDate));
    }

    /** @test */
    public function it_can_transform_to_redeam_hold_format()
    {
        $request = new BookingRequest(
            productId: 'disney-magic-kingdom-1day',
            rateId: 'adult',
            startDate: Carbon::parse('2024-12-25'),
            endDate: Carbon::parse('2024-12-25'),
            quantity: 2
        );

        $holdFormat = $request->toRedeamHoldFormat();

        $expected = [
            'product_id' => 'disney-magic-kingdom-1day',
            'rate_id' => 'adult',
            'start_date' => '2024-12-25',
            'end_date' => '2024-12-25',
            'quantity' => 2
        ];

        $this->assertEquals($expected, $holdFormat);
    }

    /** @test */
    public function it_can_transform_to_redeam_booking_format()
    {
        $request = new BookingRequest(
            productId: 'disney-magic-kingdom-1day',
            rateId: 'adult',
            startDate: Carbon::parse('2024-12-25'),
            endDate: Carbon::parse('2024-12-25'),
            quantity: 2,
            guestInfo: [
                ['name' => 'John Doe', 'age' => 35],
                ['name' => 'Jane Doe', 'age' => 32]
            ],
            paymentInfo: [
                'amount' => 218.00,
                'currency' => 'USD'
            ]
        );

        $bookingFormat = $request->toRedeamBookingFormat();

        $expected = [
            'product_id' => 'disney-magic-kingdom-1day',
            'rate_id' => 'adult',
            'start_date' => '2024-12-25',
            'end_date' => '2024-12-25',
            'quantity' => 2,
            'guests' => [
                ['name' => 'John Doe', 'age' => 35],
                ['name' => 'Jane Doe', 'age' => 32]
            ],
            'payment' => [
                'amount' => 218.00,
                'currency' => 'USD'
            ]
        ];

        $this->assertEquals($expected, $bookingFormat);
    }

    /** @test */
    public function it_can_transform_to_smartorder_format()
    {
        $request = new BookingRequest(
            productId: 'UNIV_STUDIOS_1DAY',
            rateId: 'adult',
            startDate: Carbon::parse('2024-12-25'),
            endDate: Carbon::parse('2024-12-25'),
            quantity: 2,
            guestInfo: [
                ['name' => 'John Doe'],
                ['name' => 'Jane Doe']
            ]
        );

        $smartOrderFormat = $request->toSmartOrderFormat('134853');

        $expected = [
            'CustomerID' => '134853',
            'ProductID' => 'UNIV_STUDIOS_1DAY',
            'Quantity' => 2,
            'VisitDate' => '2024-12-25',
            'Guests' => [
                ['Name' => 'John Doe'],
                ['Name' => 'Jane Doe']
            ]
        ];

        $this->assertEquals($expected, $smartOrderFormat);
    }

    /** @test */
    public function it_handles_multi_day_bookings()
    {
        $request = new BookingRequest(
            productId: 'disney-park-hopper-3day',
            rateId: 'adult',
            startDate: Carbon::parse('2024-12-25'),
            endDate: Carbon::parse('2024-12-27'),
            quantity: 1
        );

        $this->assertEquals('2024-12-25', $request->startDate->toDateString());
        $this->assertEquals('2024-12-27', $request->endDate->toDateString());
        
        $format = $request->toRedeamHoldFormat();
        $this->assertEquals('2024-12-25', $format['start_date']);
        $this->assertEquals('2024-12-27', $format['end_date']);
    }

    /** @test */
    public function it_validates_required_fields()
    {
        $this->expectException(\TypeError::class);

        new BookingRequest(
            productId: '',
            rateId: 'adult',
            startDate: Carbon::now(),
            endDate: Carbon::now(),
            quantity: 0
        );
    }

    /** @test */
    public function it_can_convert_to_array()
    {
        $request = new BookingRequest(
            productId: 'disney-magic-kingdom-1day',
            rateId: 'adult',
            startDate: Carbon::parse('2024-12-25'),
            endDate: Carbon::parse('2024-12-25'),
            quantity: 2,
            availabilityId: 'avail-123',
            guestInfo: [['name' => 'John Doe']]
        );

        $array = $request->toArray();

        $this->assertIsArray($array);
        $this->assertEquals('disney-magic-kingdom-1day', $array['product_id']);
        $this->assertEquals('adult', $array['rate_id']);
        $this->assertEquals('2024-12-25', $array['start_date']);
        $this->assertEquals(2, $array['quantity']);
        $this->assertEquals('avail-123', $array['availability_id']);
    }
}