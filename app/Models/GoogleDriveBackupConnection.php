<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Throwable;

class GoogleDriveBackupConnection extends Model
{
    protected $table = 'google_drive_backup_connections';
    public $incrementing = false;
    protected $keyType = 'int';

    protected $fillable = [
        'id', 'google_email', 'access_token', 'refresh_token', 'token_expires_at',
        'scope', 'folder_id', 'requires_reauth', 'connected_at',
    ];

    protected $hidden = ['access_token', 'refresh_token'];

    protected function casts(): array
    {
        return [
            'access_token' => 'encrypted',
            'refresh_token' => 'encrypted',
            'token_expires_at' => 'datetime',
            'requires_reauth' => 'boolean',
            'connected_at' => 'datetime',
        ];
    }

    public function isConnected(): bool
    {
        try {
            return ! $this->requires_reauth && filled($this->refresh_token);
        } catch (Throwable) {
            // A changed APP_KEY or damaged ciphertext requires a new connection.
            return false;
        }
    }
}
