<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A courier parcel and its latest known state (see config/courier_tracking.php).
 */
class CourierParcel extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'status_at'               => 'datetime',
        'entered_at'              => 'datetime',
        'approved_at'             => 'datetime',
        'picked_at'               => 'datetime',
        'out_for_delivery_at'     => 'datetime',
        'delivered_at'            => 'datetime',
        'returned_at'             => 'datetime',
        'last_event_at'           => 'datetime',
        'last_synced_at'          => 'datetime',
        'last_sync_error_at'      => 'datetime',
        'order_status_applied_at' => 'datetime',
        'linked_at'               => 'datetime',
        'courier_cod'             => 'decimal:2',
        'delivery_charge'         => 'decimal:2',
        'delivery_charge_paid'    => 'boolean',
        'is_current'              => 'boolean',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(CourierTrackingEvent::class)
            ->orderByDesc('event_time')->orderByDesc('id');
    }

    public function statusInfo(): array
    {
        return config('courier_tracking.statuses.' . $this->status)
            ?? config('courier_tracking.statuses.unknown');
    }

    public function isFinal(): bool
    {
        return (bool) ($this->statusInfo()['final'] ?? false);
    }

    /** No new event for longer than config('courier_tracking.stuck_days'), while still on its way. */
    public function isStuck(): bool
    {
        $last = $this->last_event_at ?? $this->created_at;

        return !$this->isFinal() && $last && $last->lt(now()->subDays(config('courier_tracking.stuck_days', 3)));
    }

    public function publicTrackingUrl(): ?string
    {
        $pattern = config('courier_tracking.public_tracking_url.' . $this->courier);
        $id = $this->courier === 'steadfast' ? ($this->tracking_code ?: $this->consignment_id) : $this->consignment_id;

        return $pattern && filled($id) ? sprintf($pattern, rawurlencode($id)) : null;
    }
}
