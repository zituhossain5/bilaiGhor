
<?php $__env->startSection('title', 'Low Stock'); ?>

<?php $__env->startSection('css'); ?>
    <?php echo $__env->make('backEnd.inventory._style', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid mb-5">

    <div class="d-sm-flex align-items-center justify-content-between mb-4 mt-2">
        <h1 class="h3 mb-0 text-gray-800 fw-bold">⚠️ Low Stock Products</h1>
        <a href="<?php echo e(route('purchases.index')); ?>" class="d-none d-sm-inline-block btn btn-sm btn-success shadow-sm">
            <i class="fe-shopping-bag me-1"></i> Create Purchase
        </a>
    </div>

    <div class="card">
        <div class="card-header">Products where Available ≤ Low Stock Threshold</div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th class="text-end">Available</th>
                        <th class="text-end">Threshold</th>
                        <th>Status</th>
                        <th>Supplier</th>
                        <th>Last Purchase</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $rows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <?php
                        $p = $row->product;
                        $lp = $lastPurchases->get($row->product_id);
                    ?>
                    <tr>
                        <td class="fw-semibold"><?php echo e(Str::limit($p->name ?? ('#'.$row->product_id), 46)); ?></td>
                        <td class="text-end inv-num fw-bold"><?php echo e($row->available); ?></td>
                        <td class="text-end inv-num"><?php echo e($row->low_stock_threshold); ?></td>
                        <td>
                            <?php if($row->available <= 0): ?>
                                <span class="inv-badge inv-badge-out">Out of Stock</span>
                            <?php else: ?>
                                <span class="inv-badge inv-badge-low">Low Stock</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo e(optional(optional($lp?->purchase)->supplier)->name ?? '—'); ?></td>
                        <td class="text-nowrap"><?php echo e($lp?->created_at?->format('M j, Y') ?? '—'); ?></td>
                        <td class="text-center text-nowrap">
                            <a href="<?php echo e(route('purchases.index')); ?>" class="btn btn-sm btn-success" title="Create purchase"><i class="fe-shopping-bag"></i></a>
                            <a href="<?php echo e(route('admin.inventory.adjust', ['product_id' => $row->product_id])); ?>" class="btn btn-sm btn-light" title="Adjust"><i class="fe-sliders"></i></a>
                            <?php if($p && $p->slug): ?>
                            <a href="<?php echo e(route('product', $p->slug)); ?>" target="_blank" class="btn btn-sm btn-light" title="View product"><i class="fe-external-link"></i></a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">🎉 No products are low on stock.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if($rows->hasPages()): ?>
        <div class="card-body py-3"><?php echo e($rows->links('pagination::bootstrap-4')); ?></div>
        <?php endif; ?>
    </div>

</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('backEnd.layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\bilaiGhor\resources\views\backEnd\inventory\low_stock.blade.php ENDPATH**/ ?>