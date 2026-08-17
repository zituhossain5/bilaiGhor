
<?php $__env->startSection('title','Expenses'); ?>

<?php
    use Illuminate\Support\Facades\Auth;
    // Check if current user is Admin (Super Admin or has Admin role)
    $isAdmin = false;
    $user = Auth::guard('admin')->user();
    if ($user) {
        if ($user->id == 1) {
            $isAdmin = true;
        } else {
            $spatieRoles = $user->getRoleNames()->map(function($role) {
                return strtolower($role);
            })->toArray();
            $isAdmin = in_array('admin', $spatieRoles);
        }
    }
?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid">

    <div class="d-flex align-items-center mb-3">
        <h4 class="mb-0">Expenses / খরচ</h4>
    </div>

    
    <div class="row mb-4">

      
<div class="col-md-3 mb-3">
    <div class="card bg-success text-white" style="color:#fff !important;">
        <div class="card-body" style="color:#fff !important;">
            <h5 class="mb-1" style="color:#fff !important;">Available Balance</h5>
            <h2 class="mb-0" style="color:#fff !important;"><?php echo e(number_format($balance, 2)); ?> ৳</h2>
            <small class="opacity-75 d-block mt-1" style="color:#fff !important;">
                বর্তমানে তহবিলে অবশিষ্ট ব্যালেন্স
            </small>
        </div>
    </div>
</div>


<div class="col-md-3 mb-3">
    <div class="card bg-primary text-white" style="color:#fff !important;">
        <div class="card-body" style="color:#fff !important;">
            <h5 class="mb-1" style="color:#fff !important;">This Year (<?php echo e($currentYear); ?>)</h5>
            <h3 class="mb-0" style="color:#fff !important;"><?php echo e(number_format($yearlyExpense, 2)); ?> ৳</h3>
            <small class="opacity-75 d-block mt-1" style="color:#fff !important;">
                এই বছরে মোট খরচ হয়েছে
            </small>
        </div>
    </div>
</div>


<div class="col-md-3 mb-3">
    <div class="card bg-info text-white" style="color:#fff !important;">
        <div class="card-body" style="color:#fff !important;">
            <h5 class="mb-1" style="color:#fff !important;">
                This Month (<?php echo e(\Carbon\Carbon::createFromDate(now()->year, $currentMonth, 1)->format('F')); ?>)
            </h5>
            <h3 class="mb-0" style="color:#fff !important;"><?php echo e(number_format($monthlyExpense, 2)); ?> ৳</h3>
            <small class="opacity-75 d-block mt-1" style="color:#fff !important;">
                এই মাসে মোট খরচ হয়েছে
            </small>
        </div>
    </div>
</div>


<div class="col-md-3 mb-3">
    <div class="card bg-danger text-white" style="color:#fff !important;">
        <div class="card-body" style="color:#fff !important;">
            <h5 class="mb-1" style="color:#fff !important;">Today (<?php echo e(now()->format('d M, Y')); ?>)</h5>
            <h3 class="mb-0" style="color:#fff !important;"><?php echo e(number_format($todayExpense, 2)); ?> ৳</h3>
            <small class="opacity-75 d-block mt-1" style="color:#fff !important;">
                আজকে মোট খরচ হয়েছে
            </small>
        </div>
    </div>
</div>


    </div>

    
    <div class="row">

        
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm">
                <div class="card-header">
                    <strong>+ Add Expense</strong>
                </div>
                <div class="card-body">

                    <form action="<?php echo e(route('admin.expenses.store')); ?>" method="POST">
                        <?php echo csrf_field(); ?>

                        <div class="mb-3">
                            <label class="form-label">Title *</label>
                            <input type="text"
                                   name="title"
                                   class="form-control <?php $__errorArgs = ['title'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                   value="<?php echo e(old('title')); ?>"
                                   required>
                            <?php $__errorArgs = ['title'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <span class="invalid-feedback"><?php echo e($message); ?></span>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Amount (৳) *</label>
                            <input type="number"
                                   step="0.01"
                                   name="amount"
                                   class="form-control <?php $__errorArgs = ['amount'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                   value="<?php echo e(old('amount')); ?>"
                                   required>
                            <?php $__errorArgs = ['amount'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <span class="invalid-feedback"><?php echo e($message); ?></span>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Date *</label>
                            <input type="date"
                                   name="expense_date"
                                   class="form-control <?php $__errorArgs = ['expense_date'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                   value="<?php echo e(old('expense_date', now()->format('Y-m-d'))); ?>"
                                   required>
                            <?php $__errorArgs = ['expense_date'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <span class="invalid-feedback"><?php echo e($message); ?></span>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Category (optional)</label>
                            <input type="text"
                                   name="category"
                                   class="form-control"
                                   value="<?php echo e(old('category')); ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Note (optional)</label>
                            <textarea name="note"
                                      class="form-control"
                                      rows="3"><?php echo e(old('note')); ?></textarea>
                        </div>

                        <button type="submit" class="btn btn-danger">
                            Save Expense
                        </button>
                    </form>

                </div>
            </div>
        </div>

        
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm">
                <div class="card-header">
                    <strong>📤 Export Report</strong>
                </div>
                <div class="card-body">
                    <form action="<?php echo e(route('admin.expenses.export')); ?>" method="GET" target="_blank">
                        <div class="mb-3">
                            <label class="form-label">From Date</label>
                            <input type="date" name="from_date" class="form-control"
                                   value="<?php echo e(request('from_date')); ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">To Date</label>
                            <input type="date" name="to_date" class="form-control"
                                   value="<?php echo e(request('to_date')); ?>">
                        </div>

                        <button type="submit" class="btn btn-outline-primary w-100">
                            ⬇ Download CSV
                        </button>
                    </form>
                </div>
            </div>
        </div>

    </div>

    
    <div class="card shadow-sm mt-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <strong>🧾 Expense History</strong>
            <div>
                <a href="<?php echo e(route('admin.expenses.logs')); ?>" class="btn btn-sm btn-outline-info">
                    <i data-feather="file-text" class="me-1" style="width:14px;height:14px;"></i> View Logs / Reports
                </a>
            </div>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-bordered table-hover align-middle">
                <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Date</th>
                    <th>Title</th>
                    <th>Category</th>
                    <th class="text-end">Amount (৳)</th>
                    <th>Note</th>
                    <?php if($isAdmin): ?>
                    <th>Actions</th>
                    <?php endif; ?>
                </tr>
                </thead>
                <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $expenses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $exp): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td><?php echo e($loop->iteration + ($expenses->currentPage() - 1)*$expenses->perPage()); ?></td>
                        <td><?php echo e(\Carbon\Carbon::parse($exp->expense_date)->format('d M, Y')); ?></td>
                        <td>
                            <?php echo e($exp->title); ?>

                            <?php if($exp->updated_by): ?>
                                <span class="badge bg-warning ms-1" title="This expense has been edited">
                                    <i class="fe-edit" style="width:12px;height:12px;"></i> Edited
                                </span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo e($exp->category ?? '-'); ?></td>
                        <td class="text-end"><?php echo e(number_format($exp->amount, 2)); ?></td>
                        <td><?php echo e($exp->note); ?></td>
                        <?php if($isAdmin): ?>
                        <td>
                            <div class="d-flex gap-2">
                                <a href="<?php echo e(route('admin.expenses.edit', $exp->id)); ?>" class="btn btn-sm btn-outline-primary" title="Edit">
                                    <i class="fe-edit"></i>
                                </a>
                                <form method="POST" action="<?php echo e(route('admin.expenses.destroy', $exp->id)); ?>" class="d-inline delete-form">
                                    <?php echo csrf_field(); ?>
                                    <?php echo method_field('DELETE'); ?>
                                    <button type="submit" class="btn btn-sm btn-outline-danger delete-confirm" title="Delete" onclick="return confirm('Are you sure you want to delete this expense?');">
                                        <i class="fe-trash-2"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="<?php echo e($isAdmin ? 7 : 6); ?>" class="text-center text-muted">
                            কোনো খরচের রেকর্ড পাওয়া যায়নি।
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>

            <?php echo e($expenses->links()); ?>

        </div>
    </div>

</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('backEnd.layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\bilaiGhor\resources\views\backEnd\expenses\index.blade.php ENDPATH**/ ?>