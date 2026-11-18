<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderPurposeChange extends Model
{
    protected $fillable = [];

    public function changer()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
