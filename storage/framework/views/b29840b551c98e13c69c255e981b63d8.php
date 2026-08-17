
<?php $__env->startSection('title', 'Stock Movements'); ?>

<?php $__env->startSection('css'); ?>
    <?php echo $__env->make('backEnd.inventory._style', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid mb-5">

    <div class="d-sm-flex align-items-center justify-content-between mb-4 mt-2">
        <h1 class="h3 mb-0 text-gray-800 fw-bold">📜 Stock Movements</h1>
        <span class="text-muted small">Audit trail — records cannot be edited or deleted from here</span>
    </div>

    <div class="card mb-4">
        <div class="card-body py-3">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Product</label>
                    <select name="product_id" class="form-select">
                        <option value="">All products</option>
                        <?php $__currentLoopData = $products; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($p->id); ?>" <?php if(request('product_id') == $p->id): echo 'selected'; endif; ?>><?php echo e(Str::limit($p->name, 48)); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Movement Type</label>
                    <select name="movement_type" class="form-select">
                        <option value="">All</option>
                        <?php $__currentLoopData = $types; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $t): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($t); ?>" <?php if(request('movement_type') === $t): echo 'selected'; endif; ?>><?php echo e(ucwords(str_replace('_', ' ', $t))); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">From</label>
                    <input type="date" name="from_date" value="<?php echo e(request('from_date')); ?>" class="form-control">
                </div>
                <div class="col-md-2">
                    <label class="form-label">To</label>
                    <input type="date" name="to_date" value="<?php echo e(request('to_date')); ?>" class="form-control">
                </div>
                <div class="col-md-1">
                    <label class="form-label">Order#</label>
                    <input type="number" name="order_id" value="<?php echo e(request('order_id')); ?>" class="form-control">
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button class="btn btn-primary w-100" type="submit"><i class="fe-filter me-1"></i>Filter</button>
                    <a href="<?php echo e(route('admin.inventory.movements')); ?>" class="btn btn-light" title="Reset"><i class="fe-x"></i></a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Product</th>
                        <th>Type</th>
                        <th>Reference</th>
                        <th class="text-end">Stock In</th>
                        <th class="text-end">Stock Out</th>
                        <th class="text-end">On Hand After</th>
                        <th class="text-end">Reserved After</th>
                        <th class="text-end">Available After</th>
                        <th>Admin/User</th>
                        <th>Reason</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $movements; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $m): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td class="text-nowrap"><?php echo e($m->created_at->format('M j, Y H:i')); ?></td>
                        <td><?php echo e(Str::limit(optional($m->product)->name ?? ('#'.$m->product_id), 32)); ?></td>
                        <td><span class="inv-badge <?php echo e($m->on_hand_change > 0 ? 'inv-badge-in' : ($m->on_hand_change < 0 ? 'inv-badge-out' : 'inv-badge-low')); ?>"><?php echo e(ucwords(str_replace('_', ' ', $m->movement_type))); ?></span></td>
                        <td class="text-nowrap">
                            <?php if($m->order_id): ?>
                                Order #<?php echo e($m->order_id); ?>

                            <?php elseif($m->purchase_id): ?>
                                <a href="<?php echo e(route('purchases.invoice', $m->purchase_id)); ?>">Purchase #<?php echo e($m->purchase_id); ?></a>
                            <?php else: ?>
                                <?php echo e($m->reference_type ? ucfirst($m->reference_type) : '—'); ?>

                            <?php endif; ?>
                        </td>
                        <td class="text-end inv-num inv-qty-in"><?php echo e($m->on_hand_change > 0 ? '+' . $m->on_hand_change : ''); ?></td>
                        <td class="text-end inv-num inv-qty-out"><?php echo e($m->on_hand_change < 0 ? $m->on_hand_change : ''); ?></td>
                        <td class="text-end inv-num"><?php echo e($m->new_on_hand ?? '—'); ?></td>
                        <td class="text-end inv-num"><?php echo e($m->new_reserved ?? '—'); ?></td>
                        <td class="text-end inv-num"><?php echo e(is_null($m->new_on_hand) || is_null($m->new_reserved) ? '—' : ($m->new_on_hand - $m->new_reserved)); ?></td>
                        <td><?php echo e(optional($m->creator)->name ?? ($m->created_by ? 'User #'.$m->created_by : 'System')); ?></td>
                        <td class="small"><?php echo e(Str::limit($m->reason, 44) ?: '—'); ?><?php if($m->notes): ?> <span class="text-muted" title="<?php echo e($m->notes); ?>">(note)</span><?php endif; ?></td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr><td colspan="11" class="text-center text-muted py-4">No movements found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if($movements->hasPages()): ?>
        <div class="card-body py-3"><?php echo e($movements->links('pagination::bootstrap-4')); ?></div>
        <?php endif; ?>
    </div>

</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('backEnd.layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\bilaiGhor\resources\views\backEnd\inventory\movements.blade.php ENDPATH**/ ?>