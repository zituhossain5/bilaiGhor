
<?php $__env->startSection('title', 'বিভাগ যোগ'); ?>
<?php $__env->startSection('content'); ?>
<div class="container-fluid py-4">
    <div class="card">
        <div class="card-body">
            <h5 class="mb-3">নতুন বিভাগ</h5>
            <form action="<?php echo e(route('admin.delivery.divisions.store')); ?>" method="POST">
                <?php echo csrf_field(); ?>
                <div class="mb-3"><label class="form-label">নাম *</label><input type="text" name="name" value="<?php echo e(old('name')); ?>" class="form-control" required maxlength="190"></div>
                <div class="mb-3"><label class="form-label">সাজানো (কম সংখ্যা উপরে)</label><input type="number" name="sort_order" value="<?php echo e(old('sort_order', 0)); ?>" class="form-control"></div>
                <div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="status" value="1" id="st" checked><label class="form-check-label" for="st">সক্রিয়</label></div>
                <button type="submit" class="btn btn-success">সেভ</button>
                <a href="<?php echo e(route('admin.delivery.divisions.index')); ?>" class="btn btn-light">ফিরে যান</a>
            </form>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('backEnd.layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\bilaiGhor\resources\views\backEnd\delivery\division_create.blade.php ENDPATH**/ ?>