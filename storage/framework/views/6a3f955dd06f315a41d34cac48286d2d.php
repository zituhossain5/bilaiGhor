
<?php $__env->startSection('title', 'Make Salary Payment'); ?>

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
    .input-group-text-custom {
        background-color: #f8fafc; border: 1px solid #e2e8f0; border-right: none;
        color: #64748b; border-radius: 10px 0 0 10px;
    }

    /* --- Auto-fill Box --- */
    .auto-fill-box {
        background: #eff6ff;
        border: 1px dashed #60a5fa;
        border-radius: 10px;
        padding: 1.25rem;
    }
    .fund-alert {
        background: #fffbeb; border: 1px solid #fcd34d; color: #92400e;
        border-radius: 10px; padding: 1rem; font-size: 0.9rem;
    }
</style>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid py-4">

    <div class="row justify-content-center">
        <div class="col-md-9 col-lg-7">
            
            <form action="<?php echo e(route('admin.salary_payments.store')); ?>" method="POST">
                <?php echo csrf_field(); ?>

                <div class="card card-modern">
                    
                    
                    <div class="card-header-modern">
                        <div>
                            <h5 class="mb-1 fw-bold text-dark">Process Salary Payment</h5>
                            <p class="text-muted small mb-0">Disburse salary to an employee.</p>
                        </div>
                        <a href="<?php echo e(route('admin.salary_payments.index')); ?>" class="btn btn-light btn-sm rounded-pill px-3">
                            <i data-feather="list" style="width:14px;" class="me-1"></i> History
                        </a>
                    </div>

                    <div class="card-body p-4">
                        
                        
                        <div class="mb-4">
                            <label class="form-label-custom">Select Employee <span class="text-danger">*</span></label>
                            <select name="employee_id" id="employee_id" class="form-control select2 form-select-custom <?php $__errorArgs = ['employee_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" required>
                                <option value="">-- Choose Employee --</option>
                                <?php $__currentLoopData = $employees; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $emp): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($emp->id); ?>" <?php echo e((request('employee_id') == $emp->id || old('employee_id') == $emp->id) ? 'selected' : ''); ?>>
                                        <?php echo e($emp->name); ?> (ID: <?php echo e($emp->employee_id); ?>)
                                    </option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                            <?php $__errorArgs = ['employee_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="text-danger small"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>

                        
                        <?php if(count($unpaidSalaries) > 0): ?>
                        <div class="auto-fill-box mb-4">
                            <div class="d-flex align-items-center mb-2">
                                <i data-feather="zap" class="text-primary me-2" style="width:16px;"></i>
                                <span class="fw-bold text-primary small text-uppercase">Smart Auto-fill</span>
                            </div>
                            <label class="form-label-custom">Select a calculated (unpaid) salary record:</label>
                            <select name="salary_id" class="form-select form-select-custom bg-white">
                                <option value="">-- Select to Auto-fill Amount --</option>
                                <?php $__currentLoopData = $unpaidSalaries; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $salary): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($salary->id); ?>" data-amount="<?php echo e($salary->net_salary); ?>">
                                        <?php echo e(\Carbon\Carbon::parse($salary->salary_month)->format('F Y')); ?> - Net: ৳<?php echo e(number_format($salary->net_salary, 2)); ?>

                                    </option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>
                        <?php endif; ?>

                        
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label-custom">Payment For Month <span class="text-danger">*</span></label>
                                <input type="month" name="payment_month" class="form-control form-control-custom <?php $__errorArgs = ['payment_month'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" 
                                       value="<?php echo e(old('payment_month', request('month', date('Y-m')))); ?>" required>
                                <?php $__errorArgs = ['payment_month'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="text-danger small"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label-custom">Payment Date <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-end-0 text-muted"><i data-feather="calendar" style="width:16px;"></i></span>
                                    <input type="date" name="payment_date" class="form-control form-control-custom border-start-0 <?php $__errorArgs = ['payment_date'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" 
                                           value="<?php echo e(old('payment_date', date('Y-m-d'))); ?>" required>
                                </div>
                                <?php $__errorArgs = ['payment_date'];
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
                            <label class="form-label-custom">Paying Amount <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text input-group-text-custom fw-bold">৳</span>
                                <input type="number" step="0.01" name="amount" id="amount" class="form-control form-control-custom border-start-0 ps-2 <?php $__errorArgs = ['amount'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" 
                                       value="<?php echo e(old('amount')); ?>" placeholder="0.00" required>
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

                        
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label-custom">Payment Method <span class="text-danger">*</span></label>
                                <select name="payment_method" class="form-select form-select-custom <?php $__errorArgs = ['payment_method'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" required>
                                    <option value="bank_transfer" <?php echo e(old('payment_method') == 'bank_transfer' ? 'selected' : ''); ?>>Bank Transfer</option>
                                    <option value="cash" <?php echo e(old('payment_method') == 'cash' ? 'selected' : ''); ?>>Cash</option>
                                    <option value="bkash" <?php echo e(old('payment_method') == 'bkash' ? 'selected' : ''); ?>>Bkash</option>
                                    <option value="nagad" <?php echo e(old('payment_method') == 'nagad' ? 'selected' : ''); ?>>Nagad</option>
                                    <option value="check" <?php echo e(old('payment_method') == 'check' ? 'selected' : ''); ?>>Check</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label-custom">Transaction / Check ID</label>
                                <input type="text" name="transaction_id" class="form-control form-control-custom" value="<?php echo e(old('transaction_id')); ?>" placeholder="Optional">
                            </div>
                        </div>

                        
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label-custom">Bank/Provider Name</label>
                                <input type="text" name="bank_name" class="form-control form-control-custom" value="<?php echo e(old('bank_name')); ?>" placeholder="e.g. City Bank">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label-custom">Account Number</label>
                                <input type="text" name="account_number" class="form-control form-control-custom" value="<?php echo e(old('account_number')); ?>" placeholder="Account No.">
                            </div>
                        </div>

                        
                        <div class="mb-4">
                            <label class="form-label-custom">Notes</label>
                            <textarea name="notes" class="form-control form-control-custom" rows="2" placeholder="Any comments..."><?php echo e(old('notes')); ?></textarea>
                        </div>

                        
                        <div class="fund-alert mb-4 d-flex align-items-center">
                            <i data-feather="alert-triangle" class="me-2"></i>
                            <div>
                                <strong>Warning:</strong> This amount will be deducted from your main fund. <br>
                                Current Balance: <strong>৳<?php echo e(number_format(\App\Helpers\FundHelper::balance(), 2)); ?></strong>
                            </div>
                        </div>

                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary py-2 fw-bold shadow-sm">
                                <i data-feather="check-circle" class="me-1" style="width: 16px;"></i> Confirm Payment
                            </button>
                            <a href="<?php echo e(route('admin.salary_payments.index')); ?>" class="btn btn-light py-2">Cancel</a>
                        </div>

                    </div>
                </div>
            </form>

        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
    $(document).ready(function() {
        if ($.fn.select2) {
            $('.select2').select2({ width: '100%' });
        }

        // Auto-fill amount when salary is selected
        $('select[name="salary_id"]').on('change', function() {
            var selectedOption = $(this).find('option:selected');
            var amount = selectedOption.data('amount'); // Use data attribute for cleaner value
            
            if (!amount) {
                // Fallback to regex if data-attribute isn't used
                var salaryText = selectedOption.text();
                var match = salaryText.match(/৳([\d,]+\.?\d*)/);
                if (match) {
                    amount = match[1].replace(/,/g, '');
                }
            }

            if (amount) {
                $('#amount').val(amount);
                // Highlight input briefly
                $('#amount').css('background-color', '#dcfce7').animate({backgroundColor: '#fff'}, 1000);
            }
        });
    });
</script>
<?php $__env->stopPush(); ?>
<?php echo $__env->make('backEnd.layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\bilaiGhor\resources\views\backEnd\salary_payments\create.blade.php ENDPATH**/ ?>