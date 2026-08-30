<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerAddress extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'label',
        'name',
        'phone',
        'email',
        'post_code',
        'address',
        'division_id',
        'district_id',
        'thana_id',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function district()
    {
        return $this->belongsTo(DeliveryDistrict::class, 'district_id');
    }

    public function division()
    {
        return $this->belongsTo(DeliveryDivision::class, 'division_id');
    }

    public function thana()
    {
        return $this->belongsTo(DeliveryThana::class, 'thana_id');
    }
}
