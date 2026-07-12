<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RewardPointTransaction extends Model
{
    use HasFactory;

    public const TYPE_EARNED     = 'earned';
    public const TYPE_SPENT      = 'spent';
    public const TYPE_REFUNDED   = 'refunded';  // spent points returned (order cancelled)
    public const TYPE_REVERSED   = 'reversed';  // earned points taken back (order cancelled after completion)
    public const TYPE_ADJUSTMENT = 'adjustment';

    protected $fillable = [
        'customer_id',
        'order_id',
        'type',
        'points',
        'balance_after',
        'description',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
