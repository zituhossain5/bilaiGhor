<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Our thana → Steadfast police station (see the create migration for match_type). */
class ThanaSteadfastPoliceStation extends Model
{
    protected $table = 'thana_steadfast_police_station';

    protected $guarded = [];

    /** Match types that are used when sending. "close" is only a suggestion until an admin confirms it. */
    public const USABLE = ['exact', 'manual'];

    protected function casts(): array
    {
        return [
            'thana_id'                    => 'integer',
            'steadfast_police_station_id' => 'integer',
            'score'                       => 'float',
        ];
    }

    public function thana(): BelongsTo
    {
        return $this->belongsTo(DeliveryThana::class, 'thana_id');
    }

    public function station(): BelongsTo
    {
        return $this->belongsTo(SteadfastPoliceStation::class, 'steadfast_police_station_id');
    }
}
