

<?php $__env->startSection('title', 'Expense Logs / Reports'); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid">

    
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-1">
                <i data-feather="file-text" class="me-1"></i>
                Expense Logs / Reports
            </h4>
            <small class="text-muted">
                সম্পাদিত এবং মুছে ফেলা Expense-এর বিস্তারিত রিপোর্ট
            </small>
        </div>

        <div>
            <a href="<?php echo e(route('admin.expenses.index')); ?>" class="btn btn-sm btn-outline-secondary">
                <i data-feather="arrow-left" class="me-1"></i> Back to Expenses
            </a>
        </div>
    </div>

    
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="<?php echo e(route('admin.expenses.logs')); ?>" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Action</label>
                    <select name="action" class="form-select">
                        <option value="">All Actions</option>
                        <option value="edit" <?php echo e(request('action') == 'edit' ? 'selected' : ''); ?>>Edit</option>
                        <option value="delete" <?php echo e(request('action') == 'delete' ? 'selected' : ''); ?>>Delete</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">From Date</label>
                    <input type="date" name="from_date" class="form-control" value="<?php echo e(request('from_date')); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">To Date</label>
                    <input type="date" name="to_date" class="form-control" value="<?php echo e(request('to_date')); ?>">
                </div>
                <div class="col-md-3 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary">Filter</button>
                    <a href="<?php echo e(route('admin.expenses.logs')); ?>" class="btn btn-outline-secondary">Clear</a>
                </div>
            </form>
        </div>
    </div>

    
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5 class="mb-1" style="color:#fff !important;">Total Edits</h5>
                    <h3 class="mb-0" style="color:#fff !important;"><?php echo e($total_edits); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <h5 class="mb-1" style="color:#fff !important;">Total Deletes</h5>
                    <h3 class="mb-0" style="color:#fff !important;"><?php echo e($total_deletes); ?></h3>
                </div>
            </div>
        </div>
    </div>

    
    <div class="card">
        <div class="card-header bg-light">
            <strong>Expense Logs / Reports</strong>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-bordered table-striped align-middle">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Action</th>
                        <th>Expense ID</th>
                        <th>Old Values</th>
                        <th>New Values</th>
                        <th>Fund Balance Change</th>
                        <th>Description</th>
                        <th>Performed By</th>
                        <th>Date & Time</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $logs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $log): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td><?php echo e($log->id); ?></td>
                            <td>
                                <?php if($log->action == 'edit'): ?>
                                    <span class="badge bg-warning">Edit</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Delete</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if($log->expense): ?>
                                    #<?php echo e($log->expense_id); ?>

                                <?php else: ?>
                                    <span class="text-muted">#<?php echo e($log->expense_id); ?> (Deleted)</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if($log->old_title): ?>
                                    <div><strong>Title:</strong> <?php echo e($log->old_title); ?></div>
                                    <div><strong>Amount:</strong> <?php echo e(number_format($log->old_amount, 2)); ?> ৳</div>
                                    <div><strong>Date:</strong> <?php echo e($log->old_expense_date ? \Carbon\Carbon::parse($log->old_expense_date)->format('d M Y') : '-'); ?></div>
                                    <?php if($log->old_category): ?>
                                        <div><strong>Category:</strong> <?php echo e($log->old_category); ?></div>
                                    <?php endif; ?>
                                    <?php if($log->old_note): ?>
                                        <div><strong>Note:</strong> <?php echo e(Str::limit($log->old_note, 30)); ?></div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if($log->new_title): ?>
                                    <div><strong>Title:</strong> <?php echo e($log->new_title); ?></div>
                                    <div><strong>Amount:</strong> <?php echo e(number_format($log->new_amount, 2)); ?> ৳</div>
                                    <div><strong>Date:</strong> <?php echo e($log->new_expense_date ? \Carbon\Carbon::parse($log->new_expense_date)->format('d M Y') : '-'); ?></div>
                                    <?php if($log->new_category): ?>
                                        <div><strong>Category:</strong> <?php echo e($log->new_category); ?></div>
                                    <?php endif; ?>
                                    <?php if($log->new_note): ?>
                                        <div><strong>Note:</strong> <?php echo e(Str::limit($log->new_note, 30)); ?></div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div><strong>Before:</strong> <?php echo e(number_format($log->fund_balance_before, 2)); ?> ৳</div>
                                <div><strong>After:</strong> <?php echo e(number_format($log->fund_balance_after, 2)); ?> ৳</div>
                                <?php
                                    $balance_diff = $log->fund_balance_after - $log->fund_balance_before;
                                ?>
                                <div>
                                    <strong>Change:</strong> 
                                    <span class="<?php echo e($balance_diff >= 0 ? 'text-success' : 'text-danger'); ?>">
                                        <?php echo e($balance_diff >= 0 ? '+' : ''); ?><?php echo e(number_format($balance_diff, 2)); ?> ৳
                                    </span>
                                </div>
                            </td>
                            <td>
                                <small><?php echo e($log->description); ?></small>
                            </td>
                            <td>
                                <?php if($log->performedBy): ?>
                                    <?php echo e($log->performedBy->name); ?>

                                <?php else: ?>
                                    <span class="text-muted">User #<?php echo e($log->performed_by); ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo e($log->created_at->format('d M Y, h:i A')); ?></td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="9" class="text-center text-muted">
                                No expense logs found.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            
            <div class="mt-3">
                <?php echo e($logs->links('pagination::bootstrap-4')); ?>

            </div>
        </div>
    </div>

</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('backEnd.layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\bilaiGhor\resources\views\backEnd\expenses\logs.blade.php ENDPATH**/ ?>