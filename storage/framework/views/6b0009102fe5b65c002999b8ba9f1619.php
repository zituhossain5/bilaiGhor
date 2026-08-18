<?php
    $customer = Auth::guard('customer')->user();
?>


<?php $__env->startSection('title', 'Request Refund'); ?>

<?php $__env->startPush('css'); ?>
    <?php echo $__env->make('frontEnd.layouts.customer.partials.figma-account-styles', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <style>
        .bilai-refund-main { color: var(--account-text); }
        .bilai-refund-heading {
            margin: 0; padding-bottom: 30px; border-bottom: 1px solid var(--account-border);
            color: var(--account-muted); font-size: 18px; font-weight: 400; line-height: 27px;
        }
        .bilai-refund-section { padding-top: 25px; }
        .bilai-refund-section + .bilai-refund-section {
            margin-top: 25px; border-top: 1px solid var(--account-border);
        }
        .bilai-refund-section-title {
            margin: 0 0 20px; color: #222; font-size: 20px; font-weight: 600; line-height: 30px;
        }
        .bilai-refund-summary {
            display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 20px; margin-bottom: 24px;
        }
        .bilai-refund-summary-item {
            min-width: 0; padding: 16px 20px; background: var(--account-cream);
            border: 1px solid var(--account-border); border-radius: 16px;
        }
        .bilai-refund-summary-label {
            display: block; margin-bottom: 6px; color: var(--account-muted); font-size: 14px; line-height: 21px;
        }
        .bilai-refund-summary-value {
            display: block; color: var(--account-text); font-size: 16px; font-weight: 600;
            line-height: 24px; overflow-wrap: anywhere;
        }
        .bilai-refund-status {
            display: inline-flex; padding: 3px 10px; background: var(--account-neutral);
            border-radius: 12px; font-size: 13px; font-weight: 500;
        }
        .bilai-refund-table-wrap { overflow-x: auto; border: 1px solid var(--account-border); border-radius: 16px; }
        .bilai-refund-table { width: 100%; min-width: 520px; margin: 0; border-collapse: collapse; }
        .bilai-refund-table th,
        .bilai-refund-table td { padding: 14px 20px; border-bottom: 1px solid var(--account-border); text-align: left; }
        .bilai-refund-table th { background: var(--account-cream); color: var(--account-muted); font-size: 14px; font-weight: 600; }
        .bilai-refund-table td { background: var(--account-warm); color: var(--account-text); font-size: 15px; }
        .bilai-refund-table tr:last-child td { border-bottom: 0; }
        .bilai-refund-form-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 20px; }
        .bilai-refund-field { margin-bottom: 20px; }
        .bilai-refund-field--full { grid-column: 1 / -1; }
        .bilai-refund-label {
            display: block; margin-bottom: 8px; color: var(--account-text); font-size: 16px; font-weight: 500; line-height: 24px;
        }
        .bilai-refund-required { color: #c4552a; }
        .bilai-refund-control {
            display: block; width: 100%; min-height: 54px; padding: 14px 16px;
            color: var(--account-text); background: var(--account-page); border: 1px solid var(--account-border-default);
            border-radius: 12px; font: inherit; font-size: 16px; line-height: 24px; outline: none;
        }
        textarea.bilai-refund-control { min-height: 128px; resize: vertical; }
        .bilai-refund-control:focus { border-color: var(--account-primary); box-shadow: 0 0 0 3px rgba(232, 134, 26, .12); }
        .bilai-refund-control.is-invalid { border-color: #c4552a; }
        .bilai-refund-help { display: block; margin-top: 6px; color: var(--account-muted); font-size: 13px; line-height: 20px; }
        .bilai-refund-error { margin-top: 6px; color: #c4552a; font-size: 13px; line-height: 20px; }
        .bilai-refund-note {
            display: flex; gap: 10px; margin: 4px 0 24px; padding: 16px 18px;
            color: var(--account-text); background: #fbf0eb; border: 1px solid #c4552a; border-radius: 12px;
            font-size: 14px; line-height: 22px;
        }
        .bilai-refund-actions { display: flex; flex-wrap: wrap; gap: 12px; }
        .bilai-refund-button {
            display: inline-flex; align-items: center; justify-content: center; gap: 9px; min-height: 48px;
            padding: 10px 20px; border: 1px solid transparent; border-radius: 12px;
            font-size: 16px; font-weight: 600; line-height: 24px; text-decoration: none; cursor: pointer;
        }
        .bilai-refund-button--primary { color: #222; background: var(--account-primary); }
        .bilai-refund-button--secondary { color: #fbf5e6; background: var(--account-secondary); }
        .bilai-refund-button:hover { color: inherit; filter: brightness(.96); text-decoration: none; }

        @media (max-width: 767px) {
            .bilai-refund-summary { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .bilai-refund-form-grid { grid-template-columns: 1fr; gap: 0; }
            .bilai-refund-field--full { grid-column: auto; }
        }
        @media (max-width: 575px) {
            .bilai-refund-heading { padding-bottom: 20px; }
            .bilai-refund-summary { grid-template-columns: 1fr; gap: 12px; }
            .bilai-refund-actions { flex-direction: column; }
            .bilai-refund-button { width: 100%; }
        }
    </style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<div class="bilai-account-page">
    <div class="bilai-account-container">
        <nav class="bilai-account-breadcrumb" aria-label="Breadcrumb">
            <a href="<?php echo e(route('home')); ?>">Home</a>
            <span class="bilai-account-breadcrumb-separator" aria-hidden="true">&rsaquo;</span>
            <a href="<?php echo e(route('customer.refunds')); ?>">Refund Requests</a>
            <span class="bilai-account-breadcrumb-separator" aria-hidden="true">&rsaquo;</span>
            <span class="bilai-account-breadcrumb-current">Request Refund</span>
        </nav>

        <div class="bilai-account-layout">
            <?php echo $__env->make('frontEnd.layouts.customer.partials.figma-account-sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

            <main class="bilai-account-main bilai-refund-main">
                <h1 class="bilai-refund-heading">Request Refund</h1>

                <section class="bilai-refund-section">
                    <h2 class="bilai-refund-section-title">Order Information</h2>
                    <div class="bilai-refund-summary">
                        <div class="bilai-refund-summary-item">
                            <span class="bilai-refund-summary-label">Order Invoice</span>
                            <span class="bilai-refund-summary-value">#<?php echo e($order->invoice_id); ?></span>
                        </div>
                        <div class="bilai-refund-summary-item">
                            <span class="bilai-refund-summary-label">Order Date</span>
                            <span class="bilai-refund-summary-value"><?php echo e($order->created_at->format('d-m-Y h:i A')); ?></span>
                        </div>
                        <div class="bilai-refund-summary-item">
                            <span class="bilai-refund-summary-label">Order Status</span>
                            <span class="bilai-refund-status"><?php echo e($order->status ? $order->status->name : 'Pending'); ?></span>
                        </div>
                        <div class="bilai-refund-summary-item">
                            <span class="bilai-refund-summary-label">Total Amount</span>
                            <span class="bilai-refund-summary-value">&#2547;<?php echo e(number_format($order->amount, 2)); ?></span>
                        </div>
                        <div class="bilai-refund-summary-item">
                            <span class="bilai-refund-summary-label">Shipping Charge</span>
                            <span class="bilai-refund-summary-value">&#2547;<?php echo e(number_format($order->shipping_charge, 2)); ?></span>
                        </div>
                        <div class="bilai-refund-summary-item">
                            <span class="bilai-refund-summary-label">Grand Total</span>
                            <span class="bilai-refund-summary-value">&#2547;<?php echo e(number_format($order->amount + $order->shipping_charge, 2)); ?></span>
                        </div>
                    </div>

                    <div class="bilai-refund-table-wrap">
                        <table class="bilai-refund-table">
                            <thead>
                                <tr><th>Product</th><th>Qty</th><th>Price</th></tr>
                            </thead>
                            <tbody>
                                <?php $__currentLoopData = $order->orderdetails; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <tr>
                                        <td><?php echo e($item->product_name); ?></td>
                                        <td><?php echo e($item->qty); ?></td>
                                        <td>&#2547;<?php echo e(number_format($item->sale_price * $item->qty, 2)); ?></td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="bilai-refund-section">
                    <h2 class="bilai-refund-section-title">Refund Details</h2>
                    <form action="<?php echo e(route('customer.refunds.store')); ?>" method="POST">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="order_id" value="<?php echo e($order->id); ?>">

                        <div class="bilai-refund-form-grid">
                            <div class="bilai-refund-field">
                                <label for="amount" class="bilai-refund-label">Refund Amount <span class="bilai-refund-required">*</span></label>
                                <input type="number" class="bilai-refund-control <?php $__errorArgs = ['amount'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                       id="amount" name="amount" value="<?php echo e(old('amount', $order->amount)); ?>"
                                       min="1" max="<?php echo e($order->amount); ?>" step="0.01" required>
                                <span class="bilai-refund-help">Maximum: &#2547;<?php echo e(number_format($order->amount, 2)); ?></span>
                                <?php $__errorArgs = ['amount'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="bilai-refund-error"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>

                            <div class="bilai-refund-field">
                                <label for="shipping_charge" class="bilai-refund-label">Shipping Charge Refund</label>
                                <input type="number" class="bilai-refund-control <?php $__errorArgs = ['shipping_charge'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                       id="shipping_charge" name="shipping_charge" value="<?php echo e(old('shipping_charge', $order->shipping_charge)); ?>"
                                       min="0" max="<?php echo e($order->shipping_charge); ?>" step="0.01">
                                <span class="bilai-refund-help">Maximum: &#2547;<?php echo e(number_format($order->shipping_charge, 2)); ?></span>
                                <?php $__errorArgs = ['shipping_charge'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="bilai-refund-error"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>

                            <div class="bilai-refund-field bilai-refund-field--full">
                                <label for="reason" class="bilai-refund-label">Reason for Refund <span class="bilai-refund-required">*</span></label>
                                <textarea class="bilai-refund-control <?php $__errorArgs = ['reason'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" id="reason" name="reason"
                                          required placeholder="Please explain why you want a refund..."><?php echo e(old('reason')); ?></textarea>
                                <?php $__errorArgs = ['reason'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="bilai-refund-error"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>

                            <div class="bilai-refund-field">
                                <label for="refund_method" class="bilai-refund-label">Refund Method <span class="bilai-refund-required">*</span></label>
                                <select class="bilai-refund-control <?php $__errorArgs = ['refund_method'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" id="refund_method" name="refund_method" required>
                                    <option value="">Select Method</option>
                                    <option value="original_payment" <?php echo e(old('refund_method') == 'original_payment' ? 'selected' : ''); ?>>Original Payment Method</option>
                                    <option value="bkash" <?php echo e(old('refund_method') == 'bkash' ? 'selected' : ''); ?>>bKash</option>
                                    <option value="nagad" <?php echo e(old('refund_method') == 'nagad' ? 'selected' : ''); ?>>Nagad</option>
                                    <option value="bank" <?php echo e(old('refund_method') == 'bank' ? 'selected' : ''); ?>>Bank Transfer</option>
                                    <option value="manual" <?php echo e(old('refund_method') == 'manual' ? 'selected' : ''); ?>>Manual/Cash</option>
                                </select>
                                <?php $__errorArgs = ['refund_method'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="bilai-refund-error"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>

                            <div class="bilai-refund-field">
                                <label for="refund_account" class="bilai-refund-label">Account Number/Phone <span class="bilai-refund-required">*</span></label>
                                <input type="text" class="bilai-refund-control <?php $__errorArgs = ['refund_account'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                       id="refund_account" name="refund_account" value="<?php echo e(old('refund_account')); ?>" required
                                       placeholder="Enter bKash/Nagad number or bank account number">
                                <?php $__errorArgs = ['refund_account'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="bilai-refund-error"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>

                            <div class="bilai-refund-field bilai-refund-field--full">
                                <label for="refund_account_name" class="bilai-refund-label">Account Holder Name</label>
                                <input type="text" class="bilai-refund-control <?php $__errorArgs = ['refund_account_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                       id="refund_account_name" name="refund_account_name" value="<?php echo e(old('refund_account_name')); ?>"
                                       placeholder="Enter account holder name (if applicable)">
                                <?php $__errorArgs = ['refund_account_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="bilai-refund-error"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>
                        </div>

                        <div class="bilai-refund-note">
                            <i class="fa fa-info-circle" aria-hidden="true"></i>
                            <span><strong>Note:</strong> Your refund request will be reviewed by our admin team. You will be notified once the refund is processed.</span>
                        </div>

                        <div class="bilai-refund-actions">
                            <button type="submit" class="bilai-refund-button bilai-refund-button--primary">
                                <i class="fa fa-paper-plane" aria-hidden="true"></i> Submit Refund Request
                            </button>
                            <a href="<?php echo e(route('customer.orders')); ?>" class="bilai-refund-button bilai-refund-button--secondary">
                                <i class="fa fa-arrow-left" aria-hidden="true"></i> Back to Orders
                            </a>
                        </div>
                    </form>
                </section>
            </main>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('frontEnd.layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\bilaiGhor\resources\views/frontEnd/layouts/customer/refund_request.blade.php ENDPATH**/ ?>