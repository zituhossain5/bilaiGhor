<?php

namespace App\Models;

use App\Support\ShortName;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderDetails extends Model
{
    use HasFactory;

    protected $table = 'order_details';

    protected $fillable = [
        'order_id',
        'product_id',
        'product_name',
        'invoice_name',
        'purchase_price',
        'sale_price',
        'product_discount',
        'product_size',
        'variant_price_id',
        'product_color',
        'qty',
        'vendor_id',
        'commission_rate',
        'admin_commission',
        'vendor_earning',
        'vendor_paid_at',
        'manual_variant',
        'is_manual_item',
        'kitten_pack_id',
        'line_discount',
        'line_total',
    ];

    /**
     * Each line keeps the name it was sold under on receipts (invoice_name): the product's
     * short name, otherwise its name shortened. Renaming the product later doesn't change it.
     */
    protected static function booted(): void
    {
        static::creating(function (OrderDetails $line) {
            if (blank($line->invoice_name)) {
                $product = $line->product_id ? Product::find($line->product_id, ['id', 'name', 'short_name']) : null;
                $line->invoice_name = mb_substr(ShortName::forLine($product, $line->product_name), 0, 60);
            }
        });
    }

    /** Name printed on receipts; lines saved before invoice_name existed fall back to the product. */
    public function getDisplayNameAttribute(): string
    {
        $saved = trim((string) $this->invoice_name);

        return $saved !== '' ? $saved : ShortName::forLine($this->product, $this->product_name);
    }

    // ✅ অর্ডারের সাথে রিলেশন
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    // ✅ প্রোডাক্ট রিলেশন (ডিজিটাল চেক করার জন্য খুব জরুরি)
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
        // চাইলে select ব্যবহার করতে পারো:
        // ->select('id', 'name', 'slug', 'is_digital', 'digital_file', 'download_limit', 'download_expire_days');
    }

    // ✅ কালার রিলেশন (product_color = Color ID)
    public function color()
    {
        return $this->belongsTo(Color::class, 'product_color', 'id');
    }

    // ✅ সাইজ রিলেশন (product_size = Size ID)
    public function size()
    {
        return $this->belongsTo(Size::class, 'product_size', 'id');
    }

    // ✅ ইমেজ (প্রোডাক্ট আইডি দিয়ে)
    public function image()
    {
        return $this->belongsTo(Productimage::class, 'product_id', 'product_id')
                    ->select('id', 'product_id', 'image');
    }

    // ✅ ভেন্ডর রিলেশন
    public function vendor()
    {
        return $this->belongsTo(Vendor::class, 'vendor_id')
                    ->select('id', 'shop_name', 'owner_name');
    }
}
