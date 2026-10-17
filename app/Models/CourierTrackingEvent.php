<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One entry of a parcel's tracking timeline.
 */
class CourierTrackingEvent extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'event_time'              => 'datetime',
        'event_time_from_courier' => 'boolean',
        'raw_payload'             => 'array',
    ];

    public function parcel(): BelongsTo
    {
        return $this->belongsTo(CourierParcel::class, 'courier_parcel_id');
    }

    public function statusInfo(): ?array
    {
        return $this->normalized_status ? config('courier_tracking.statuses.' . $this->normalized_status) : null;
    }
}
