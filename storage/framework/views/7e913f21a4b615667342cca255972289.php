
<?php $__env->startSection('title','Edit Purchase'); ?>

<?php $__env->startSection('css'); ?>
<style>
    /* --- Modern Card & Form Styles --- */
    .card-modern {
        border: none;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        transition: all 0.3s ease;
    }
    .card-header-modern {
        background-color: #fff;
        border-bottom: 1px solid #f1f5f9;
        padding: 1.25rem 1.5rem;
        border-radius: 12px 12px 0 0 !important;
    }
    .form-label {
        font-weight: 600;
        color: #344767;
        margin-bottom: 0.5rem;
        font-size: 0.875rem;
    }
    .form-control {
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 0.6rem 1rem;
        font-size: 0.9rem;
    }
    .form-control:focus {
        border-color: #4e73df;
        box-shadow: 0 0 0 3px rgba(78, 115, 223, 0.1);
    }
    .form-control[readonly] {
        background-color: #f8f9fc;
        border-color: #eaecf4;
        color: #6e707e;
    }
    
    /* --- Info Cards on Right --- */
    .info-label { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px; color: #858796; font-weight: 700; }
    .info-value { font-size: 1.1rem; font-weight: 600; color: #4e73df; }
    .grand-total-box {
        background: linear-gradient(45deg, #4e73df, #224abe);
        color: white;
        border-radius: 10px;
        padding: 1.5rem;
        text-align: center;
    }
</style>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid mb-5">

    
    <div class="d-flex justify-content-between align-items-center mb-4 mt-2">
        <div>
            <h4 class="mb-1 fw-bold text-gray-800">
                <i data-feather="edit-3" class="text-primary me-1"></i> Edit Purchase
            </h4>
            <p class="text-muted small mb-0">Update invoice details and payment information.</p>
        </div>
        <a href="<?php echo e(route('purchases.index')); ?>" class="btn btn-white border shadow-sm rounded-pill px-3">
            <i data-feather="arrow-left" class="me-1"></i> Back to List
        </a>
    </div>

    <form action="<?php echo e(route('purchases.update', $purchase->id)); ?>" method="POST">
        <?php echo csrf_field(); ?>
        <div class="row">
            
            
            <div class="col-lg-8">
                <div class="card card-modern mb-4">
                    <div class="card-header-modern">
                        <h6 class="m-0 font-weight-bold text-primary">Editable Information</h6>
                    </div>
                    <div class="card-body p-4">
                        
                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <label class="form-label">Invoice No <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0"><i data-feather="hash" style="width:14px;"></i></span>
                                    <input type="text" name="invoice_no" class="form-control border-start-0 <?php $__errorArgs = ['invoice_no'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" 
                                           value="<?php echo e(old('invoice_no', $purchase->invoice_no)); ?>" required>
                                </div>
                                <?php $__errorArgs = ['invoice_no'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="text-danger small"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>

                            <div class="col-md-6 mb-4">
                                <label class="form-label">Purchase Date <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0"><i data-feather="calendar" style="width:14px;"></i></span>
                                    <input type="date" name="purchase_date" class="form-control border-start-0 <?php $__errorArgs = ['purchase_date'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" 
                                           value="<?php echo e(old('purchase_date', $purchase->purchase_date ? \Carbon\Carbon::parse($purchase->purchase_date)->format('Y-m-d') : now()->format('Y-m-d'))); ?>" required>
                                </div>
                                <?php $__errorArgs = ['purchase_date'];
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
                            <label class="form-label">Paid Amount (৳) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="paid_amount" class="form-control <?php $__errorArgs = ['paid_amount'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" 
                                   value="<?php echo e(old('paid_amount', $purchase->paid_amount)); ?>" min="0" max="<?php echo e($purchase->grand_total); ?>" required>
                            
                            <?php $__errorArgs = ['paid_amount'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="text-danger small"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            
                            <div class="alert alert-soft-warning d-flex align-items-center mt-2 p-2 rounded" style="background: #fff3cd; border: 1px solid #ffeeba; color: #856404;">
                                <i data-feather="alert-circle" class="me-2"></i>
                                <small><strong>Warning:</strong> Changing the "Paid Amount" will automatically adjust your company fund balance.</small>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Note (Optional)</label>
                            <textarea name="note" class="form-control" rows="3" placeholder="Add any additional details here..."><?php echo e(old('note', $purchase->note)); ?></textarea>
                        </div>

                        <div class="d-flex align-items-center gap-2">
                            <button type="submit" class="btn btn-primary px-4">
                                <i data-feather="save" class="me-1"></i> Update Changes
                            </button>
                            <a href="<?php echo e(route('purchases.index')); ?>" class="btn btn-light px-4">Cancel</a>
                        </div>

                    </div>
                </div>
            </div>

            
            <div class="col-lg-4">
                
                
                <div class="card card-modern mb-4">
                    <div class="card-body p-0">
                        <div class="grand-total-box">
                            <div class="opacity-75 text-uppercase small fw-bold mb-1">Grand Total Amount</div>
                            <h2 class="mb-0 fw-bold"><?php echo e(number_format($purchase->grand_total, 2)); ?> ৳</h2>
                            <small class="opacity-75">(Cannot be edited here)</small>
                        </div>
                    </div>
                </div>

                
                <div class="card card-modern mb-4">
                    <div class="card-header-modern bg-light">
                        <h6 class="m-0 font-weight-bold text-dark">Supplier Details</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="info-label">Name</div>
                            <div class="info-value text-dark"><?php echo e($purchase->supplier->name ?? 'N/A'); ?></div>
                        </div>
                        <div class="mb-0">
                            <div class="info-label">Phone</div>
                            <div class="text-secondary"><?php echo e($purchase->supplier->phone ?? 'N/A'); ?></div>
                        </div>
                    </div>
                </div>

                
                <div class="card card-modern">
                    <div class="card-body">
                        <div class="d-flex align-items-start mb-3">
                            <i data-feather="clock" class="text-muted me-2 mt-1" style="width:16px;"></i>
                            <div>
                                <div class="info-label">Created At</div>
                                <div class="small text-dark"><?php echo e($purchase->created_at->format('d M Y, h:i A')); ?></div>
                            </div>
                        </div>
                        
                        <?php if($purchase->updated_by): ?>
                        <div class="d-flex align-items-start">
                            <i data-feather="refresh-cw" class="text-muted me-2 mt-1" style="width:16px;"></i>
                            <div>
                                <div class="info-label">Last Updated</div>
                                <div class="small text-dark"><?php echo e($purchase->updated_at->format('d M Y, h:i A')); ?></div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </div>
    </form>

</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('backEnd.layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\bilaiGhor\resources\views\backEnd\purchases\edit.blade.php ENDPATH**/ ?>