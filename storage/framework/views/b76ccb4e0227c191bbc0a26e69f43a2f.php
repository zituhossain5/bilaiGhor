
<?php $__env->startSection('title', 'Salary Details'); ?>

<?php $__env->startSection('css'); ?>
<style>
    /* --- Payslip Card --- */
    .payslip-card {
        border: none;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        background: #fff;
        max-width: 900px;
        margin: 0 auto;
    }
    .payslip-header {
        background: linear-gradient(135deg, #1e293b, #334155);
        color: #fff;
        padding: 2rem;
        border-radius: 12px 12px 0 0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    /* --- Info Section --- */
    .emp-info-box {
        padding: 1.5rem 2rem;
        border-bottom: 1px dashed #e2e8f0;
    }
    .info-label { font-size: 0.8rem; text-transform: uppercase; color: #64748b; font-weight: 600; letter-spacing: 0.5px; }
    .info-value { font-size: 1rem; color: #1e293b; font-weight: 500; }

    /* --- Breakdown Table --- */
    .breakdown-section { padding: 2rem; }
    .breakdown-title {
        font-size: 0.9rem; font-weight: 700; text-transform: uppercase; 
        color: #334155; margin-bottom: 1rem; border-bottom: 2px solid #f1f5f9; padding-bottom: 0.5rem;
    }
    .breakdown-row {
        display: flex; justify-content: space-between; margin-bottom: 0.8rem;
        font-size: 0.95rem; color: #475569;
    }
    .amount { font-weight: 600; color: #1e293b; }
    .amount-deduct { color: #ef4444; }

    /* --- Net Salary Box --- */
    .net-salary-box {
        background: #f8fafc;
        border-top: 1px solid #e2e8f0;
        padding: 1.5rem 2rem;
        border-radius: 0 0 12px 12px;
        display: flex; justify-content: space-between; align-items: center;
    }
    .net-label { font-size: 1.1rem; font-weight: 700; color: #334155; }
    .net-amount { font-size: 1.8rem; font-weight: 800; color: #16a34a; }

    /* --- Badges --- */
    .badge-status {
        padding: 6px 12px; border-radius: 6px; font-size: 0.8rem; font-weight: 600;
        background: rgba(255, 255, 255, 0.2); color: #fff; backdrop-filter: blur(5px);
    }
</style>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid py-4">

    
    <div class="d-flex justify-content-between align-items-center mb-4 max-width-900 mx-auto" style="max-width: 900px;">
        <a href="<?php echo e(route('admin.salaries.index')); ?>" class="btn btn-white border shadow-sm rounded-pill px-3">
            <i data-feather="arrow-left" class="me-1" style="width: 16px;"></i> Back to List
        </a>
        
        <?php if($salary->status == 'calculated'): ?>
            <a href="<?php echo e(route('admin.salary_payments.create', ['employee_id' => $salary->employee_id, 'salary_id' => $salary->id])); ?>" class="btn btn-primary px-4 rounded-pill shadow-sm">
                <i data-feather="credit-card" class="me-2" style="width: 16px;"></i> Process Payment
            </a>
        <?php endif; ?>
    </div>

    
    <div class="payslip-card">
        
        
        <div class="payslip-header">
            <div>
                <h4 class="mb-1 fw-bold text-white">Salary Slip</h4>
                <div class="opacity-75 small">Month: <?php echo e(\Carbon\Carbon::parse($salary->salary_month)->format('F Y')); ?></div>
            </div>
            <div>
                <span class="badge-status">
                    <?php echo e(ucfirst($salary->status)); ?>

                </span>
            </div>
        </div>

        
        <div class="emp-info-box">
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="info-label">Employee Name</div>
                    <div class="info-value"><?php echo e($salary->employee->name); ?></div>
                </div>
                <div class="col-md-4">
                    <div class="info-label">Employee ID</div>
                    <div class="info-value"><?php echo e($salary->employee->employee_id); ?></div>
                </div>
                <div class="col-md-4">
                    <div class="info-label">Department</div>
                    <div class="info-value"><?php echo e($salary->employee->department ?? 'N/A'); ?></div>
                </div>
                <div class="col-md-4">
                    <div class="info-label">Designation</div>
                    <div class="info-value"><?php echo e($salary->employee->designation ?? 'N/A'); ?></div>
                </div>
                <div class="col-md-4">
                    <div class="info-label">Working Days</div>
                    <div class="info-value"><?php echo e($salary->working_days); ?> Days</div>
                </div>
                <div class="col-md-4">
                    <div class="info-label">Attendance</div>
                    <div class="info-value text-success">P: <?php echo e($salary->present_days); ?> <span class="text-muted mx-1">|</span> <span class="text-danger">A: <?php echo e($salary->absent_days); ?></span></div>
                </div>
            </div>
        </div>

        
        <div class="breakdown-section">
            <div class="row">
                <div class="col-md-6 border-end pe-md-4">
                    <div class="breakdown-title text-success">Earnings</div>
                    
                    <div class="breakdown-row">
                        <span>Basic Salary</span>
                        <span class="amount">৳<?php echo e(number_format($salary->basic_salary, 2)); ?></span>
                    </div>
                    <div class="breakdown-row">
                        <span>Allowances</span>
                        <span class="amount">৳<?php echo e(number_format($salary->allowance, 2)); ?></span>
                    </div>
                    <div class="breakdown-row">
                        <span>Bonus</span>
                        <span class="amount">৳<?php echo e(number_format($salary->bonus, 2)); ?></span>
                    </div>
                    <div class="breakdown-row">
                        <span>Overtime</span>
                        <span class="amount">৳<?php echo e(number_format($salary->overtime, 2)); ?></span>
                    </div>
                    
                    <div class="breakdown-row mt-3 pt-2 border-top">
                        <span class="fw-bold text-dark">Gross Salary</span>
                        <span class="fw-bold text-dark">৳<?php echo e(number_format($salary->gross_salary, 2)); ?></span>
                    </div>
                </div>

                <div class="col-md-6 ps-md-4 mt-4 mt-md-0">
                    <div class="breakdown-title text-danger">Deductions</div>
                    
                    <div class="breakdown-row">
                        <span>Absent Penalty</span>
                        <span class="amount-deduct">Coming Soon</span>
                    </div>
                    <div class="breakdown-row">
                        <span>Other Deductions</span>
                        <span class="amount-deduct">-৳<?php echo e(number_format($salary->deduction, 2)); ?></span>
                    </div>

                    <div class="breakdown-row mt-3 pt-2 border-top">
                        <span class="fw-bold text-dark">Total Deduction</span>
                        <span class="fw-bold text-danger">-৳<?php echo e(number_format($salary->deduction, 2)); ?></span>
                    </div>
                </div>
            </div>
        </div>

        
        <div class="net-salary-box">
            <div>
                <div class="net-label">Net Payable Salary</div>
                <small class="text-muted fst-italic">*Final amount to be paid to employee</small>
            </div>
            <div class="net-amount">৳<?php echo e(number_format($salary->net_salary, 2)); ?></div>
        </div>

        
        <?php if($salary->notes): ?>
        <div class="p-4 bg-light border-top">
            <h6 class="fw-bold text-dark mb-2">Additional Notes</h6>
            <p class="text-muted small mb-0"><?php echo e($salary->notes); ?></p>
        </div>
        <?php endif; ?>

    </div>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('backEnd.layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\bilaiGhor\resources\views\backEnd\salaries\show.blade.php ENDPATH**/ ?>