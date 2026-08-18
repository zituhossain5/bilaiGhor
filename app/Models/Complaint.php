<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Complaint extends Model
{
    protected $fillable = [
        'ticket_number',
        'customer_id',
        'order_id',
        'order_reference',
        'name',
        'phone',
        'email',
        'image',
        'description',
        'status'
    ];
}
