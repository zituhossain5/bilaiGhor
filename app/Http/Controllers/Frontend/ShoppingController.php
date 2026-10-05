<?php

namespace App\Http\Controllers\Frontend;

use App\Helpers\KittenPackCart;
use App\Http\Controllers\Controller;
use App\Models\Color;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\ProductVariantPrice;
use App\Models\Size;
use App\Services\InventoryService;
use Brian2694\Toastr\Facades\Toastr;
use Carbon\Carbon;
use Gloudemans\Shoppingcart\Facades\Cart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

/**
 * Storefront cart actions (quantity, remove, counts, sidebar, coupons, campaign cart) and the
 * cart helpers that checkout, reseller checkout and kitten packs call statically.
 *
 * Rewritten in plain PHP to replace the vendor's ionCube-encoded controller. Every request is
 * answered with the same views/redirects the pages already expect. Adding to the cart is done
 * by FrontendController::cartStore(); this class only changes what is already in the cart.
 */
class ShoppingController extends Controller
{
    private const CART = 'shopping';

    // =========================================================
    // Cart page / sidebar / header actions
    // =========================================================

    /** GET /add-to-cart/{id}/{qty} — same rules as the normal Add to Cart button. */
    public function addTocartGet(Request $request, $id, $qty)
    {
        $request->merge(['id' => (int) $id, 'qty' => max(1, (int) $qty)]);

        return app(FrontendController::class)->cartStore($request);
    }

    public function cart_increment(Request $request)
    {
        $item = $this->findRow($request->id);

        if ($item) {
            $newQty = (int) $item->qty + 1;
            $available = $this->availableFor($item);

            if ($available !== null && $newQty > $available) {
                Toastr::error('স্টকে যত আছে তার বেশি নেওয়া যাবে না। সর্বোচ্চ ' . max(0, $available) . ' টি।', 'স্টক সীমা!');
            } else {
                Cart::instance(self::CART)->update($item->rowId, $newQty);
                $this->afterCartChange();
            }
        }

        return $this->cartView($request);
    }

    public function cart_decrement(Request $request)
    {
        $item = $this->findRow($request->id);

        // Never below 1 here — removing a line is the remove button's job.
        if ($item && (int) $item->qty > 1) {
            Cart::instance(self::CART)->update($item->rowId, (int) $item->qty - 1);
            $this->afterCartChange();
        }

        return $this->cartView($request);
    }

    public function cart_remove(Request $request)
    {
        $item = $this->findRow($request->id);

        if ($item) {
            Cart::instance(self::CART)->remove($item->rowId);
            $this->afterCartChange();
        }

        return $this->cartView($request);
    }

    /** GET cart/update?id=rowId&qty=n — set a line's quantity directly (stock-capped). */
    public function cart_update(Request $request)
    {
        $item = $this->findRow($request->id);
        $qty = max(1, (int) $request->qty);

        if ($item) {
            $available = $this->availableFor($item);
            if ($available !== null && $qty > $available) {
                $qty = max(1, $available);
                Toastr::error('স্টকে যত আছে তার বেশি নেওয়া যাবে না। সর্বোচ্চ ' . max(0, $available) . ' টি।', 'স্টক সীমা!');
            }
            Cart::instance(self::CART)->update($item->rowId, $qty);
            $this->afterCartChange();
        }

        return $this->cartView($request);
    }

    public function cart_count()
    {
        return view('frontEnd.layouts.ajax.cart_count');
    }

    public function mobilecart_qty()
    {
        return view('frontEnd.layouts.ajax.mobilecart_qty');
    }

    public function sidebarCart()
    {
        return view('frontEnd.layouts.ajax.sidebar-cart');
    }

    /** Campaign landing page: the cart always holds exactly the product the visitor picked. */
    public function changeProduct(Request $request)
    {
        $product = Product::with('image')
            ->where('status', 1)
            ->find((int) $request->id);

        if ($product) {
            $available = InventoryService::available($product->id);
            if ($available <= 0) {
                Toastr::error('এই পণ্যটি বর্তমানে স্টক আউট।', 'স্টক আউট!');
            } else {
                Cart::instance(self::CART)->destroy();
                self::setCampaignCartProduct(
                    $product,
                    $request->filled('color_id') ? (int) $request->color_id : null,
                    $request->filled('size_id') ? (int) $request->size_id : null
                );
                $this->afterCartChange();
            }
        }

        return view('frontEnd.layouts.ajax.campaign-cart-table');
    }

    // =========================================================
    // Coupons
    // =========================================================

    public function applyCoupon(Request $request)
    {
        $code = trim((string) $request->coupon_code);
        $result = self::evaluateCoupon($code, self::cartSubtotal());

        if ($result['error']) {
            Toastr::error($result['error'], 'কুপন');

            return redirect()->back();
        }

        Session::put('coupon_code', $result['coupon']->code);
        Session::put('discount', $result['discount']);
        Toastr::success('কুপন সফলভাবে প্রয়োগ হয়েছে।', 'কুপন');

        return redirect()->back();
    }

    public function removeCoupon()
    {
        Session::forget('coupon_code');
        Session::forget('discount');
        Toastr::success('কুপন সরানো হয়েছে।', 'কুপন');

        return redirect()->back();
    }

    // =========================================================
    // Static cart helpers (called by checkout, reseller checkout, campaign, kitten packs)
    // =========================================================

    /** Re-price wholesale lines for their current quantity tier (a no-op for normal products). */
    public static function refreshCartWholesalePrices(): void
    {
        $cart = Cart::instance(self::CART);

        foreach ($cart->content() as $item) {
            if (KittenPackCart::isPackRow($item)) {
                continue;
            }

            $product = Product::with('wholesalePrices')->find($item->id);
            if (!$product || !$product->is_wholesale) {
                continue;
            }

            $price = $product->resolveSalePrice(
                (int) $item->qty,
                $item->options->color_id ? (int) $item->options->color_id : null,
                $item->options->size_id ? (int) $item->options->size_id : null
            );

            if ($price > 0 && (float) $item->price !== (float) $price) {
                $cart->update($item->rowId, ['price' => $price]);
            }
        }
    }

    /** True when the cart holds a digital (download) product — those cannot be Cash On Delivery. */
    public static function hasDigitalProductInCart(): bool
    {
        $ids = self::productRows()->pluck('id')->map(fn ($id) => (int) $id)->unique();

        return $ids->isNotEmpty() && Product::whereIn('id', $ids)->where('is_digital', 1)->exists();
    }

    /**
     * True when every product line ships free (free_delivery) or does not ship at all (digital).
     * Kitten pack lines are ignored here; KittenPackCart::hasAllFreeDelivery() accounts for them.
     * An empty cart is never "all free".
     */
    public static function hasAllFreeDeliveryProducts(): bool
    {
        if (Cart::instance(self::CART)->count() <= 0) {
            return false;
        }

        $ids = self::productRows()->pluck('id')->map(fn ($id) => (int) $id)->unique();
        if ($ids->isEmpty()) {
            return true;
        }

        $paid = Product::whereIn('id', $ids)
            ->where(fn ($q) => $q->where('free_delivery', '!=', 1)->orWhereNull('free_delivery'))
            ->where(fn ($q) => $q->where('is_digital', '!=', 1)->orWhereNull('is_digital'))
            ->exists();

        return !$paid;
    }

    /** Advance payment due now: each product's advance_amount × its quantity (reseller checkout). */
    public static function getCartAdvanceAmount(): float|int
    {
        $rows = self::productRows();
        if ($rows->isEmpty()) {
            return 0;
        }

        $advance = Product::whereIn('id', $rows->pluck('id')->unique())->pluck('advance_amount', 'id');

        return (float) $rows->sum(fn ($item) => (float) ($advance[$item->id] ?? 0) * (int) $item->qty);
    }

    /** Put one unit of a campaign product (optionally a colour/size variant) into the cart. */
    public static function setCampaignCartProduct(Product $product, ?int $colorId = null, ?int $sizeId = null): void
    {
        $product->loadMissing('image');

        $variantPrice = null;
        if ($colorId || $sizeId) {
            $query = ProductVariantPrice::where('product_id', $product->id);
            if ($colorId && $sizeId) {
                $variantPrice = (clone $query)->where('color_id', $colorId)->where('size_id', $sizeId)->first();
            } elseif ($colorId) {
                $variantPrice = (clone $query)->where('color_id', $colorId)->whereNull('size_id')->first();
            } else {
                $variantPrice = (clone $query)->where('size_id', $sizeId)->whereNull('color_id')->first();
            }
        }

        $price = $product->resolveSalePrice(1, $colorId, $sizeId);
        if ($price <= 0) {
            $price = (float) ($product->new_price ?? $product->old_price ?? 1);
        }

        $size = $sizeId ? Size::find($sizeId) : null;
        $color = $colorId ? Color::find($colorId) : null;

        Cart::instance(self::CART)->add([
            'id'      => $product->id,
            'name'    => $product->name,
            'qty'     => 1,
            'price'   => $price,
            'options' => [
                'slug'             => $product->slug,
                'image'            => optional($product->image)->image,
                'old_price'        => $product->old_price,
                'purchase_price'   => $product->purchase_price,
                'advance_amount'   => (float) $product->advance_amount,
                'is_digital'       => (int) $product->is_digital,
                'free_delivery'    => (int) $product->free_delivery,
                'color_id'         => $colorId,
                'size_id'          => $sizeId,
                'product_size'     => $size ? ($size->sizeName ?? $size->size_name ?? null) : null,
                'product_color'    => $color ? ($color->getDisplayName() ?? $color->colorName ?? $color->color_name ?? null) : null,
                'variant_price_id' => $variantPrice->id ?? null,
                'is_wholesale'     => (int) $product->is_wholesale,
            ],
        ]);
    }

    // =========================================================
    // Internals
    // =========================================================

    /** Cart subtotal as a number (the cart library returns a formatted string). */
    private static function cartSubtotal(): float
    {
        return (float) Cart::instance(self::CART)->subtotal(2, '.', '');
    }

    /** Product lines only (kitten pack lines excluded). */
    private static function productRows()
    {
        return Cart::instance(self::CART)->content()
            ->reject(fn ($item) => KittenPackCart::isPackRow($item))
            ->values();
    }

    /**
     * Validate a coupon against a subtotal.
     *
     * @return array{coupon: ?Coupon, discount: float, error: ?string}
     */
    private static function evaluateCoupon(string $code, float $subtotal): array
    {
        $fail = fn (string $message) => ['coupon' => null, 'discount' => 0.0, 'error' => $message];

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

    /** Keep wholesale prices and any applied coupon in step with the cart's new contents. */
    private function afterCartChange(): void
    {
        self::refreshCartWholesalePrices();

        $code = Session::get('coupon_code');
        if (!$code) {
            return;
        }

        $result = self::evaluateCoupon((string) $code, self::cartSubtotal());
        if ($result['error']) {
            Session::forget('coupon_code');
            Session::forget('discount');
        } else {
            Session::put('discount', $result['discount']);
        }
    }

    private function findRow($rowId)
    {
        if (!$rowId) {
            return null;
        }

        return Cart::instance(self::CART)->content()->first(fn ($item) => $item->rowId === $rowId);
    }

    /** Units the shop can still sell for this line (null = no limit known). */
    private function availableFor($item): ?int
    {
        if (KittenPackCart::isPackRow($item)) {
            return InventoryService::packAvailable(KittenPackCart::packId($item));
        }

        $product = Product::find($item->id);
        if (!$product || $product->is_digital) {
            return null;
        }

        return InventoryService::available($product->id);
    }

    private function cartView(Request $request)
    {
        return $request->filled('campaign')
            ? view('frontEnd.layouts.ajax.campaign-cart-table')
            : view('frontEnd.layouts.ajax.cart');
    }
}
