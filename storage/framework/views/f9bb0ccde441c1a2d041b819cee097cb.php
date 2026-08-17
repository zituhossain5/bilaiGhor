
<?php $__env->startSection('title','Edit Role'); ?>

<?php $__env->startSection('css'); ?>
<link href="<?php echo e(asset('public/backEnd')); ?>/assets/libs/select2/css/select2.min.css" rel="stylesheet" type="text/css" />

<style>
    /* Premium Card */
    .card {
        border: none;
        box-shadow: 0 0 20px rgba(18, 38, 63, 0.03);
        border-radius: 12px;
        overflow: hidden;
    }
    .card-header {
        background: #fff;
        border-bottom: 1px solid #f1f5f7;
        padding: 20px 25px;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    
    /* Permission Items */
    .permission-item {
        border: 1px solid #eef2f7;
        padding: 10px 15px;
        border-radius: 8px;
        transition: all 0.2s;
        background: #f9fbfd;
        cursor: pointer;
    }
    .permission-item:hover {
        background: #fff;
        border-color: #727cf5;
        box-shadow: 0 2px 8px rgba(114, 124, 245, 0.1);
    }
    .permission-item.active {
        background-color: rgba(114, 124, 245, 0.05);
        border-color: rgba(114, 124, 245, 0.3);
    }
    .form-check-input:checked + .form-check-label {
        color: #727cf5;
        font-weight: 600;
    }

    /* Select All Toggle */
    .select-all-wrapper {
        background: rgba(114, 124, 245, 0.1);
        padding: 8px 15px;
        border-radius: 6px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .switch { position: relative; display: inline-block; width: 40px; height: 22px; }
    .switch input { opacity: 0; width: 0; height: 0; }
    .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: .4s; border-radius: 34px; }
    .slider:before { position: absolute; content: ""; height: 16px; width: 16px; left: 3px; bottom: 3px; background-color: white; transition: .4s; border-radius: 50%; }
    input:checked + .slider { background-color: #727cf5; }
    input:checked + .slider:before { transform: translateX(18px); }

    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 40px;
        background: #fff5f5;
        border: 1px dashed #fc8181;
        border-radius: 8px;
        color: #c53030;
    }
</style>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid">
    
    <div class="row mb-3 mt-3">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <div>
                <h4 class="page-title mb-0" style="font-weight: 700; color: #2d3436;">Edit Role</h4>
                <p class="text-muted font-size-13 mb-0">Modify role name and update permissions.</p>
            </div>
            <a href="<?php echo e(route('roles.index')); ?>" class="btn btn-light rounded-pill border shadow-sm px-4">
                <i class="fe-arrow-left me-1"></i> Back to Roles
            </a>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">
                    <form action="<?php echo e(route('roles.update')); ?>" method="POST" data-parsley-validate>
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="hidden_id" value="<?php echo e($edit_data->id); ?>">
                        
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="name" class="form-label">Role Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control form-control-lg <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" 
                                           name="name" value="<?php echo e($edit_data->name); ?>" id="name" required>
                                    <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                        <div class="invalid-feedback"><?php echo e($message); ?></div>
                                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                </div>
                            </div>
                        </div>

                        <hr style="border-color: #f1f5f7;">

                        <div class="d-flex justify-content-between align-items-center mb-3 mt-3">
                            <h5 class="text-uppercase text-muted font-size-14 fw-bold">Manage Permissions</h5>
                            
                            <div class="select-all-wrapper">
                                <span class="font-size-13 fw-bold text-primary">Select All Permissions</span>
                                <label class="switch mb-0">
                                    <input type="checkbox" id="checkall">
                                    <span class="slider round"></span>
                                </label>
                            </div>
                        </div>

                        <?php if(isset($permission) && $permission->count() > 0): ?>
                            <div class="row g-3">
                                <?php $__currentLoopData = $permission; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <?php
                                    // Check if this permission is assigned to the role
                                    $isChecked = $edit_data->permissions->contains('id', $value->id);
                                ?>
                                <div class="col-md-3 col-sm-6">
                                    <div class="permission-item <?php echo e($isChecked ? 'active' : ''); ?>">
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input permission-checkbox" 
                                                   value="<?php echo e($value->id); ?>" id="perm_<?php echo e($value->id); ?>" 
                                                   name="permission[]" <?php echo e($isChecked ? 'checked' : ''); ?>>
                                            <label class="form-check-label w-100" for="perm_<?php echo e($value->id); ?>" style="cursor: pointer;">
                                                <?php echo e($value->name); ?>

                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fe-alert-triangle font-size-24 mb-2"></i>
                                <h5>No Permissions Found!</h5>
                                <p class="mb-0">Please create permissions in the system first with <code>guard_name = 'admin'</code>.</p>
                            </div>
                        <?php endif; ?>

                        <div class="row mt-4">
                            <div class="col-12 text-end">
                                <button type="submit" class="btn btn-success rounded-pill px-4 shadow-sm">
                                    <i class="fe-check-circle me-1"></i> Update Role
                                </button>
                            </div>
                        </div>

                    </form>
                </div> </div> </div> </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('script'); ?>
<script src="<?php echo e(asset('public/backEnd/')); ?>/assets/libs/parsleyjs/parsley.min.js"></script>
<script src="<?php echo e(asset('public/backEnd/')); ?>/assets/js/pages/form-validation.init.js"></script>
<script src="<?php echo e(asset('public/backEnd/')); ?>/assets/libs/select2/js/select2.min.js"></script>

<script>
    $(document).ready(function() {
        // Initial check for Select All toggle
        checkSelectAllState();

        // Select All Functionality
        $("#checkall").click(function() {
            if ($(this).is(':checked')) {
                $('.permission-checkbox').prop('checked', true).closest('.permission-item').addClass('active');
            } else {
                $('.permission-checkbox').prop('checked', false).closest('.permission-item').removeClass('active');
            }
        });

        // Individual checkbox change
        $('.permission-checkbox').change(function() {
            if($(this).is(':checked')) {
                $(this).closest('.permission-item').addClass('active');
            } else {
                $(this).closest('.permission-item').removeClass('active');
            }
            checkSelectAllState();
        });

        // Function to update Select All state based on individual checkboxes
        function checkSelectAllState() {
            if ($('.permission-checkbox:checked').length == $('.permission-checkbox').length) {
                $('#checkall').prop('checked', true);
            } else {
                $('#checkall').prop('checked', false);
            }
        }
    });
</script>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('backEnd.layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\bilaiGhor\resources\views\backEnd\roles\edit.blade.php ENDPATH**/ ?>