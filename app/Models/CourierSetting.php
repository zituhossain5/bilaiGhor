<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Raw key/value row. Read and write through App\Support\CourierSettings (keys, defaults, cache). */
class CourierSetting extends Model
{
    protected $guarded = [];
}
