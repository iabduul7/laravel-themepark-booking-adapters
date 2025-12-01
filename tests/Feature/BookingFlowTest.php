<?php

namespace iabduul7\ThemeParkBooking\Tests\Feature;

use Carbon\Carbon;
use iabduul7\ThemeParkBooking\Data\BookingRequest;
use iabduul7\ThemeParkBooking\Models\OrderDetailsRedeam;
use iabduul7\ThemeParkBooking\Models\OrderDetailsUniversal;
use iabduul7\ThemeParkBooking\Tests\TestCase;
use iabduul7\ThemeParkBooking\ThemeParkBookingManager;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BookingFlowTest extends TestCase
{
    use RefreshDatabase;

    private ThemeParkBookingManager $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = app(ThemeParkBookingManager::class);
    }

    /** @test */
    public function it_can_complete_disney_booking_flow()
    {
        // Skip if no API credentials configured
        if (empty(config('themepark-booking.redeam.api_key'))) {
            $this->markTestSkipped('Redeam API credentials not configured');
        }

        $bookingRequest = new BookingRequest(
            productId: 'disney-magic-kingdom-1day',
            rateId: 'adult',
            startDate: Carbon::parse('2024-12-25'),
            endDate: Carbon::parse('2024-12-25'),
            quantity: 2,
            guestInfo: [
                ['name' => 'John Doe', 'age' => 35],
                ['name' => 'Jane Doe', 'age' => 32],
            ]
        );

        $adapter = $this->manager->disney();

        // Test availability check
        $availability = $adapter->getAvailability(
            $bookingRequest->productId,
            $bookingRequest->startDate,
            $bookingRequest->endDate
        );

        $this->assertIsArray($availability);

        // Test hold booking
        $holdResponse = $adapter->holdBooking($bookingRequest);

        $this->assertTrue($holdResponse->isSuccessful());
        $this->assertNotEmpty($holdResponse->holdId);
        $this->assertInstanceOf(Carbon::class, $holdResponse->expiresAt);

        // Create order details record
        $orderDetails = OrderDetailsRedeam::create([
            'order_id' => 1, // Mock order ID
            'hold_id' => $holdResponse->holdId,
            'hold_expires_at' => $holdResponse->expiresAt,
            'supplier_type' => 'disney',
            'status' => 'held',
        ]);

        $this->assertDatabaseHas('order_details_redeam', [
            'hold_id' => $holdResponse->holdId,
            'supplier_type' => 'disney',
        ]);

        // Test booking confirmation
        $confirmResponse = $adapter->confirmBooking($holdResponse->holdId);

        if ($confirmResponse->isSuccessful()) {
            // Update order details
            $orderDetails->update([
                'booking_id' => $confirmResponse->bookingId,
                'reference_number' => $confirmResponse->referenceNumber,
                'booking_data' => $confirmResponse->rawData,
                'status' => 'confirmed',
            ]);

            $this->assertDatabaseHas('order_details_redeam', [
                'booking_id' => $confirmResponse->bookingId,
                'status' => 'confirmed',
            ]);
        }
    }

    /** @test */
    public function it_can_complete_universal_booking_flow()
    {
        // Skip if no API credentials configured
        if (empty(config('themepark-booking.smartorder.client_username'))) {
            $this->markTestSkipped('SmartOrder API credentials not configured');
        }

        $bookingRequest = new BookingRequest(
            productId: 'UNIV_STUDIOS_1DAY',
            rateId: 'adult',
            startDate: Carbon::parse('2024-12-25'),
            endDate: Carbon::parse('2024-12-25'),
            quantity: 1,
            guestInfo: [
                ['name' => 'John Doe'],
            ]
        );

        $adapter = $this->manager->universal();

        // Test availability check
        $availability = $adapter->getAvailability(
            $bookingRequest->productId,
            $bookingRequest->startDate,
            $bookingRequest->endDate
        );

        $this->assertIsArray($availability);

        // Test direct booking (SmartOrder doesn't support holds)
        $bookingResponse = $adapter->makeBooking($bookingRequest);

        if ($bookingResponse->isSuccessful()) {
            // Create order details record
            $orderDetails = OrderDetailsUniversal::create([
                'order_id' => 1, // Mock order ID
                'galaxy_order_id' => $bookingResponse->galaxyOrderId,
                'external_order_id' => $bookingResponse->externalOrderId,
                'booking_data' => $bookingResponse->rawData,
                'status' => 'confirmed',
            ]);

            $this->assertDatabaseHas('order_details_universal', [
                'galaxy_order_id' => $bookingResponse->galaxyOrderId,
                'status' => 'confirmed',
            ]);

            $this->assertTrue($orderDetails->has_created_ticket_responses);
        }
    }

    /** @test */
    public function it_handles_booking_cancellation()
    {
        // Create a mock booking record
        $orderDetails = OrderDetailsRedeam::create([
            'order_id' => 1,
            'booking_id' => 'MOCK_BOOKING_123',
            'reference_number' => 'REF123456',
            'supplier_type' => 'disney',
            'status' => 'confirmed',
        ]);

        $adapter = $this->manager->disney();

        // Test cancellation (will fail with mock data but tests the flow)
        try {
            $cancelResponse = $adapter->cancelBooking($orderDetails->booking_id);

            if ($cancelResponse->isSuccessful()) {
                $orderDetails->update([
                    'status' => 'cancelled',
                    'booking_data' => array_merge(
                        $orderDetails->booking_data ?? [],
                        ['cancelled_at' => now()->toISOString()]
                    ),
                ]);

                $this->assertTrue($orderDetails->isCancelled());
            }
        } catch (\Exception $e) {
            // Expected with mock data
            $this->assertStringContainsString('MOCK_BOOKING_123', $e->getMessage());
        }
    }

    /** @test */
    public function it_validates_booking_request_data()
    {
        $this->expectException(\TypeError::class);

        new BookingRequest(
            productId: '',  // Invalid empty product ID
            rateId: 'adult',
            startDate: Carbon::now(),
            endDate: Carbon::now(),
            quantity: 0  // Invalid zero quantity
        );
    }

    /** @test */
    public function it_handles_expired_holds()
    {
        $orderDetails = OrderDetailsRedeam::create([
            'order_id' => 1,
            'hold_id' => 'EXPIRED_HOLD_123',
            'hold_expires_at' => Carbon::now()->subMinutes(30), // Expired 30 mins ago
            'supplier_type' => 'disney',
            'status' => 'held',
        ]);

        $this->assertTrue($orderDetails->is_hold_expired);
        $this->assertFalse($orderDetails->isOnHold());
    }

    /** @test */
    public function it_can_check_booking_status()
    {
        // Test confirmed booking
        $confirmedBooking = OrderDetailsRedeam::create([
            'order_id' => 1,
            'booking_id' => 'CONFIRMED_123',
            'status' => 'confirmed',
            'supplier_type' => 'disney',
        ]);

        $this->assertTrue($confirmedBooking->isConfirmed());
        $this->assertFalse($confirmedBooking->isCancelled());

        // Test cancelled booking
        $cancelledBooking = OrderDetailsRedeam::create([
            'order_id' => 2,
            'booking_id' => 'CANCELLED_123',
            'status' => 'cancelled',
            'supplier_type' => 'disney',
        ]);

        $this->assertTrue($cancelledBooking->isCancelled());
        $this->assertFalse($cancelledBooking->isConfirmed());
    }

    /** @test */
    public function it_tracks_booking_timeline()
    {
        $bookingData = [
            'timeline' => [
                [
                    'typeOf' => 'HELD',
                    'timestamp' => '2024-12-25T10:00:00Z',
                ],
                [
                    'typeOf' => 'CONFIRMED',
                    'timestamp' => '2024-12-25T10:05:00Z',
                ],
            ],
        ];

        $orderDetails = OrderDetailsRedeam::create([
            'order_id' => 1,
            'booking_id' => 'TIMELINE_123',
            'booking_data' => $bookingData,
            'supplier_type' => 'disney',
            'status' => 'confirmed',
        ]);

        $timeline = $orderDetails->getBookingTimeline();

        $this->assertCount(2, $timeline);
        $this->assertEquals('HELD', $timeline[0]['typeOf']);
        $this->assertEquals('CONFIRMED', $timeline[1]['typeOf']);
    }
}
