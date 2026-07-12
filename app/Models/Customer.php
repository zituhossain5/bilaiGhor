<?php

namespace App\Models;

use App\Notifications\CustomerResetPasswordNotification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Laravel\Sanctum\HasApiTokens;

class Customer extends Authenticatable
{
    use HasFactory, HasRoles, HasApiTokens, Notifiable;

    protected $guard = 'customer';
    
    // Set guard name for Spatie Permission
    protected $guard_name = 'customer';

    // 🔥 এখানে সব প্রয়োজনীয় ফিল্ডগুলো রাখলাম
    protected $fillable = [
        'name',
        'slug',
        'phone',
        'email',
        'password',
        'verify',
        'status',
        'forgot',
        'address',
        'district',      // legacy district name (kept for old data)
        'area',          // legacy legacy_district_areas id (kept for old data)
        'district_id',   // districts.id — used by Profile Edit / addresses
        'zone_id',       // delivery_zones.id — used by Profile Edit / addresses
        'image',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /** Password-reset mail must point at the customer reset route, not the admin one. */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new CustomerResetPasswordNotification($token));
    }

    /** Available reward points — single source used by every view (sidebar, rewards, checkout). */
    public function rewardBalance(): int
    {
        return \App\Services\RewardPointService::balance($this->id);
    }

    public function cust_area()
    {
        return $this->belongsTo(District::class, 'area');
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'customer_id');
    }

    /**
     * Get the customer's profile image URL with default fallback
     * 
     * @return string
     */
    public function getProfileImageUrlAttribute()
    {
        $image = $this->attributes['image'] ?? null;
        $defaultImage = 'public/uploads/default/user.svg';
        $legacyDefault = 'public/uploads/default/user.png';
        $fallbackImage = 'public/uploads/default/no-image.png';

        // If image is empty or doesn't exist, use default
        if (empty($image) || !file_exists(public_path($image))) {
            if (file_exists(public_path($defaultImage))) {
                return $defaultImage;
            }
            if (file_exists(public_path($legacyDefault))) {
                return $legacyDefault;
            }
            if (file_exists(public_path($fallbackImage))) {
                return $fallbackImage;
            }
            return $defaultImage;
        }
        
        return $image;
    }
}
