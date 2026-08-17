
<?php $__env->startSection('title', 'Inventory Dashboard'); ?>

<?php $__env->startSection('css'); ?>
    <?php echo $__env->make('backEnd.inventory._style', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid mb-5">

    <div class="d-sm-flex align-items-center justify-content-between mb-4 mt-2">
        <h1 class="h3 mb-0 text-gray-800 fw-bold">📦 Inventory Dashboard</h1>
        <a href="<?php echo e(route('admin.inventory.adjust')); ?>" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
            <i class="fe-sliders me-1"></i> Adjust Stock
        </a>
    </div>

    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card h-100 py-2 border-start border-4 border-primary">
                <div class="card-body stats-card">
                    <div class="stats-icon bg-soft-primary"><i class="fe-box"></i></div>
                    <div>
                        <div class="stats-label text-primary">Tracked Products</div>
                        <div class="stats-value"><?php echo e(number_format($totals->products)); ?></div>
                        <div class="stats-sub">under inventory control</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card h-100 py-2 border-start border-4 border-success">
                <div class="card-body stats-card">
                    <div class="stats-icon bg-soft-success"><i class="fe-layers"></i></div>
                    <div>
                        <div class="stats-label text-success">On-Hand Units</div>
                        <div class="stats-value"><?php echo e(number_format($totals->on_hand)); ?></div>
                        <div class="stats-sub">physically in stock</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card h-100 py-2 border-start border-4 border-info">
                <div class="card-body stats-card">
                    <div class="stats-icon bg-soft-info"><i class="fe-clock"></i></div>
                    <div>
                        <div class="stats-label text-info">Reserved Units</div>
                        <div class="stats-value"><?php echo e(number_format($totals->reserved)); ?></div>
                        <div class="stats-sub">committed to active orders</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card h-100 py-2 border-start border-4 border-success">
                <div class="card-body stats-card">
                    <div class="stats-icon bg-soft-success"><i class="fe-check-circle"></i></div>
                    <div>
                        <div class="stats-label text-success">Available Units</div>
                        <div class="stats-value"><?php echo e(number_format($totals->available)); ?></div>
                        <div class="stats-sub">on hand − reserved</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card h-100 py-2 border-start border-4 border-warning">
                <div class="card-body stats-card">
                    <div class="stats-icon bg-soft-warning"><i class="fe-alert-triangle"></i></div>
                    <div>
                        <div class="stats-label text-warning">Low Stock</div>
                        <div class="stats-value"><?php echo e(number_format($lowStockCount)); ?></div>
                        <div class="stats-sub"><a href="<?php echo e(route('admin.inventory.low_stock')); ?>">view products →</a></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card h-100 py-2 border-start border-4 border-danger">
                <div class="card-body stats-card">
                    <div class="stats-icon bg-soft-danger"><i class="fe-x-circle"></i></div>
                    <div>
                        <div class="stats-label text-danger">Out of Stock</div>
                        <div class="stats-value"><?php echo e(number_format($outOfStockCount)); ?></div>
                        <div class="stats-sub">available ≤ 0</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-6 col-md-12 mb-4">
            <div class="card h-100 py-2 border-start border-4 border-primary">
                <div class="card-body stats-card">
                    <div class="stats-icon bg-soft-primary"><i class="fe-dollar-sign"></i></div>
                    <div>
                        <div class="stats-label text-primary">Inventory Value</div>
                        <div class="stats-value">৳ <?php echo e(number_format($inventoryValue, 2)); ?></div>
                        <div class="stats-sub">on-hand quantity × latest purchase cost</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-7 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    Recent Stock Movements
                    <a href="<?php echo e(route('admin.inventory.movements')); ?>" class="small">View all</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>Date</th><th>Product</th><th>Type</th><th class="text-end">On Hand Δ</th><th class="text-end">After</th></tr></thead>
                        <tbody>
                            <?php $__empty_1 = true; $__currentLoopData = $recentMovements; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $m): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr>
                                <td class="text-nowrap"><?php echo e($m->created_at->format('M j, H:i')); ?></td>
                                <td><?php echo e(Str::limit(optional($m->product)->name ?? ('#'.$m->product_id), 34)); ?></td>
                                <td><span class="inv-badge <?php echo e($m->on_hand_change > 0 ? 'inv-badge-in' : ($m->on_hand_change < 0 ? 'inv-badge-out' : 'inv-badge-low')); ?>"><?php echo e(str_replace('_', ' ', $m->movement_type)); ?></span></td>
                                <td class="text-end inv-num <?php echo e($m->on_hand_change > 0 ? 'inv-qty-in' : ($m->on_hand_change < 0 ? 'inv-qty-out' : '')); ?>"><?php echo e($m->on_hand_change > 0 ? '+' : ''); ?><?php echo e($m->on_hand_change); ?></td>
                                <td class="text-end inv-num"><?php echo e($m->new_on_hand); ?></td>
                            </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr><td colspan="5" class="text-center text-muted py-4">No movements yet.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-5 mb-4">
            <div class="card">
                <div class="card-header">Recently Restocked</div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>Product</th><th class="text-end">On Hand</th><th class="text-end">Restocked</th></tr></thead>
                        <tbody>
                            <?php $__empty_1 = true; $__currentLoopData = $recentRestocks; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr>
                                <td><?php echo e(Str::limit(optional($r->product)->name ?? ('#'.$r->product_id), 30)); ?></td>
                                <td class="text-end inv-num"><?php echo e($r->on_hand); ?></td>
                                <td class="text-end text-nowrap"><?php echo e($r->last_restocked_at->format('M j, Y')); ?></td>
                            </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr><td colspan="3" class="text-center text-muted py-4">Nothing restocked yet.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('backEnd.layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\bilaiGhor\resources\views\backEnd\inventory\dashboard.blade.php ENDPATH**/ ?>