<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeliveryThana extends Model
{
    protected $table = 'thanas';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'district_id' => 'integer',
            'delivery_charge' => 'decimal:2',
            'sort_order' => 'integer',
            'status' => 'integer',
        ];
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(DeliveryDistrict::class, 'district_id');
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class, 'thana_id');
    }

    public function customerAddresses(): HasMany
    {
        return $this->hasMany(CustomerAddress::class, 'thana_id');
    }

    public function shippings(): HasMany
    {
        return $this->hasMany(Shipping::class, 'thana_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }
}
