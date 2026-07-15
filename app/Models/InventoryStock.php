<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryStock extends Model
{
    protected $fillable = [
        'product_id', 'variant_id', 'on_hand', 'reserved',
        'low_stock_threshold', 'last_restocked_at',
    ];

    protected function casts(): array
    {
        return [
            'on_hand'            => 'integer',
            'reserved'           => 'integer',
            'low_stock_threshold'=> 'integer',
            'last_restocked_at'  => 'datetime',
        ];
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function movements()
    {
        return $this->hasMany(InventoryMovement::class, 'product_id', 'product_id');
    }

    // Available is always derived — never stored, never directly editable.
    public function getAvailableAttribute(): int
    {
        return (int) $this->on_hand - (int) $this->reserved;
    }

    public function getStockStatusAttribute(): string
    {
        if ($this->available <= 0) {
            return 'out_of_stock';
        }
        return $this->available <= $this->low_stock_threshold ? 'low_stock' : 'in_stock';
    }
}
