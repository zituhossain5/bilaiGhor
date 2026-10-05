<?php

namespace App\Services;

use App\Models\Coupon;
use Carbon\Carbon;

/** Coupon rules shared by the storefront cart and the admin POS. */
class CouponService
{
    /**
     * Validate a coupon code against a cart subtotal.
     *
     * @return array{coupon: ?Coupon, discount: float, error: ?string}
     */
    public static function evaluate(string $code, float $subtotal): array
    {
        $fail = fn (string $message) => ['coupon' => null, 'discount' => 0.0, 'error' => $message];

        $code = trim($code);
        if ($code === '') {
            return $fail('কুপন কোড লিখুন।');
        }
        if ($subtotal <= 0) {
            return $fail('কার্ট খালি।');
        }

        $coupon = Coupon::where('code', $code)->where('status', 1)->first();
        if (!$coupon) {
            return $fail('কুপন কোডটি সঠিক নয়।');
        }

        $today = Carbon::today();
        if ($coupon->valid_from && $today->lt(Carbon::parse($coupon->valid_from)->startOfDay())) {
            return $fail('এই কুপন এখনও চালু হয়নি।');
        }
        if ($coupon->valid_to && $today->gt(Carbon::parse($coupon->valid_to)->startOfDay())) {
            return $fail('এই কুপনের মেয়াদ শেষ।');
        }
        if ($coupon->min_purchase && $subtotal < (float) $coupon->min_purchase) {
            return $fail('এই কুপনের জন্য সর্বনিম্ন ৳' . number_format((float) $coupon->min_purchase, 0) . ' কেনাকাটা করতে হবে।');
        }

        $discount = $coupon->type === 'percent'
            ? $subtotal * (float) $coupon->value / 100
            : (float) $coupon->value;

        return ['coupon' => $coupon, 'discount' => round(min($discount, $subtotal), 2), 'error' => null];
    }
}
