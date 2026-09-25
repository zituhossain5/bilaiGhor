<?php

namespace App\Models;

use App\Services\InventoryService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
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
        'status',
        'sort_order',
    ];

    protected $casts = [
        'price'      => 'decimal:2',
        'old_price'  => 'decimal:2',
        'status'     => 'boolean',
        'sort_order' => 'integer',
    ];

    /** Every row on the card. Included rows are what ships and what stock is taken from. */
    public function items(): HasMany
    {
        return $this->hasMany(KittenPackItem::class)->orderBy('sort_order')->orderBy('id');
    }

    /** Packs sellable right now — see InventoryService::packAvailable(). */
    public function getAvailableStockAttribute(): int
    {
        return InventoryService::packAvailable($this->id);
    }

    public function getIsInStockAttribute(): bool
    {
        return $this->available_stock > 0;
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

    /** Cost of one pack (sum of the included products' purchase prices) — feeds profit reports. */
    public function getPurchasePriceAttribute(): ?float
    {
        $included = $this->items->where('is_included', true)->whereNotNull('product_id');
        if ($included->isEmpty()) {
            return null;
        }

        return (float) $included->sum(fn ($item) => (float) ($item->product->purchase_price ?? 0) * $item->quantity);
    }
}
