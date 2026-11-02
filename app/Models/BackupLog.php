<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BackupLog extends Model
{
    protected $fillable = ['type', 'filename', 'size_bytes', 'status', 'created_by', 'error_message'];
}
