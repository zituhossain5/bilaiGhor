
<?php $__env->startSection('title', 'Edit Reseller Profile'); ?>

<?php $__env->startSection('css'); ?>
<?php echo $__env->make('backEnd.partials.admin_layout_gap_fix', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<style>
    html, body { background: #eef1f8; }
    /* --- Modern Card --- */
    .card-modern {
        border: none;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        background: #fff;
        margin-bottom: 20px;
    }
    .card-header-modern {
        background: #fff;
        border-bottom: 1px solid #f1f5f9;
        padding: 1.25rem 1.5rem;
        border-radius: 12px 12px 0 0 !important;
    }
    .section-title {
        font-size: 0.95rem; font-weight: 700; color: #334155;
        display: flex; align-items: center; gap: 8px; margin: 0;
    }

    /* --- Form Elements --- */
    .form-label-custom {
        font-size: 0.85rem; font-weight: 600; color: #475569; margin-bottom: 0.5rem;
    }
    .form-control-custom, .form-select-custom {
        border: 1px solid #e2e8f0; border-radius: 8px; padding: 0.65rem 1rem;
    }
    .form-control-custom:focus, .form-select-custom:focus {
        border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
    }

    /* --- Badges & Highlights --- */
    .wallet-card {
        background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 10px; padding: 15px;
    }
    .verification-badge {
        font-size: 0.8rem; padding: 5px 10px; border-radius: 6px; font-weight: 600;
    }
</style>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid py-4">

    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 fw-bold text-dark">Edit Reseller</h4>
            <p class="text-muted small mb-0">Update reseller profile and account status.</p>
        </div>
        <a href="<?php echo e(route('admin.resellers.index')); ?>" class="btn btn-white border shadow-sm rounded-pill px-4">
            <i data-feather="arrow-left" class="me-1" style="width: 16px;"></i> Back to List
        </a>
    </div>

    <form action="<?php echo e(route('admin.resellers.update')); ?>" method="POST" data-parsley-validate>
        <?php echo csrf_field(); ?>
        <input type="hidden" value="<?php echo e($reseller->id); ?>" name="hidden_id">

        <div class="row">
            
            
            <div class="col-lg-8">
                
                
                <div class="card card-modern">
                    <div class="card-header-modern">
                        <h5 class="section-title"><i data-feather="user" class="text-primary" style="width: 18px;"></i> Reseller Information</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <label class="form-label-custom">Full Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-custom <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" name="name" value="<?php echo e($reseller->name); ?>" required>
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
                                <label class="form-label-custom">Shop Name</label>
                                <input type="text" class="form-control form-control-custom <?php $__errorArgs = ['shop_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" name="shop_name" value="<?php echo e($reseller->shop_name); ?>">
                                <?php $__errorArgs = ['shop_name'];
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
                                <input type="email" class="form-control form-control-custom <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" name="email" value="<?php echo e($reseller->email); ?>" required>
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
                                <input type="text" class="form-control form-control-custom" value="<?php echo e($reseller->phone ?? 'N/A'); ?>" readonly disabled>
                                <small class="text-muted">Phone number cannot be changed directly.</small>
                            </div>
                        </div>
                    </div>
                </div>

                
                <div class="card card-modern">
                    <div class="card-header-modern">
                        <h5 class="section-title"><i data-feather="shield" class="text-info" style="width: 18px;"></i> Account Status</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-4 align-items-center">
                            <div class="col-md-6">
                                <label class="form-label-custom">Active Status</label>
                                <select class="form-select form-select-custom" name="status">
                                    <option value="1" <?php echo e($reseller->status == 1 ? 'selected' : ''); ?>>Active</option>
                                    <option value="0" <?php echo e($reseller->status == 0 ? 'selected' : ''); ?>>Inactive</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label-custom d-block">Verification Status</label>
                                <?php if($reseller->verification_status == 'approved'): ?>
                                    <span class="badge bg-success verification-badge"><i class="mdi mdi-check-decagram"></i> Verified</span>
                                <?php elseif($reseller->verification_status == 'rejected'): ?>
                                    <span class="badge bg-danger verification-badge">Rejected</span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark verification-badge">Pending</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            
            <div class="col-lg-4">
                
                
                <div class="card card-modern">
                    <div class="card-header-modern">
                        <h5 class="section-title"><i data-feather="credit-card" class="text-success" style="width: 18px;"></i> Financials</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="wallet-card text-center">
                            <label class="form-label-custom mb-1 text-muted">Current Wallet Balance</label>
                            <h3 class="mb-0 text-dark fw-bold">৳<?php echo e(number_format($reseller->wallet_balance ?? 0, 2)); ?></h3>
                        </div>
                    </div>
                </div>

                
                <div class="card card-modern">
                    <div class="card-header-modern">
                        <h5 class="section-title"><i data-feather="lock" class="text-danger" style="width: 18px;"></i> Security</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="mb-3">
                            <label class="form-label-custom">New Password</label>
                            <input type="password" class="form-control form-control-custom <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" name="password" placeholder="Leave blank to keep current">
                            <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="invalid-feedback"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                        <div class="mb-3">
                            <label class="form-label-custom">Confirm Password</label>
                            <input type="password" class="form-control form-control-custom" name="password_confirmation" placeholder="Retype password">
                        </div>
                        
                        <div class="d-grid mt-4">
                            <button type="submit" class="btn btn-primary fw-bold py-2 shadow-sm">
                                <i data-feather="save" class="me-1" style="width: 16px;"></i> Update Reseller
                            </button>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </form>

</div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('script'); ?>
<script src="<?php echo e(asset('public/backEnd/')); ?>/assets/libs/parsleyjs/parsley.min.js"></script>
<script src="<?php echo e(asset('public/backEnd/')); ?>/assets/js/pages/form-validation.init.js"></script>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('backEnd.layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\bilaiGhor\resources\views\backEnd\reseller\edit.blade.php ENDPATH**/ ?>