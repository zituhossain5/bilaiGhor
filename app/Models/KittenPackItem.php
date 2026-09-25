<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KittenPackItem extends Model
{
    protected $fillable = [
        'kitten_pack_id',
        'product_id',
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

    /** The live product name; the stored name is only a snapshot / label for unlinked rows. */
    public function getDisplayNameAttribute(): string
    {
        return $this->product->name ?? (string) $this->name;
    }

    /** An included row with no product cannot be shipped, so it blocks the pack. */
    public function getNeedsProductAttribute(): bool
    {
        return $this->product_id === null;
    }

    public function pack(): BelongsTo
    {
        return $this->belongsTo(KittenPack::class, 'kitten_pack_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
