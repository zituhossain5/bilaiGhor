
<?php $__env->startSection('title', 'জোন এডিট'); ?>
<?php $__env->startSection('content'); ?>
<div class="container-fluid py-4">
    <div class="card">
        <div class="card-body">
            <h5 class="mb-3">জোন এডিট</h5>
            <form action="<?php echo e(route('admin.delivery.zones.update')); ?>" method="POST">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="id" value="<?php echo e($edit_data->id); ?>">
                <div class="mb-3">
                    <label class="form-label">জেলা *</label>
                    <select name="district_id" class="form-select" required>
                        <?php $__currentLoopData = $divisions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $division): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <optgroup label="<?php echo e($division->name); ?>">
                                <?php $__currentLoopData = $division->districts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $d): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($d->id); ?>" <?php if(old('district_id', $edit_data->district_id) == $d->id): echo 'selected'; endif; ?>><?php echo e($d->name); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </optgroup>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
                <div class="mb-3"><label class="form-label">জোনের নাম *</label><input type="text" name="name" value="<?php echo e(old('name', $edit_data->name)); ?>" class="form-control" required maxlength="190"></div>
                <div class="mb-3"><label class="form-label">বাংলা নাম (ঐচ্ছিক)</label><input type="text" name="name_bn" value="<?php echo e(old('name_bn', $edit_data->name_bn)); ?>" class="form-control" maxlength="190"></div>
                <div class="mb-3"><label class="form-label">পোস্ট কোড (ঐচ্ছিক)</label><input type="text" name="post_code" value="<?php echo e(old('post_code', $edit_data->post_code)); ?>" class="form-control" maxlength="20"></div>
                <div class="mb-3"><label class="form-label">ডেলিভারি চার্জ (৳)</label><input type="number" name="delivery_charge" value="<?php echo e(old('delivery_charge', $edit_data->delivery_charge)); ?>" class="form-control" min="0" step="0.01"></div>
                <div class="mb-3"><label class="form-label">সাজানো</label><input type="number" name="sort_order" value="<?php echo e(old('sort_order', $edit_data->sort_order)); ?>" class="form-control"></div>
                <div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="status" value="1" id="st" <?php echo e($edit_data->status ? 'checked' : ''); ?>><label class="form-check-label" for="st">সক্রিয়</label></div>
                <button type="submit" class="btn btn-success">আপডেট</button>
                <a href="<?php echo e(route('admin.delivery.zones.index', $edit_data->district_id)); ?>" class="btn btn-light">ফিরে যান</a>
            </form>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('backEnd.layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\bilaiGhor\resources\views\backEnd\delivery\zone_edit.blade.php ENDPATH**/ ?>