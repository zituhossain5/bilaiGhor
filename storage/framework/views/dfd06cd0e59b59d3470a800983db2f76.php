
<?php $__env->startSection('title','Edit Employee'); ?>

<?php $__env->startSection('css'); ?>
<style>
    /* --- Form Styles --- */
    .card-form {
        border: none;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
        background: #fff;
        margin-bottom: 20px;
    }
    .card-header-form {
        background: #fff;
        border-bottom: 1px solid #f1f5f9;
        padding: 1.5rem;
        border-radius: 12px 12px 0 0 !important;
    }
    .section-title {
        font-size: 1rem;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 0;
        display: flex; align-items: center; gap: 8px;
    }
    
    .form-label-custom {
        font-size: 0.85rem;
        font-weight: 600;
        color: #475569;
        margin-bottom: 0.5rem;
    }
    .form-control-custom, .form-select-custom {
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 0.7rem 1rem;
        font-size: 0.9rem;
        transition: all 0.2s;
    }
    .form-control-custom:focus, .form-select-custom:focus {
        border-color: #6366f1;
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
    }
    .form-control-custom:disabled, .form-control-custom[readonly] {
        background-color: #f8fafc;
        opacity: 1;
    }
</style>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid py-4">

    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 fw-bold text-dark">Edit Employee</h4>
            <p class="text-muted small mb-0">Update employee profile and information.</p>
        </div>
        <a href="<?php echo e(route('admin.employees.index')); ?>" class="btn btn-white border shadow-sm rounded-pill px-4">
            <i data-feather="arrow-left" class="me-1" style="width: 16px;"></i> Back to List
        </a>
    </div>

    <form action="<?php echo e(route('admin.employees.update', $employee->id)); ?>" method="POST">
        <?php echo csrf_field(); ?>
        <?php echo method_field('PUT'); ?>

        <div class="row">
            
            
            <div class="col-lg-8">
                
                
                <div class="card card-form">
                    <div class="card-header-form">
                        <div class="section-title">
                            <i data-feather="user" class="text-primary" style="width: 18px;"></i> Employee Information
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <label class="form-label-custom">Employee ID</label>
                                <input type="text" class="form-control form-control-custom" value="<?php echo e($employee->employee_id); ?>" disabled>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label-custom">Linked User Account</label>
                                <select name="user_id" class="form-control select2">
                                    <option value="">No User Linked</option>
                                    <?php $__currentLoopData = $availableUsers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $user): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($user->id); ?>" <?php echo e($employee->user_id == $user->id ? 'selected' : ''); ?>>
                                            <?php echo e($user->name); ?> (<?php echo e($user->email); ?>)
                                        </option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label-custom">Full Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control form-control-custom <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" value="<?php echo e(old('name', $employee->name)); ?>" required>
                                <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="invalid-feedback"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label-custom">Email Address <span class="text-danger">*</span></label>
                                <input type="email" name="email" class="form-control form-control-custom <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" value="<?php echo e(old('email', $employee->email)); ?>" required>
                                <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="invalid-feedback"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label-custom">Phone Number</label>
                                <input type="text" name="phone" class="form-control form-control-custom" value="<?php echo e(old('phone', $employee->phone)); ?>">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label-custom">Joining Date <span class="text-danger">*</span></label>
                                <input type="date" name="joining_date" class="form-control form-control-custom <?php $__errorArgs = ['joining_date'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" value="<?php echo e(old('joining_date', $employee->joining_date->format('Y-m-d'))); ?>" required>
                                <?php $__errorArgs = ['joining_date'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="invalid-feedback"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label-custom">Department</label>
                                <input type="text" name="department" class="form-control form-control-custom" value="<?php echo e(old('department', $employee->department)); ?>">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label-custom">Designation</label>
                                <input type="text" name="designation" class="form-control form-control-custom" value="<?php echo e(old('designation', $employee->designation)); ?>">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label-custom">Employment Status <span class="text-danger">*</span></label>
                                <select name="status" class="form-select form-select-custom" required>
                                    <option value="active" <?php echo e($employee->status == 'active' ? 'selected' : ''); ?>>Active</option>
                                    <option value="inactive" <?php echo e($employee->status == 'inactive' ? 'selected' : ''); ?>>Inactive</option>
                                    <option value="terminated" <?php echo e($employee->status == 'terminated' ? 'selected' : ''); ?>>Terminated</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            
            <div class="col-lg-4">
                
                
                <div class="card card-form">
                    <div class="card-header-form">
                        <div class="section-title">
                            <i data-feather="credit-card" class="text-success" style="width: 18px;"></i> Financial Details
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <div class="mb-3">
                            <label class="form-label-custom">Basic Salary (৳) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="basic_salary" class="form-control form-control-custom <?php $__errorArgs = ['basic_salary'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" value="<?php echo e(old('basic_salary', $employee->basic_salary)); ?>" required>
                            <?php $__errorArgs = ['basic_salary'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="invalid-feedback"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>

                        <div class="mb-3">
                            <label class="form-label-custom">Bank Name</label>
                            <input type="text" name="bank_name" class="form-control form-control-custom" value="<?php echo e(old('bank_name', $employee->bank_name)); ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label-custom">Bank Account No.</label>
                            <input type="text" name="bank_account" class="form-control form-control-custom" value="<?php echo e(old('bank_account', $employee->bank_account)); ?>">
                        </div>
                    </div>
                </div>

                
                <div class="card card-form">
                    <div class="card-header-form">
                        <div class="section-title">
                            <i data-feather="file-text" class="text-dark" style="width: 18px;"></i> Other Information
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <div class="mb-3">
                            <label class="form-label-custom">NID Number</label>
                            <input type="text" name="nid" class="form-control form-control-custom" value="<?php echo e(old('nid', $employee->nid)); ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label-custom">Address</label>
                            <textarea name="address" class="form-control form-control-custom" rows="2"><?php echo e(old('address', $employee->address)); ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label-custom">Additional Notes</label>
                            <textarea name="notes" class="form-control form-control-custom" rows="2"><?php echo e(old('notes', $employee->notes)); ?></textarea>
                        </div>
                    </div>
                </div>

                
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary py-2 fw-bold shadow-sm">
                        <i data-feather="save" class="me-1" style="width: 16px;"></i> Update Employee
                    </button>
                    <a href="<?php echo e(route('admin.employees.index')); ?>" class="btn btn-light py-2">Cancel</a>
                </div>

            </div>

        </div>
    </form>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
    $(document).ready(function() {
        if ($.fn.select2) {
            $('.select2').select2({ width: '100%' });
        }
    });
</script>
<?php $__env->stopPush(); ?>
<?php echo $__env->make('backEnd.layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\bilaiGhor\resources\views\backEnd\employees\edit.blade.php ENDPATH**/ ?>