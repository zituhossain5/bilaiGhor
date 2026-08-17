
<?php $__env->startSection('title', 'Edit Leave Request'); ?>

<?php $__env->startSection('css'); ?>
<style>
    /* --- Card & Form Styles --- */
    .card-modern {
        border: none;
        border-radius: 16px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        background: #fff;
    }
    .card-header-modern {
        background: #fff;
        border-bottom: 1px solid #f1f5f9;
        padding: 1.5rem;
        border-radius: 16px 16px 0 0 !important;
        display: flex; justify-content: space-between; align-items: center;
    }
    
    .form-label-custom {
        font-size: 0.85rem; font-weight: 600; color: #475569; margin-bottom: 0.5rem;
    }
    .form-control-custom, .form-select-custom {
        border: 1px solid #e2e8f0; border-radius: 10px;
        padding: 0.75rem 1rem; font-size: 0.95rem;
        transition: all 0.2s;
    }
    .form-control-custom:focus, .form-select-custom:focus {
        border-color: #6366f1; box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
    }
    .form-control-custom:disabled {
        background-color: #f8fafc; color: #64748b; border-color: #e2e8f0; opacity: 1;
    }

    /* --- Leave Type Selector --- */
    .leave-type-option { font-weight: 500; color: #334155; }
</style>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid py-4">

    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            
            <form action="<?php echo e(route('admin.leaves.update', $leave->id)); ?>" method="POST">
                <?php echo csrf_field(); ?>
                <?php echo method_field('PUT'); ?>

                <div class="card card-modern">
                    
                    
                    <div class="card-header-modern">
                        <div>
                            <h5 class="mb-1 fw-bold text-dark">Edit Leave Request</h5>
                            <p class="text-muted small mb-0">Modify leave details for an employee.</p>
                        </div>
                        <a href="<?php echo e(route('admin.leaves.index')); ?>" class="btn btn-light btn-sm rounded-pill px-3">
                            <i data-feather="x" style="width:14px;"></i> Cancel
                        </a>
                    </div>

                    <div class="card-body p-4">
                        
                        
                        <div class="mb-4">
                            <label class="form-label-custom">Employee Name</label>
                            <input type="text" class="form-control form-control-custom" 
                                   value="<?php echo e($leave->employee->name); ?> (ID: <?php echo e($leave->employee->employee_id); ?>)" disabled>
                        </div>

                        
                        <div class="mb-4">
                            <label class="form-label-custom">Type of Leave <span class="text-danger">*</span></label>
                            <select name="leave_type" class="form-select form-select-custom <?php $__errorArgs = ['leave_type'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" required>
                                <option value="" disabled>Choose Leave Type...</option>
                                <option value="casual" <?php echo e($leave->leave_type == 'casual' ? 'selected' : ''); ?> class="leave-type-option">Casual Leave</option>
                                <option value="sick" <?php echo e($leave->leave_type == 'sick' ? 'selected' : ''); ?> class="leave-type-option">Sick Leave</option>
                                <option value="annual" <?php echo e($leave->leave_type == 'annual' ? 'selected' : ''); ?> class="leave-type-option">Annual Leave</option>
                                <option value="emergency" <?php echo e($leave->leave_type == 'emergency' ? 'selected' : ''); ?> class="leave-type-option">Emergency Leave</option>
                                <option value="maternity" <?php echo e($leave->leave_type == 'maternity' ? 'selected' : ''); ?> class="leave-type-option">Maternity Leave</option>
                                <option value="paternity" <?php echo e($leave->leave_type == 'paternity' ? 'selected' : ''); ?> class="leave-type-option">Paternity Leave</option>
                                <option value="unpaid" <?php echo e($leave->leave_type == 'unpaid' ? 'selected' : ''); ?> class="leave-type-option">Unpaid Leave</option>
                            </select>
                            <?php $__errorArgs = ['leave_type'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="text-danger small"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>

                        
                        <div class="row g-3 mb-4">
                            <div class="col-6">
                                <label class="form-label-custom">Start Date <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-end-0 text-muted"><i data-feather="calendar" style="width:16px;"></i></span>
                                    <input type="date" name="start_date" class="form-control form-control-custom border-start-0 <?php $__errorArgs = ['start_date'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" 
                                           value="<?php echo e(old('start_date', $leave->start_date->format('Y-m-d'))); ?>" required>
                                </div>
                                <?php $__errorArgs = ['start_date'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="text-danger small"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>
                            <div class="col-6">
                                <label class="form-label-custom">End Date <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-end-0 text-muted"><i data-feather="calendar" style="width:16px;"></i></span>
                                    <input type="date" name="end_date" class="form-control form-control-custom border-start-0 <?php $__errorArgs = ['end_date'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" 
                                           value="<?php echo e(old('end_date', $leave->end_date->format('Y-m-d'))); ?>" required>
                                </div>
                                <?php $__errorArgs = ['end_date'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="text-danger small"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>
                        </div>

                        
                        <div class="mb-4">
                            <label class="form-label-custom">Reason for Leave</label>
                            <textarea name="reason" class="form-control form-control-custom" rows="4" placeholder="Briefly describe the reason..."><?php echo e(old('reason', $leave->reason)); ?></textarea>
                        </div>

                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary py-2 fw-bold shadow-sm">
                                <i data-feather="check-circle" class="me-1" style="width: 16px;"></i> Update Request
                            </button>
                            <a href="<?php echo e(route('admin.leaves.index')); ?>" class="btn btn-light py-2">Discard Changes</a>
                        </div>

                    </div>
                </div>
            </form>

        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('backEnd.layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\bilaiGhor\resources\views\backEnd\leaves\edit.blade.php ENDPATH**/ ?>