
<?php $__env->startSection('title','Edit delivery person'); ?>
<?php $__env->startSection('content'); ?>
<div class="container-fluid" style="max-width:720px;">
    <h4 class="mb-3">Edit delivery person</h4>
    <div class="card card-body">
        <form action="<?php echo e(route('admin.delivery-boys.update', $edit_data->id)); ?>" method="post" enctype="multipart/form-data">
            <?php echo csrf_field(); ?>
            <div class="mb-3"><label class="form-label">Name *</label><input type="text" name="name" class="form-control" value="<?php echo e(old('name', $edit_data->name)); ?>" required></div>
            <div class="mb-3"><label class="form-label">Phone *</label><input type="text" name="phone" class="form-control" value="<?php echo e(old('phone', $edit_data->phone)); ?>" required></div>
            <div class="mb-3"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="<?php echo e(old('email', $edit_data->email)); ?>"></div>
            <div class="mb-3"><label class="form-label">New password (optional)</label><input type="password" name="password" class="form-control"></div>
            <div class="mb-3"><label class="form-label">Commission per delivery (৳) *</label><input type="number" name="commission_per_delivery" class="form-control" value="<?php echo e(old('commission_per_delivery', $edit_data->commission_per_delivery)); ?>" min="0" step="0.01" required></div>
            <div class="mb-3"><label class="form-label">Reference monthly salary (৳)</label><input type="number" name="monthly_salary_amount" class="form-control" value="<?php echo e(old('monthly_salary_amount', $edit_data->monthly_salary_amount)); ?>" min="0" step="0.01"></div>
            <div class="mb-3"><label class="form-label">Status</label><select name="status" class="form-control"><option value="1" <?php if($edit_data->status==1): echo 'selected'; endif; ?>>Active</option><option value="0" <?php if($edit_data->status==0): echo 'selected'; endif; ?>>Inactive</option></select></div>
            <div class="mb-3"><label class="form-label">Photo</label><input type="file" name="image" class="form-control" accept="image/*"></div>
            <button class="btn btn-primary">Update</button>
            <a href="<?php echo e(route('admin.delivery-boys.index')); ?>" class="btn btn-light">Back</a>
        </form>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('backEnd.layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\bilaiGhor\resources\views\backEnd\delivery_boys\edit.blade.php ENDPATH**/ ?>