<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** An order an admin has acknowledged in the dashboard new-orders popup. */
class AdminOrderSeen extends Model
{
    protected $table = 'admin_order_seen';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'order_id',
        'seen_at',
    ];

    protected $casts = [
        'seen_at' => 'datetime',
    ];
}
