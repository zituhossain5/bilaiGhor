<?php
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Models\Order;

$customer            = Auth::guard('customer')->user();
$customerId          = $customer->id;
$pendingOrdersCount  = Order::where('customer_id', $customerId)->whereNotIn('order_status', ['6', '11'])->count();
$totalOrderAmount    = Order::where('customer_id', $customerId)->sum('amount');
$profileImage        = $customer->image ? asset($customer->image) : null;
$customerInitial     = strtoupper(substr($customer->name ?? 'U', 0, 1));

// ── Payment calc (same logic as invoice/orders pages) ──
$payment        = \App\Models\Payment::where('order_id', $order->id)->orderBy('id', 'desc')->first();
$gateway_status = $payment ? strtolower(trim($payment->payment_status)) : '';
$payment_method = $payment ? strtolower(trim($payment->payment_method)) : strtolower(trim($order->payment_gateway ?? ''));
$admin_status   = strtolower(trim($order->payment_status ?? ''));
$order_slug     = strtolower(trim($order->status->slug ?? $order->status->name ?? ''));

$grand_total = $order->amount;
$paid_amount = 0;
if ($payment && !in_array($gateway_status, ['failed', 'cancel', 'cancelled', 'rejected'])) {
    $paid_amount = $payment->amount;
}
$is_cod             = in_array($payment_method, ['cod', 'cash', 'cash_on_delivery', 'hand cash']);
$is_order_completed = in_array($order_slug, ['completed', 'delivered']) || in_array($admin_status, ['completed', 'delivered']);
if ($is_cod && !$is_order_completed && $paid_amount >= $grand_total) { $paid_amount = 0; }
if ($is_order_completed) { $paid_amount = $grand_total; }
elseif (($paid_amount == 0 || !$payment) && in_array($admin_status, ['paid', 'success', 'approved'])) { $paid_amount = $grand_total; }
$due_amount = max(0, $grand_total - $paid_amount);

// ── Summary derived values ──
$subtotal        = ($order->amount + $order->discount) - $order->shipping_charge;
$paymentLabel    = $payment_method ? strtoupper($payment_method) : 'N/A';
$paymentBadge    = $is_cod ? 'COD' : strtoupper($payment_method ?: 'N/A');
$paymentReadable = $is_cod ? 'Cash on Delivery' : ($payment_method ? ucwords(str_replace('_', ' ', $payment_method)) : 'N/A');

// ── Order status stepper mapping ──
// Real order_status ids: 1 Pending, 2 Processing, 3 On The Way, 5 In Courier, 6 Completed, 8 Unpaid, 11 Cancelled
$isCancelled  = ((string) $order->order_status === '11');
$statusToStep = ['1' => 1, '8' => 1, '2' => 2, '3' => 3, '5' => 3, '6' => 4];
$currentStep  = $isCancelled ? 0 : ($statusToStep[(string) $order->order_status] ?? 1);

$deliveredAt = $order->rider_delivered_at ?? $order->created_at;
$steps = [
    ['label' => 'Processing', 'lvl' => 1, 'time' => $order->created_at],
    ['label' => 'Confirmed',  'lvl' => 2, 'time' => $order->created_at],
    ['label' => 'Shipped',    'lvl' => 3, 'time' => $order->courier_sent_at ?? $order->created_at],
    ['label' => 'Delivered',  'lvl' => 4, 'time' => $deliveredAt],
];

// ── Courier / tracking ──
$courierName    = $order->courier_type ? ucfirst($order->courier_type) : null;
$trackingId     = $order->courier_tracking_id ?? $order->consignment_id ?? null;
$steadfastTrack = ($order->courier_tracking_code ?? $trackingId);
?>


<?php $__env->startSection('title', 'Order #' . ($order->invoice_id ?? $order->id) . ' | ' . ($customer->name ?? 'Account')); ?>

<?php $__env->startPush('css'); ?>
<style>
/* BilaiGhor Customer Order Details Start */

:root {
    --bilai-od-primary: var(--bilai-primary, #F28C00);
    --bilai-od-primary-dark: var(--bilai-primary-dark, #c96f00);
    --bilai-od-brown:   var(--bilai-brown,   #3A1F0F);
    --bilai-od-cream:   var(--bilai-cream,   #FFF8EC);
    --bilai-od-card:    #FFFDF8;
    --bilai-od-border:  var(--bilai-border,  #E8CDA5);
    --bilai-od-text:    var(--bilai-text,    #2B1A10);
    --bilai-od-muted:   var(--bilai-muted,   #77706A);
    --bilai-od-radius:  14px;
    --bilai-od-radius-sm: 8px;
}

/* ── Page ── */
.bilai-od-page { background: #f5f5f0; min-height: 72vh; padding: 20px 0 52px; }

/* ── Breadcrumb ── */
.bilai-od-bc { display: flex; align-items: center; gap: 5px; font-size: 12.5px; margin-bottom: 18px; flex-wrap: wrap; }
.bilai-od-bc a { color: var(--bilai-od-muted); text-decoration: none; }
.bilai-od-bc a:hover { color: var(--bilai-od-primary); }
.bilai-od-bc-sep    { color: #c0b0a0; font-size: 11px; }
.bilai-od-bc-active { color: var(--bilai-od-primary); font-weight: 600; }

/* ── Layout ── */
.bilai-od-layout { display: grid; grid-template-columns: 248px 1fr; gap: 20px; align-items: start; }
.bilai-od-sidebar { position: sticky; top: 90px; display: flex; flex-direction: column; gap: 14px; }
.bilai-od-content { display: flex; flex-direction: column; gap: 18px; }
.bilai-card { background: #fff; border: 1px solid var(--bilai-od-border); border-radius: var(--bilai-od-radius); overflow: hidden; }

/* ── Sidebar (shared style with orders/dashboard) ── */
.bilai-od-profile-box { padding: 18px 16px 16px; }
.bilai-od-profile-row { display: flex; align-items: center; gap: 12px; margin-bottom: 14px; }
.bilai-od-avatar { width: 56px; height: 56px; border-radius: 50%; object-fit: cover; border: 2px solid var(--bilai-od-border); flex-shrink: 0; }
.bilai-od-avatar-ph { width: 56px; height: 56px; border-radius: 50%; background: var(--bilai-od-primary); color: #fff; font-size: 22px; font-weight: 700; display: flex; align-items: center; justify-content: center; flex-shrink: 0; text-transform: uppercase; }
.bilai-od-profile-info { flex: 1; min-width: 0; }
.bilai-od-profile-name { font-size: 14px; font-weight: 700; color: var(--bilai-od-text); margin: 0 0 2px; line-height: 1.3; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; text-transform: capitalize; }
.bilai-od-profile-sub { font-size: 12px; color: var(--bilai-od-muted); margin: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.bilai-od-divider { border: none; border-top: 1px solid var(--bilai-od-border); margin: 0 0 12px; }
.bilai-od-rp-row { display: flex; align-items: center; justify-content: center; gap: 10px; }
.bilai-od-rp-item { display: flex; align-items: center; gap: 5px; font-size: 12px; font-weight: 600; color: var(--bilai-od-text); }
.bilai-od-rp-icon { color: var(--bilai-od-primary); font-size: 13px; }
.bilai-od-rp-sep { width: 24px; height: 24px; border-radius: 50%; background: var(--bilai-od-cream); border: 1px solid var(--bilai-od-border); display: flex; align-items: center; justify-content: center; color: var(--bilai-od-muted); font-size: 10px; flex-shrink: 0; }
.bilai-od-nav { padding: 6px 0 8px; }
.bilai-od-nav-title { font-size: 11px; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; color: var(--bilai-od-muted); padding: 12px 16px 8px; margin: 0; }
.bilai-od-nav-item { display: flex; align-items: center; gap: 10px; padding: 9px 16px; color: var(--bilai-od-text); font-size: 13.5px; font-weight: 500; text-decoration: none; border-left: 3px solid transparent; transition: background 0.12s, color 0.12s; line-height: 1.3; }
.bilai-od-nav-item:hover { background: var(--bilai-od-cream); color: var(--bilai-od-primary); text-decoration: none; }
.bilai-od-nav-item.active { background: var(--bilai-od-cream); color: var(--bilai-od-primary); border-left-color: var(--bilai-od-primary); font-weight: 600; }
.bilai-od-nav-icon { width: 16px; text-align: center; flex-shrink: 0; font-size: 13px; opacity: 0.7; }
.bilai-od-nav-item.active .bilai-od-nav-icon, .bilai-od-nav-item:hover .bilai-od-nav-icon { opacity: 1; }
.bilai-od-nav-badge { margin-left: auto; background: #e53935; color: #fff; font-size: 10px; font-weight: 700; padding: 1px 6px; border-radius: 100px; line-height: 1.5; }
.bilai-od-nav-sep { border: none; border-top: 1px solid var(--bilai-od-border); margin: 4px 0; }
.bilai-od-nav-item--logout { color: #c0392b; }
.bilai-od-nav-item--logout .bilai-od-nav-icon { opacity: 0.8; }
.bilai-od-nav-item--logout:hover { background: #fff5f5; color: #a93226; }

/* ── Order selector ── */
.bilai-od-selector-wrap { position: relative; display: inline-block; margin-bottom: 2px; }
.bilai-od-selector {
    appearance: none; -webkit-appearance: none;
    background: #fff; border: 1px solid var(--bilai-od-border);
    border-radius: 100px; padding: 8px 34px 8px 16px;
    font-size: 13px; font-weight: 600; color: var(--bilai-od-text);
    cursor: pointer; min-width: 160px;
}
.bilai-od-selector:focus { outline: none; border-color: var(--bilai-od-primary); }
.bilai-od-selector-caret { position: absolute; right: 14px; top: 50%; transform: translateY(-50%); pointer-events: none; color: var(--bilai-od-muted); font-size: 12px; }

/* ── Generic card body ── */
.bilai-od-cardbody { padding: 20px 22px 22px; }
.bilai-od-card-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 18px; flex-wrap: wrap; }
.bilai-od-card-title { font-size: 15.5px; font-weight: 700; color: var(--bilai-od-text); margin: 0; }
.bilai-od-card-sub { font-size: 12px; color: var(--bilai-od-muted); font-weight: 500; }

/* ── Buttons ── */
.bilai-od-btn { display: inline-flex; align-items: center; gap: 7px; padding: 8px 16px; border-radius: 100px; font-size: 12.5px; font-weight: 600; text-decoration: none; border: none; cursor: pointer; transition: all 0.13s; }
.bilai-od-btn--primary { background: var(--bilai-od-primary); color: #fff; }
.bilai-od-btn--primary:hover { background: var(--bilai-od-primary-dark); color: #fff; text-decoration: none; }
.bilai-od-btn--soft { background: var(--bilai-od-cream); color: var(--bilai-od-text); border: 1px solid var(--bilai-od-border); }
.bilai-od-btn--soft:hover { border-color: var(--bilai-od-primary); color: var(--bilai-od-primary); text-decoration: none; }
.bilai-od-btn--disabled { background: #f0ece2; color: #b3a893; cursor: not-allowed; pointer-events: none; }

/* ── Stepper ── */
.bilai-od-stepper { display: flex; align-items: flex-start; }
.bilai-od-step { flex: 1; text-align: center; position: relative; min-width: 0; }
.bilai-od-step-dot { width: 34px; height: 34px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 14px; position: relative; z-index: 2; background: #eee; color: #fff; border: 2px solid #eee; }
.bilai-od-step-dot--done { background: #2e9e4f; border-color: #2e9e4f; }
.bilai-od-step-dot--todo { background: #fff; border-color: #ddd; color: #cfcfcf; }
.bilai-od-step-dot--cancel { background: #fff; border-color: #ddd; color: #cfcfcf; }
.bilai-od-step-dot--cancel-active { background: #fff; border-color: #e05252; color: #e05252; }
/* connector line */
.bilai-od-step:not(:first-child)::before { content: ''; position: absolute; top: 16px; left: -50%; width: 100%; height: 2px; background: #ddd; z-index: 1; }
.bilai-od-step--done:not(:first-child)::before { background: #2e9e4f; }
.bilai-od-step-label { display: block; margin-top: 8px; font-size: 12.5px; font-weight: 600; color: var(--bilai-od-text); }
.bilai-od-step--todo .bilai-od-step-label { color: var(--bilai-od-muted); font-weight: 500; }
.bilai-od-step-time { display: block; margin-top: 2px; font-size: 10.5px; color: var(--bilai-od-muted); }

/* ── Ordered product row ── */
.bilai-od-product { display: flex; gap: 14px; padding: 4px 0; }
.bilai-od-product + .bilai-od-product { border-top: 1px solid #f0e8d8; padding-top: 16px; margin-top: 12px; }
.bilai-od-product-img { width: 72px; height: 72px; border-radius: 10px; object-fit: cover; border: 1px solid var(--bilai-od-border); background: #fff; flex-shrink: 0; }
.bilai-od-product-info { flex: 1; min-width: 0; }
.bilai-od-product-name { font-size: 14px; font-weight: 600; color: var(--bilai-od-text); margin: 0 0 4px; line-height: 1.4; }
.bilai-od-product-cat { font-size: 12px; color: var(--bilai-od-muted); margin: 0 0 6px; }
.bilai-od-tags { display: flex; gap: 6px; flex-wrap: wrap; }
.bilai-od-tag { font-size: 10.5px; font-weight: 600; padding: 2px 8px; border-radius: 100px; }
.bilai-od-tag--stock-out { background: #fdecea; color: #c0392b; }
.bilai-od-tag--stock-in { background: #e8f5e9; color: #2e7d32; }
.bilai-od-tag--cat { background: var(--bilai-od-cream); color: var(--bilai-od-primary-dark); border: 1px solid var(--bilai-od-border); }
.bilai-od-product-price { text-align: right; flex-shrink: 0; white-space: nowrap; }
.bilai-od-price-old { font-size: 12px; color: var(--bilai-od-muted); text-decoration: line-through; display: block; }
.bilai-od-price-new { font-size: 15px; font-weight: 700; color: var(--bilai-od-text); }
.bilai-od-qty { font-size: 11.5px; color: var(--bilai-od-muted); display: block; margin-top: 2px; }

/* ── Two-up cards row ── */
.bilai-od-2col { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; }
.bilai-od-info-row { display: flex; gap: 12px; }
.bilai-od-info-icon { width: 34px; height: 34px; border-radius: 8px; background: var(--bilai-od-cream); border: 1px solid var(--bilai-od-border); display: flex; align-items: center; justify-content: center; color: var(--bilai-od-primary); flex-shrink: 0; font-size: 14px; }
.bilai-od-info-name { font-size: 13.5px; font-weight: 600; color: var(--bilai-od-text); margin: 0 0 3px; }
.bilai-od-info-line { font-size: 12.5px; color: var(--bilai-od-muted); margin: 0 0 2px; line-height: 1.5; }

/* ── Summary grid ── */
.bilai-od-sum-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
.bilai-od-sum-item { display: flex; align-items: center; justify-content: space-between; gap: 10px; background: var(--bilai-od-cream); border: 1px solid #f0e2c8; border-radius: var(--bilai-od-radius-sm); padding: 11px 14px; }
.bilai-od-sum-label { font-size: 12.5px; color: var(--bilai-od-muted); }
.bilai-od-sum-value { font-size: 13px; font-weight: 700; color: var(--bilai-od-text); white-space: nowrap; }
.bilai-od-sum-total { grid-column: 1 / -1; display: flex; align-items: center; justify-content: space-between; gap: 10px; border: 1.5px solid var(--bilai-od-primary); background: #fff8ee; border-radius: var(--bilai-od-radius-sm); padding: 13px 16px; margin-top: 2px; }
.bilai-od-sum-total .bilai-od-sum-label { color: var(--bilai-od-primary-dark); font-weight: 700; font-size: 13.5px; }
.bilai-od-sum-total .bilai-od-sum-value { color: var(--bilai-od-primary-dark); font-size: 15px; }

/* ── Payment method ── */
.bilai-od-pay-row { display: flex; align-items: center; justify-content: space-between; gap: 14px; flex-wrap: wrap; }
.bilai-od-pay-box { background: var(--bilai-od-cream); border: 1px solid #f0e2c8; border-radius: var(--bilai-od-radius-sm); padding: 12px 16px; display: flex; align-items: center; gap: 12px; flex: 1; min-width: 220px; }
.bilai-od-pay-label { font-size: 11.5px; color: var(--bilai-od-muted); margin: 0 0 2px; }
.bilai-od-pay-method { font-size: 14px; font-weight: 700; color: var(--bilai-od-text); margin: 0; }
.bilai-od-pay-badge { margin-left: auto; background: var(--bilai-od-primary); color: #fff; font-size: 11px; font-weight: 700; padding: 3px 12px; border-radius: 100px; }

/* ── Responsive ── */
@media (max-width: 1199px) { .bilai-od-layout { grid-template-columns: 228px 1fr; gap: 16px; } }
@media (max-width: 991px)  { .bilai-od-layout { grid-template-columns: 1fr; } .bilai-od-sidebar { position: static; } }
@media (max-width: 767px)  {
    .bilai-od-2col { grid-template-columns: 1fr; }
    .bilai-od-sum-grid { grid-template-columns: 1fr; }
    .bilai-od-cardbody { padding: 16px; }
    .bilai-od-step-label { font-size: 11px; }
    .bilai-od-product-img { width: 60px; height: 60px; }
}

/* BilaiGhor Customer Order Details End */

/* BilaiGhor Order Details Polish Start */

/* 1. Divider under every card title/header */
.bilai-od-card-head {
    border-bottom: 1px solid var(--bilai-od-border);
    padding-bottom: 13px;
    margin-bottom: 18px;
}
.bilai-od-card-head--plain { display: block; }

/* 2. Product image box: cream bg, beige border, inner padding, contain */
.bilai-od-product-imgbox {
    width: 76px;
    height: 76px;
    border-radius: 12px;
    background: var(--bilai-od-cream);
    border: 1px solid var(--bilai-od-border);
    padding: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.bilai-od-product-imgbox .bilai-od-product-img {
    width: 100%;
    height: 100%;
    border: none;
    border-radius: 6px;
    background: transparent;
    object-fit: contain;
}

/* 5. Payment method inner card compact (does not stretch full width) */
.bilai-od-pay-box {
    flex: 0 0 auto;
    min-width: 0;
    width: auto;
    max-width: 340px;
    gap: 16px;
}
.bilai-od-pay-box .bilai-od-pay-badge { margin-left: 8px; }
@media (max-width: 767px) {
    .bilai-od-pay-box { width: 100%; max-width: none; }
    .bilai-od-pay-row .bilai-od-btn { width: 100%; justify-content: center; }
}

/* BilaiGhor Order Details Polish End */
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<div class="bilai-od-page">
    <div class="container">

        
        <nav class="bilai-od-bc" aria-label="breadcrumb">
            <a href="<?php echo e(route('home')); ?>">Home</a>
            <span class="bilai-od-bc-sep">›</span>
            <span>Profile</span>
            <span class="bilai-od-bc-sep">›</span>
            <a href="<?php echo e(route('customer.orders')); ?>">Orders</a>
            <span class="bilai-od-bc-sep">›</span>
            <span class="bilai-od-bc-active">View Order #<?php echo e($order->invoice_id ?? $order->id); ?></span>
        </nav>

        <div class="bilai-od-layout">

            
            <aside class="bilai-od-sidebar">
                
                <div class="bilai-card">
                    <div class="bilai-od-profile-box">
                        <div class="bilai-od-profile-row">
                            <?php if($profileImage): ?>
                                <img src="<?php echo e($profileImage); ?>" onerror="this.style.display='none';this.nextElementSibling.style.display='flex';" class="bilai-od-avatar" alt="<?php echo e($customer->name); ?>">
                                <div class="bilai-od-avatar-ph" style="display:none;"><?php echo e($customerInitial); ?></div>
                            <?php else: ?>
                                <div class="bilai-od-avatar-ph"><?php echo e($customerInitial); ?></div>
                            <?php endif; ?>
                            <div class="bilai-od-profile-info">
                                <p class="bilai-od-profile-name"><?php echo e($customer->name ?? 'Customer'); ?></p>
                                <p class="bilai-od-profile-sub"><?php echo e($customer->phone ?? $customer->email ?? ''); ?></p>
                            </div>
                        </div>
                        <hr class="bilai-od-divider">
                        <div class="bilai-od-rp-row">
                            <span class="bilai-od-rp-item">
                                
                                <span class="bilai-od-rp-icon"><i class="fa fa-star"></i></span><span>0 RP</span>
                            </span>
                            
                            <span class="bilai-od-rp-sep"><i class="fa fa-exchange"></i></span>
                            <span class="bilai-od-rp-item">
                                
                                <span class="bilai-od-rp-icon"><i class="fa fa-money"></i></span><span>৳<?php echo e(number_format($totalOrderAmount, 0)); ?> TK</span>
                            </span>
                        </div>
                    </div>
                </div>

                
                <div class="bilai-card">
                    <nav class="bilai-od-nav">
                        <p class="bilai-od-nav-title">My Account</p>
                        <a href="<?php echo e(route('customer.account')); ?>" class="bilai-od-nav-item <?php echo e(request()->is('customer/account') ? 'active' : ''); ?>">
                            
                            <span class="bilai-od-nav-icon"><i class="fa fa-home"></i></span> Dashboard
                        </a>
                        <a href="<?php echo e(route('customer.orders')); ?>" class="bilai-od-nav-item active">
                            
                            <span class="bilai-od-nav-icon"><i class="fa fa-shopping-bag"></i></span> Orders
                            <?php if($pendingOrdersCount > 0): ?><span class="bilai-od-nav-badge"><?php echo e($pendingOrdersCount); ?></span><?php endif; ?>
                        </a>
                        <a href="<?php echo e(route('customer.profile_edit')); ?>" class="bilai-od-nav-item <?php echo e(request()->is('customer/profile-edit') ? 'active' : ''); ?>">
                            
                            <span class="bilai-od-nav-icon"><i class="fa fa-user"></i></span> Profile
                        </a>
                        <a href="#" class="bilai-od-nav-item">
                            
                            <span class="bilai-od-nav-icon"><i class="fa fa-heart-o"></i></span> Wishlist
                        </a>
                        <a href="#" class="bilai-od-nav-item">
                            
                            <span class="bilai-od-nav-icon"><i class="fa fa-ticket"></i></span> Coupon
                        </a>
                        <a href="#" class="bilai-od-nav-item">
                            
                            <span class="bilai-od-nav-icon"><i class="fa fa-gift"></i></span> Gift Cards
                        </a>
                        <a href="#" class="bilai-od-nav-item">
                            
                            <span class="bilai-od-nav-icon"><i class="fa fa-star-o"></i></span> Reward Points
                        </a>
                        <a href="<?php echo e(route('customer.order_track')); ?>" class="bilai-od-nav-item <?php echo e(request()->is('customer/order-track*') ? 'active' : ''); ?>">
                            
                            <span class="bilai-od-nav-icon"><i class="fa fa-truck"></i></span> Track Order
                        </a>
                        <a href="<?php echo e(route('customer.refunds')); ?>" class="bilai-od-nav-item <?php echo e(request()->is('customer/refunds*') ? 'active' : ''); ?>">
                            
                            <span class="bilai-od-nav-icon"><i class="fa fa-undo"></i></span> Return Request
                        </a>
                        <a href="<?php echo e(route('complaint')); ?>" class="bilai-od-nav-item <?php echo e(request()->is('complaint') ? 'active' : ''); ?>">
                            
                            <span class="bilai-od-nav-icon"><i class="fa fa-headphones"></i></span> Support Ticket
                        </a>
                        <hr class="bilai-od-nav-sep">
                        <a href="<?php echo e(route('customer.logout')); ?>" onclick="event.preventDefault(); document.getElementById('bilai-od-logout-form').submit();" class="bilai-od-nav-item bilai-od-nav-item--logout">
                            
                            <span class="bilai-od-nav-icon"><i class="fa fa-sign-out"></i></span> Logout
                        </a>
                        <form id="bilai-od-logout-form" action="<?php echo e(route('customer.logout')); ?>" method="POST" style="display:none;"><?php echo csrf_field(); ?></form>
                    </nav>
                </div>
            </aside>

            
            <main class="bilai-od-content">

                
                <div class="bilai-od-selector-wrap">
                    <select class="bilai-od-selector" onchange="if(this.value) window.location.href=this.value;">
                        <?php $__currentLoopData = $recentOrders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ro): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e(route('customer.order_details', $ro->id)); ?>" <?php echo e($ro->id == $order->id ? 'selected' : ''); ?>>
                                Order ID: #<?php echo e($ro->invoice_id ?? $ro->id); ?>

                            </option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                    
                    <span class="bilai-od-selector-caret"><i class="fa fa-chevron-down"></i></span>
                </div>

                
                <div class="bilai-card">
                    <div class="bilai-od-cardbody">
                        <div class="bilai-od-card-head">
                            <h3 class="bilai-od-card-title">Order Status</h3>
                            <?php if($trackingId && $courierName): ?>
                                <a href="<?php echo e($order->courier_type === 'steadfast' && $steadfastTrack ? 'https://steadfast.com.bd/t/' . $steadfastTrack : '#'); ?>"
                                   target="_blank" class="bilai-od-btn bilai-od-btn--soft">
                                    
                                    <i class="fa fa-truck"></i> Track with <?php echo e($courierName); ?>

                                </a>
                            <?php else: ?>
                                <span class="bilai-od-btn bilai-od-btn--disabled">
                                    
                                    <i class="fa fa-truck"></i> Tracking N/A
                                </span>
                            <?php endif; ?>
                        </div>

                        <div class="bilai-od-stepper">
                            <?php $__currentLoopData = $steps; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $step): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <?php
                                    $done = !$isCancelled && $currentStep >= $step['lvl'];
                                    $stepClass = $done ? 'bilai-od-step--done' : 'bilai-od-step--todo';
                                    $dotClass  = $done ? 'bilai-od-step-dot--done' : 'bilai-od-step-dot--todo';
                                ?>
                                <div class="bilai-od-step <?php echo e($stepClass); ?>">
                                    <span class="bilai-od-step-dot <?php echo e($dotClass); ?>">
                                        
                                        <i class="fa <?php echo e($done ? 'fa-check' : 'fa-circle-o'); ?>"></i>
                                    </span>
                                    <span class="bilai-od-step-label"><?php echo e($step['label']); ?></span>
                                    <span class="bilai-od-step-time"><?php echo e($done ? $step['time']->format('M d, h:i A') : '--'); ?></span>
                                </div>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                            
                            <div class="bilai-od-step <?php echo e($isCancelled ? '' : 'bilai-od-step--todo'); ?>">
                                <span class="bilai-od-step-dot <?php echo e($isCancelled ? 'bilai-od-step-dot--cancel-active' : 'bilai-od-step-dot--cancel'); ?>">
                                    
                                    <i class="fa <?php echo e($isCancelled ? 'fa-times' : 'fa-ban'); ?>"></i>
                                </span>
                                <span class="bilai-od-step-label">Cancelled</span>
                                <span class="bilai-od-step-time"><?php echo e($isCancelled ? $order->updated_at->format('M d, h:i A') : '--'); ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                
                <div class="bilai-card">
                    <div class="bilai-od-cardbody">
                        <div class="bilai-od-card-head">
                            <h3 class="bilai-od-card-title">Ordered Products</h3>
                            <span class="bilai-od-card-sub"><?php echo e($order->orderdetails->count()); ?> <?php echo e(Str::plural('Item', $order->orderdetails->count())); ?></span>
                        </div>

                        <?php $__currentLoopData = $order->orderdetails; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $detail): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php
                                $prod = $detail->product;
                                $prodImg = null;
                                if ($detail->image && $detail->image->image) { $prodImg = $detail->image->image; }
                                elseif ($prod && $prod->image && $prod->image->image) { $prodImg = $prod->image->image; }
                                $catName = $prod ? optional($prod->category)->name : null;
                                // Subcategory (field: subcategoryName), fallback to category name
                                $subName  = $prod ? optional($prod->subcategory)->subcategoryName : null;
                                $subLine  = $subName ?: $catName;
                                $oldPrice = $prod && $prod->old_price && $prod->old_price > $detail->sale_price ? $prod->old_price : null;
                                $inStock  = $prod ? ($prod->stock > 0) : null;
                            ?>
                            <div class="bilai-od-product">
                                <div class="bilai-od-product-imgbox">
                                    <img src="<?php echo e($prodImg ? asset($prodImg) : asset('public/uploads/default/no-image.png')); ?>"
                                         onerror="this.src='<?php echo e(asset('public/uploads/default/no-image.png')); ?>'"
                                         class="bilai-od-product-img" alt="<?php echo e($detail->product_name); ?>">
                                </div>
                                <div class="bilai-od-product-info">
                                    <p class="bilai-od-product-name"><?php echo e($detail->product_name); ?></p>
                                    <?php if($subLine): ?><p class="bilai-od-product-cat"><?php echo e($subLine); ?></p><?php endif; ?>
                                    <div class="bilai-od-tags">
                                        <?php if(!is_null($inStock)): ?>
                                            <span class="bilai-od-tag <?php echo e($inStock ? 'bilai-od-tag--stock-in' : 'bilai-od-tag--stock-out'); ?>"><?php echo e($inStock ? 'In Stock' : 'Stock Out'); ?></span>
                                        <?php endif; ?>
                                        <?php if($catName): ?><span class="bilai-od-tag bilai-od-tag--cat"><?php echo e($catName); ?></span><?php endif; ?>
                                    </div>
                                </div>
                                <div class="bilai-od-product-price">
                                    <?php if($oldPrice): ?><span class="bilai-od-price-old">৳<?php echo e(number_format($oldPrice, 0)); ?></span><?php endif; ?>
                                    <span class="bilai-od-price-new">৳<?php echo e(number_format($detail->sale_price, 0)); ?></span>
                                    <span class="bilai-od-qty">Qty: <?php echo e($detail->qty); ?></span>
                                </div>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                        <?php $firstProduct = optional($order->orderdetails->first())->product; ?>
                        <?php if($firstProduct && $firstProduct->slug): ?>
                            <div style="margin-top:16px;">
                                
                                <a href="<?php echo e(route('product', $firstProduct->slug)); ?>" class="bilai-od-btn bilai-od-btn--primary">
                                    
                                    <i class="fa fa-shopping-cart"></i> Reorder this product
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                
                <div class="bilai-od-2col">
                    
                    <div class="bilai-card">
                        <div class="bilai-od-cardbody">
                            <div class="bilai-od-card-head bilai-od-card-head--plain"><h3 class="bilai-od-card-title">Shipping Address</h3></div>
                            <div class="bilai-od-info-row">
                                <div class="bilai-od-info-icon">
                                    
                                    <i class="fa fa-map-marker"></i>
                                </div>
                                <div>
                                    <p class="bilai-od-info-name"><?php echo e($order->shipping->name ?? ($customer->name ?? 'N/A')); ?></p>
                                    <p class="bilai-od-info-line"><?php echo e($order->shipping->phone ?? ($customer->phone ?? 'N/A')); ?></p>
                                    <p class="bilai-od-info-line">
                                        <?php echo e($order->shipping->address ?? 'N/A'); ?><?php if(!empty($order->shipping?->area)): ?>, <?php echo e($order->shipping->area); ?><?php endif; ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    
                    <div class="bilai-card">
                        <div class="bilai-od-cardbody">
                            <div class="bilai-od-card-head bilai-od-card-head--plain"><h3 class="bilai-od-card-title">Delivery Partner Info</h3></div>
                            <div class="bilai-od-info-row">
                                <div class="bilai-od-info-icon">
                                    
                                    <i class="fa fa-bicycle"></i>
                                </div>
                                <div>
                                    <p class="bilai-od-info-name"><?php echo e($courierName ?? 'N/A'); ?></p>
                                    <p class="bilai-od-info-line">Tracking: <?php echo e($trackingId ?? 'N/A'); ?></p>
                                    <p class="bilai-od-info-line"><?php echo e($order->courier_sent_at ? 'Sent: ' . $order->courier_sent_at->format('M d, Y') : 'N/A'); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                
                <div class="bilai-card">
                    <div class="bilai-od-cardbody">
                        <div class="bilai-od-card-head bilai-od-card-head--plain"><h3 class="bilai-od-card-title">Order Summary</h3></div>
                        <div class="bilai-od-sum-grid">
                            <div class="bilai-od-sum-item"><span class="bilai-od-sum-label">Order ID</span><span class="bilai-od-sum-value">#<?php echo e($order->invoice_id ?? $order->id); ?></span></div>
                            <div class="bilai-od-sum-item"><span class="bilai-od-sum-label">Shipping Charge</span><span class="bilai-od-sum-value">৳<?php echo e(number_format($order->shipping_charge, 0)); ?></span></div>

                            <div class="bilai-od-sum-item"><span class="bilai-od-sum-label">Order Date</span><span class="bilai-od-sum-value"><?php echo e($order->created_at->format('M j, Y; h:i A')); ?></span></div>
                            <div class="bilai-od-sum-item"><span class="bilai-od-sum-label">Discount</span><span class="bilai-od-sum-value">৳<?php echo e(number_format($order->discount, 0)); ?></span></div>

                            <div class="bilai-od-sum-item"><span class="bilai-od-sum-label">Subtotal</span><span class="bilai-od-sum-value">৳<?php echo e(number_format($subtotal, 0)); ?></span></div>
                            <div class="bilai-od-sum-item"><span class="bilai-od-sum-label">Coupon</span><span class="bilai-od-sum-value"><?php echo e($order->coupon_code ?: '৳0'); ?></span></div>

                            <div class="bilai-od-sum-item"><span class="bilai-od-sum-label">Total Payable</span><span class="bilai-od-sum-value">৳<?php echo e(number_format($order->customer_payable_amount ?? $order->amount, 0)); ?></span></div>
                            <div class="bilai-od-sum-item"><span class="bilai-od-sum-label">Amount Paid</span><span class="bilai-od-sum-value">৳<?php echo e(number_format($paid_amount, 0)); ?></span></div>

                            <div class="bilai-od-sum-total">
                                <span class="bilai-od-sum-label">Grand Total</span>
                                <span class="bilai-od-sum-value">৳<?php echo e(number_format($grand_total, 0)); ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                
                <div class="bilai-card">
                    <div class="bilai-od-cardbody">
                        <div class="bilai-od-card-head bilai-od-card-head--plain"><h3 class="bilai-od-card-title">Payment Method</h3></div>
                        <div class="bilai-od-pay-row">
                            <div class="bilai-od-pay-box">
                                <div>
                                    <p class="bilai-od-pay-label">Paid Via</p>
                                    <p class="bilai-od-pay-method"><?php echo e($paymentReadable); ?></p>
                                </div>
                                <span class="bilai-od-pay-badge"><?php echo e($paymentBadge); ?></span>
                            </div>
                            <a href="<?php echo e(route('customer.invoice', ['id' => $order->id])); ?>" class="bilai-od-btn bilai-od-btn--primary">
                                
                                <i class="fa fa-file-text-o"></i> View Invoice
                            </a>
                        </div>
                    </div>
                </div>

            </main>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('frontEnd.layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\projects\bilaiGhor\resources\views/frontEnd/layouts/customer/order_details.blade.php ENDPATH**/ ?>