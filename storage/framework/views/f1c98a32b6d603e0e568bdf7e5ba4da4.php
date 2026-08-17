
<?php $__env->startSection('title', 'জোন যোগ'); ?>
<?php $__env->startSection('content'); ?>
<div class="container-fluid py-4">
    <div class="card">
        <div class="card-body">
            <h5 class="mb-3">নতুন জোন — <?php echo e($district->division->name ?? ''); ?> — <?php echo e($district->name); ?></h5>
            <form action="<?php echo e(route('admin.delivery.zones.store')); ?>" method="POST">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="district_id" value="<?php echo e($district->id); ?>">
                <div class="mb-3"><label class="form-label">জেলা</label><input type="text" class="form-control" value="<?php echo e(($district->division->name ?? '') . ' — ' . $district->name); ?>" disabled></div>
                <div class="mb-3"><label class="form-label">জোনের নাম *</label><input type="text" name="name" value="<?php echo e(old('name')); ?>" class="form-control" required maxlength="190"></div>
                <div class="mb-3"><label class="form-label">বাংলা নাম (ঐচ্ছিক)</label><input type="text" name="name_bn" value="<?php echo e(old('name_bn')); ?>" class="form-control" maxlength="190"></div>
                <div class="mb-3"><label class="form-label">পোস্ট কোড (ঐচ্ছিক)</label><input type="text" name="post_code" value="<?php echo e(old('post_code')); ?>" class="form-control" maxlength="20"></div>
                <div class="mb-3"><label class="form-label">ডেলিভারি চার্জ (৳)</label><input type="number" name="delivery_charge" value="<?php echo e(old('delivery_charge', 0)); ?>" class="form-control" min="0" step="0.01"></div>
                <div class="mb-3"><label class="form-label">সাজানো</label><input type="number" name="sort_order" value="<?php echo e(old('sort_order', 0)); ?>" class="form-control"></div>
                <div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="status" value="1" id="st" checked><label class="form-check-label" for="st">সক্রিয়</label></div>
                <button type="submit" class="btn btn-success">সেভ</button>
                <a href="<?php echo e(route('admin.delivery.zones.index', $district->id)); ?>" class="btn btn-light">ফিরে যান</a>
            </form>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('backEnd.layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\bilaiGhor\resources\views\backEnd\delivery\zone_create.blade.php ENDPATH**/ ?>