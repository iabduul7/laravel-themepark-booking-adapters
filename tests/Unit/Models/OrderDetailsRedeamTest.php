<?php

namespace iabduul7\ThemeParkBooking\Tests\Unit\Models;

use iabduul7\ThemeParkBooking\Models\OrderDetailsRedeam;
use iabduul7\ThemeParkBooking\Tests\TestCase;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

class OrderDetailsRedeamTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();        
    }

    /** @test */
    public function it_can_create_order_details_redeam_record()
    {
        $orderDetails = OrderDetailsRedeam::create([
            'order_id' => 1,
            'supplier_type' => 'disney',
            'reference_number' => 'REF123456',
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('order_details_redeam', [
            'order_id' => 1,
            'supplier_type' => 'disney',
            'reference_number' => 'REF123456',
            'status' => 'pending',
        ]);
    }

    /** @test */
    public function it_has_correct_fillable_attributes()
    {
        $orderDetails = new OrderDetailsRedeam();

        $expectedFillable = [
            'order_id',
            'reference_number',
            'hold_id',
            'hold_expires_at',
            'booking_id',
            'booking_data',
            'voucher',
            'supplier_type',
            'supplier_reference',
            'confirmation_number',
            'status',
            'created_by',
            'updated_by',
            'deleted_by',
        ];

        $this->assertEquals($expectedFillable, $orderDetails->getFillable());
    }

    /** @test */
    public function it_casts_attributes_correctly()
    {
        $orderDetails = OrderDetailsRedeam::create([
            'order_id' => 1,
            'supplier_type' => 'disney',
            'status' => 'confirmed',
            'booking_data' => ['reference' => 'BOOK123'],
            'hold_expires_at' => '2024-12-20 10:30:00',
        ]);

        $this->assertIsArray($orderDetails->booking_data);
        $this->assertInstanceOf(\Carbon\Carbon::class, $orderDetails->hold_expires_at);
    }

    /** @test */
    public function it_has_confirmed_status_scope()
    {
        OrderDetailsRedeam::create(['order_id' => 1, 'supplier_type' => 'disney', 'status' => 'confirmed']);
        OrderDetailsRedeam::create(['order_id' => 2, 'supplier_type' => 'disney', 'status' => 'pending']);

        $confirmed = OrderDetailsRedeam::confirmed()->get();

        $this->assertCount(1, $confirmed);
        $this->assertEquals('confirmed', $confirmed->first()->status);
    }

    /** @test */
    public function it_has_disney_supplier_scope()
    {
        OrderDetailsRedeam::create(['order_id' => 1, 'supplier_type' => 'disney', 'status' => 'pending']);
        OrderDetailsRedeam::create(['order_id' => 2, 'supplier_type' => 'united_parks', 'status' => 'pending']);

        $disney = OrderDetailsRedeam::disney()->get();

        $this->assertCount(1, $disney);
        $this->assertEquals('disney', $disney->first()->supplier_type);
    }

    /** @test */
    public function it_has_united_parks_supplier_scope()
    {
        OrderDetailsRedeam::create(['order_id' => 1, 'supplier_type' => 'disney', 'status' => 'pending']);
        OrderDetailsRedeam::create(['order_id' => 2, 'supplier_type' => 'united_parks', 'status' => 'pending']);

        $unitedParks = OrderDetailsRedeam::unitedParks()->get();

        $this->assertCount(1, $unitedParks);
        $this->assertEquals('united_parks', $unitedParks->first()->supplier_type);
    }

    /** @test */
    public function it_has_status_checker_methods()
    {
        $pending = OrderDetailsRedeam::create(['order_id' => 1, 'supplier_type' => 'disney', 'status' => 'pending']);
        $confirmed = OrderDetailsRedeam::create(['order_id' => 2, 'supplier_type' => 'disney', 'status' => 'confirmed']);
        $cancelled = OrderDetailsRedeam::create(['order_id' => 3, 'supplier_type' => 'disney', 'status' => 'cancelled']);
        $onHold = OrderDetailsRedeam::create(['order_id' => 4, 'supplier_type' => 'disney', 'status' => 'on_hold', 'hold_id' => 'HOLD123']);

        $this->assertFalse($pending->isConfirmed());
        $this->assertFalse($pending->isCancelled());
        $this->assertFalse($pending->isOnHold());

        $this->assertTrue($confirmed->isConfirmed());
        $this->assertFalse($confirmed->isCancelled());

        $this->assertTrue($cancelled->isCancelled());
        $this->assertFalse($cancelled->isConfirmed());

        $this->assertTrue($onHold->isOnHold());
        $this->assertFalse($onHold->isConfirmed());
    }

    /** @test */
    public function it_can_check_if_hold_is_expired()
    {
        $expiredHold = OrderDetailsRedeam::create([
            'order_id' => 1,
            'supplier_type' => 'disney',
            'status' => 'on_hold',
            'hold_expires_at' => Carbon::now()->subMinutes(5),
        ]);

        $validHold = OrderDetailsRedeam::create([
            'order_id' => 2,
            'supplier_type' => 'disney',
            'status' => 'on_hold',
            'hold_expires_at' => Carbon::now()->addMinutes(15),
        ]);

        $noHoldExpiry = OrderDetailsRedeam::create([
            'order_id' => 3,
            'supplier_type' => 'disney',
            'status' => 'confirmed',
        ]);

        $this->assertTrue($expiredHold->is_hold_expired);
        $this->assertFalse($validHold->is_hold_expired);
        $this->assertFalse($noHoldExpiry->is_hold_expired);
    }

    /** @test */
    public function it_can_get_voucher_information()
    {
        $orderDetails = OrderDetailsRedeam::create([
            'order_id' => 1,
            'supplier_type' => 'disney',
            'status' => 'confirmed',
            'voucher' => 'vouchers/ticket_123.pdf',
        ]);

        $this->assertNotEmpty($orderDetails->voucher);
        $this->assertNotEmpty($orderDetails->voucher_url);
    }

    /** @test */
    public function it_returns_default_values_when_no_voucher()
    {
        $orderDetails = OrderDetailsRedeam::create([
            'order_id' => 1,
            'supplier_type' => 'disney',
            'status' => 'pending',
        ]);

        $this->assertEmpty($orderDetails->voucher);
        $this->assertEquals('', $orderDetails->voucher_url);
    }
}
