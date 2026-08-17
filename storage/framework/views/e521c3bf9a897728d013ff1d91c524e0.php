
<?php $__env->startSection('title','Edit Flavor'); ?>
<?php $__env->startSection('css'); ?>
<style>
    .card { border: none; box-shadow: 0 0 20px rgba(18,38,63,.03); border-radius: 12px; background: #fff; margin-bottom: 24px; }
    .card-header { background: #fff; border-bottom: 1px solid #f1f5f7; padding: 20px 25px; display: flex; align-items: center; gap: 10px; }
    .card-title { font-size: 16px; font-weight: 700; color: #2d3436; margin: 0; }
    .header-icon { width: 35px; height: 35px; background: rgba(114,124,245,.1); color: #727cf5; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 16px; }
    .form-label { font-weight: 600; font-size: 13px; color: #636e72; margin-bottom: 8px; }
    .form-control { background-color: #fbfcff; border: 1px solid #eef2f7; padding: 12px 15px; border-radius: 8px; font-size: 14px; color: #2d3436; transition: all .3s; }
    .form-control:focus { background-color: #fff; border-color: #727cf5; box-shadow: 0 0 0 4px rgba(114,124,245,.1); }
    .switch { position: relative; display: inline-block; width: 46px; height: 24px; }
    .switch input { opacity: 0; width: 0; height: 0; }
    .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #eef2f7; transition: .4s; border-radius: 34px; border: 1px solid #dee2e6; }
    .slider:before { position: absolute; content: ""; height: 18px; width: 18px; left: 2px; bottom: 2px; background-color: white; transition: .4s; border-radius: 50%; box-shadow: 0 2px 4px rgba(0,0,0,.1); }
    input:checked + .slider { background-color: #0acf97; border-color: #0acf97; }
    input:checked + .slider:before { transform: translateX(22px); }
    .btn-submit { background: linear-gradient(45deg,#0acf97,#06b6d4); border: none; color: white; padding: 12px; font-weight: 600; letter-spacing: .5px; box-shadow: 0 4px 15px rgba(10,207,151,.3); transition: .3s; }
    .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(10,207,151,.4); }
</style>
<?php $__env->stopSection(); ?>
<?php $__env->startSection('content'); ?>
<div class="container-fluid">
    <div class="row"><div class="col-12">
        <div class="page-title-box d-flex align-items-center justify-content-between py-4">
            <div><h4 class="page-title mb-1 text-dark fw-bold">Edit Flavor</h4></div>
            <a href="<?php echo e(route('flavors.index')); ?>" class="btn btn-light rounded-pill border shadow-sm px-4"><i class="fe-arrow-left me-1"></i> Back to List</a>
        </div>
    </div></div>
    <form action="<?php echo e(route('flavors.update')); ?>" method="POST" data-parsley-validate>
        <?php echo csrf_field(); ?>
        <input type="hidden" name="id" value="<?php echo e($edit_data->id); ?>">
        <div class="row">
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-header"><div class="header-icon"><i class="fe-maximize"></i></div><h5 class="card-title">Flavor Details</h5></div>
                    <div class="card-body">
                        <div class="form-group mb-4">
                            <label class="form-label">Flavor Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" value="<?php echo e($edit_data->name); ?>" required>
                        </div>
                        <div class="form-group mb-4">
                            <label class="form-label">Sort Order</label>
                            <input type="number" class="form-control" name="sort_order" value="<?php echo e($edit_data->sort_order); ?>">
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card mb-4">
                    <div class="card-header"><div class="header-icon"><i class="fe-settings"></i></div><h5 class="card-title">Visibility</h5></div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4 p-3 bg-light rounded border border-light">
                            <div><h6 class="mb-1 text-dark fw-bold">Active Status</h6></div>
                            <label class="switch"><input type="checkbox" name="status" value="1" <?php if($edit_data->status==1): ?> checked <?php endif; ?>><span class="slider round"></span></label>
                        </div>
                        <button type="submit" class="btn btn-submit w-100 rounded-pill"><i class="fe-check-circle me-1"></i> Update Flavor</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
<?php $__env->stopSection(); ?>


<?php echo $__env->make('backEnd.layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\bilaiGhor\resources\views\backEnd\flavor\edit.blade.php ENDPATH**/ ?>