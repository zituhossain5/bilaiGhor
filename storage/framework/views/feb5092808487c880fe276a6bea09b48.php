

<?php $__env->startSection('title', 'ওয়ালেট ডিপোজিট'); ?>
<?php $__env->startSection('page-title', 'ওয়ালেট ডিপোজিট'); ?>

<?php $__env->startSection('content'); ?>
<div class="mb-4">
    <h4 class="fw-bold text-dark mb-1">ওয়ালেট ডিপোজিট</h4>
    <p class="text-muted small">উদ্যোক্তা পে এর মাধ্যমে ওয়ালেটে টাকা ডিপোজিট করুন। প্রথমে ডিপোজিট করেই অর্ডার করতে পারবেন।</p>
</div>

<div class="row">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-body p-4">
                <h5 class="fw-bold mb-4"><i class="fas fa-plus-circle text-success me-2"></i> ডিপোজিট করুন</h5>
                <form action="<?php echo e(route('reseller.deposit.store')); ?>" method="POST">
                    <?php echo csrf_field(); ?>
                    <div class="mb-4">
                        <label class="form-label fw-bold">টাকার পরিমাণ (৳)</label>
                        <input type="number" name="amount" class="form-control form-control-lg <?php $__errorArgs = ['amount'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                               placeholder="ন্যূনতম ৳<?php echo e(number_format($depositMin ?? 100, 0)); ?>" min="<?php echo e($depositMin ?? 100); ?>" max="<?php echo e($depositMax ?? 1000000); ?>" step="1" value="<?php echo e(old('amount', max($depositMin ?? 100, 500))); ?>" required>
                        <?php $__errorArgs = ['amount'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <div class="invalid-feedback"><?php echo e($message); ?></div>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        <small class="text-muted">ন্যূনতম ৳<?php echo e(number_format($depositMin ?? 100, 0)); ?> - সর্বোচ্চ ৳<?php echo e(number_format($depositMax ?? 1000000, 0)); ?> ডিপোজিট করুন</small>
                    </div>
                    <button type="submit" class="btn btn-success btn-lg w-100">
                        <i class="fas fa-wallet me-2"></i> উদ্যোক্তা পে দিয়ে পেমেন্ট করুন
                    </button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-body p-4">
                <h5 class="fw-bold mb-4"><i class="fas fa-history text-primary me-2"></i> সম্প্রতি ডিপোজিট</h5>
                <?php $__empty_1 = true; $__currentLoopData = $deposits ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $d): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <div class="d-flex justify-content-between align-items-center py-3 border-bottom">
                    <div>
                        <span class="fw-bold text-success">+৳<?php echo e(number_format($d->amount, 2)); ?></span>
                        <br><small class="text-muted"><?php echo e($d->created_at->format('d M Y, h:i A')); ?></small>
                    </div>
                    <span class="badge bg-success">সম্পন্ন</span>
                </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <p class="text-muted mb-0">এখনও কোনো ডিপোজিট নেই</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('reseller.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\bilaiGhor\resources\views\reseller\deposit.blade.php ENDPATH**/ ?>