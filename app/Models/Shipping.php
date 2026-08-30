<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shipping extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::saving(function (Shipping $shipping) {
            if ($shipping->thana_id) {
                $shipping->upazila_id = $shipping->thana_id;
            } elseif ($shipping->upazila_id) {
                $shipping->thana_id = $shipping->upazila_id;
            }
        });
    }
    
    protected $fillable = [
        'order_id',
        'customer_id',
        'name',
        'phone',
        'address',
        'area',
        'division_id',
        'district_id',
        'thana_id',
        'upazila_id', // ionCube OrderController compatibility alias
        'post_code',
    ];

    public function thana()
    {
        return $this->belongsTo(DeliveryThana::class, 'thana_id');
    }
    
    public function division()
    {
        return $this->belongsTo(DeliveryDivision::class, 'division_id');
    }

    public function district()
    {
        return $this->belongsTo(DeliveryDistrict::class, 'district_id');
    }

}
