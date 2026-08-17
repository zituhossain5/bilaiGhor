
<?php $__env->startSection('title', 'ইতিহাস'); ?>
<?php $__env->startSection('header_title', 'ডেলিভারি ইতিহাস'); ?>

<?php $__env->startSection('page_subtitle'); ?>
    <p>সম্পন্ন করা ডেলিভারির রেকর্ড।</p>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="d-link-top">
    <a href="<?php echo e(route('delivery.orders.index')); ?>">← অসম্পূর্ণ অর্ডার</a>
</div>

<?php $__empty_1 = true; $__currentLoopData = $orders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $o): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
    <?php
        $collectAmount = !empty($o->customer_payable_amount)
            ? (float) $o->customer_payable_amount
            : (float) $o->amount;
    ?>
    <div class="d-card d-card--flush mb-3">
        <div class="d-list-link" style="cursor:default;padding:16px;">
            <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;">
                <div class="d-inv">#<?php echo e($o->invoice_id); ?></div>
                <strong class="d-history-amount">৳<?php echo e(number_format($collectAmount, 2)); ?></strong>
            </div>
            <small class="d-muted-small" style="display:block;margin-top:6px;"><?php echo e($o->rider_delivered_at ? $o->rider_delivered_at->format('d M Y, h:i A') : ''); ?></small>
        </div>
    </div>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
    <div class="d-card"><p class="d-empty" style="padding:0;margin:0;">এখনো কিছু নেই</p></div>
<?php endif; ?>

<div class="d-pagination"><?php echo e($orders->links()); ?></div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('css'); ?>
<style>
    .d-history-amount {
        color: #047857;
        font-size: 0.98rem;
        white-space: nowrap;
    }
</style>
<?php $__env->stopPush(); ?>


<?php echo $__env->make('delivery.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\bilaiGhor\resources\views\delivery\orders\history.blade.php ENDPATH**/ ?>