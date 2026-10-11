<?php

namespace App\Models;

use App\Notifications\CustomerResetPasswordNotification;
use App\Support\BdPhone;
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
        'google_id',
        'facebook_id',
        'password',
        'verify',
        'status',
        'forgot',
        'address',
        'district',      // legacy district name (kept for old data)
        'area',          // legacy legacy_district_areas id (kept for old data)
        'district_id',   // districts.id — used by Profile Edit / addresses
        'thana_id',      // thanas.id - used by Profile Edit / addresses
        'image',
        'source',        // registered | manual (see SOURCE_* below)
        'post_code',
        'notes',         // admin-only note
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'notes',
    ];

    /** Signed up on the site/app/checkout — can log in. */
    public const SOURCE_REGISTERED = 'registered';
    /** Added by an admin — a record only, never a login. */
    public const SOURCE_MANUAL = 'manual';

    protected $attributes = [
        'source' => self::SOURCE_REGISTERED,
    ];

    protected static function booted(): void
    {
        // phone_normalized is what every lookup matches on; keep it in step with phone.
        static::saving(function (Customer $customer) {
            if ($customer->isDirty('phone') || ($customer->phone && ! $customer->phone_normalized)) {
                $customer->phone_normalized = BdPhone::normalize($customer->phone);
            }
        });
    }

    public function isManual(): bool
    {
        return $this->source === self::SOURCE_MANUAL;
    }

    public function scopeRegistered($query)
    {
        return $query->where('source', self::SOURCE_REGISTERED);
    }

    public function scopeManual($query)
    {
        return $query->where('source', self::SOURCE_MANUAL);
    }

    /** Same number in any format (+8801…, 8801…, 01…). */
    public function scopeWithPhone($query, ?string $phone)
    {
        return $query->where('phone_normalized', BdPhone::normalize($phone) ?? '__no_phone__');
    }

    /**
     * The customer an order placed with this phone belongs to: the registered account if there
     * is one, otherwise a manual customer with the same number. Null when nobody has it.
     */
    public static function forOrderPhone(?string $phone): ?self
    {
        if (blank($phone)) {
            return null;
        }

        return static::registered()->where('phone', $phone)->first()
            ?? static::manual()->withPhone($phone)->orderBy('id')->first();
    }

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

    public function wishlists()
    {
        return $this->hasMany(Wishlist::class, 'customer_id');
    }

    public function cust_area()
    {
        return $this->belongsTo(District::class, 'area');
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'customer_id');
    }

    public function district()
    {
        return $this->belongsTo(DeliveryDistrict::class, 'district_id');
    }

    public function thana()
    {
        return $this->belongsTo(DeliveryThana::class, 'thana_id');
    }

    public function addresses()
    {
        return $this->hasMany(CustomerAddress::class, 'customer_id');
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
