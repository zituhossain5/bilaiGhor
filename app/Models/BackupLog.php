<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BackupLog extends Model
{
    protected $fillable = [
        'type', 'destination', 'filename', 'size_bytes', 'status', 'drive_file_id',
        'encrypted', 'started_at', 'completed_at', 'created_by', 'error_message', 'warning_message',
    ];

    protected $casts = [
        'encrypted' => 'boolean',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
