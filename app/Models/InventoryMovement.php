<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryMovement extends Model
{
    public const TYPE_OPENING_STOCK        = 'opening_stock';
    public const TYPE_PURCHASE_RECEIVED    = 'purchase_received';
    public const TYPE_PURCHASE_RETURNED    = 'purchase_returned';
    public const TYPE_ORDER_RESERVED       = 'order_reserved';
    public const TYPE_RESERVATION_RELEASED = 'reservation_released';
    public const TYPE_SALE_COMPLETED       = 'sale_completed';
    public const TYPE_RETURN_RESTOCK       = 'return_restock';
    public const TYPE_MANUAL_ADJUSTMENT    = 'manual_adjustment';
    public const TYPE_CORRECTION           = 'correction';
    public const TYPE_DAMAGE               = 'damage';
    public const TYPE_LOST                 = 'lost';

    protected $fillable = [
        'product_id', 'variant_id', 'movement_type', 'quantity',
        'on_hand_change', 'reserved_change',
        'previous_on_hand', 'new_on_hand', 'previous_reserved', 'new_reserved',
        'reference_type', 'reference_id', 'order_id', 'purchase_id',
        'reason', 'notes', 'created_by',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
