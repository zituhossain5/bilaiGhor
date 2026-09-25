<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KittenPackComponent extends Model
{
    protected $fillable = [
        'kitten_pack_id',
        'product_id',
        'quantity',
    ];

    protected $casts = [
        'quantity' => 'integer',
    ];

    public function pack(): BelongsTo
    {
        return $this->belongsTo(KittenPack::class, 'kitten_pack_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
