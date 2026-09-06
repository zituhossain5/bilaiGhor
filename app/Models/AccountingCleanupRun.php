<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class AccountingCleanupRun extends Model
{
    use HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'cutoff_at',
        'snapshot_path',
        'options',
        'summary',
        'executed_at',
    ];

    protected $casts = [
        'cutoff_at' => 'datetime',
        'options' => 'array',
        'summary' => 'array',
        'executed_at' => 'datetime',
    ];
}
