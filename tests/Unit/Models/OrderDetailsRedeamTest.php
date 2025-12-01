<?php

namespace iabduul7\ThemeParkBooking\Tests\Unit\Models;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
iabduul7\ThemeParkBooking\Models\OrderDetailsRedeam;
iabduul7\ThemeParkBooking\Tests\TestCase;

class OrderDetailsRedeamTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_order_details_redeam_record()
    {
        $orderDetails = OrderDetailsRedeam::create([
            'order_id' => 1,
            'supplier_type' => 'disney',
            'supplier_id' => '20',
            'product_id' => 'magic-kingdom-1day',
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('order_details_redeam', [
            'order_id' => 1,
            'supplier_type' => 'disney',
            'supplier_id' => '20',
            'product_id' => 'magic-kingdom-1day',
            'status' => 'pending',
        ]);
    }

    /** @test */
    public function it_has_correct_fillable_attributes()
    {
        $orderDetails = new OrderDetailsRedeam();
        
        $expectedFillable = [
            'order_id',
            'supplier_type',
            'supplier_id', 
            'product_id',
            'product_name',
            'hold_id',
            'booking_id',
            'reference_number',
            'status',
            'visit_date',
            'guest_details',
            'voucher_url',
            'voucher_data',
            'confirmation_details',
            'cancellation_details',
            'error_details',
            'hold_expires_at',
            'confirmed_at',
            'cancelled_at',
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
            'guest_details' => ['name' => 'John Doe'],
            'voucher_data' => ['url' => 'test.pdf'],
            'confirmation_details' => ['id' => 'CONF123'],
            'visit_date' => '2024-12-25',
            'hold_expires_at' => '2024-12-20 10:30:00',
            'confirmed_at' => '2024-12-20 09:00:00',
        ]);

        $this->assertIsArray($orderDetails->guest_details);
        $this->assertIsArray($orderDetails->voucher_data);
        $this->assertIsArray($orderDetails->confirmation_details);
        $this->assertInstanceOf(Carbon::class, $orderDetails->visit_date);
        $this->assertInstanceOf(Carbon::class, $orderDetails->hold_expires_at);
        $this->assertInstanceOf(Carbon::class, $orderDetails->confirmed_at);
    }

    /** @test */
    public function it_has_pending_status_scope()
    {
        OrderDetailsRedeam::create(['order_id' => 1, 'supplier_type' => 'disney', 'status' => 'pending']);
        OrderDetailsRedeam::create(['order_id' => 2, 'supplier_type' => 'disney', 'status' => 'confirmed']);
        OrderDetailsRedeam::create(['order_id' => 3, 'supplier_type' => 'disney', 'status' => 'pending']);

        $pending = OrderDetailsRedeam::pending()->get();
        
        $this->assertCount(2, $pending);
        $this->assertTrue($pending->every(fn($order) => $order->status === 'pending'));
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
    public function it_has_cancelled_status_scope()
    {
        OrderDetailsRedeam::create(['order_id' => 1, 'supplier_type' => 'disney', 'status' => 'cancelled']);
        OrderDetailsRedeam::create(['order_id' => 2, 'supplier_type' => 'disney', 'status' => 'confirmed']);

        $cancelled = OrderDetailsRedeam::cancelled()->get();
        
        $this->assertCount(1, $cancelled);
        $this->assertEquals('cancelled', $cancelled->first()->status);
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
        $onHold = OrderDetailsRedeam::create(['order_id' => 4, 'supplier_type' => 'disney', 'status' => 'on_hold']);

        $this->assertTrue($pending->isPending());
        $this->assertFalse($pending->isConfirmed());
        $this->assertFalse($pending->isCancelled());
        $this->assertFalse($pending->isOnHold());

        $this->assertTrue($confirmed->isConfirmed());
        $this->assertFalse($confirmed->isPending());
        
        $this->assertTrue($cancelled->isCancelled());
        $this->assertFalse($cancelled->isConfirmed());
        
        $this->assertTrue($onHold->isOnHold());
        $this->assertFalse($onHold->isPending());
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

        $this->assertTrue($expiredHold->isHoldExpired());
        $this->assertFalse($validHold->isHoldExpired());
        $this->assertFalse($noHoldExpiry->isHoldExpired());
    }

    /** @test */
    public function it_can_get_voucher_information()
    {
        $orderDetails = OrderDetailsRedeam::create([
            'order_id' => 1,
            'supplier_type' => 'disney',
            'status' => 'confirmed',
            'voucher_url' => 'https://vouchers.redeam.io/ticket_123.pdf',
            'voucher_data' => [
                'barcode' => '123456789',
                'qr_code' => 'QR123ABC',
                'valid_until' => '2024-12-31'
            ],
        ]);

        $this->assertTrue($orderDetails->hasVoucher());
        $this->assertEquals('https://vouchers.redeam.io/ticket_123.pdf', $orderDetails->getVoucherUrl());
        $this->assertEquals(['barcode' => '123456789', 'qr_code' => 'QR123ABC', 'valid_until' => '2024-12-31'], $orderDetails->getVoucherData());
    }

    /** @test */
    public function it_returns_default_values_when_no_voucher()
    {
        $orderDetails = OrderDetailsRedeam::create([
            'order_id' => 1,
            'supplier_type' => 'disney',
            'status' => 'pending',
        ]);

        $this->assertFalse($orderDetails->hasVoucher());
        $this->assertNull($orderDetails->getVoucherUrl());
        $this->assertEquals([], $orderDetails->getVoucherData());
    }
}