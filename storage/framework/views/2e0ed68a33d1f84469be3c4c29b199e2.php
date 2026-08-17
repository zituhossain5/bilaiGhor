
<?php $__env->startSection('title', 'Stock Adjustment'); ?>

<?php $__env->startSection('css'); ?>
    <?php echo $__env->make('backEnd.inventory._style', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid mb-5">

    <div class="d-sm-flex align-items-center justify-content-between mb-4 mt-2">
        <h1 class="h3 mb-0 text-gray-800 fw-bold">🛠️ Stock Adjustment</h1>
        <a href="<?php echo e(route('admin.inventory.stock')); ?>" class="d-none d-sm-inline-block btn btn-sm btn-light shadow-sm">
            <i class="fe-arrow-left me-1"></i> Back to Stock Overview
        </a>
    </div>

    <div class="row">
        <div class="col-lg-5 mb-4">
            <div class="card">
                <div class="card-header">1. Select Product</div>
                <div class="card-body">
                    <form method="GET" action="<?php echo e(route('admin.inventory.adjust')); ?>">
                        <label class="form-label">Product</label>
                        <div class="d-flex gap-2">
                            <select name="product_id" class="form-select">
                                <option value="">Choose a product…</option>
                                <?php $__currentLoopData = $products; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($p->id); ?>" <?php if(optional($selected)->product_id == $p->id): echo 'selected'; endif; ?>><?php echo e(Str::limit($p->name, 56)); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                            <button class="btn btn-primary" type="submit">Load</button>
                        </div>
                    </form>

                    <?php if($selected): ?>
                    <hr>
                    <div class="row text-center">
                        <div class="col-4">
                            <div class="stats-label text-primary">On Hand</div>
                            <div class="stats-value" id="inv-cur-onhand"><?php echo e($selected->on_hand); ?></div>
                        </div>
                        <div class="col-4">
                            <div class="stats-label text-info">Reserved</div>
                            <div class="stats-value"><?php echo e($selected->reserved); ?></div>
                        </div>
                        <div class="col-4">
                            <div class="stats-label text-success">Available</div>
                            <div class="stats-value"><?php echo e($selected->available); ?></div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-7 mb-4">
            <div class="card">
                <div class="card-header">2. Apply Adjustment</div>
                <div class="card-body">
                    <?php if(!$selected): ?>
                        <p class="text-muted mb-0">Select a product first — its current On Hand / Reserved / Available will appear here.</p>
                    <?php else: ?>
                    <form method="POST" action="<?php echo e(route('admin.inventory.adjust.store')); ?>" id="invAdjustForm">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="product_id" value="<?php echo e($selected->product_id); ?>">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Adjustment Type</label>
                                <select name="type" id="inv-adj-type" class="form-select" required>
                                    <option value="increase">Increase (+)</option>
                                    <option value="decrease">Decrease (−)</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Quantity</label>
                                <input type="number" name="quantity" id="inv-adj-qty" class="form-control" min="1" required placeholder="e.g. 2">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Reason</label>
                                <select name="reason" class="form-select" required>
                                    <?php $__currentLoopData = $reasons; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($r); ?>"><?php echo e($r); ?></option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Notes (optional)</label>
                                <textarea name="notes" class="form-control" rows="2" maxlength="1000" placeholder="e.g. 2 damaged packets found during shelf count"><?php echo e(old('notes')); ?></textarea>
                            </div>
                            <div class="col-12">
                                <div class="alert alert-secondary py-2 mb-2">
                                    Preview: <strong id="inv-preview-cur"><?php echo e($selected->on_hand); ?></strong>
                                    <span id="inv-preview-op">→</span>
                                    New On Hand: <strong id="inv-preview-new"><?php echo e($selected->on_hand); ?></strong>
                                    <span class="small text-muted d-block">The server recalculates and validates — this preview is informational only.</span>
                                </div>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary"
                                        onclick="return confirm('Apply this stock adjustment? A permanent movement record will be created.');">
                                    <i class="fe-check me-1"></i> Apply Adjustment
                                </button>
                            </div>
                        </div>
                    </form>

                    <script>
                        (function () {
                            var cur = <?php echo e((int) $selected->on_hand); ?>;
                            var typeEl = document.getElementById('inv-adj-type');
                            var qtyEl  = document.getElementById('inv-adj-qty');
                            var out    = document.getElementById('inv-preview-new');
                            function paint() {
                                var q = parseInt(qtyEl.value || '0', 10);
                                var next = typeEl.value === 'increase' ? cur + q : cur - q;
                                out.textContent = isNaN(next) ? cur : next;
                                out.style.color = next < 0 ? '#e74a3b' : '';
                            }
                            typeEl.addEventListener('change', paint);
                            qtyEl.addEventListener('input', paint);
                        })();
                    </script>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('backEnd.layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\bilaiGhor\resources\views\backEnd\inventory\adjust.blade.php ENDPATH**/ ?>