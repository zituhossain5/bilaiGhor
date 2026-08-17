
<?php $__env->startSection('title','Order Note'); ?>
<?php $__env->startSection('content'); ?>
<section class="customer-section">
    <div class="container">
        <div class="row">
            <div class="col-sm-3">
                <div class="customer-sidebar">
                    <?php echo $__env->make('frontEnd.layouts.customer.sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                </div>
            </div>
            <div class="col-sm-9">
                <div class="customer-content">
                    <h5 class="account-title">Order Note [Invoice #<?php echo e($order->invoice_id); ?>]</h5>
                     <a href="<?php echo e(route('customer.orders')); ?>"><strong><i class="fa-solid fa-arrow-left"></i> Back To Order</strong></a>
                    <div class="card mt-2">
                        <div class="card-body">
                            <?php echo $order->admin_note; ?>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('frontEnd.layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\bilaiGhor\resources\views\frontEnd\layouts\customer\order_note.blade.php ENDPATH**/ ?>