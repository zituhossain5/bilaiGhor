
<?php $__env->startSection('title','Add New Employee'); ?>

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

    /* --- Account Linking Section --- */
    .account-setup-box {
        background: #f8fafc;
        border: 1px dashed #cbd5e1;
        border-radius: 10px;
        padding: 1.5rem;
    }
    
    /* --- Toggle Switch --- */
    .form-switch .form-check-input {
        width: 3em; height: 1.5em;
        cursor: pointer;
    }
    .form-switch .form-check-input:checked {
        background-color: #10b981;
        border-color: #10b981;
    }
</style>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid py-4">

    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 fw-bold text-dark">Add New Employee</h4>
            <p class="text-muted small mb-0">Create a new employee profile and link user account.</p>
        </div>
        <a href="<?php echo e(route('admin.employees.index')); ?>" class="btn btn-white border shadow-sm rounded-pill px-4">
            <i data-feather="arrow-left" class="me-1" style="width: 16px;"></i> Back to List
        </a>
    </div>

    <form action="<?php echo e(route('admin.employees.store')); ?>" method="POST">
        <?php echo csrf_field(); ?>
        <div class="row">
            
            
            <div class="col-lg-8">
                
                
                <div class="card card-form">
                    <div class="card-header-form">
                        <div class="section-title">
                            <i data-feather="user" class="text-primary" style="width: 18px;"></i> Personal & Job Details
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <label class="form-label-custom">Full Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control form-control-custom <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" value="<?php echo e(old('name')); ?>" placeholder="e.g. John Doe" required>
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
unset($__errorArgs, $__bag); ?>" value="<?php echo e(old('email')); ?>" placeholder="john@example.com" required>
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
                                <input type="text" name="phone" class="form-control form-control-custom" value="<?php echo e(old('phone')); ?>" placeholder="+880 1xxxxxxxxx">
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
unset($__errorArgs, $__bag); ?>" value="<?php echo e(old('joining_date')); ?>" required>
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
                                <input type="text" name="department" class="form-control form-control-custom" value="<?php echo e(old('department')); ?>" placeholder="e.g. IT, HR, Sales">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label-custom">Designation</label>
                                <input type="text" name="designation" class="form-control form-control-custom" value="<?php echo e(old('designation')); ?>" placeholder="e.g. Senior Developer">
                            </div>
                        </div>
                    </div>
                </div>

                
                <div class="card card-form">
                    <div class="card-header-form">
                        <div class="section-title">
                            <i data-feather="credit-card" class="text-success" style="width: 18px;"></i> Financial & Address
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-4">
                            <div class="col-md-4">
                                <label class="form-label-custom">Basic Salary (৳) <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" name="basic_salary" class="form-control form-control-custom <?php $__errorArgs = ['basic_salary'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" value="<?php echo e(old('basic_salary')); ?>" required>
                                <?php $__errorArgs = ['basic_salary'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="invalid-feedback"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label-custom">NID Number</label>
                                <input type="text" name="nid" class="form-control form-control-custom" value="<?php echo e(old('nid')); ?>">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label-custom">Bank Name</label>
                                <input type="text" name="bank_name" class="form-control form-control-custom" value="<?php echo e(old('bank_name')); ?>">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label-custom">Bank Account No.</label>
                                <input type="text" name="bank_account" class="form-control form-control-custom" value="<?php echo e(old('bank_account')); ?>">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label-custom">Address</label>
                                <input type="text" name="address" class="form-control form-control-custom" value="<?php echo e(old('address')); ?>" placeholder="Full address">
                            </div>

                            <div class="col-12">
                                <label class="form-label-custom">Additional Notes</label>
                                <textarea name="notes" class="form-control form-control-custom" rows="2" placeholder="Any extra information..."><?php echo e(old('notes')); ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            
            <div class="col-lg-4">
                <div class="card card-form h-100">
                    <div class="card-header-form bg-light">
                        <div class="section-title">
                            <i data-feather="settings" class="text-dark" style="width: 18px;"></i> Account Setup
                        </div>
                    </div>
                    <div class="card-body p-4">
                        
                        
                        <div class="mb-4">
                            <label class="form-label-custom">Link Existing User (Optional)</label>
                            <select name="user_id" class="form-control select2">
                                <option value="">-- Select User --</option>
                                <?php $__currentLoopData = $availableUsers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $user): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($user->id); ?>"><?php echo e($user->name); ?> (<?php echo e($user->email); ?>)</option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                            <small class="text-muted d-block mt-1">If the employee already has a login account.</small>
                        </div>

                        <div class="text-center text-muted my-3 position-relative">
                            <hr>
                            <span class="position-absolute top-50 start-50 translate-middle bg-white px-2 small">OR</span>
                        </div>

                        
                        <div class="account-setup-box mt-4">
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" name="create_user" id="create_user" value="1">
                                <label class="form-check-label fw-bold ms-2 pt-1" for="create_user">Create Login Account</label>
                            </div>
                            
                            <div id="user_role_section" style="display: none;">
                                <div class="mb-3">
                                    <label class="form-label-custom">Assign Role <span class="text-danger">*</span></label>
                                    <select name="user_role" class="form-select form-select-custom">
                                        <option value="">Select Role</option>
                                        <?php $__currentLoopData = $roles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $role): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <option value="<?php echo e($role->id); ?>"><?php echo e($role->name); ?></option>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </select>
                                </div>
                                <div class="alert alert-soft-primary bg-light border p-2 rounded small text-muted">
                                    <i data-feather="info" style="width: 12px;"></i> A temporary password will be generated or sent to their email.
                                </div>
                            </div>
                        </div>

                        <div class="mt-5 d-grid gap-2">
                            <button type="submit" class="btn btn-primary py-2 fw-bold shadow-sm">
                                <i data-feather="save" class="me-1" style="width: 16px;"></i> Save Employee
                            </button>
                            <a href="<?php echo e(route('admin.employees.index')); ?>" class="btn btn-light py-2">Cancel</a>
                        </div>

                    </div>
                </div>
            </div>

        </div>
    </form>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
    $(document).ready(function() {
        // Initialize Select2 if available
        if ($.fn.select2) {
            $('.select2').select2({ width: '100%' });
        }

        // Toggle User Role Section
        $('#create_user').on('change', function() {
            if ($(this).is(':checked')) {
                $('#user_role_section').slideDown(300);
            } else {
                $('#user_role_section').slideUp(300);
            }
        });
    });
</script>
<?php $__env->stopPush(); ?>
<?php echo $__env->make('backEnd.layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\bilaiGhor\resources\views\backEnd\employees\create.blade.php ENDPATH**/ ?>