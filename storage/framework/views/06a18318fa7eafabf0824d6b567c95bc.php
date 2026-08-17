<?php
    // ── Payment calc (same logic as order-details/orders pages) ──
    $payment        = \App\Models\Payment::where('order_id', $order->id)->orderBy('id', 'desc')->first();
    $gateway_status = $payment ? strtolower(trim($payment->payment_status)) : '';
    $payment_method = $payment ? strtolower(trim($payment->payment_method)) : strtolower(trim($order->payment_gateway ?? ''));
    $admin_status   = strtolower(trim($order->payment_status ?? ''));
    $order_slug     = strtolower(trim($order->status->slug ?? $order->status->name ?? ''));

    $grand_total = (float) $order->amount;
    $paid_amount = 0;
    if ($payment && !in_array($gateway_status, ['failed', 'cancel', 'cancelled', 'rejected'])) {
        $paid_amount = (float) $payment->amount;
    }
    $is_cod             = in_array($payment_method, ['cod', 'cash', 'cash_on_delivery', 'hand cash']);
    $is_order_completed = in_array($order_slug, ['completed', 'delivered']) || in_array($admin_status, ['completed', 'delivered']);
    // COD collects cash on delivery: until the order completes, a pending COD payment
    // row is a payable snapshot (incl. legacy advance-era ৳2 rows), not received money.
    if ($is_cod && !$is_order_completed && ($gateway_status === 'pending' || $paid_amount >= $grand_total)) { $paid_amount = 0; }
    if ($is_order_completed) { $paid_amount = $grand_total; }
    elseif (($paid_amount == 0 || !$payment) && in_array($admin_status, ['paid', 'success', 'approved'])) { $paid_amount = $grand_total; }
    $due_amount = max(0, $grand_total - $paid_amount);
    $is_failed  = $paid_amount == 0 && in_array($gateway_status, ['failed', 'cancel', 'cancelled']);

    $paymentReadable = $is_cod ? 'Cash on Delivery' : ($payment_method ? ucwords(str_replace('_', ' ', $payment_method)) : 'N/A');

    if ($grand_total > 0 && $paid_amount >= $grand_total) { $payStatusLabel = 'Paid';           $payStatusClass = 'bilai-inv-status--paid'; }
    elseif ($is_failed)                                   { $payStatusLabel = 'Payment Failed'; $payStatusClass = 'bilai-inv-status--failed'; }
    elseif ($paid_amount > 0)                             { $payStatusLabel = 'Partially Paid'; $payStatusClass = 'bilai-inv-status--partial'; }
    else                                                  { $payStatusLabel = 'Payment Due';    $payStatusClass = ''; }

    // ── Summary (amount already includes shipping minus discount & reward deduction) ──
    $discount       = (float) ($order->discount ?? 0);
    $rewardDiscount = (float) ($order->reward_discount_amount ?? 0);
    $shippingCharge = (float) ($order->shipping_charge ?? 0);
    $subtotal       = ($grand_total + $discount + $rewardDiscount) - $shippingCharge;

    // ── Customer / shipping snapshot (order-time data, not live profile) ──
    $ship          = $order->shipping;
    $custName      = $ship->name ?? ($order->customer->name ?? 'N/A');
    $custPhone     = $ship->phone ?? ($order->customer->phone ?? null);
    $custEmail     = $order->customer->email ?? null;
    $addressLine   = implode(', ', array_filter([$ship->address ?? null, $ship->area ?? null]));
    $locationLine  = implode(', ', array_filter([optional($ship?->zone)->name, optional($ship?->district)->name]));
    if (!empty($ship?->post_code)) { $locationLine = trim($locationLine . ' ' . $ship->post_code); }

    $money = fn ($v) => '৳' . number_format((float) $v, 0);
?>


<?php $__env->startSection('title', 'Invoice #' . ($order->invoice_id ?? $order->id)); ?>

<?php $__env->startPush('css'); ?>
<style>
/* BilaiGhor Customer Invoice Start */
.bilai-inv-page {
    --bilai-inv-primary: var(--bilai-primary, #F28C00);
    --bilai-inv-brown:   #241307;
    --bilai-inv-cream:   #FBF5EA;
    --bilai-inv-card:    #FFFDF8;
    --bilai-inv-border:  #E8CDA5;
    --bilai-inv-border-soft: #F0DDBE;
    --bilai-inv-text:    #2B1A10;
    --bilai-inv-muted:   #77706A;
    background: var(--bilai-inv-cream);
    min-height: 70vh;
    padding: 34px 0 60px;
}
.bilai-inv-wrap { max-width: 1080px; margin: 0 auto; }

/* ── Action buttons ── */
.bilai-invoice-actions { display: flex; justify-content: center; gap: 14px; margin-bottom: 30px; flex-wrap: wrap; }
.bilai-inv-btn { display: inline-flex; align-items: center; gap: 8px; padding: 11px 22px; border-radius: 8px; font-size: 14px; font-weight: 600; text-decoration: none; cursor: pointer; transition: all 0.13s; line-height: 1.2; }
.bilai-inv-btn--primary { background: var(--bilai-inv-primary); color: #fff; border: 1px solid var(--bilai-inv-primary); }
.bilai-inv-btn--primary:hover { background: #d97e00; color: #fff; text-decoration: none; }
.bilai-inv-btn--ghost { background: #F6EEDD; color: var(--bilai-inv-text); border: 1px solid var(--bilai-inv-border); }
.bilai-inv-btn--ghost:hover { background: #efe4cc; color: var(--bilai-inv-text); text-decoration: none; }

/* ── Invoice card ── */
.bilai-invoice-card { background: var(--bilai-inv-card); border: 1px solid var(--bilai-inv-border); border-radius: 16px; padding: 34px 38px 30px; }
.bilai-inv-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; flex-wrap: wrap; }
.bilai-inv-logo img { height: 62px; width: auto; }
.bilai-inv-logo-text { font-size: 24px; font-weight: 800; color: var(--bilai-inv-brown); }
.bilai-inv-head-right { text-align: right; }
.bilai-inv-label { font-size: 13px; color: var(--bilai-inv-muted); margin: 0 0 6px; }
.bilai-inv-orderid { font-size: 20px; font-weight: 700; color: var(--bilai-inv-primary); margin: 0; }
.bilai-inv-divider { border: none; border-top: 1px solid var(--bilai-inv-border-soft); margin: 22px 0 26px; }

/* ── Info cards ── */
.bilai-inv-info-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 22px; margin-bottom: 30px; }
.bilai-inv-info-card { border: 1px solid #F2CE96; border-radius: 12px; padding: 18px 20px 20px; background: transparent; }
.bilai-inv-info-title { font-size: 15.5px; font-weight: 600; color: var(--bilai-inv-primary); margin: 0 0 12px; padding-bottom: 12px; border-bottom: 1px solid var(--bilai-inv-border-soft); }
.bilai-inv-info-name { font-size: 17px; font-weight: 700; color: var(--bilai-inv-text); margin: 4px 0 14px; }
.bilai-inv-info-line { font-size: 13.5px; color: var(--bilai-inv-text); margin: 0 0 9px; line-height: 1.55; word-break: break-word; }
.bilai-inv-info-line:last-child { margin-bottom: 0; }
.bilai-inv-pay-method { color: var(--bilai-inv-primary); font-weight: 600; }
.bilai-inv-status--paid    { color: #1e8e3e; font-weight: 600; }
.bilai-inv-status--partial { color: #0d6efd; font-weight: 600; }
.bilai-inv-status--failed  { color: #d93025; font-weight: 600; }

/* ── Product table ── */
.bilai-inv-scroll { overflow-x: auto; }
.bilai-inv-table { width: 100%; min-width: 560px; border-collapse: collapse; }
.bilai-inv-table thead tr { background: var(--bilai-inv-brown); }
.bilai-inv-table thead th { color: #fff; font-size: 13.5px; font-weight: 600; padding: 14px 18px; text-align: left; white-space: nowrap; }
.bilai-inv-table thead th:first-child { border-radius: 8px 0 0 8px; }
.bilai-inv-table thead th:last-child  { border-radius: 0 8px 8px 0; }
.bilai-inv-table thead th:not(:first-child), .bilai-inv-table tbody td:not(:first-child) { text-align: right; }
.bilai-inv-table tbody td { padding: 16px 18px; border-bottom: 1px solid var(--bilai-inv-border-soft); vertical-align: top; font-size: 14px; color: var(--bilai-inv-text); }
.bilai-inv-prod-name { font-size: 14px; font-weight: 600; color: var(--bilai-inv-text); margin: 0 0 6px; line-height: 1.5; }
.bilai-inv-prod-meta { font-size: 12.5px; color: var(--bilai-inv-muted); margin: 0; }

/* ── Summary ── */
.bilai-inv-bottom { display: flex; justify-content: flex-end; margin-top: 6px; }
.bilai-inv-summary { width: 400px; max-width: 100%; }
.bilai-inv-sum-row { display: flex; justify-content: space-between; align-items: center; padding: 9px 18px; font-size: 13.5px; color: var(--bilai-inv-text); }
.bilai-inv-sum-row span:last-child { font-weight: 600; white-space: nowrap; }
.bilai-inv-sum-total { display: flex; justify-content: space-between; align-items: center; padding: 15px 18px; margin-top: 6px; border-top: 1px solid var(--bilai-inv-border); font-size: 17px; font-weight: 700; color: var(--bilai-inv-text); }
.bilai-inv-sum-total span:last-child { color: var(--bilai-inv-primary); }

/* ── Paid / Due card ── */
.bilai-inv-paycard { background: var(--bilai-inv-brown); border-radius: 12px; padding: 16px 20px; margin-top: 12px; }
.bilai-inv-paycard-row { display: flex; justify-content: space-between; align-items: center; padding: 6px 0; font-size: 14.5px; font-weight: 700; }
.bilai-inv-paycard-row span:last-child { color: #fff; }
.bilai-inv-paycard-paid { color: #6fce6f; }
.bilai-inv-paycard-due  { color: #ff5c4d; }

/* ── Thank you ── */
.bilai-inv-thanks { text-align: center; font-size: 13px; color: var(--bilai-inv-muted); margin: 34px 0 4px; }

/* ── Responsive ── */
@media (max-width: 991px) {
    .bilai-inv-info-grid { grid-template-columns: 1fr; gap: 14px; }
    .bilai-invoice-card { padding: 22px 18px; }
}
@media (max-width: 575px) {
    .bilai-inv-head { flex-direction: column; }
    .bilai-inv-head-right { text-align: left; }
    .bilai-inv-summary { width: 100%; }
}
/* BilaiGhor Customer Invoice End */

/* BilaiGhor Invoice Print Start */
@media print {
    @page { size: a4; margin: 10mm; }
    body * { visibility: hidden; }
    .bilai-inv-page { padding: 0; background: #fff; }
    .bilai-invoice-card, .bilai-invoice-card * { visibility: visible; }
    .bilai-invoice-card { position: absolute; left: 0; top: 0; width: 100%; border: none; padding: 0; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .bilai-invoice-actions, header, footer, nav { display: none !important; }
    .bilai-inv-scroll { overflow-x: visible; }
    .bilai-inv-table { min-width: 0; }
    .bilai-inv-table tbody tr, .bilai-inv-info-card, .bilai-inv-paycard { page-break-inside: avoid; }
}
/* BilaiGhor Invoice Print End */
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<section class="bilai-inv-page">
    <div class="container">
        <div class="bilai-inv-wrap">

            
            <div class="bilai-invoice-actions">
                <a href="<?php echo e(route('customer.order_details', $order->id)); ?>" class="bilai-inv-btn bilai-inv-btn--primary">
                    <i class="fa fa-home"></i> Back to Order
                </a>
                <button type="button" onclick="window.print()" class="bilai-inv-btn bilai-inv-btn--ghost">
                    <i class="fa fa-print"></i> Print Invoice
                </button>
            </div>

            
            <div class="bilai-invoice-card">

                <div class="bilai-inv-head">
                    <div class="bilai-inv-logo">
                        <?php if(!empty($generalsetting->dark_logo)): ?>
                            <img src="<?php echo e(asset($generalsetting->dark_logo)); ?>" alt="<?php echo e($generalsetting->name); ?>">
                        <?php else: ?>
                            <span class="bilai-inv-logo-text"><?php echo e($generalsetting->name); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="bilai-inv-head-right">
                        <p class="bilai-inv-label">Invoice</p>
                        <p class="bilai-inv-orderid">Order ID: #<?php echo e($order->invoice_id ?? $order->id); ?></p>
                    </div>
                </div>

                <hr class="bilai-inv-divider">

                
                <div class="bilai-inv-info-grid">

                    <div class="bilai-inv-info-card">
                        <h3 class="bilai-inv-info-title">Bill From</h3>
                        <p class="bilai-inv-info-name"><?php echo e($generalsetting->name); ?></p>
                        <?php if(!empty($contact->address)): ?>
                            <p class="bilai-inv-info-line"><?php echo e($contact->address); ?></p>
                        <?php endif; ?>
                        <?php if(!empty($contact->email)): ?>
                            <p class="bilai-inv-info-line">Email: <?php echo e($contact->email); ?></p>
                        <?php endif; ?>
                        <?php if(!empty($contact->phone) || !empty($contact->whatsapp)): ?>
                            <p class="bilai-inv-info-line">Mobile/WhatsApp: <?php echo e($contact->phone ?: $contact->whatsapp); ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="bilai-inv-info-card">
                        <h3 class="bilai-inv-info-title">Customer Details</h3>
                        <p class="bilai-inv-info-name"><?php echo e($custName); ?></p>
                        <?php if($addressLine !== ''): ?>
                            <p class="bilai-inv-info-line"><?php echo e($addressLine); ?></p>
                        <?php endif; ?>
                        <?php if($locationLine !== ''): ?>
                            <p class="bilai-inv-info-line"><?php echo e($locationLine); ?></p>
                        <?php endif; ?>
                        <?php if(!empty($custEmail)): ?>
                            <p class="bilai-inv-info-line">Email: <?php echo e($custEmail); ?></p>
                        <?php endif; ?>
                        <?php if(!empty($custPhone)): ?>
                            <p class="bilai-inv-info-line">Mobile: <?php echo e($custPhone); ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="bilai-inv-info-card">
                        <h3 class="bilai-inv-info-title">Payment Info</h3>
                        <p class="bilai-inv-info-line">Date: <?php echo e($order->created_at->format('j F, Y')); ?></p>
                        <p class="bilai-inv-info-line">Payment: <span class="bilai-inv-pay-method"><?php echo e($paymentReadable); ?></span></p>
                        <p class="bilai-inv-info-line">Status: <span class="<?php echo e($payStatusClass); ?>"><?php echo e($payStatusLabel); ?></span></p>
                    </div>

                </div>

                
                <div class="bilai-inv-scroll">
                    <table class="bilai-inv-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>QTY.</th>
                                <th>Unit Price</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__currentLoopData = $order->orderdetails; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <?php
                                    $sizeDisplay = null;
                                    $colorDisplay = null;
                                    if ($value->size) {
                                        $sizeDisplay = $value->size->sizeName ?? $value->size->size_name ?? $value->size->name ?? null;
                                    } elseif ($value->product_size) {
                                        $s = \App\Models\Size::find($value->product_size);
                                        $sizeDisplay = $s ? ($s->sizeName ?? $s->size_name ?? null) : null;
                                    }
                                    if ($value->color) {
                                        $colorDisplay = $value->color->getDisplayName() ?? $value->color->colorName ?? $value->color->name ?? null;
                                    } elseif ($value->product_color) {
                                        $c = \App\Models\Color::find($value->product_color);
                                        $colorDisplay = $c ? ($c->getDisplayName() ?? $c->colorName ?? null) : null;
                                    }
                                    // In this store size values are mostly weights/volumes (1.5kg, 5 Litre) —
                                    // label them "Weight" when they look like one, else fall back to "Size".
                                    $sizeLabel = ($sizeDisplay && preg_match('/\d\s*(kg|g|gm|gram|ml|l|ltr|litre|liter|litter|lb|oz)\b/i', $sizeDisplay)) ? 'Weight' : 'Size';
                                    $metaParts = array_filter([
                                        $sizeDisplay ? "$sizeLabel: $sizeDisplay" : null,
                                        $colorDisplay ? "Color: $colorDisplay" : null,
                                    ]);
                                ?>
                                <tr>
                                    <td>
                                        <p class="bilai-inv-prod-name"><?php echo e($value->product_name); ?></p>
                                        <?php if($metaParts): ?>
                                            <p class="bilai-inv-prod-meta"><?php echo e(implode(' · ', $metaParts)); ?></p>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo e($value->qty); ?></td>
                                    <td><?php echo e($money($value->sale_price)); ?></td>
                                    <td><?php echo e($money($value->sale_price * $value->qty)); ?></td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tbody>
                    </table>
                </div>

                
                <div class="bilai-inv-bottom">
                    <div class="bilai-inv-summary">
                        <div class="bilai-inv-sum-row"><span>Subtotal</span><span><?php echo e($money($subtotal)); ?></span></div>
                        <div class="bilai-inv-sum-row"><span>Delivery Charge</span><span><?php echo e($money($shippingCharge)); ?></span></div>
                        <div class="bilai-inv-sum-row"><span>Discount<?php echo e(!empty($order->coupon_code) ? ' ('.$order->coupon_code.')' : ''); ?></span><span>-<?php echo e($money($discount)); ?></span></div>
                        <div class="bilai-inv-sum-row"><span>Cash from Reward Points</span><span>-<?php echo e($money($rewardDiscount)); ?></span></div>
                        <div class="bilai-inv-sum-total"><span>Grand Total</span><span><?php echo e($money($grand_total)); ?></span></div>

                        <div class="bilai-inv-paycard">
                            <div class="bilai-inv-paycard-row"><span class="bilai-inv-paycard-paid">Paid Amount</span><span><?php echo e($money($paid_amount)); ?></span></div>
                            <div class="bilai-inv-paycard-row"><span class="bilai-inv-paycard-due">Due Amount</span><span><?php echo e($money($due_amount)); ?></span></div>
                        </div>
                    </div>
                </div>

                <p class="bilai-inv-thanks">Thank you for shopping at <?php echo e($generalsetting->name); ?>!</p>

            </div>

        </div>
    </div>
</section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('frontEnd.layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\bilaiGhor\resources\views\frontEnd\layouts\customer\invoice.blade.php ENDPATH**/ ?>