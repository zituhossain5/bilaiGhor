<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KittenPack extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'tier_label',
        'badge',
        'theme',
        'image',
        'price',
        'old_price',
        'product_id',
        'status',
        'sort_order',
    ];

    protected $casts = [
        'price'      => 'decimal:2',
        'old_price'  => 'decimal:2',
        'status'     => 'boolean',
        'sort_order' => 'integer',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(KittenPackItem::class)->orderBy('sort_order')->orderBy('id');
    }

    /** The product that actually gets added to the cart when someone buys this pack. */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 1);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    public function getIsDarkAttribute(): bool
    {
        return $this->theme === 'dark';
    }

    /** "23 Items" on the card — the total pieces included, never stored, so it cannot drift. */
    public function getItemCountAttribute(): int
    {
        return (int) $this->items->where('is_included', true)->sum('quantity');
    }

    /** "You save ৳66 vs buying separately" — derived from the two prices. */
    public function getSavingsAttribute(): float
    {
        if (!$this->old_price || $this->old_price <= $this->price) {
            return 0;
        }

        return (float) $this->old_price - (float) $this->price;
    }
}
