<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KittenPackItem extends Model
{
    protected $fillable = [
        'kitten_pack_id',
        'name',
        'quantity',
        'is_included',
        'sort_order',
    ];

    protected $casts = [
        'quantity'    => 'integer',
        'is_included' => 'boolean',
        'sort_order'  => 'integer',
    ];

    /** Rendered after the item name, e.g. "4 pcs". */
    public function getQuantityLabelAttribute(): string
    {
        return $this->quantity . ' pcs';
    }

    public function pack(): BelongsTo
    {
        return $this->belongsTo(KittenPack::class, 'kitten_pack_id');
    }
}
