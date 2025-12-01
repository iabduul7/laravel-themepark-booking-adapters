<?php

namespace iabduul7\ThemeParkBooking\Tests\Unit\Models;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

iabduul7\ThemeParkBooking\Models\OrderDetailsUniversal;
iabduul7\ThemeParkBooking\Tests\TestCase;

class OrderDetailsUniversalTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_order_details_universal_record()
    {
        $orderDetails = OrderDetailsUniversal::create([
            'order_id' => 1,
            'product_id' => 'UNIV_STUDIOS_1DAY',
            'galaxy_order_id' => 'GAL123456',
            'external_order_id' => 'EXT789-2KNOW',
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('order_details_universal', [
            'order_id' => 1,
            'product_id' => 'UNIV_STUDIOS_1DAY',
            'galaxy_order_id' => 'GAL123456',
            'external_order_id' => 'EXT789-2KNOW',
            'status' => 'pending',
        ]);
    }

    /** @test */
    public function it_has_correct_fillable_attributes()
    {
        $orderDetails = new OrderDetailsUniversal();

        $expectedFillable = [
            'order_id',
            'product_id',
            'product_name',
            'galaxy_order_id',
            'external_order_id',
            'status',
            'visit_date',
            'guest_details',
            'tickets_data',
            'confirmation_details',
            'cancellation_details',
            'error_details',
            'confirmed_at',
            'cancelled_at',
        ];

        $this->assertEquals($expectedFillable, $orderDetails->getFillable());
    }

    /** @test */
    public function it_casts_attributes_correctly()
    {
        $orderDetails = OrderDetailsUniversal::create([
            'order_id' => 1,
            'status' => 'confirmed',
            'guest_details' => ['name' => 'Jane Doe'],
            'tickets_data' => [['ticket_id' => 'TKT001', 'barcode' => '123']],
            'confirmation_details' => ['galaxy_id' => 'GAL123'],
            'visit_date' => '2024-12-25',
            'confirmed_at' => '2024-12-20 09:00:00',
        ]);

        $this->assertIsArray($orderDetails->guest_details);
        $this->assertIsArray($orderDetails->tickets_data);
        $this->assertIsArray($orderDetails->confirmation_details);
        $this->assertInstanceOf(Carbon::class, $orderDetails->visit_date);
        $this->assertInstanceOf(Carbon::class, $orderDetails->confirmed_at);
    }

    /** @test */
    public function it_has_status_scopes()
    {
        OrderDetailsUniversal::create(['order_id' => 1, 'status' => 'pending']);
        OrderDetailsUniversal::create(['order_id' => 2, 'status' => 'confirmed']);
        OrderDetailsUniversal::create(['order_id' => 3, 'status' => 'cancelled']);
        OrderDetailsUniversal::create(['order_id' => 4, 'status' => 'pending']);

        $this->assertCount(2, OrderDetailsUniversal::pending()->get());
        $this->assertCount(1, OrderDetailsUniversal::confirmed()->get());
        $this->assertCount(1, OrderDetailsUniversal::cancelled()->get());
    }

    /** @test */
    public function it_has_status_checker_methods()
    {
        $pending = OrderDetailsUniversal::create(['order_id' => 1, 'status' => 'pending']);
        $confirmed = OrderDetailsUniversal::create(['order_id' => 2, 'status' => 'confirmed']);
        $cancelled = OrderDetailsUniversal::create(['order_id' => 3, 'status' => 'cancelled']);

        $this->assertTrue($pending->isPending());
        $this->assertFalse($pending->isConfirmed());
        $this->assertFalse($pending->isCancelled());

        $this->assertTrue($confirmed->isConfirmed());
        $this->assertFalse($confirmed->isPending());

        $this->assertTrue($cancelled->isCancelled());
        $this->assertFalse($cancelled->isConfirmed());
    }

    /** @test */
    public function it_can_manage_tickets_data()
    {
        $ticketsData = [
            [
                'ticket_id' => 'TKT001',
                'barcode' => '123456789',
                'product_name' => 'Universal Studios 1-Day',
                'guest_name' => 'John Doe',
                'visit_date' => '2024-12-25',
            ],
            [
                'ticket_id' => 'TKT002',
                'barcode' => '987654321',
                'product_name' => 'Universal Studios 1-Day',
                'guest_name' => 'Jane Doe',
                'visit_date' => '2024-12-25',
            ],
        ];

        $orderDetails = OrderDetailsUniversal::create([
            'order_id' => 1,
            'status' => 'confirmed',
            'tickets_data' => $ticketsData,
        ]);

        $this->assertTrue($orderDetails->hasTickets());
        $this->assertCount(2, $orderDetails->getTicketsData());
        $this->assertEquals($ticketsData, $orderDetails->getTicketsData());
        $this->assertCount(2, $orderDetails->getTicketBarcodes());
        $this->assertContains('123456789', $orderDetails->getTicketBarcodes());
        $this->assertContains('987654321', $orderDetails->getTicketBarcodes());
    }

    /** @test */
    public function it_returns_empty_data_when_no_tickets()
    {
        $orderDetails = OrderDetailsUniversal::create([
            'order_id' => 1,
            'status' => 'pending',
        ]);

        $this->assertFalse($orderDetails->hasTickets());
        $this->assertEquals([], $orderDetails->getTicketsData());
        $this->assertEquals([], $orderDetails->getTicketBarcodes());
    }

    /** @test */
    public function it_can_get_specific_ticket_by_id()
    {
        $ticketsData = [
            [
                'ticket_id' => 'TKT001',
                'barcode' => '123456789',
                'guest_name' => 'John Doe',
            ],
            [
                'ticket_id' => 'TKT002',
                'barcode' => '987654321',
                'guest_name' => 'Jane Doe',
            ],
        ];

        $orderDetails = OrderDetailsUniversal::create([
            'order_id' => 1,
            'status' => 'confirmed',
            'tickets_data' => $ticketsData,
        ]);

        $ticket = $orderDetails->getTicketById('TKT001');

        $this->assertIsArray($ticket);
        $this->assertEquals('TKT001', $ticket['ticket_id']);
        $this->assertEquals('John Doe', $ticket['guest_name']);

        $nonExistentTicket = $orderDetails->getTicketById('TKT999');
        $this->assertNull($nonExistentTicket);
    }

    /** @test */
    public function it_can_get_primary_guest_name()
    {
        $orderDetails = OrderDetailsUniversal::create([
            'order_id' => 1,
            'status' => 'confirmed',
            'guest_details' => ['primary_guest' => 'John Doe'],
            'tickets_data' => [
                ['ticket_id' => 'TKT001', 'guest_name' => 'Jane Smith'],
            ],
        ]);

        // Should return from guest_details first
        $this->assertEquals('John Doe', $orderDetails->getPrimaryGuestName());

        // Test fallback to first ticket
        $orderDetailsNoGuest = OrderDetailsUniversal::create([
            'order_id' => 2,
            'status' => 'confirmed',
            'tickets_data' => [
                ['ticket_id' => 'TKT002', 'guest_name' => 'Bob Wilson'],
            ],
        ]);

        $this->assertEquals('Bob Wilson', $orderDetailsNoGuest->getPrimaryGuestName());

        // Test when no guest data available
        $orderDetailsEmpty = OrderDetailsUniversal::create([
            'order_id' => 3,
            'status' => 'pending',
        ]);

        $this->assertNull($orderDetailsEmpty->getPrimaryGuestName());
    }
}
