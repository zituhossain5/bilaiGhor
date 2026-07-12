<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class PasswordResetOtp extends Model
{
    use HasFactory;

    /** Max wrong guesses before an OTP is burned. */
    public const MAX_ATTEMPTS = 5;

    protected $fillable = [
        'user_type',
        'user_id',
        'mobile',
        'otp_hash',
        'expires_at',
        'attempts',
        'verified_at',
        'used_at',
        'request_ip',
    ];

    protected function casts(): array
    {
        return [
            'expires_at'  => 'datetime',
            'verified_at' => 'datetime',
            'used_at'     => 'datetime',
        ];
    }

    /** Still usable: not consumed, not expired, attempts left. */
    public function isUsable(): bool
    {
        return is_null($this->used_at)
            && $this->expires_at->isFuture()
            && $this->attempts < self::MAX_ATTEMPTS;
    }

    public function matches(string $otp): bool
    {
        return Hash::check($otp, $this->otp_hash);
    }

    /** Invalidate every live OTP for a mobile — a new code must kill the old one. */
    public static function invalidateFor(string $mobile): void
    {
        static::where('mobile', $mobile)->whereNull('used_at')->update(['used_at' => now()]);
    }
}
