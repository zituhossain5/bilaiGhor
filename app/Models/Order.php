<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    // সব ফিল্ড mass assign করতে পারবে
    protected $guarded = [];

    protected static function booted(): void
    {
        // Reward points react to order-status transitions. Hooking the model event
        // covers every Eloquent write site (admin panel, courier webhooks, delivery
        // boy app, refunds) without touching each controller. The service methods
        // are idempotent, so repeated flips can never double-award or double-restore.
        static::updated(function (self $order) {
            if (!$order->wasChanged('order_status')) {
                return;
            }
            try {
                $status = (int) $order->order_status;
                if ($status === (int) config('rewards.award_status', 6)) {
                    \App\Services\RewardPointService::awardOrderPoints($order);
                } elseif ($status === (int) config('rewards.cancel_status', 11)) {
                    \App\Services\RewardPointService::handleCancellation($order);
                }
            } catch (\Throwable $e) {
                // Reward bookkeeping must never break a status update (webhooks etc.).
                \Log::error('Reward point hook failed for order '.$order->id.': '.$e->getMessage());
            }

            try {
                // Inventory reacts to every status transition through one idempotent
                // service — this hook is the only interception point that also covers
                // the IonCube-encoded admin OrderController's status updates.
                \App\Services\InventoryService::syncOrderStatus($order, (int) $order->order_status);
            } catch (\Throwable $e) {
                \Log::error('Inventory status hook failed for order '.$order->id.': '.$e->getMessage());
            }
        });
    }

    protected function casts(): array
    {
        return [
            'delivery_assigned_at' => 'datetime',
            'rider_delivered_at'   => 'datetime',
            'delivery_otp_sent_at' => 'datetime',
            'delivery_otp_expires_at' => 'datetime',
            'delivery_otp_verified_at' => 'datetime',
            // Missing here was the root cause of the "In Courier" order-details 500:
            // uncast, courier_sent_at came back as a raw string, and code that called
            // ->format() on it (assuming a Carbon instance, like its sibling columns)
            // crashed only for orders that actually had this column populated.
            'courier_sent_at' => 'datetime',
        ];
    }

    // ============================
    // 🌟 RELATIONSHIPS
    // ============================

    // অর্ডারের সব প্রোডাক্ট আইটেম (order_details টেবিল)
    public function orderdetails()
    {
        return $this->hasMany(OrderDetails::class, 'order_id');
    }

    // alias: items()
    public function items()
    {
        return $this->hasMany(OrderDetails::class, 'order_id');
    }

    // 🔥 অর্ডার থেকে সরাসরি Products আনতে (hasManyThrough)
    public function products()
    {
        return $this->hasManyThrough(
            Product::class,      // শেষ মডেল (যেটা চাও)
            OrderDetails::class, // মধ্যবর্তী মডেল
            'order_id',          // order_details টেবিলের foreign key (order_id)
            'id',                // products টেবিলের primary key
            'id',                // orders টেবিলের local key
            'product_id'         // order_details টেবিলের foreign key (product_id)
        );
    }

    // পুরোনো কোডে যদি with('product') / $order->product থাকে,
    // সেটা ব্রেক না করার জন্য product() নামেও একই relation দিলাম।
    public function product()
    {
        return $this->products();
    }

    // পেমেন্ট স্ট্যাটাস / অর্ডার স্ট্যাটাস
    public function status()
    {
        return $this->belongsTo(OrderStatus::class, 'order_status');
    }

    // শিপিং তথ্য
    public function shipping()
    {
        return $this->hasOne(Shipping::class, 'order_id', 'id');
    }

    // পেমেন্ট ডাটা
    public function payment()
    {
        return $this->hasOne(Payment::class, 'order_id', 'id')->latestOfMany();
    }

    // কাস্টমার (frontend user)
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    // অ্যাডমিন ইউজার (order created by)
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function editor()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // ============================
    // 🌟 DIGITAL DOWNLOAD SUPPORT
    // ============================

    // অর্ডার থেকে সব ডিজিটাল ডাউনলোড লিঙ্ক
    public function digitalDownloads()
    {
        return $this->hasMany(DigitalDownload::class, 'order_id');
    }

    // ============================
    // 🌟 REFUND SUPPORT
    // ============================

    // অর্ডারের সব রিফান্ড রিকোয়েস্ট
    public function refunds()
    {
        return $this->hasMany(Refund::class, 'order_id');
    }

    public function deliveryBoy()
    {
        return $this->belongsTo(DeliveryBoy::class, 'delivery_boy_id');
    }

    // অর্ডারের active/pending refund আছে কিনা
    public function hasPendingRefund()
    {
        return $this->refunds()->whereIn('status', ['pending', 'approved'])->exists();
    }
}
