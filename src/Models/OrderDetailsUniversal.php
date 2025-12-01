<?php

namespace iabduul7\ThemeParkBooking\Models;

use Illuminate\Contracts\Mail\Attachable;
use Illuminate\Database\Eloquent\Concerns\HasTimestamps;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Mail\Attachment;
use Illuminate\Support\Arr;

class OrderDetailsUniversal extends Model implements Attachable
{
    use HasTimestamps;
    use SoftDeletes;

    protected $table = 'order_details_universal';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'order_id',
        'galaxy_order_id',
        'external_order_id',
        'booking_data',
        'voucher',
        'confirmation_number',
        'status',
        'supplier_reference',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'booking_data' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $appends = [
        'voucher_url',
        'has_created_ticket_responses',
        'booking_status',
        'ticket_count',
    ];

    /**
     * Get the voucher URL attribute
     */
    public function getVoucherUrlAttribute(): string
    {
        if (empty($this->voucher)) {
            return '';
        }

        // Check if it's a full URL or a storage path
        if (filter_var($this->voucher, FILTER_VALIDATE_URL)) {
            return $this->voucher;
        }

        // Use storage path for local files
        if (function_exists('storage_path')) {
            return storage_path("app/{$this->voucher}");
        }

        return $this->voucher;
    }

    /**
     * Check if booking has created ticket responses
     */
    public function getHasCreatedTicketResponsesAttribute(): bool
    {
        if (! $this->booking_data) {
            return false;
        }

        return Arr::has($this->booking_data, 'createdTicketResponses') &&
               ! empty($this->booking_data['createdTicketResponses']);
    }

    /**
     * Get booking status
     */
    public function getBookingStatusAttribute(): ?string
    {
        if (! $this->booking_data) {
            return $this->status;
        }

        // Check various status fields in booking data
        return $this->booking_data['status'] ??
               $this->booking_data['orderStatus'] ??
               $this->status;
    }

    /**
     * Get ticket count from booking data
     */
    public function getTicketCountAttribute(): int
    {
        if (! $this->has_created_ticket_responses) {
            return 0;
        }

        $tickets = $this->booking_data['createdTicketResponses'] ?? [];

        return count($tickets);
    }

    /**
     * Get created ticket responses
     */
    public function getCreatedTicketResponses(): array
    {
        if (! $this->has_created_ticket_responses) {
            return [];
        }

        return $this->booking_data['createdTicketResponses'] ?? [];
    }

    /**
     * Get Galaxy Order details
     */
    public function getGalaxyOrderDetails(): array
    {
        if (! $this->booking_data) {
            return [];
        }

        return [
            'galaxy_order_id' => $this->galaxy_order_id,
            'external_order_id' => $this->external_order_id,
            'status' => $this->booking_status,
            'confirmation_number' => $this->confirmation_number,
            'supplier_reference' => $this->supplier_reference,
            'ticket_count' => $this->ticket_count,
        ];
    }

    /**
     * Get booking confirmation details
     */
    public function getConfirmationDetails(): array
    {
        return [
            'galaxy_order_id' => $this->galaxy_order_id,
            'external_order_id' => $this->external_order_id,
            'confirmation_number' => $this->confirmation_number,
            'status' => $this->booking_status,
            'voucher_url' => $this->voucher_url,
            'tickets_created' => $this->has_created_ticket_responses,
            'ticket_count' => $this->ticket_count,
        ];
    }

    /**
     * Check if booking is confirmed
     */
    public function isConfirmed(): bool
    {
        $status = strtolower($this->booking_status ?? '');

        return in_array($status, ['confirmed', 'booked', 'completed', 'success']);
    }

    /**
     * Check if booking is cancelled
     */
    public function isCancelled(): bool
    {
        $status = strtolower($this->booking_status ?? '');

        return in_array($status, ['cancelled', 'canceled', 'failed']);
    }

    /**
     * Check if booking is pending
     */
    public function isPending(): bool
    {
        $status = strtolower($this->booking_status ?? '');

        return in_array($status, ['pending', 'processing', 'submitted']);
    }

    /**
     * Get tickets information from booking data
     */
    public function getTicketsInfo(): array
    {
        $tickets = $this->getCreatedTicketResponses();
        $info = [];

        foreach ($tickets as $ticket) {
            $info[] = [
                'ticket_id' => $ticket['ticketId'] ?? null,
                'barcode' => $ticket['barcode'] ?? null,
                'product_name' => $ticket['productName'] ?? null,
                'guest_name' => $ticket['guestName'] ?? null,
                'visit_date' => $ticket['visitDate'] ?? null,
                'status' => $ticket['status'] ?? null,
            ];
        }

        return $info;
    }

    /**
     * Relationship to order
     */
    public function order(): BelongsTo
    {
        // This will need to be configured to point to the application's Order model
        $orderModel = config('themepark-booking.order_model', 'App\Models\Order');

        return $this->belongsTo($orderModel);
    }

    /**
     * Mail attachment for voucher
     */
    public function toMailAttachment(): Attachment
    {
        if (empty($this->voucher)) {
            throw new \Exception('No voucher available for attachment');
        }

        return Attachment::fromPath($this->voucher);
    }

    /**
     * Scope for confirmed bookings
     */
    public function scopeConfirmed($query)
    {
        return $query->whereIn('status', ['confirmed', 'booked', 'completed', 'success'])
                    ->orWhereJsonContains('booking_data->status', 'CONFIRMED')
                    ->orWhereJsonContains('booking_data->status', 'SUCCESS');
    }

    /**
     * Scope for bookings with tickets
     */
    public function scopeWithTickets($query)
    {
        return $query->whereJsonLength('booking_data->createdTicketResponses', '>', 0);
    }

    /**
     * Scope for Universal bookings (this is the default for this model)
     */
    public function scopeUniversal($query)
    {
        return $query; // All records in this table are Universal bookings
    }
}
