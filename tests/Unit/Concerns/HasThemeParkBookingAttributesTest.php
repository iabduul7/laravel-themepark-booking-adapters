<?php

namespace iabduul7\ThemeParkBooking\Tests\Unit\Concerns;

use iabduul7\ThemeParkBooking\Concerns\HasThemeParkBookingAttributes;
use iabduul7\ThemeParkBooking\Models\OrderDetailsRedeam;
use iabduul7\ThemeParkBooking\Models\OrderDetailsUniversal;
use iabduul7\ThemeParkBooking\Tests\TestCase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

class HasThemeParkBookingAttributesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create orders table for testing
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('status')->default('pending');
            $table->timestamps();
        });
    }

    /** @test */
    public function it_can_check_if_order_has_disney_items()
    {
        $this->skipIfMethodMissing(TestOrder::class, 'hasDisneyItems');

        $order = TestOrder::create(['status' => 'confirmed']);

        // No disney items initially
        $this->assertFalse($order->hasDisneyItems());

        // Add disney booking details
        OrderDetailsRedeam::create([
            'order_id' => $order->id,
            'supplier_type' => 'disney',
            'status' => 'confirmed',
        ]);

        // Refresh and check
        $order->refresh();
        $this->assertTrue($order->hasDisneyItems());
    }

    /** @test */
    public function it_can_check_if_order_has_universal_items()
    {
        $this->skipIfMethodMissing(TestOrder::class, 'hasUniversalItems');

        $order = TestOrder::create(['status' => 'confirmed']);

        $this->assertFalse($order->hasUniversalItems());

        OrderDetailsUniversal::create([
            'order_id' => $order->id,
            'status' => 'confirmed',
        ]);

        $order->refresh();
        $this->assertTrue($order->hasUniversalItems());
    }

    /** @test */
    public function it_can_check_if_order_has_united_parks_items()
    {
        $this->skipIfMethodMissing(TestOrder::class, 'hasUnitedParksItems');

        $order = TestOrder::create(['status' => 'confirmed']);

        $this->assertFalse($order->hasUnitedParksItems());

        OrderDetailsRedeam::create([
            'order_id' => $order->id,
            'supplier_type' => 'united_parks',
            'status' => 'confirmed',
        ]);

        $order->refresh();
        $this->assertTrue($order->hasUnitedParksItems());
    }

    /** @test */
    public function it_can_get_disney_booking_details()
    {
        $this->skipIfMethodMissing(TestOrder::class, 'getDisneyBookingDetails');

        $order = TestOrder::create(['status' => 'confirmed']);

        $disneyDetails = OrderDetailsRedeam::create([
            'order_id' => $order->id,
            'supplier_type' => 'disney',
            'product_name' => 'Magic Kingdom 1-Day',
            'status' => 'confirmed',
        ]);

        $retrievedDetails = $order->getDisneyBookingDetails();

        $this->assertCount(1, $retrievedDetails);
        $this->assertEquals($disneyDetails->id, $retrievedDetails->first()->id);
        $this->assertEquals('Magic Kingdom 1-Day', $retrievedDetails->first()->product_name);
    }

    /** @test */
    public function it_can_get_universal_booking_details()
    {
        $this->skipIfMethodMissing(TestOrder::class, 'getUniversalBookingDetails');

        $order = TestOrder::create(['status' => 'confirmed']);

        $universalDetails = OrderDetailsUniversal::create([
            'order_id' => $order->id,
            'product_name' => 'Universal Studios 1-Day',
            'status' => 'confirmed',
        ]);

        $retrievedDetails = $order->getUniversalBookingDetails();

        $this->assertCount(1, $retrievedDetails);
        $this->assertEquals($universalDetails->id, $retrievedDetails->first()->id);
        $this->assertEquals('Universal Studios 1-Day', $retrievedDetails->first()->product_name);
    }

    /** @test */
    public function it_can_get_united_parks_booking_details()
    {
        $this->skipIfMethodMissing(TestOrder::class, 'getUnitedParksBookingDetails');

        $order = TestOrder::create(['status' => 'confirmed']);

        $unitedParksDetails = OrderDetailsRedeam::create([
            'order_id' => $order->id,
            'supplier_type' => 'united_parks',
            'product_name' => 'SeaWorld Orlando 1-Day',
            'status' => 'confirmed',
        ]);

        $retrievedDetails = $order->getUnitedParksBookingDetails();

        $this->assertCount(1, $retrievedDetails);
        $this->assertEquals($unitedParksDetails->id, $retrievedDetails->first()->id);
        $this->assertEquals('SeaWorld Orlando 1-Day', $retrievedDetails->first()->product_name);
    }

    /** @test */
    public function it_can_check_hold_status()
    {
        $this->skipIfMethodMissing(TestOrder::class, 'hasActiveHolds');

        $order = TestOrder::create(['status' => 'confirmed']);

        // No hold initially
        $this->assertFalse($order->hasActiveHolds());

        // Create a hold
        OrderDetailsRedeam::create([
            'order_id' => $order->id,
            'supplier_type' => 'disney',
            'status' => 'on_hold',
            'hold_id' => 'HOLD123',
            'hold_expires_at' => now()->addMinutes(15),
        ]);

        $order->refresh();
        $this->assertTrue($order->hasActiveHolds());
    }

    /** @test */
    public function it_can_get_booking_confirmation_data()
    {
        $this->skipIfMethodMissing(TestOrder::class, 'getBookingConfirmationData');

        $order = TestOrder::create(['status' => 'confirmed']);

        $redeamDetails = OrderDetailsRedeam::create([
            'order_id' => $order->id,
            'supplier_type' => 'disney',
            'status' => 'confirmed',
            'booking_id' => 'BOOK123',
            'reference_number' => 'REF456',
            'voucher_url' => 'https://vouchers.test/voucher_123.pdf',
        ]);

        $universalDetails = OrderDetailsUniversal::create([
            'order_id' => $order->id,
            'status' => 'confirmed',
            'galaxy_order_id' => 'GAL789',
            'external_order_id' => 'EXT012-2KNOW',
            'tickets_data' => [['ticket_id' => 'TKT001', 'barcode' => '123456789']],
        ]);

        $confirmationData = $order->getBookingConfirmationData();

        $this->assertArrayHasKey('redeam_bookings', $confirmationData);
        $this->assertArrayHasKey('universal_bookings', $confirmationData);

        $redeamBookings = $confirmationData['redeam_bookings'];
        $this->assertCount(1, $redeamBookings);
        $this->assertEquals('BOOK123', $redeamBookings->first()->booking_id);

        $universalBookings = $confirmationData['universal_bookings'];
        $this->assertCount(1, $universalBookings);
        $this->assertEquals('GAL789', $universalBookings->first()->galaxy_order_id);
    }

    /** @test */
    public function it_can_get_voucher_information()
    {
        $this->skipIfMethodMissing(TestOrder::class, 'getAllVouchers');

        $order = TestOrder::create(['status' => 'confirmed']);

        OrderDetailsRedeam::create([
            'order_id' => $order->id,
            'supplier_type' => 'disney',
            'status' => 'confirmed',
            'voucher_url' => 'https://vouchers.test/disney_voucher.pdf',
            'voucher_data' => ['barcode' => 'DISNEY123'],
        ]);

        OrderDetailsUniversal::create([
            'order_id' => $order->id,
            'status' => 'confirmed',
            'tickets_data' => [
                ['ticket_id' => 'TKT001', 'barcode' => 'UNIVERSAL123'],
            ],
        ]);

        $vouchers = $order->getAllVouchers();

        $this->assertArrayHasKey('redeam_vouchers', $vouchers);
        $this->assertArrayHasKey('universal_tickets', $vouchers);

        $this->assertCount(1, $vouchers['redeam_vouchers']);
        $this->assertCount(1, $vouchers['universal_tickets']);
    }

    /** @test */
    public function relationships_work_correctly()
    {
        $this->skipIfMethodMissing(TestOrder::class, 'redeamBookingDetails');

        $order = TestOrder::create(['status' => 'confirmed']);

        // Test relationships are defined
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $order->redeamBookingDetails());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $order->universalBookingDetails());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $order->disneyBookingDetails());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $order->unitedParksBookingDetails());

        // Test relationships return correct collections when empty
        $this->assertCount(0, $order->redeamBookingDetails);
        $this->assertCount(0, $order->universalBookingDetails);
    }
}

/**
 * Test model that uses the HasThemeParkBookingAttributes trait.
 */
class TestOrder extends Model
{
    use HasThemeParkBookingAttributes;

    protected $table = 'orders';
    protected $fillable = ['status'];
}
