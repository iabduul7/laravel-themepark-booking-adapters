<?php

namespace iabduul7\ThemeParkBooking\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasTimestamps;
use Illuminate\Contracts\Mail\Attachable;
use Illuminate\Mail\Attachment;
use Carbon\Carbon;

class OrderDetailsRedeam extends Model implements Attachable
{
    use HasTimestamps;
    use SoftDeletes;

    protected $table = 'order_details_redeam';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
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

    protected $casts = [
        'booking_data' => 'array',
        'hold_expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $appends = [
        'voucher_url',
        'is_disney',
        'is_united_parks',
        'is_hold_expired',
        'booking_status',
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

        // Use storage URL helper if available
        if (function_exists('storage_path')) {
            return storage_path("app/{$this->voucher}");
        }

        return $this->voucher;
    }

    /**
     * Check if this is a Disney booking
     */
    public function getIsDisneyAttribute(): bool
    {
        return $this->supplier_type === 'disney' || 
               str_contains(strtolower($this->supplier_type ?? ''), 'disney');
    }

    /**
     * Check if this is a United Parks booking
     */
    public function getIsUnitedParksAttribute(): bool
    {
        return $this->supplier_type === 'united_parks' || 
               str_contains(strtolower($this->supplier_type ?? ''), 'united');
    }

    /**
     * Check if hold has expired
     */
    public function getIsHoldExpiredAttribute(): bool
    {
        if (!$this->hold_expires_at) {
            return false;
        }

        return $this->hold_expires_at->isPast();
    }

    /**
     * Get booking status from booking data
     */
    public function getBookingStatusAttribute(): ?string
    {
        if (!$this->booking_data) {
            return $this->status;
        }

        return $this->booking_data['status'] ?? $this->status;
    }

    /**
     * Get booking timeline
     */
    public function getBookingTimeline(): array
    {
        if (!$this->booking_data) {
            return [];
        }

        return $this->booking_data['timeline'] ?? [];
    }

    /**
     * Get supplier reference number
     */
    public function getSupplierReference(): ?string
    {
        if ($this->supplier_reference) {
            return $this->supplier_reference;
        }

        if (!$this->booking_data) {
            return null;
        }

        return $this->booking_data['ext']['supplier']['reference'] ?? null;
    }

    /**
     * Get confirmation details
     */
    public function getConfirmationDetails(): array
    {
        if (!$this->booking_data) {
            return [];
        }

        return [
            'reference_number' => $this->reference_number,
            'booking_id' => $this->booking_id,
            'supplier_reference' => $this->getSupplierReference(),
            'confirmation_number' => $this->confirmation_number,
            'status' => $this->booking_status,
            'voucher_url' => $this->voucher_url,
        ];
    }

    /**
     * Check if booking is confirmed
     */
    public function isConfirmed(): bool
    {
        $status = strtolower($this->booking_status ?? '');
        return in_array($status, ['confirmed', 'booked', 'completed']);
    }

    /**
     * Check if booking is cancelled
     */
    public function isCancelled(): bool
    {
        $status = strtolower($this->booking_status ?? '');
        return in_array($status, ['cancelled', 'canceled']);
    }

    /**
     * Check if booking is on hold
     */
    public function isOnHold(): bool
    {
        return !empty($this->hold_id) && !$this->is_hold_expired;
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
     * Scope for Disney bookings
     */
    public function scopeDisney($query)
    {
        return $query->where('supplier_type', 'disney')
                    ->orWhere('supplier_type', 'like', '%disney%');
    }

    /**
     * Scope for United Parks bookings
     */
    public function scopeUnitedParks($query)
    {
        return $query->where('supplier_type', 'united_parks')
                    ->orWhere('supplier_type', 'like', '%united%');
    }

    /**
     * Scope for confirmed bookings
     */
    public function scopeConfirmed($query)
    {
        return $query->whereIn('status', ['confirmed', 'booked', 'completed'])
                    ->orWhereJsonContains('booking_data->status', 'CONFIRMED')
                    ->orWhereJsonContains('booking_data->status', 'BOOKED');
    }

    /**
     * Scope for active holds
     */
    public function scopeActiveHolds($query)
    {
        return $query->whereNotNull('hold_id')
                    ->where(function ($q) {
                        $q->whereNull('hold_expires_at')
                          ->orWhere('hold_expires_at', '>', Carbon::now());
                    });
    }
}
