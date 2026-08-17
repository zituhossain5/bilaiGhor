
<?php $__env->startSection('title', 'Edit Bonus'); ?>

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
    .input-group-text-custom {
        background-color: #f8fafc; border: 1px solid #e2e8f0; border-right: none;
        color: #64748b; border-radius: 10px 0 0 10px;
    }
</style>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid py-4">

    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            
            <form action="<?php echo e(route('admin.bonuses.update', $bonus->id)); ?>" method="POST">
                <?php echo csrf_field(); ?>
                <?php echo method_field('PUT'); ?>

                <div class="card card-modern">
                    
                    
                    <div class="card-header-modern">
                        <div>
                            <h5 class="mb-1 fw-bold text-dark">Edit Bonus</h5>
                            <p class="text-muted small mb-0">Modify existing bonus details.</p>
                        </div>
                        <a href="<?php echo e(route('admin.bonuses.index')); ?>" class="btn btn-light btn-sm rounded-pill px-3">
                            <i data-feather="x" style="width:14px;"></i> Close
                        </a>
                    </div>

                    <div class="card-body p-4">
                        
                        
                        <div class="mb-4">
                            <label class="form-label-custom">Employee Name</label>
                            <input type="text" class="form-control form-control-custom" 
                                   value="<?php echo e($bonus->employee->name); ?> (ID: <?php echo e($bonus->employee->employee_id); ?>)" disabled>
                        </div>

                        
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label-custom">Bonus Type <span class="text-danger">*</span></label>
                                <input type="text" name="bonus_type" class="form-control form-control-custom <?php $__errorArgs = ['bonus_type'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" 
                                       value="<?php echo e(old('bonus_type', $bonus->bonus_type)); ?>" required>
                                <?php $__errorArgs = ['bonus_type'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="text-danger small"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label-custom">Amount <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text input-group-text-custom">৳</span>
                                    <input type="number" step="0.01" name="amount" class="form-control form-control-custom border-start-0 ps-2 <?php $__errorArgs = ['amount'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" 
                                           value="<?php echo e(old('amount', $bonus->amount)); ?>" required>
                                </div>
                                <?php $__errorArgs = ['amount'];
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
                            <label class="form-label-custom">Applicable Month (Optional)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0 text-muted"><i data-feather="calendar" style="width:16px;"></i></span>
                                <input type="month" name="salary_month" class="form-control form-control-custom border-start-0" 
                                       value="<?php echo e(old('salary_month', $bonus->salary_month)); ?>">
                            </div>
                        </div>

                        
                        <div class="mb-4">
                            <label class="form-label-custom">Reason</label>
                            <textarea name="reason" class="form-control form-control-custom" rows="2"><?php echo e(old('reason', $bonus->reason)); ?></textarea>
                        </div>

                        
                        <div class="mb-4">
                            <label class="form-label-custom">Private Notes</label>
                            <textarea name="notes" class="form-control form-control-custom" rows="2"><?php echo e(old('notes', $bonus->notes)); ?></textarea>
                        </div>

                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary py-2 fw-bold shadow-sm">
                                <i data-feather="save" class="me-1" style="width: 16px;"></i> Update Changes
                            </button>
                            <a href="<?php echo e(route('admin.bonuses.index')); ?>" class="btn btn-light py-2">Cancel</a>
                        </div>

                    </div>
                </div>
            </form>

        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('backEnd.layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\bilaiGhor\resources\views\backEnd\bonuses\edit.blade.php ENDPATH**/ ?>