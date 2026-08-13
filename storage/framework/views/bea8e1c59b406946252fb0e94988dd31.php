
<?php $__env->startSection('title', 'Customer Checkout'); ?>
<?php
    $generalsetting = \App\Models\GeneralSetting::first();
?>
<?php $__env->startPush('css'); ?>

<style>
/* BilaiGhor Checkout Figma Start */
:root {
    --co-primary: var(--bilai-primary, #F28C00);
    --co-primary-dark: var(--bilai-primary-dark, #c96f00);
    --co-brown:   var(--bilai-brown,  #3A1F0F);
    --co-cream:   var(--bilai-cream,  #FFF8EC);
    --co-card:    #FFFDF8;
    --co-border:  var(--bilai-border, #E8CDA5);
    --co-text:    var(--bilai-text,   #2B1A10);
    --co-muted:   var(--bilai-muted,  #77706A);
    --co-radius:  var(--bilai-radius-lg, 16px);
    --co-radius-sm: var(--bilai-radius-md, 10px);
}

.checkout-section {
    background: #f5f5f0;
    padding: 22px 0 56px;
    font-family: inherit;
    color: var(--co-text);
}

/* ── Breadcrumb ── */
.bilai-co-bc { display: flex; align-items: center; gap: 6px; font-size: 12.5px; margin-bottom: 20px; flex-wrap: wrap; }
.bilai-co-bc a { color: var(--co-muted); text-decoration: none; }
.bilai-co-bc a:hover { color: var(--co-primary); }
.bilai-co-bc-sep { color: #c0b0a0; font-size: 11px; }
.bilai-co-bc-active { color: var(--co-primary); font-weight: 600; }

/* ── Cards ── */
.checkout-card {
    background: var(--co-cream);
    border: 1px solid var(--co-border);
    border-radius: var(--co-radius);
    margin-bottom: 22px;
    overflow: hidden;
}
.checkout-header {
    padding: 18px 22px 14px;
    border-bottom: 1px solid var(--co-border);
    display: flex;
    align-items: center;
    gap: 10px;
}
.checkout-header i { color: var(--co-primary); font-size: 18px; }
.checkout-header h6 {
    margin: 0;
    font-size: 16px;
    font-weight: 700;
    color: var(--co-text);
    letter-spacing: 0;
    text-transform: none;
}
.card-body-custom { padding: 20px 22px 22px; }

/* ── Form ── */
.form-group { margin-bottom: 16px; }
.form-label-custom {
    font-size: 13px;
    font-weight: 600;
    color: var(--co-text);
    margin-bottom: 7px;
    display: block;
}
.form-label-custom .req { color: #e04b4b; }
.form-control-custom {
    width: 100%;
    height: 46px;
    border: 1px solid var(--co-border);
    border-radius: var(--co-radius-sm);
    padding: 0 14px;
    font-size: 14px;
    color: var(--co-text);
    transition: all 0.18s;
    background-color: #fff;
}
.form-control-custom::placeholder { color: #b6ab9c; }
.form-control-custom:focus {
    border-color: var(--co-primary);
    box-shadow: 0 0 0 3px rgba(242,140,0,0.10);
    outline: none;
}
select.form-control-custom { cursor: pointer; appearance: none; -webkit-appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2377706A' stroke-width='2'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");
    background-repeat: no-repeat; background-position: right 14px center; padding-right: 34px; }
textarea.form-control-custom { height: auto; padding: 12px 14px; line-height: 1.5; }

/* ── Payment options ── */
.payment-option-label {
    display: flex;
    align-items: center;
    justify-content: space-between;
    border: 1px solid var(--co-border);
    border-radius: var(--co-radius-sm);
    padding: 14px 16px;
    cursor: pointer;
    transition: all 0.18s ease;
    margin-bottom: 12px;
    background: #fff;
    position: relative;
}
.payment-option-label:hover { border-color: var(--co-primary); }
.payment-option-label input { position: absolute; opacity: 0; cursor: pointer; }
.payment-option-label:has(input:checked) {
    border-color: var(--co-primary);
    background-color: #fdefd6;
    box-shadow: 0 0 0 1px var(--co-primary);
}
.payment-content { display: flex; align-items: center; gap: 13px; width: 100%; }
.pay-logo { width: 36px; height: 36px; object-fit: contain; flex-shrink: 0; }
.pay-info strong { display: block; font-size: 14.5px; font-weight: 700; color: var(--co-text); }
.pay-info small { font-size: 12px; color: var(--co-muted); }
.check-circle {
    width: 20px; height: 20px;
    border: 2px solid #cbb99c;
    border-radius: 50%;
    position: relative; flex-shrink: 0;
}
.payment-option-label input:checked ~ .check-circle { border-color: var(--co-primary); }
.payment-option-label input:checked ~ .check-circle::after {
    content: ''; position: absolute; top: 50%; left: 50%;
    transform: translate(-50%, -50%);
    width: 10px; height: 10px; background: var(--co-primary); border-radius: 50%;
}

/* ── Sticky sidebar ── */
.sticky-sidebar { position: sticky; top: 90px; }

/* ── Order items ── */
.cart-items-scroll { max-height: 340px; overflow-y: auto; }
.cart-items-scroll::-webkit-scrollbar { width: 5px; }
.cart-items-scroll::-webkit-scrollbar-thumb { background: var(--co-border); border-radius: 5px; }
.checkout-item {
    display: flex; gap: 13px; padding: 14px 0;
    border-bottom: 1px solid #f0e8d8; position: relative; align-items: flex-start;
}
.checkout-item:last-child { border-bottom: none; }
.checkout-pro-img {
    width: 58px; height: 58px; border-radius: 8px;
    border: 1px solid var(--co-border); object-fit: cover; background: #fff;
}
.checkout-pro-info h6 { font-size: 13.5px; font-weight: 600; color: var(--co-text); margin: 0 0 4px; line-height: 1.4; }
.checkout-pro-info .meta { font-size: 11.5px; color: var(--co-muted); }
.co-price-line { font-size: 12px; color: var(--co-muted); }
.co-price-old { text-decoration: line-through; color: #b6ab9c; margin-left: 6px; }
.co-line-total { font-size: 14px; font-weight: 700; color: var(--co-primary); white-space: nowrap; }
.remove-item-btn { color: #d9534f; cursor: pointer; font-size: 14px; flex-shrink: 0; transition: 0.2s; }
.remove-item-btn:hover { color: #b52b27; }

/* Qty box */
.qty-box { display: flex; align-items: center; background: #fff; border: 1px solid var(--co-border); border-radius: 6px; padding: 2px; margin-top: 8px; width: fit-content; }
.qty-btn { width: 26px; height: 26px; border: none; background: transparent; border-radius: 4px; color: var(--co-primary); font-weight: bold; cursor: pointer; display: flex; align-items: center; justify-content: center; }
.qty-btn:hover { background: var(--co-cream); }
.qty-val { width: 30px; text-align: center; font-size: 13px; font-weight: 600; }

/* ── Edit cart link ── */
.bilai-co-edit { margin-left: auto; font-size: 12.5px; font-weight: 600; color: var(--co-primary); text-decoration: none; }
.bilai-co-edit:hover { color: var(--co-primary-dark); text-decoration: underline; }

/* ── Coupon ── */
.coupon-wrapper { padding: 16px 22px; border-top: 1px solid var(--co-border); }
.coupon-label { font-size: 13px; font-weight: 600; color: var(--co-text); margin-bottom: 8px; display: block; }
.coupon-group-modern {
    display: flex; width: 100%; height: 46px;
    border: 1px solid var(--co-border); border-radius: var(--co-radius-sm);
    overflow: hidden; background: #fff;
}
.coupon-group-modern:focus-within { border-color: var(--co-primary); box-shadow: 0 0 0 3px rgba(242,140,0,0.08); }
.coupon-input-modern { flex-grow: 1; border: none; padding: 0 14px; font-size: 14px; color: var(--co-text); outline: none; background: transparent; }
.coupon-input-modern::placeholder { color: #b6ab9c; }
.coupon-btn-modern {
    background: var(--co-brown); color: #fff; border: none;
    padding: 0 24px; font-weight: 600; font-size: 13px; cursor: pointer; transition: 0.2s;
}
.coupon-btn-modern:hover { background: #24140a; }

/* ── Reward earn info box ── */
.bilai-co-earn {
    display: flex; align-items: flex-start; gap: 12px;
    margin: 16px 22px 20px;
    background: #fff; border: 1px solid var(--co-border);
    border-radius: var(--co-radius-sm); padding: 14px;
}
.bilai-co-earn-ic {
    width: 34px; height: 34px; border-radius: 50%;
    background: var(--co-cream); color: var(--co-primary);
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0; font-size: 15px;
}
.bilai-co-earn p { margin: 0; font-size: 12.5px; color: var(--co-text); line-height: 1.55; }
.bilai-co-earn a { color: var(--co-primary); font-weight: 600; text-decoration: none; font-size: 12px; }
.bilai-co-earn a:hover { text-decoration: underline; }

/* ── Reward point card ── */
.bilai-co-reward-row { display: flex; align-items: center; justify-content: space-between; gap: 14px; }
.bilai-co-reward-pill {
    display: inline-flex; align-items: center; gap: 8px;
    background: var(--co-brown); color: #fff;
    padding: 8px 16px; border-radius: 100px; font-size: 13px; font-weight: 600;
}
.bilai-co-reward-pill .dot { width: 8px; height: 8px; border-radius: 50%; background: var(--co-primary); }
.bilai-co-policy { display: inline-block; margin-top: 12px; font-size: 12px; font-weight: 600; color: var(--co-primary); text-decoration: none; }
.bilai-co-policy:hover { text-decoration: underline; }
/* toggle (visual placeholder, disabled) */
.bilai-co-toggle { position: relative; width: 46px; height: 26px; flex-shrink: 0; }
.bilai-co-toggle input { opacity: 0; width: 0; height: 0; }
.bilai-co-toggle .track { position: absolute; inset: 0; background: #e3d8c4; border-radius: 100px; transition: 0.2s; cursor: not-allowed; }
.bilai-co-toggle .track::before { content: ''; position: absolute; top: 3px; left: 3px; width: 20px; height: 20px; background: #fff; border-radius: 50%; transition: 0.2s; box-shadow: 0 1px 3px rgba(0,0,0,0.2); }
.bilai-co-toggle input:checked + .track { background: var(--co-primary); }
.bilai-co-toggle input:checked + .track::before { transform: translateX(20px); }

/* ── Totals ── */
.summary-totals { padding: 6px 22px 4px; }
.total-row { display: flex; justify-content: space-between; margin-bottom: 12px; font-size: 13.5px; color: var(--co-text); }
.total-row span:first-child { color: var(--co-muted); }
.total-row.final {
    border-top: 1px solid var(--co-border);
    margin-top: 6px; padding-top: 14px;
    font-size: 17px; font-weight: 800; color: var(--co-primary);
}
.total-row.final span:first-child { color: var(--co-text); }

/* ── Place order button ── */
.btn-place-order {
    background: var(--co-primary); color: #fff; width: 100%;
    border: none; padding: 15px; border-radius: 100px;
    font-size: 15px; font-weight: 700; letter-spacing: 0.3px;
    transition: 0.2s; cursor: pointer;
    display: flex; justify-content: center; align-items: center; gap: 10px;
}
.btn-place-order:hover { background: var(--co-primary-dark); }

/* ── Manual payment fields ── */
#manual-payment-fields { background: #fff; border: 1px solid var(--co-border) !important; border-radius: var(--co-radius-sm); }

/* ── Responsive ── */
@media (max-width: 991px) {
    .cus-order-2 { order: 2; }
    .cust-order-1 { order: 1; margin-bottom: 26px; }
    .sticky-sidebar { position: static; }
    .mobile-submit-btn { display: block !important; margin-top: 22px; }
    .desktop-submit-btn { display: none !important; }
}
@media (min-width: 992px) {
    .mobile-submit-btn { display: none !important; }
    .desktop-submit-btn { display: block !important; }
}
/* BilaiGhor Checkout Figma End */

/* BilaiGhor Checkout Payment Fix Start */
/* Only Figma methods (COD + manual gateways) are rendered; online gateways hidden via Blade flag. */
.payment-options-list .payment-option-label { margin-bottom: 12px; }
.payment-options-list .payment-option-label:last-child { margin-bottom: 0; }
/* BilaiGhor Checkout Payment Fix End */

/* BilaiGhor Checkout Order Items Fix Start */
/* Qty +/- and trash removed on checkout — line total sits centered on the right. */
.checkout-item { align-items: center; }
.checkout-item .co-line-total { margin-top: 0; }
.checkout-item .text-end { display: flex; align-items: center; }
/* BilaiGhor Checkout Order Items Fix End */

/* BilaiGhor Checkout Summary Fix Start */
/* BilaiGhor Checkout Summary Fix End */

/* BilaiGhor Checkout Address Modal Start */
.bilai-addr-btn {
    display: inline-flex; align-items: center; gap: 8px;
    margin-top: 12px; padding: 10px 20px 10px 16px;
    background: #241307; color: #fff;
    border: none; border-radius: 100px;
    font-size: 13px; font-weight: 600;
    line-height: 1; cursor: pointer; transition: 0.15s;
}
.bilai-addr-btn:hover { background: var(--co-brown); }
.bilai-addr-btn-ic { width: 16px; height: 16px; flex-shrink: 0; display: block; }

/* Overlay */
.bilai-addr-overlay {
    display: none; position: fixed; inset: 0; z-index: 1600;
    background: rgba(30, 18, 8, 0.5);
    align-items: flex-start; justify-content: center;
    padding: 40px 16px; overflow-y: auto;
}
.bilai-addr-overlay.open { display: flex; }

/* Logged-in dialog */
.bilai-addr-dialog {
    background: var(--co-cream); width: 100%; max-width: 960px;
    border-radius: 14px; overflow: hidden;
}
.bilai-addr-head {
    background: var(--co-primary); color: #fff;
    display: flex; align-items: center; justify-content: space-between;
    padding: 16px 24px;
}
.bilai-addr-head h5 { margin: 0; font-size: 16px; font-weight: 700; color: #fff; }
.bilai-addr-close {
    background: transparent; border: none; color: #fff;
    font-size: 20px; cursor: pointer; line-height: 1; padding: 4px;
}
.bilai-addr-body { padding: 28px; }
.bilai-addr-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }

/* Address card */
.bilai-addr-card { background: #FFFDF8; border: 1px solid var(--co-border); border-radius: 10px; overflow: hidden; }
.bilai-addr-card-head {
    display: flex; align-items: center; justify-content: space-between; gap: 10px;
    padding: 14px 18px; border-bottom: 1px solid var(--co-border);
}
.bilai-addr-card-title { font-size: 14px; font-weight: 700; color: var(--co-text); margin: 0; }
.bilai-addr-card-actions { display: flex; gap: 8px; }
.bilai-addr-select {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 6px 14px; border: none; border-radius: 8px;
    background: var(--co-primary); color: #fff;
    font-size: 12px; font-weight: 600; cursor: pointer; transition: 0.15s;
}
.bilai-addr-select:hover { background: var(--co-primary-dark); }
.bilai-addr-select.is-selected { background: #2e9e4f; }
.bilai-addr-edit {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 6px 14px; border: none; border-radius: 8px;
    background: var(--co-brown); color: #fff !important;
    font-size: 12px; font-weight: 600; cursor: pointer; text-decoration: none; transition: 0.15s;
}
.bilai-addr-edit:hover { background: #24140a; text-decoration: none; }
.bilai-addr-card-body { padding: 16px 18px; }
.bilai-addr-row { display: flex; gap: 8px; font-size: 13px; margin-bottom: 9px; line-height: 1.5; }
.bilai-addr-row:last-child { margin-bottom: 0; }
.bilai-addr-row .lbl { width: 70px; flex-shrink: 0; color: var(--co-muted); }
.bilai-addr-row .sep { color: var(--co-muted); flex-shrink: 0; }
.bilai-addr-row .val { color: var(--co-text); word-break: break-word; }

/* Add more card */
.bilai-addr-add {
    margin-top: 24px; display: flex; align-items: center; justify-content: center; gap: 8px;
    width: 100%; padding: 44px 16px;
    background: #FFFDF8; border: 1px solid var(--co-border); border-radius: 10px;
    color: var(--co-text); font-size: 14.5px; font-weight: 600;
    text-decoration: none; cursor: pointer; transition: 0.15s;
}
.bilai-addr-add:hover { border-color: var(--co-primary); color: var(--co-primary); text-decoration: none; }
.bilai-addr-empty { text-align: center; font-size: 13.5px; color: var(--co-muted); padding: 8px 0 0; }

/* Guest dialog */
.bilai-addr-guest {
    position: relative; background: #FFFDF8;
    width: 100%; max-width: 420px; margin-top: 60px;
    border-radius: 12px; padding: 38px 28px 34px; text-align: center;
}
.bilai-addr-guest .bilai-addr-close { position: absolute; top: 12px; right: 14px; color: var(--co-muted); }
.bilai-addr-guest-icon { font-size: 22px; color: var(--co-muted); margin-bottom: 12px; }
.bilai-addr-guest p { margin: 0; font-size: 14.5px; font-weight: 600; color: var(--co-text); line-height: 1.6; }
.bilai-addr-guest a { color: var(--co-primary); text-decoration: none; }
.bilai-addr-guest a:hover { text-decoration: underline; }

@media (max-width: 767px) {
    .bilai-addr-grid { grid-template-columns: 1fr; gap: 14px; }
    .bilai-addr-body { padding: 16px; }
    .bilai-addr-add { margin-top: 14px; padding: 28px 16px; }
}
/* BilaiGhor Checkout Address Modal End */
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<section class="checkout-section">
    <?php
        // ==============================================================
        //  PHP LOGIC: CART, SHIPPING, DISCOUNT (UNCHANGED)
        // ==============================================================
        $subtotal = Cart::instance('shopping')->subtotal();
        $subtotal = str_replace(',', '', $subtotal);
        $subtotal = str_replace('.00', '', $subtotal);
        $subtotal = (float) $subtotal;

        // ✅ শিপিং লজিক চেক
        $requires_shipping = false;
        foreach (Cart::instance('shopping')->content() as $item) {
            $product = \App\Models\Product::find($item->id);
            if ($product && $product->is_digital != 1) {
                $requires_shipping = true;
                break;
            }
        }

        // ✅ শিপিং চার্জ সেট
        // ⭐ Free Delivery Check - যদি সব প্রোডাক্ট free delivery eligible হয়, shipping charge 0
        $hasAllFreeDelivery = \App\Http\Controllers\Frontend\ShoppingController::hasAllFreeDeliveryProducts();

        if ($requires_shipping && !$hasAllFreeDelivery) {
            $shipping = Session::get('shipping') ? Session::get('shipping') : 0;
        } else {
            $shipping = 0;
            Session::put('shipping', 0);
        }

        $discount = Session::get('discount', 0);
        // ⭐ Grand Total Calculation - Free delivery হলে shipping charge 0
        $grand_total = $subtotal + $shipping - $discount;

        // ✅ JS ডেটা অ্যারে
        $cartItemsForJs = [];
        $hasDigital = false;
        foreach (Cart::instance('shopping')->content() as $item) {
            $p = \App\Models\Product::find($item->id);
            if ($p && $p->is_digital == 1) { $hasDigital = true; }
            $cartItemsForJs[] = [
                'id'                => $item->id,
                'name'              => $item->name,
                'qty'               => $item->qty,
                'price'             => (float) $item->price,
                'image'             => asset($item->options->image ?? ''),
                'link'              => isset($item->options->slug) ? url('/product/'.$item->options->slug) : '#',
                'is_digital'        => (int) ($p->is_digital ?? 0),
                'free_delivery'     => (int) ($p->free_delivery ?? 0),
                'color_id'          => $item->options->color_id ?? null,
                'size_id'           => $item->options->size_id ?? null,
                'variant_price_id'  => $item->options->variant_price_id ?? null,
            ];
        }

        // Advance Payment has been fully retired from this checkout flow (order_save() always
        // charges the real grand total now). Variables kept at zero/false so nothing downstream
        // that still references these names breaks.
        $advance_amount = 0.0;
        $hasAdvance     = false;
        $payable_now    = $grand_total;
        $due_amount     = 0;

        // Online payment gateways hidden from the frontend for now (kept in backend/admin for later use).
        $__showOnlineGateways = false;

        // ── Prefill resolution: old() input (after validation error) wins, else controller prefill ──
        $checkoutPrefill = $checkoutPrefill ?? [];
        $selDistrict = old('district_id', $checkoutPrefill['district_id'] ?? '');
        $selZone     = old('zone_id',     $checkoutPrefill['zone_id']     ?? '');
        $selPostCode = old('post_code',   $checkoutPrefill['post_code']   ?? '');

        $__gsCheckoutOtp = \App\Models\GeneralSetting::where('status', 1)->first();
        $__custCheckoutOtpPending = session('chkotp_customer_pending');
        $__showCheckoutOtpModal = $__gsCheckoutOtp && ($__gsCheckoutOtp->checkout_otp_enabled ?? 0) == 1 && $__custCheckoutOtpPending;

        // --- Breadcrumb context (display only; no logic change) ---
        $__firstItem   = Cart::instance('shopping')->content()->first();
        $__bcProduct   = $__firstItem ? \App\Models\Product::find($__firstItem->id) : null;
        $__bcCategory  = $__bcProduct ? optional($__bcProduct->category)->name : null;
        $__bcSub       = $__bcProduct ? optional($__bcProduct->subcategory)->subcategoryName : null;
        $__bcName      = $__bcProduct ? $__bcProduct->name : null;
    ?>

    <div class="container">

        
        <nav class="bilai-co-bc" aria-label="breadcrumb">
            <a href="<?php echo e(route('home')); ?>">Home</a>
            <?php if($__bcName): ?>
                <?php if($__bcCategory): ?>
                    <span class="bilai-co-bc-sep">›</span><span><?php echo e($__bcCategory); ?></span>
                <?php endif; ?>
                <?php if($__bcSub): ?>
                    <span class="bilai-co-bc-sep">›</span><span><?php echo e($__bcSub); ?></span>
                <?php endif; ?>
                <span class="bilai-co-bc-sep">›</span><span class="bilai-co-bc-active"><?php echo e(Str::limit($__bcName, 40)); ?></span>
            <?php else: ?>
                <span class="bilai-co-bc-sep">›</span><span class="bilai-co-bc-active">Checkout</span>
            <?php endif; ?>
        </nav>

        
        <form id="checkout-form" action="<?php echo e(route('customer.ordersave')); ?>" method="POST" data-parsley-validate="">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="checkout_otp" id="checkout_otp_hidden" value="<?php echo e(old('checkout_otp')); ?>">
            
            <input type="hidden" name="traffic_source" id="inp_ts" value="<?php echo e(old('traffic_source', session('order_traffic_source', 'direct'))); ?>">
            <input type="hidden" name="traffic_referrer" id="inp_tsr" value="<?php echo e(old('traffic_referrer', session('order_traffic_referrer', ''))); ?>">
            <script>
            try {
                var elTs = document.getElementById('inp_ts');
                var elTsr = document.getElementById('inp_tsr');
                var ts = sessionStorage.getItem('_ts');
                var tsr = sessionStorage.getItem('_tsr');
                if (ts !== null && ts !== '') {
                    elTs.value = ts;
                } else if (elTs.value && elTs.value !== 'direct') {
                    sessionStorage.setItem('_ts', elTs.value);
                }
                if (tsr !== null && tsr !== '') {
                    elTsr.value = tsr;
                } else if (elTsr.value) {
                    sessionStorage.setItem('_tsr', elTsr.value);
                }
            } catch (e) {}
            </script>

            <div class="row">

                
                <div class="col-lg-7 col-md-12 cus-order-2">

                    
                    <div class="checkout-card">
                        <div class="checkout-header">
                            
                            <i class="fa fa-truck"></i>
                            <h6>Shipping Information</h6>
                        </div>
                        <div class="card-body-custom">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label-custom">Your Name <span class="req">*</span></label>
                                        <input type="text" name="name" class="form-control-custom"
                                            value="<?php echo e(old('name', $checkoutPrefill['name'] ?? '')); ?>" placeholder="Write your full name" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label-custom">Mobile <span class="req">*</span></label>
                                        <input type="text" name="phone" class="form-control-custom" minlength="11" maxlength="11" pattern="0[0-9]+"
                                            value="<?php echo e(old('phone', $checkoutPrefill['mobile'] ?? '')); ?>" placeholder="01xxxxxxxxx" required>
                                    </div>
                                </div>

                                <?php if($requires_shipping): ?>
                                
                                <div class="col-12">
                                    <div class="row g-2 g-md-3 align-items-end checkout-location-fields">
                                        <div class="col-12 col-md-4">
                                            <div class="form-group mb-0 mb-md-2">
                                                <label class="form-label-custom">Post Code</label>
                                                <input type="text" name="post_code" id="checkout_post_code" class="form-control-custom"
                                                    maxlength="20" value="<?php echo e($selPostCode); ?>" placeholder="1xxxx">
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-4">
                                            <div class="form-group mb-0 mb-md-2">
                                                <label class="form-label-custom">District <span class="req">*</span></label>
                                                <select name="district_id" id="checkout_district" class="form-control-custom" required>
                                                    <option value="">Select District</option>
                                                    <?php $__currentLoopData = ($checkoutDistricts ?? collect()); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $d): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                        <option value="<?php echo e($d->id); ?>" data-charge="<?php echo e($d->delivery_charge); ?>"
                                                            <?php if((string) $selDistrict === (string) $d->id): echo 'selected'; endif; ?>><?php echo e($d->name); ?> (৳<?php echo e($d->delivery_charge); ?>)</option>
                                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-4">
                                            <div class="form-group mb-0 mb-md-2">
                                                <label class="form-label-custom">Zone <span class="req">*</span></label>
                                                <select name="zone_id" id="checkout_zone" class="form-control-custom" required disabled>
                                                    <option value="">Select Zone</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php else: ?>
                                <div class="col-12">
                                    <div class="form-group">
                                        <label class="form-label-custom">Delivery Location</label>
                                        <input type="text" class="form-control-custom" value="Free shipping — no location required" readonly disabled style="background:#f3f4f6;">
                                        <input type="hidden" name="district_id" value="">
                                        <input type="hidden" name="zone_id" value="">
                                    </div>
                                </div>
                                <?php endif; ?>

                                
                                <div class="col-12">
                                    <div class="form-group">
                                        <label class="form-label-custom">Full Address <span class="req">*</span></label>
                                        <input type="text" name="address" class="form-control-custom"
                                            value="<?php echo e(old('address', $checkoutPrefill['address'] ?? '')); ?>" placeholder="100 Rasulpur Rd" required>
                                        <button type="button" id="bilai-addr-open" class="bilai-addr-btn">
                                            
                                            <svg class="bilai-addr-btn-ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/>
                                                <circle cx="12" cy="10" r="3"/>
                                            </svg>
                                            <span>Select Address</span>
                                        </button>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-group" style="margin-bottom:0;">
                                        <label class="form-label-custom">Order Note (Optional)</label>
                                        <textarea name="order_note" id="order_note" class="form-control-custom" rows="3" style="height:auto; resize:none;"
                                            placeholder="Write your note about the product"><?php echo e($order_note ?? ''); ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    
                    <div class="checkout-card">
                        <div class="checkout-header">
                            
                            <i class="fa fa-credit-card fa-wallet"></i>
                            <h6>Select Payment Method</h6>
                        </div>
                        <div class="card-body-custom">

                            
                            <div class="payment-options-list">

                                
                                <?php if(!$hasDigital): ?>
                                    <label class="payment-option-label">
                                        <input type="radio" name="payment_method" value="cod" checked required>
                                        <div class="payment-content">
                                            
                                            <div class="text-center" style="width: 36px;"><i class="fa fa-truck" style="color:#2e9e4f; font-size:24px;"></i></div>
                                            <div class="pay-info">
                                                <strong>Cash on Delivery</strong>
                                                <small>Pay when you receive the product</small>
                                            </div>
                                        </div>
                                        <div class="check-circle"></div>
                                    </label>
                                <?php endif; ?>

                                
                                <?php if($__showOnlineGateways && $bkash_gateway): ?>
                                    <label class="payment-option-label">
                                        <input type="radio" name="payment_method" value="bkash" required>
                                        <div class="payment-content">
                                            <img src="<?php echo e(asset('public/frontEnd/images/bkash.svg')); ?>" class="pay-logo" alt="bKash">
                                            <div class="pay-info">
                                                <strong>bKash Payment</strong>
                                                <small>Pay via bKash app or gateway</small>
                                            </div>
                                        </div>
                                        <div class="check-circle"></div>
                                    </label>
                                <?php endif; ?>

                                
                                <?php if($__showOnlineGateways && $shurjopay_gateway): ?>
                                    <label class="payment-option-label">
                                        <input type="radio" name="payment_method" value="shurjopay" required>
                                        <div class="payment-content">
                                            <img src="<?php echo e(asset('public/frontEnd/images/shurjoPay.png')); ?>" class="pay-logo" alt="ShurjoPay">
                                            <div class="pay-info">
                                                <strong>Online Payment</strong>
                                                <small>ShurjoPay (Card/Mobile Banking)</small>
                                            </div>
                                        </div>
                                        <div class="check-circle"></div>
                                    </label>
                                <?php endif; ?>

                                
                                <?php if($__showOnlineGateways && $uddoktapay_gateway): ?>
                                    <label class="payment-option-label">
                                        <input type="radio" name="payment_method" value="uddoktapay" required>
                                        <div class="payment-content">
                                            <img src="<?php echo e(asset('public/frontEnd/images/uddokta.png')); ?>" class="pay-logo" alt="UddoktaPay">
                                            <div class="pay-info">
                                                <strong>UddoktaPay</strong>
                                                <small>Mobile banking payment gateway</small>
                                            </div>
                                        </div>
                                        <div class="check-circle"></div>
                                    </label>
                                <?php endif; ?>

                                
                                <?php if($__showOnlineGateways && $aamarpay_gateway): ?>
                                    <label class="payment-option-label">
                                        <input type="radio" name="payment_method" value="aamarpay" required>
                                        <div class="payment-content">
                                            <img src="<?php echo e(asset('public/frontEnd/images/aamarpay.png')); ?>" class="pay-logo" alt="aamarPay" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                            <div class="pay-info" style="display: none;">
                                                <i class="fa fa-credit-card" style="color:var(--co-primary); font-size:20px;"></i>
                                            </div>
                                            <div class="pay-info">
                                                <strong>aamarPay</strong>
                                                <small>Card &amp; mobile banking payment</small>
                                            </div>
                                        </div>
                                        <div class="check-circle"></div>
                                    </label>
                                <?php endif; ?>

                                <?php $__currentLoopData = $manual_gateways ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $mg): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <label class="payment-option-label">
                                        <input type="radio" name="payment_method" value="manual_<?php echo e($mg->id); ?>" required>
                                        <div class="payment-content">
                                            <?php if($mg->logo_asset_url): ?>
                                                <img src="<?php echo e($mg->logo_asset_url); ?>" class="pay-logo" alt="<?php echo e($mg->title); ?>">
                                            <?php else: ?>
                                                
                                                <div class="text-center" style="width: 36px;"><i class="fa fa-money" style="color:var(--co-primary); font-size:20px;"></i></div>
                                            <?php endif; ?>
                                            <div class="pay-info">
                                                <strong><?php echo e($mg->title); ?></strong>
                                                <small>Manual payment — send money and enter the transaction ID</small>
                                            </div>
                                        </div>
                                        <div class="check-circle"></div>
                                    </label>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                            </div>
                            <div id="manual-payment-fields" class="mt-3 p-3" style="display:none;">
                                <h6 class="fw-bold mb-2" style="color:var(--co-text);"><i class="fa fa-info-circle" style="color:var(--co-primary);"></i> Manual Payment Instructions</h6>
                                <div id="manual-instructions-body" class="small mb-3" style="white-space:pre-wrap; color:var(--co-muted);"></div>
                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold">Transaction ID / Reference <span class="req">*</span></label>
                                        <input type="text" name="manual_trx_id" id="manual_trx_id" class="form-control form-control-custom" value="<?php echo e(old('manual_trx_id')); ?>" maxlength="55" placeholder="TrxID">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold">Sender number (optional)</label>
                                        <input type="text" name="manual_sender_number" class="form-control form-control-custom" value="<?php echo e(old('manual_sender_number')); ?>" maxlength="55" placeholder="01xxx">
                                    </div>
                                </div>
                            </div>
                            <script>
                                window.MANUAL_GATEWAYS = <?php echo json_encode(($manual_gateways ?? collect())->map(fn ($g) => [
                                    'code' => 'manual_'.$g->id, 'instructions' => (string) ($g->instructions ?? ''), ])->values()->all()) ?>;
                            </script>
                            
                            <div id="payment-error" class="text-danger fw-bold mt-2 text-center" style="display:none;">
                                <i class="fa fa-exclamation-circle"></i> Please select a payment method.
                            </div>
                        </div>
                    </div>

                    
                    <div class="mobile-submit-btn">
                        <button type="submit" class="btn-place-order">
                            Place Order <i class="fa fa-arrow-right"></i>
                        </button>
                        <div class="text-center small mt-3" style="color:var(--co-muted);">
                            <i class="fa fa-shield"></i> 100% safe &amp; secure checkout
                        </div>
                    </div>

                </div>

                
                <div class="col-lg-5 col-md-12 cust-order-1">
                    <div class="sticky-sidebar">

                        
                        <div class="checkout-card">
                            <div class="checkout-header">
                                
                                <i class="fa fa-shopping-bag"></i>
                                <h6>Order Items (<?php echo e(Cart::instance('shopping')->count()); ?>)</h6>
                                <a href="<?php echo e(route('cart.index')); ?>" class="bilai-co-edit">Edit Cart</a>
                            </div>

                            
                            <div class="card-body-custom" style="padding-top:6px; padding-bottom:6px;">
                                <div class="cart-items-scroll cartlist">
                                    <?php $__currentLoopData = Cart::instance('shopping')->content(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <?php
                                            $__cp = \App\Models\Product::find($value->id);
                                            $__old = ($__cp && $__cp->old_price && $__cp->old_price > $value->price) ? $__cp->old_price : null;
                                        ?>
                                        <div class="checkout-item">
                                            
                                            <a href="<?php echo e(route('product', $value->options->slug)); ?>">
                                                <img src="<?php echo e(asset($value->options->image)); ?>" class="checkout-pro-img" alt="<?php echo e($value->name); ?>">
                                            </a>

                                            
                                            <div class="checkout-pro-info flex-grow-1">
                                                <a href="<?php echo e(route('product', $value->options->slug)); ?>" style="text-decoration:none;">
                                                    <h6><?php echo e(Str::limit($value->name, 40)); ?></h6>
                                                </a>
                                                <?php if($value->options->product_size || $value->options->product_color): ?>
                                                    <div class="meta mb-1">
                                                        <?php if($value->options->product_size): ?> Size: <?php echo e($value->options->product_size); ?> <?php endif; ?>
                                                        <?php if($value->options->product_color): ?> | Color: <?php echo e($value->options->product_color); ?> <?php endif; ?>
                                                    </div>
                                                <?php endif; ?>
                                                <div class="co-price-line">
                                                    <?php echo e($value->qty); ?> x ৳<?php echo e(number_format($value->price, 0)); ?>

                                                    <?php if($__old): ?><span class="co-price-old">৳<?php echo e(number_format($__old, 0)); ?></span><?php endif; ?>
                                                </div>
                                                
                                            </div>

                                            
                                            <div class="text-end">
                                                <div class="co-line-total">৳<?php echo e(number_format($value->price * $value->qty, 0)); ?></div>
                                            </div>
                                        </div>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </div>
                            </div>

                            
                            <div class="coupon-wrapper">
                                <span class="coupon-label">Coupon Code</span>
                                <?php if(!Session::has('coupon_code')): ?>
                                    <div class="coupon-group-modern">
                                        
                                        <input type="text" id="coupon_input" class="coupon-input-modern" placeholder="Put your coupon code">
                                        <button type="button" class="coupon-btn-modern" onclick="submitCoupon()">Apply</button>
                                    </div>
                                <?php else: ?>
                                    <div class="d-flex justify-content-between align-items-center" style="background:#eef7e9; border:1px solid #cfe6c0; border-radius:10px; padding:10px 14px;">
                                        <span style="color:#2e7d32; font-size:13px;"><i class="fa fa-check-circle"></i> Coupon <b><?php echo e(Session::get('coupon_code')); ?></b> applied!</span>
                                        <a href="<?php echo e(route('coupon.remove')); ?>" class="fw-bold text-decoration-none" style="color:#c0392b; font-size:12px;">REMOVE</a>
                                    </div>
                                <?php endif; ?>
                            </div>

                            
                            <?php $__rwEarnPreview = \App\Services\RewardPointService::earnedPointsFor(max(0, $subtotal - $discount)); ?>
                            <div class="bilai-co-earn">
                                <div class="bilai-co-earn-ic">
                                    
                                    <i class="fa fa-star"></i>
                                </div>
                                <div>
                                    <p>You will earn <strong id="bilai-rw-earn"><?php echo e($__rwEarnPreview); ?></strong> reward points for this order — points are credited once your order has been delivered.</p>
                                    <a href="<?php echo e(route('customer.rewards')); ?>">Reward Points Policy</a>
                                </div>
                            </div>
                        </div>

                        
                        <?php if(auth()->guard('customer')->check()): ?>
                        <?php $__rwBalance = Auth::guard('customer')->user()->rewardBalance(); ?>
                        <div class="checkout-card">
                            <div class="checkout-header">
                                
                                <i class="fa fa-star"></i>
                                <h6>Use Your Reward Point</h6>
                            </div>
                            <div class="card-body-custom">
                                <div class="bilai-co-reward-row">
                                    <span class="bilai-co-reward-pill">
                                        <span class="dot"></span> <span id="bilai-rw-avail"><?php echo e($__rwBalance); ?></span> Points available
                                    </span>
                                    
                                    <input type="hidden" name="use_reward_points" id="bilai-rw-input" value="<?php echo e(old('use_reward_points') ? 1 : 0); ?>">
                                    <label class="bilai-co-toggle" <?php if($__rwBalance < 1): ?> title="No points available" <?php endif; ?>>
                                        <input type="checkbox" id="bilai-rw-toggle" <?php if(old('use_reward_points')): echo 'checked'; endif; ?> <?php if($__rwBalance < 1): echo 'disabled'; endif; ?>>
                                        <span class="track"></span>
                                    </label>
                                </div>
                                <a href="<?php echo e(route('customer.rewards')); ?>" class="bilai-co-policy">Reward Points Policy</a>
                            </div>
                        </div>
                        <?php endif; ?>

                        
                        <div class="checkout-card">
                            <div class="checkout-header">
                                
                                <i class="fa fa-list-alt"></i>
                                <h6>Order Summary</h6>
                            </div>
                            <div class="summary-totals" style="padding-top:16px;">
                                <div class="total-row"><span>Subtotal</span> <span id="subtotalAmount">৳ <?php echo e(number_format($subtotal, 2)); ?></span></div>
                                <div class="total-row"><span>Delivery Charge</span> <span id="shippingAmount">৳ <?php echo e(number_format($shipping, 2)); ?></span></div>
                                <div class="total-row"><span>Discount</span> <span id="discountAmount">- ৳ <?php echo e(number_format($discount, 2)); ?></span></div>
                                <div class="total-row"><span>Cash from Reward Points</span> <span id="rewardDiscountAmount">- ৳ 0.00</span></div>
                                <div class="total-row final"><span>Total</span> <span id="grandTotalAmount">৳ <?php echo e(number_format($grand_total, 2)); ?></span></div>
                            </div>

                            
                            <div class="desktop-submit-btn" style="padding:8px 22px 22px;">
                                <button type="submit" class="btn-place-order">
                                    Place Order <i class="fa fa-check-circle"></i>
                                </button>
                                <div class="text-center small mt-3" style="color:var(--co-muted);">
                                    <i class="fa fa-lock"></i> 100% safe checkout process
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

            </div>
        </form>

        
        <div id="bilai-addr-modal" class="bilai-addr-overlay" aria-hidden="true">
            <?php if(auth()->guard('customer')->check()): ?>
                <div class="bilai-addr-dialog" role="dialog" aria-label="Your Addresses">
                    <div class="bilai-addr-head">
                        <h5>Your Addresses</h5>
                        <button type="button" class="bilai-addr-close" aria-label="Close">&times;</button>
                    </div>
                    <div class="bilai-addr-body">
                        <?php if(count($savedAddresses ?? []) > 0): ?>
                            <div class="bilai-addr-grid">
                                <?php $__currentLoopData = $savedAddresses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $addr): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <div class="bilai-addr-card">
                                        <div class="bilai-addr-card-head">
                                            <p class="bilai-addr-card-title">Address <?php echo e($i + 1); ?></p>
                                            <div class="bilai-addr-card-actions">
                                                <button type="button" class="bilai-addr-select"
                                                        data-name="<?php echo e($addr['name']); ?>"
                                                        data-mobile="<?php echo e($addr['mobile']); ?>"
                                                        data-address="<?php echo e($addr['address']); ?>"
                                                        data-postcode="<?php echo e($addr['post_code'] ?? ''); ?>"
                                                        data-dist="<?php echo e($addr['district_id']); ?>"
                                                        data-zone="<?php echo e($addr['zone_id'] ?? ''); ?>">
                                                    
                                                    <i class="fa fa-check"></i> <span>Select</span>
                                                </button>
                                                <?php if(!empty($addr['id'])): ?>
                                                    <button type="button" class="bilai-addr-edit bilai-afm-edit-open"
                                                            data-id="<?php echo e($addr['id']); ?>"
                                                            data-name="<?php echo e($addr['name']); ?>"
                                                            data-mobile="<?php echo e($addr['mobile']); ?>"
                                                            data-email="<?php echo e($addr['email']); ?>"
                                                            data-postcode="<?php echo e($addr['post_code'] ?? ''); ?>"
                                                            data-district="<?php echo e($addr['district_id']); ?>"
                                                            data-zone="<?php echo e($addr['zone_id'] ?? ''); ?>"
                                                            data-address="<?php echo e($addr['address']); ?>">
                                                        
                                                        <i class="fa fa-pencil-square-o"></i> Edit
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="bilai-addr-card-body">
                                            <div class="bilai-addr-row"><span class="lbl">Name</span><span class="sep">:</span><span class="val"><?php echo e($addr['name'] ?: 'N/A'); ?></span></div>
                                            <div class="bilai-addr-row"><span class="lbl">Mobile</span><span class="sep">:</span><span class="val"><?php echo e($addr['mobile'] ?: 'N/A'); ?></span></div>
                                            <?php if(!empty($addr['email'])): ?>
                                                <div class="bilai-addr-row"><span class="lbl">Email</span><span class="sep">:</span><span class="val"><?php echo e($addr['email']); ?></span></div>
                                            <?php endif; ?>
                                            <div class="bilai-addr-row"><span class="lbl">Address</span><span class="sep">:</span><span class="val"><?php echo e($addr['address']); ?></span></div>
                                        </div>
                                    </div>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </div>
                        <?php else: ?>
                            <p class="bilai-addr-empty">No saved addresses yet.</p>
                        <?php endif; ?>

                        <button type="button" class="bilai-addr-add bilai-afm-add-open" style="cursor:pointer;">
                            
                            <i class="fa fa-plus"></i> Add More Address
                        </button>
                    </div>
                </div>
            <?php else: ?>
                <div class="bilai-addr-guest" role="dialog" aria-label="Login required">
                    <button type="button" class="bilai-addr-close" aria-label="Close">&times;</button>
                    
                    <div class="bilai-addr-guest-icon"><i class="fa fa-user-o"></i></div>
                    <p>
                        <a href="<?php echo e(route('customer.login')); ?>">Login</a> or
                        <a href="<?php echo e(route('customer.register')); ?>">Register</a> to Select/Add<br>Your Address
                    </p>
                </div>
            <?php endif; ?>
        </div>

        
        <?php if(auth()->guard('customer')->check()): ?>
            <?php echo $__env->make('frontEnd.layouts.customer.partials.address-form-modal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <?php endif; ?>

        <?php if(!empty($__showCheckoutOtpModal)): ?>
        <div class="modal fade" id="checkoutOtpModal" tabindex="-1" aria-labelledby="checkoutOtpModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg overflow-hidden">
                    <div class="modal-header text-white" style="background: linear-gradient(135deg,#F28C00,#c96f00);">
                        <h5 class="modal-title d-flex align-items-center gap-2 mb-0" id="checkoutOtpModalLabel">
                            <i class="fa fa-mobile"></i> OTP Verification
                        </h5>
                    </div>
                    <div class="modal-body p-4">
                        <p class="text-muted small mb-3">A <strong>6 digit OTP</strong> has been sent to your mobile number via SMS. Enter the code and confirm below.</p>
                        <?php $__errorArgs = ['checkout_otp'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <div class="alert alert-danger py-2 small mb-3"><?php echo e($message); ?></div>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        <label class="form-label fw-semibold">OTP Code</label>
                        <input type="text" id="checkout_otp_modal_field" class="form-control form-control-lg text-center letter-spacing-wide" maxlength="6"
                            inputmode="numeric" autocomplete="one-time-code" placeholder="● ● ● ● ● ●" style="letter-spacing: 0.35em;"
                            value="<?php echo e(old('checkout_otp')); ?>">
                        <div class="d-flex flex-wrap gap-2 mt-4 justify-content-between align-items-center">
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="checkout_otp_resend_btn">Resend OTP</button>
                            <button type="button" class="btn btn-success px-4 fw-bold" id="checkout_otp_confirm_btn">Complete Order</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <form id="checkout_otp_resend_form" action="<?php echo e(route('customer.checkout.resend_otp')); ?>" method="POST" style="display:none;">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="phone" id="checkout_otp_resend_phone" value="">
        </form>
        <?php endif; ?>

    </div>
</section>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('script'); ?>


<?php if(!empty($__showCheckoutOtpModal)): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var modalEl = document.getElementById('checkoutOtpModal');
    if (!modalEl) return;
    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        bootstrap.Modal.getOrCreateInstance(modalEl, { backdrop: 'static', keyboard: false }).show();
    } else if (window.jQuery && jQuery.fn.modal) {
        jQuery(modalEl).modal({ backdrop: 'static', keyboard: false });
        jQuery(modalEl).modal('show');
    }
    var modalInput = document.getElementById('checkout_otp_modal_field');
    if (modalInput) {
        setTimeout(function () { modalInput.focus(); }, 400);
    }
    document.getElementById('checkout_otp_confirm_btn').addEventListener('click', function () {
        var raw = modalInput ? modalInput.value : '';
        document.getElementById('checkout_otp_hidden').value = raw.replace(/\D/g, '').slice(0, 6);
        document.getElementById('checkout-form').submit();
    });
    document.getElementById('checkout_otp_resend_btn').addEventListener('click', function () {
        var phoneIn = document.querySelector('#checkout-form input[name="phone"]');
        document.getElementById('checkout_otp_resend_phone').value = phoneIn ? phoneIn.value : '';
        document.getElementById('checkout_otp_resend_form').submit();
    });
});
</script>
<?php endif; ?>





        
        
        

        
        <form id="coupon-form" action="<?php echo e(route('coupon.apply')); ?>" method="POST" style="display:none;">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="coupon_code" id="hidden_coupon_code">
        </form>

        
        <script>
            function submitCoupon() {
                var code = document.getElementById('coupon_input').value;
                if(code) {
                    document.getElementById('hidden_coupon_code').value = code;
                    document.getElementById('coupon-form').submit();
                } else {
                    // টোস্টার থাকলে টোস্টার, নাহলে এলার্ট
                    if(typeof toastr !== 'undefined') {
                        toastr.error('Please enter a coupon code');
                    } else {
                        alert('Please enter a coupon code');
                    }
                }
            }
        </script>
<script>
    // গ্লোবাল ভেরিয়েবল (Global Variables)
    let incompleteOrderTimer;
    let isSubmitting = false; // অর্ডার সাবমিট হচ্ছে কিনা তা চেক করার জন্য

    $(document).ready(function() {
        // Select2 Initialize (guarded: select2 only ships via the address modal partial for logged-in users).
        // Scoped to real <select> elements only: select2 v4's generated container spans also
        // carry the class "select2", and initializing on those spans renders empty duplicate boxes.
        if ($.fn && $.fn.select2) { $("select.select2").not(".select2-hidden-accessible").select2({ width: '100%' }); }

        // ==========================================
        // 1. CART LOGIC (REMOVE, INCREASE, DECREASE)
        // ==========================================

        // Remove Item
        $(document).on('click', '.cart_remove', function(e) {
            e.preventDefault(); e.stopImmediatePropagation();
            var id = $(this).data("id");
            if (id) {
                $("#loading").show();
                $.ajax({
                    type: "GET",
                    url: "<?php echo e(route('cart.remove')); ?>",
                    data: { id: id },
                    success: function() { toastr.success('Success', 'Item removed'); window.location.reload(); },
                    error: function() { window.location.reload(); }
                });
            }
        });

        // Quantity Increment
        $('.checkout-qty .plus').on('click', function() {
            var rowId = $(this).closest('.checkout-qty').data('rowid');
            $("#loading").show();
            $.get("<?php echo e(route('cart.increment')); ?>", { id: rowId }, function() { window.location.reload(); });
        });

        // Quantity Decrement
        $('.checkout-qty .minus').on('click', function() {
            var rowId = $(this).closest('.checkout-qty').data('rowid');
            $("#loading").show();
            $.get("<?php echo e(route('cart.decrement')); ?>", { id: rowId }, function() { window.location.reload(); });
        });

        // ==========================================
        // 2. SHIPPING & TOTAL CALCULATION
        // ==========================================

        const baseSubtotal = parseFloat("<?php echo e($subtotal ?? 0); ?>");
        const baseDiscount = parseFloat("<?php echo e($discount ?? 0); ?>");
        const requiresShipping = <?php echo json_encode($requires_shipping ?? false, 15, 512) ?>;
        const cartItems = <?php echo json_encode($cartItemsForJs ?? [], 15, 512) ?>;
        const hasAllFreeDelivery = <?php echo json_encode($hasAllFreeDelivery ?? false, 15, 512) ?>;

        // ⭐ Free Delivery Check Function
        function checkFreeDelivery() {
            // Check if all physical products have free_delivery = 1
            let allFreeDelivery = true;
            for (let i = 0; i < cartItems.length; i++) {
                let item = cartItems[i];
                // Skip digital products
                if (item.is_digital == 1) {
                    continue;
                }
                // If any physical product doesn't have free_delivery, return false
                if (item.free_delivery != 1) {
                    allFreeDelivery = false;
                    break;
                }
            }
            return allFreeDelivery;
        }

        function districtChargeFromSelect() {
            if (!$('#checkout_district').length || !$('#checkout_district').val()) {
                return 0;
            }
            return parseFloat($('#checkout_district option:selected').attr('data-charge')) || 0;
        }

        // Reward discount currently applied (display only — server recomputes on submit).
        window.bilaiRewardDiscount = 0;

        function applyShippingToDomAndSession() {
            var isFreeDelivery = checkFreeDelivery();
            var shippingCharge = isFreeDelivery ? 0 : districtChargeFromSelect();

            var grandTotal = Math.max(0, baseSubtotal + shippingCharge - baseDiscount - (window.bilaiRewardDiscount || 0));

            $('#shippingAmount').text('৳ ' + shippingCharge.toFixed(2));
            $('#grandTotalAmount').text('৳ ' + grandTotal.toFixed(2));

            if (!requiresShipping) {
                return;
            }

            if (isFreeDelivery) {
                $.get('<?php echo e(route("shipping.charge")); ?>', { id: 'free_delivery' });
            } else {
                var did = $('#checkout_district').val();
                if (did) {
                    $.get('<?php echo e(route("shipping.charge")); ?>', { id: did });
                }
            }
        }

        // District → Zone. Zone options are loaded by the shared BilaiDistrictZone helper
        // (same endpoint as the Add/Edit Address popup); this handler only refreshes the
        // shipping charge, which is still district-based — charge logic is unchanged.
        $('#checkout_district').on('change', function () {
            applyShippingToDomAndSession();
            saveIncompleteOrder();
        });

        // ── Use Your Reward Point toggle (logged-in only; card absent for guests) ──
        // Numbers always come from the backend preview endpoint, never from JS math.
        $('#bilai-rw-toggle').on('change', function () {
            var on = this.checked;
            var $toggle = $(this).prop('disabled', true);
            $.post('<?php echo e(route("customer.checkout.reward_preview")); ?>', {
                _token: '<?php echo e(csrf_token()); ?>',
                use_reward_points: on ? 1 : 0
            }, function (res) {
                $('#bilai-rw-input').val(on ? 1 : 0);
                $('#bilai-rw-avail').text(res.available_points);
                $('#rewardDiscountAmount').text('- ৳ ' + Number(res.reward_discount).toFixed(2));
                $('#bilai-rw-earn').text(res.earn_points);
                window.bilaiRewardDiscount = Number(res.reward_discount) || 0;
                applyShippingToDomAndSession(); // recompute total with the new discount
            }).fail(function () {
                $('#bilai-rw-toggle').prop('checked', false);
                $('#bilai-rw-input').val(0);
            }).always(function () {
                $toggle.prop('disabled', false);
            });
        });

        $('#checkout_zone').on('change', function () {
            saveIncompleteOrder();
        });

        // ⭐ পেজ লোড — ফ্রি ডেলিভারি / শিপিং সার্ফেস
        $(document).ready(function() {
            var isFreeDeliveryOnLoad = hasAllFreeDelivery || checkFreeDelivery();

            if (!requiresShipping) {
                return;
            }

            if (isFreeDeliveryOnLoad) {
                applyShippingToDomAndSession();
            } else {
                var currentShipping = parseFloat($('#shippingAmount').text().replace(/[৳,\s]/g, '').trim()) || 0;
                var grandTotal = Math.max(0, baseSubtotal + currentShipping - baseDiscount - (window.bilaiRewardDiscount || 0));

                $('#grandTotalAmount').text('৳ ' + grandTotal.toFixed(2));

                var did = $('#checkout_district').val();
                if (did) {
                    $.get('<?php echo e(route("shipping.charge")); ?>', { id: did });
                }
            }
        });

        // ==========================================
        // 3. INCOMPLETE ORDER LOGIC (MAIN REQUEST)
        // ==========================================

        function selectedLocationText($sel) {
            if (!$sel.length || !$sel.val()) return '';
            return ($sel.find('option:selected').text() || '').replace(/\s*\(৳[^)]*\)\s*/g, '').trim();
        }

        function buildCheckoutAddress() {
            var street = ($('input[name="address"]').val() || '').trim();
            var parts = [];
            if (requiresShipping) {
                var zone = selectedLocationText($('#checkout_zone'));
                var dist = selectedLocationText($('#checkout_district'));
                if (zone) parts.push(zone);
                if (dist) parts.push(dist);
            }
            if (street) parts.unshift(street);
            return parts.join(', ');
        }

        function buildCheckoutMeta(shippingCharge) {
            var meta = {
                subtotal: baseSubtotal,
                discount: baseDiscount,
                shipping_charge: shippingCharge,
                order_note: ($('#order_note').val() || '').trim()
            };
            if (requiresShipping) {
                // Incomplete-order meta: district_id is still sent; location_label carries
                // "Zone, District" (IncompleteOrderController already falls back to it).
                meta.district_id = $('#checkout_district').val() || null;
                meta.zone_id     = $('#checkout_zone').val() || null;
                meta.post_code   = ($('#checkout_post_code').val() || '').trim();
                var loc = [];
                var zone = selectedLocationText($('#checkout_zone'));
                var dist = selectedLocationText($('#checkout_district'));
                if (zone) loc.push(zone);
                if (dist) loc.push(dist);
                meta.location_label = loc.join(', ');
            }
            return meta;
        }

        function saveIncompleteOrder() {
            if (isSubmitting) return;
            if (incompleteOrderTimer) clearTimeout(incompleteOrderTimer);

            incompleteOrderTimer = setTimeout(function() {
                var name = ($('input[name="name"]').val() || '').trim();
                var phone = ($('input[name="phone"]').val() || '').replace(/\D/g, '');
                var address = buildCheckoutAddress();

                if (!name || phone.length < 11) {
                    return;
                }

                if (!cartItems || !cartItems.length) {
                    return;
                }

                var isFreeDelivery = checkFreeDelivery();
                var shippingCharge = isFreeDelivery ? 0 : districtChargeFromSelect();
                var total = (baseSubtotal + shippingCharge - baseDiscount).toFixed(2);
                var meta = buildCheckoutMeta(shippingCharge);

                $.ajax({
                    url: '<?php echo e(route("incomplete.order.store")); ?>',
                    type: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                        'Accept': 'application/json'
                    },
                    data: {
                        name: name,
                        phone: phone,
                        address: address,
                        items: cartItems,
                        checkout_meta: meta,
                        total_amount: total,
                        product_image: cartItems[0] && cartItems[0].image ? cartItems[0].image : '',
                        product_link: cartItems[0] && cartItems[0].link ? cartItems[0].link : ''
                    }
                });
            }, 2000);
        }

        // ফর্মের যেকোনো ইনপুট চেঞ্জ হলে এই ফাংশন কল হবে
        $('#checkout-form input, #checkout-form select, #checkout-form textarea').on('input change', function() {
             if($(this).attr('name') !== 'payment_method') {
                 saveIncompleteOrder();
             }
        });

        // ==========================================
        // 4. FORM SUBMISSION & VALIDATION
        // ==========================================

        $('#checkout-form').on('submit', function(e) {
            // পেমেন্ট মেথড চেক
            var paymentMethod = $('input[name="payment_method"]:checked').val();

            if (!paymentMethod) {
                e.preventDefault();
                toastr.error('অর্ডার সম্পন্ন করতে পেমেন্ট মেথড নির্বাচন করুন।', 'Error');
                $('#payment-error').show();
                $('html, body').animate({ scrollTop: $(".checkout-card .fa-wallet").offset().top - 150 }, 500);
                $('.btn-place-order').prop('disabled', false);
                return false;
            } else {
                $('#payment-error').hide();

                var pm = $('input[name="payment_method"]:checked').val() || '';
                if (pm.indexOf('manual_') === 0) {
                    var trx = $('input[name="manual_trx_id"]').val();
                    if (!trx || !String(trx).trim()) {
                        e.preventDefault();
                        toastr.error('ম্যানুয়াল পেমেন্টের জন্য ট্রানজেকশন আইডি লিখুন।', 'Error');
                        $('#manual-payment-fields').show();
                        $('html, body').animate({ scrollTop: $('#manual-payment-fields').offset().top - 120 }, 400);
                        $('.btn-place-order').prop('disabled', false);
                        return false;
                    }
                }

                // ৩. অর্ডার সাবমিট হচ্ছে, তাই ইনকমপ্লিট টাইমার বন্ধ করে দেওয়া হলো
                isSubmitting = true;
                if(incompleteOrderTimer) {
                    clearTimeout(incompleteOrderTimer);
                }

                // ফর্ম সাবমিট হতে দিন...
            }
        });

        // পেমেন্ট সিলেক্ট করলে এরর হাইড হবে
        function syncManualPaymentUi() {
            var v = $('input[name="payment_method"]:checked').val() || '';
            if (v.indexOf('manual_') === 0) {
                $('#manual-payment-fields').show();
                var inst = '';
                (window.MANUAL_GATEWAYS || []).forEach(function (g) {
                    if (g.code === v) {
                        inst = g.instructions || '';
                    }
                });
                $('#manual-instructions-body').html($('<div/>').text(inst).html().replace(/\n/g, '<br>'));
                $('#manual_trx_id').prop('required', true);
            } else {
                $('#manual-payment-fields').hide();
                $('#manual_trx_id').prop('required', false);
            }
        }

        $('input[name="payment_method"]').on('change', function() {
            $('#payment-error').hide();
            syncManualPaymentUi();
        });
        if (!$('input[name="payment_method"]:checked').length && $('input[name="payment_method"]').length) {
            $('input[name="payment_method"]:first').prop('checked', true);
        }
        syncManualPaymentUi();

        // চেকআউটে আগে থেকে তথ্য থাকলে একবার ইনকমপ্লিট সেভ ট্রিগার
        setTimeout(function() { saveIncompleteOrder(); }, 2500);
    });
</script>

<script type="text/javascript">
(function () {
    if (typeof window.EcomTracking === 'undefined') return;

    var items = <?php echo json_encode($cartItemsForJs, 15, 512) ?>;
    var grandTotal = parseFloat("<?php echo e($grand_total); ?>") || 0;
    var payableNow = grandTotal;
    var coupon = <?php echo json_encode(Session::get('coupon_code', null), 512) ?>;

    function checkoutUserFromForm() {
        return {
            name: ($('input[name="name"]').val() || '').trim(),
            phone: ($('input[name="phone"]').val() || '').trim(),
            address: ($('input[name="address"]').val() || '').trim(),
            city: ($('#checkout_district option:selected').text() || '').replace(/\s*\(৳[^)]*\)\s*/g, '').trim()
        };
    }

    if (items.length) {
        EcomTracking.initiateCheckout({
            items: items,
            value: payableNow,
            coupon: coupon
        });
    }

    var identifyTimer;
    $('#checkout-form input[name="name"], #checkout-form input[name="phone"]').on('input blur', function () {
        clearTimeout(identifyTimer);
        identifyTimer = setTimeout(function () {
            var u = checkoutUserFromForm();
            if (u.phone && String(u.phone).replace(/\D/g, '').length >= 11) {
                EcomTracking.identify(u);
            }
        }, 800);
    });

    <?php if(auth()->guard('customer')->check()): ?>
    EcomTracking.identify(<?php echo json_encode(\App\Support\EcommerceTrackingUser::fromCustomer(auth('customer')->user()), 15, 512) ?>);
    <?php endif; ?>

    var form = document.getElementById('checkout-form');
    if (form) {
        form.addEventListener('submit', function () {
            var pm = form.querySelector('input[name="payment_method"]:checked');
            EcomTracking.identify(checkoutUserFromForm());
            EcomTracking.addPaymentInfo({
                items: items,
                value: payableNow,
                coupon: coupon,
                payment_method: pm ? pm.value : ''
            });
        });
    }
})();
</script>

<?php if($requires_shipping): ?>

<?php echo $__env->make('frontEnd.layouts.customer.partials.district-zone-js', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php
    $__prefillJs = [
        'district_id' => (string) $selDistrict,
        'zone_id'     => (string) $selZone,
        'subtotal'    => (float) ($subtotal ?? 0),
        'discount'    => (float) ($discount ?? 0),
        'free'        => (bool) ($hasAllFreeDelivery ?? false),
    ];
?>
<script>
$(function () {
    var PF = <?php echo json_encode($__prefillJs, 15, 512) ?>;

    // District is rendered pre-selected server-side. initFields() makes both selects
    // searchable, loads the district's zones, and selects the saved zone once the AJAX
    // resolves (no setTimeout). Changing district clears the zone automatically.
    window.BilaiDistrictZone.initFields({
        district:     '#checkout_district',
        zone:         '#checkout_zone',
        selectedZone: PF.zone_id || null,
        fresh:        true
    });

    // Shipping charge for the prefilled district (charge stays district-based).
    if (PF.district_id) {
        var $dist  = $('#checkout_district');
        var charge = PF.free ? 0 : (parseFloat($dist.find('option:selected').attr('data-charge')) || 0);
        $('#shippingAmount').text('৳ ' + charge.toFixed(2));
        $('#grandTotalAmount').text('৳ ' + Math.max(0, PF.subtotal + charge - PF.discount - (window.bilaiRewardDiscount || 0)).toFixed(2));
        $.get('<?php echo e(route("shipping.charge")); ?>', { id: PF.free ? 'free_delivery' : PF.district_id });
    }
});
</script>
<?php endif; ?>


<?php
    $__addrLocJs = [
        'subtotal' => (float) ($subtotal ?? 0),
        'discount' => (float) ($discount ?? 0),
        'free'     => (bool) ($hasAllFreeDelivery ?? false),
    ];
?>
<script>
(function () {
    var modal   = document.getElementById('bilai-addr-modal');
    var openBtn = document.getElementById('bilai-addr-open');
    if (!modal || !openBtn) return;

    var LOC = <?php echo json_encode($__addrLocJs, 15, 512) ?>;

    function openModal()  { modal.classList.add('open');  document.body.style.overflow = 'hidden'; }
    function closeModal() { modal.classList.remove('open'); document.body.style.overflow = ''; }

    openBtn.addEventListener('click', openModal);
    modal.querySelectorAll('.bilai-addr-close').forEach(function (b) { b.addEventListener('click', closeModal); });
    modal.addEventListener('click', function (e) { if (e.target === modal) closeModal(); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && modal.classList.contains('open')) closeModal(); });

    // Apply a saved address' District → Zone. District options are already on the page,
    // so we just select it, refresh the (district-based) charge, then let the shared
    // helper load that district's zones and preselect the saved zone when the AJAX lands.
    function applyLocation(distId, zoneId) {
        var $dist = $('#checkout_district'), $zone = $('#checkout_zone');
        if (!$dist.length || !distId) return;

        window.BilaiDistrictZone.setVal($dist, distId);

        var charge = LOC.free ? 0 : (parseFloat($dist.find('option:selected').attr('data-charge')) || 0);
        $('#shippingAmount').text('৳ ' + charge.toFixed(2));
        $('#grandTotalAmount').text('৳ ' + Math.max(0, LOC.subtotal + charge - LOC.discount - (window.bilaiRewardDiscount || 0)).toFixed(2));
        $.get('<?php echo e(route("shipping.charge")); ?>', { id: LOC.free ? 'free_delivery' : distId });

        window.BilaiDistrictZone.loadZones($zone, distId, zoneId || null);
    }

    // Select an address: fill checkout fields, mark card selected, close.
    modal.querySelectorAll('.bilai-addr-select').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var d = this.dataset;
            $('input[name="name"]').val(d.name || '').trigger('change');
            $('input[name="phone"]').val(d.mobile || '').trigger('change');
            $('input[name="address"]').val(d.address || '').trigger('change');
            $('#checkout_post_code').val(d.postcode || '').trigger('change');
            if (d.dist) { applyLocation(d.dist, d.zone); }

            modal.querySelectorAll('.bilai-addr-select').forEach(function (b) {
                b.classList.remove('is-selected');
                b.querySelector('span').textContent = 'Select';
            });
            this.classList.add('is-selected');
            this.querySelector('span').textContent = 'Selected';
            closeModal();
        });
    });
}());
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('frontEnd.layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\bilaiGhor\resources\views/frontEnd/layouts/customer/checkout.blade.php ENDPATH**/ ?>