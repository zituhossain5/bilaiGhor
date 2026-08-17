
<?php $__env->startSection('title','Purchase Invoice'); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid">

    <div class="card">
        <div class="card-header d-flex justify-content-between">
            <h4 class="mb-0">Purchase Invoice #<?php echo e($purchase->invoice_no); ?></h4>
            <a href="javascript:window.print()" class="btn btn-sm btn-primary">Print</a>
        </div>
        <div class="card-body">

            <div class="row mb-3">
                <div class="col-md-6">
                    <h5>Supplier:</h5>
                    <p class="mb-0"><strong><?php echo e(optional($purchase->supplier)->name); ?></strong></p>
                    <p class="mb-0"><?php echo e(optional($purchase->supplier)->phone); ?></p>
                    <p class="mb-0"><?php echo e(optional($purchase->supplier)->address); ?></p>
                </div>
                <div class="col-md-6 text-end">
                    <p class="mb-0"><strong>Date:</strong> <?php echo e($purchase->purchase_date); ?></p>
                    <p class="mb-0"><strong>Invoice:</strong> <?php echo e($purchase->invoice_no); ?></p>
                </div>
            </div>

            <table class="table table-bordered">
                <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Product</th>
                    <th class="text-end">Qty</th>
                    <th class="text-end">Unit Cost</th>
                    <th class="text-end">Line Total</th>
                </tr>
                </thead>
                <tbody>
                <?php $__currentLoopData = $purchase->items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <tr>
                        <td><?php echo e($loop->iteration); ?></td>
                        <td><?php echo e(optional($item->product)->name); ?></td>
                        <td class="text-end"><?php echo e($item->qty); ?></td>
                        <td class="text-end"><?php echo e(number_format($item->unit_cost,2)); ?></td>
                        <td class="text-end"><?php echo e(number_format($item->line_total,2)); ?></td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
            </table>

            <div class="row justify-content-end">
                <div class="col-md-4">
                    <table class="table table-sm">
                        <tr>
                            <th>Subtotal</th>
                            <td class="text-end"><?php echo e(number_format($purchase->subtotal,2)); ?> ৳</td>
                        </tr>
                        <tr>
                            <th>Discount</th>
                            <td class="text-end"><?php echo e(number_format($purchase->discount,2)); ?> ৳</td>
                        </tr>
                        <tr>
                            <th>Shipping</th>
                            <td class="text-end"><?php echo e(number_format($purchase->shipping_cost,2)); ?> ৳</td>
                        </tr>
                        <tr>
                            <th>Grand Total</th>
                            <td class="text-end"><strong><?php echo e(number_format($purchase->grand_total,2)); ?> ৳</strong></td>
                        </tr>
                        <tr>
                            <th>Paid</th>
                            <td class="text-end text-success"><?php echo e(number_format($purchase->paid_amount,2)); ?> ৳</td>
                        </tr>
                        <tr>
                            <th>Due</th>
                            <td class="text-end text-danger"><?php echo e(number_format($purchase->due_amount,2)); ?> ৳</td>
                        </tr>
                    </table>
                </div>
            </div>

        </div>
    </div>

</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('backEnd.layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\bilaiGhor\resources\views\backEnd\purchases\invoice.blade.php ENDPATH**/ ?>