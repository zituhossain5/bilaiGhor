
<?php $__env->startSection('title','Edit Expense'); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid">

    
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-1">
                <i data-feather="edit-3" class="me-1"></i>
                Edit Expense / খরচ এডিট
            </h4>
            <small class="text-muted">
                এখানে তুমি খরচের তথ্য আপডেট করতে পারো।
            </small>
        </div>

        <div>
            <a href="<?php echo e(route('admin.expenses.index')); ?>" class="btn btn-sm btn-outline-secondary">
                <i data-feather="arrow-left" class="me-1"></i> Back to List
            </a>
        </div>
    </div>

    
    <div class="row mb-4">

        
        <div class="col-md-3 mb-3">
            <div class="card border-0 shadow-sm bg-success" style="color:white; border-radius: 12px;">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <h6 class="mb-0" style="color:white;">Available Balance</h6>
                        <i data-feather="wallet" style="width:18px;height:18px;"></i>
                    </div>
                    <h2 class="mb-0 fw-bold" style="color:white;">
                        <?php echo e(number_format($balance, 2)); ?> ৳
                    </h2>
                    <small class="d-block mt-1" style="color:white;opacity: .9;">
                        বর্তমানে তহবিলে অবশিষ্ট ব্যালেন্স
                    </small>
                </div>
            </div>
        </div>

        
        <div class="col-md-3 mb-3">
            <div class="card border-0 shadow-sm bg-primary" style="color:white; border-radius: 12px;">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <h6 class="mb-0" style="color:white;">This Year (<?php echo e($currentYear); ?>)</h6>
                        <i data-feather="calendar" style="width:18px;height:18px;"></i>
                    </div>
                    <h3 class="mb-0 fw-bold" style="color:white;">
                        <?php echo e(number_format($yearlyExpense, 2)); ?> ৳
                    </h3>
                    <small class="d-block mt-1" style="color:white;opacity: .9;">
                        এই বছরে মোট খরচ হয়েছে
                    </small>
                </div>
            </div>
        </div>

        
        <div class="col-md-3 mb-3">
            <div class="card border-0 shadow-sm bg-info" style="color:white; border-radius: 12px;">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <h6 class="mb-0" style="color:white;">
                            This Month (
                            <?php echo e(\Carbon\Carbon::createFromDate(now()->year, $currentMonth, 1)->format('F')); ?>

                            )
                        </h6>
                        <i data-feather="calendar-clock" style="width:18px;height:18px;"></i>
                    </div>
                    <h3 class="mb-0 fw-bold" style="color:white;">
                        <?php echo e(number_format($monthlyExpense, 2)); ?> ৳
                    </h3>
                    <small class="d-block mt-1" style="color:white;opacity: .9;">
                        এই মাসে মোট খরচ হয়েছে
                    </small>
                </div>
            </div>
        </div>

        
        <div class="col-md-3 mb-3">
            <div class="card border-0 shadow-sm bg-danger" style="color:white; border-radius: 12px;">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <h6 class="mb-0" style="color:white;">Today (<?php echo e(now()->format('d M, Y')); ?>)</h6>
                        <i data-feather="activity" style="width:18px;height:18px;"></i>
                    </div>
                    <h3 class="mb-0 fw-bold" style="color:white;">
                        <?php echo e(number_format($todayExpense, 2)); ?> ৳
                    </h3>
                    <small class="d-block mt-1" style="color:white;opacity: .9;">
                        আজকে মোট খরচ হয়েছে
                    </small>
                </div>
            </div>
        </div>

    </div>

    <div class="row">

        
        <div class="col-lg-5 mb-4">
            <div class="card shadow-sm border-0" style="border-radius: 12px;">
                <div class="card-header border-0 bg-light" style="border-radius: 12px 12px 0 0;">
                    <strong>
                        <i data-feather="file-text" class="me-1" style="width:16px;height:16px;"></i>
                        Edit Expense
                    </strong>
                </div>
                <div class="card-body">

                    <form action="<?php echo e(route('admin.expenses.update', $expense->id)); ?>" method="POST">
                        <?php echo csrf_field(); ?>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Title *</label>
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
                                   value="<?php echo e(old('title', $expense->title)); ?>"
                                   placeholder="expense title লিখুন"
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

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Amount (৳) *</label>
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
                                       value="<?php echo e(old('amount', $expense->amount)); ?>"
                                       placeholder="0.00"
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

                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Date *</label>
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
                                       value="<?php echo e(old(
                                            'expense_date',
                                            $expense->expense_date
                                                ? \Carbon\Carbon::parse($expense->expense_date)->format('Y-m-d')
                                                : now()->format('Y-m-d')
                                       )); ?>"
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
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Category (optional)</label>
                            <input type="text"
                                   name="category"
                                   class="form-control"
                                   value="<?php echo e(old('category', $expense->category)); ?>"
                                   placeholder="যেমন: Marketing, Office, Others">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Note (optional)</label>
                            <textarea name="note"
                                      class="form-control"
                                      rows="3"
                                      placeholder="এই খরচ সম্পর্কে বাড়তি নোট..."><?php echo e(old('note', $expense->note)); ?></textarea>
                        </div>

                        <div class="d-flex justify-content-between">
                            <button type="submit" class="btn btn-danger">
                                <i data-feather="save" class="me-1" style="width:16px;height:16px;"></i>
                                Update Expense
                            </button>

                            <a href="<?php echo e(route('admin.expenses.index')); ?>"
                               class="btn btn-outline-secondary">
                                Cancel
                            </a>
                        </div>

                    </form>

                </div>
            </div>
        </div>

        
        <div class="col-lg-7 mb-4">
            <div class="card shadow-sm border-0" style="border-radius: 12px;">
                <div class="card-header border-0 bg-light" style="border-radius: 12px 12px 0 0;">
                    <div class="d-flex justify-content-between align-items-center">
                        <strong>
                            <i data-feather="clock" class="me-1" style="width:16px;height:16px;"></i>
                            Last Expenses
                        </strong>
                        <small class="text-muted">
                            সাম্প্রতিক কিছু খরচের লিস্ট
                        </small>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 430px; overflow-y:auto;">
                        <table class="table table-sm table-hover table-striped mb-0 align-middle">
                            <thead class="table-light">
                            <tr>
                                <th style="width:60px;">#</th>
                                <th style="width:130px;">Date</th>
                                <th>Title</th>
                                <th class="text-end" style="width:120px;">Amount (৳)</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php $__empty_1 = true; $__currentLoopData = $expenses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $exp): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <tr <?php if($exp->id == $expense->id): ?> class="table-warning" <?php endif; ?>>
                                    <td><?php echo e($exp->id); ?></td>
                                    <td>
                                        <?php if($exp->expense_date): ?>
                                            <?php echo e(\Carbon\Carbon::parse($exp->expense_date)->format('d M, Y')); ?>

                                        <?php else: ?>
                                            <?php echo e(optional($exp->created_at)->format('d M, Y')); ?>

                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo e($exp->title); ?></td>
                                    <td class="text-end"><?php echo e(number_format($exp->amount, 2)); ?></td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-3">
                                        কোনো খরচের রেকর্ড পাওয়া যায়নি।
                                    </td>
                                </tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="p-2">
                        <?php echo e($expenses->links()); ?>

                    </div>
                </div>
            </div>
        </div>

    </div>

</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('backEnd.layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\bilaiGhor\resources\views\backEnd\expenses\edit.blade.php ENDPATH**/ ?>