
<?php $__env->startSection('title', 'Stock Overview'); ?>

<?php $__env->startSection('css'); ?>
    <?php echo $__env->make('backEnd.inventory._style', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid mb-5">

    <div class="d-sm-flex align-items-center justify-content-between mb-4 mt-2">
        <h1 class="h3 mb-0 text-gray-800 fw-bold">📊 Stock Overview</h1>
        <a href="<?php echo e(route('admin.inventory.adjust')); ?>" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
            <i class="fe-sliders me-1"></i> Adjust Stock
        </a>
    </div>

    <div class="card mb-4">
        <div class="card-body py-3">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Search Product</label>
                    <input type="text" name="keyword" value="<?php echo e(request('keyword')); ?>" class="form-control" placeholder="Product name...">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Category</label>
                    <select name="category_id" class="form-select">
                        <option value="">All</option>
                        <?php $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($c->id); ?>" <?php if(request('category_id') == $c->id): echo 'selected'; endif; ?>><?php echo e($c->name); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Brand</label>
                    <select name="brand_id" class="form-select">
                        <option value="">All</option>
                        <?php $__currentLoopData = $brands; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $b): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($b->id); ?>" <?php if(request('brand_id') == $b->id): echo 'selected'; endif; ?>><?php echo e($b->name); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Stock Status</label>
                    <select name="status" class="form-select">
                        <option value="">All</option>
                        <option value="in"  <?php if(request('status') === 'in'): echo 'selected'; endif; ?>>In Stock</option>
                        <option value="low" <?php if(request('status') === 'low'): echo 'selected'; endif; ?>>Low Stock</option>
                        <option value="out" <?php if(request('status') === 'out'): echo 'selected'; endif; ?>>Out of Stock</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button class="btn btn-primary w-100" type="submit"><i class="fe-filter me-1"></i>Filter</button>
                    <a href="<?php echo e(route('admin.inventory.stock')); ?>" class="btn btn-light" title="Reset"><i class="fe-x"></i></a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Weight/Variant</th>
                        <th class="text-end">On Hand</th>
                        <th class="text-end">Reserved</th>
                        <th class="text-end">Available</th>
                        <th class="text-center">Low Stock Level</th>
                        <th>Status</th>
                        <th class="text-end">Cost</th>
                        <th class="text-end">Value</th>
                        <th>Last Restocked</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $stocks; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <?php
                        $p = $row->product;
                        $available = $row->available;
                        $cost = (float) ($p->purchase_price ?? 0);
                    ?>
                    <tr>
                        <td>
                            <div class="fw-semibold"><?php echo e(Str::limit($p->name ?? ('#'.$row->product_id), 42)); ?></div>
                            <div class="small text-muted">
                                <?php echo e(optional($p->category)->name); ?>

                                <?php if(optional($p->brand)->name): ?> · <?php echo e($p->brand->name); ?> <?php endif; ?>
                            </div>
                        </td>
                        <td><?php echo e(optional($p->weight)->name ?? '—'); ?></td>
                        <td class="text-end inv-num fw-bold"><?php echo e($row->on_hand); ?></td>
                        <td class="text-end inv-num"><?php echo e($row->reserved); ?></td>
                        <td class="text-end inv-num fw-bold"><?php echo e($available); ?></td>
                        <td class="text-center">
                            <form method="POST" action="<?php echo e(route('admin.inventory.threshold', $row->product_id)); ?>" class="d-inline-flex align-items-center gap-1">
                                <?php echo csrf_field(); ?>
                                <input type="number" min="0" name="low_stock_threshold" value="<?php echo e($row->low_stock_threshold); ?>" class="inv-threshold-input text-end">
                                <button class="btn btn-sm btn-light btn-action" type="submit" title="Save threshold"><i class="fe-check"></i></button>
                            </form>
                        </td>
                        <td>
                            <?php if($row->stock_status === 'out_of_stock'): ?>
                                <span class="inv-badge inv-badge-out">Out of Stock</span>
                            <?php elseif($row->stock_status === 'low_stock'): ?>
                                <span class="inv-badge inv-badge-low">Low Stock</span>
                            <?php else: ?>
                                <span class="inv-badge inv-badge-in">In Stock</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end inv-num">৳<?php echo e(number_format($cost, 0)); ?></td>
                        <td class="text-end inv-num">৳<?php echo e(number_format($row->on_hand * $cost, 0)); ?></td>
                        <td class="text-nowrap"><?php echo e($row->last_restocked_at ? $row->last_restocked_at->format('M j, Y') : '—'); ?></td>
                        <td class="text-center text-nowrap">
                            <a href="<?php echo e(route('admin.inventory.movements', ['product_id' => $row->product_id])); ?>" class="btn btn-sm btn-light btn-action" title="View History"><i class="fe-list"></i></a>
                            <a href="<?php echo e(route('admin.inventory.adjust', ['product_id' => $row->product_id])); ?>" class="btn btn-sm btn-light btn-action" title="Adjust Stock"><i class="fe-sliders"></i></a>
                            <?php if($p && $p->slug): ?>
                            <a href="<?php echo e(route('product', $p->slug)); ?>" target="_blank" class="btn btn-sm btn-light btn-action" title="View Product"><i class="fe-external-link"></i></a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr><td colspan="11" class="text-center text-muted py-4">No inventory records match your filters.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if($stocks->hasPages()): ?>
        <div class="card-body py-3"><?php echo e($stocks->links('pagination::bootstrap-4')); ?></div>
        <?php endif; ?>
    </div>

</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('backEnd.layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\bilaiGhor\resources\views\backEnd\inventory\stock.blade.php ENDPATH**/ ?>