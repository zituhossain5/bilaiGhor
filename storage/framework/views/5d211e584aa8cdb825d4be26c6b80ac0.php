
<?php $__env->startSection('title', 'Purchase Logs'); ?>

<?php $__env->startSection('css'); ?>
<style>
    /* --- Modern Card & Layout --- */
    .card-modern {
        border: none;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        transition: all 0.3s ease;
        background: #fff;
    }
    
    /* --- Stats Widgets --- */
    .stats-card {
        display: flex;
        align-items: center;
        padding: 1.5rem;
        border-radius: 12px;
        background: #fff;
        box-shadow: 0 2px 10px rgba(0,0,0,0.03);
        border: 1px solid #f1f5f9;
    }
    .stats-icon {
        width: 48px;
        height: 48px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        margin-right: 1rem;
    }
    
    /* --- Table Styles --- */
    .table thead th {
        background-color: #f8f9fc;
        color: #64748b;
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-bottom: 1px solid #e2e8f0;
        padding: 1rem;
    }
    .table tbody td {
        font-size: 0.875rem;
        vertical-align: top;
        padding: 1rem;
        color: #334155;
        border-bottom: 1px solid #f1f5f9;
    }
    
    /* --- Data List within Table --- */
    .data-list { list-style: none; padding: 0; margin: 0; font-size: 0.8rem; }
    .data-list li { margin-bottom: 4px; display: flex; }
    .data-label { font-weight: 600; color: #64748b; width: 60px; display: inline-block; }
    .data-value { color: #1e293b; font-weight: 500; }

    /* --- Badges & Indicators --- */
    .badge-soft-warning { background-color: #fff7ed; color: #c2410c; border: 1px solid #ffedd5; }
    .badge-soft-danger  { background-color: #fef2f2; color: #b91c1c; border: 1px solid #fee2e2; }
    
    .bg-icon-primary { background: rgba(59, 130, 246, 0.1); color: #3b82f6; }
    .bg-icon-danger { background: rgba(239, 68, 68, 0.1); color: #ef4444; }

    .fund-badge {
        padding: 4px 8px;
        border-radius: 6px;
        font-size: 0.75rem;
        font-weight: 600;
        display: inline-block;
    }
    .fund-up { background: #dcfce7; color: #166534; }
    .fund-down { background: #fee2e2; color: #991b1b; }
</style>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid mb-5">

    
    <div class="d-flex justify-content-between align-items-center mb-4 mt-2">
        <div>
            <h4 class="mb-1 fw-bold text-gray-800">
                <i data-feather="activity" class="text-primary me-1"></i> Purchase Logs
            </h4>
            <p class="text-muted small mb-0">Audit trail of edited and deleted purchase records.</p>
        </div>
        <a href="<?php echo e(route('purchases.index')); ?>" class="btn btn-white border shadow-sm rounded-pill px-3">
            <i data-feather="arrow-left" class="me-1"></i> Back to Purchases
        </a>
    </div>

    
    <div class="row mb-4">
        <div class="col-md-6 mb-3 mb-md-0">
            <div class="stats-card">
                <div class="stats-icon bg-icon-primary">
                    <i data-feather="edit-2"></i>
                </div>
                <div>
                    <h3 class="mb-0 fw-bold text-dark"><?php echo e($total_edits); ?></h3>
                    <small class="text-muted text-uppercase fw-bold">Total Edited Records</small>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="stats-card">
                <div class="stats-icon bg-icon-danger">
                    <i data-feather="trash-2"></i>
                </div>
                <div>
                    <h3 class="mb-0 fw-bold text-dark"><?php echo e($total_deletes); ?></h3>
                    <small class="text-muted text-uppercase fw-bold">Total Deleted Records</small>
                </div>
            </div>
        </div>
    </div>

    
    <div class="card card-modern">
        
        
        <div class="card-header bg-white border-bottom p-4">
            <form method="GET" action="<?php echo e(route('purchases.logs')); ?>">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-muted">Action Type</label>
                        <select name="action" class="form-select">
                            <option value="">All Actions</option>
                            <option value="edit" <?php echo e(request('action') == 'edit' ? 'selected' : ''); ?>>Edit</option>
                            <option value="delete" <?php echo e(request('action') == 'delete' ? 'selected' : ''); ?>>Delete</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-muted">From Date</label>
                        <input type="date" name="from_date" class="form-control" value="<?php echo e(request('from_date')); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-muted">To Date</label>
                        <input type="date" name="to_date" class="form-control" value="<?php echo e(request('to_date')); ?>">
                    </div>
                    <div class="col-md-3 d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-grow-1">
                            <i data-feather="filter" class="me-1" style="width:14px;"></i> Filter
                        </button>
                        <a href="<?php echo e(route('purchases.logs')); ?>" class="btn btn-light border">
                            <i data-feather="refresh-cw" style="width:14px;"></i>
                        </a>
                    </div>
                </div>
            </form>
        </div>

        
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th width="5%">#</th>
                            <th width="10%">Action</th>
                            <th width="15%">Reference</th>
                            <th width="20%">Previous Data</th>
                            <th width="20%">New Data</th>
                            <th width="15%">Fund Impact</th>
                            <th width="15%">User & Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__empty_1 = true; $__currentLoopData = $logs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $log): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr>
                                <td class="text-muted"><?php echo e($log->id); ?></td>
                                
                                
                                <td>
                                    <?php if($log->action == 'edit'): ?>
                                        <span class="badge badge-soft-warning px-3 py-2 rounded-pill">
                                            <i data-feather="edit-2" style="width:10px;"></i> Edited
                                        </span>
                                    <?php else: ?>
                                        <span class="badge badge-soft-danger px-3 py-2 rounded-pill">
                                            <i data-feather="trash" style="width:10px;"></i> Deleted
                                        </span>
                                    <?php endif; ?>
                                </td>

                                
                                <td>
                                    <div class="fw-bold text-dark">
                                        <?php if($log->purchase): ?>
                                            #<?php echo e($log->purchase->invoice_no ?? $log->purchase_id); ?>

                                        <?php else: ?>
                                            #<?php echo e($log->old_invoice_no ?? $log->purchase_id); ?>

                                        <?php endif; ?>
                                    </div>
                                    <small class="text-muted d-block mt-1">ID: <?php echo e($log->purchase_id); ?></small>
                                </td>

                                
                                <td>
                                    <?php if($log->old_invoice_no): ?>
                                        <ul class="data-list">
                                            <li><span class="data-label">Inv:</span> <span class="data-value"><?php echo e($log->old_invoice_no); ?></span></li>
                                            <li><span class="data-label">Date:</span> <span class="data-value"><?php echo e($log->old_purchase_date ? \Carbon\Carbon::parse($log->old_purchase_date)->format('d M y') : '-'); ?></span></li>
                                            <li><span class="data-label">Paid:</span> <span class="data-value text-secondary"><?php echo e(number_format($log->old_paid_amount, 2)); ?></span></li>
                                            <li><span class="data-label">Total:</span> <span class="data-value"><?php echo e(number_format($log->old_grand_total, 2)); ?></span></li>
                                        </ul>
                                    <?php else: ?>
                                        <span class="text-muted small">N/A</span>
                                    <?php endif; ?>
                                </td>

                                
                                <td>
                                    <?php if($log->new_invoice_no): ?>
                                        <ul class="data-list">
                                            <li><span class="data-label">Inv:</span> <span class="data-value"><?php echo e($log->new_invoice_no); ?></span></li>
                                            <li><span class="data-label">Date:</span> <span class="data-value"><?php echo e($log->new_purchase_date ? \Carbon\Carbon::parse($log->new_purchase_date)->format('d M y') : '-'); ?></span></li>
                                            <li><span class="data-label">Paid:</span> <span class="data-value fw-bold text-dark"><?php echo e(number_format($log->new_paid_amount, 2)); ?></span></li>
                                            <li><span class="data-label">Total:</span> <span class="data-value"><?php echo e(number_format($log->new_grand_total, 2)); ?></span></li>
                                        </ul>
                                    <?php else: ?>
                                        <span class="text-muted small fst-italic">Record Deleted</span>
                                    <?php endif; ?>
                                </td>

                                
                                <td>
                                    <?php
                                        $diff = $log->fund_balance_after - $log->fund_balance_before;
                                    ?>
                                    
                                    <div class="mb-1 text-muted small">Balance:</div>
                                    <div class="small"><?php echo e(number_format($log->fund_balance_before, 2)); ?> ➝ <?php echo e(number_format($log->fund_balance_after, 2)); ?></div>
                                    
                                    <div class="mt-2">
                                        <?php if($diff > 0): ?>
                                            <span class="fund-badge fund-up">
                                                <i data-feather="arrow-up-right" style="width:12px;"></i> <?php echo e(number_format(abs($diff), 2)); ?>

                                            </span>
                                        <?php elseif($diff < 0): ?>
                                            <span class="fund-badge fund-down">
                                                <i data-feather="arrow-down-right" style="width:12px;"></i> <?php echo e(number_format(abs($diff), 2)); ?>

                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-light text-muted border">No Change</span>
                                        <?php endif; ?>
                                    </div>
                                </td>

                                
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-sm bg-light rounded-circle d-flex align-items-center justify-content-center text-primary fw-bold me-2" style="width:30px; height:30px; font-size:12px;">
                                            <?php echo e($log->performedBy ? substr($log->performedBy->name, 0, 1) : 'U'); ?>

                                        </div>
                                        <div>
                                            <div class="fw-bold text-dark small"><?php echo e($log->performedBy->name ?? 'Unknown'); ?></div>
                                            <div class="text-muted" style="font-size: 11px;">
                                                <?php echo e($log->created_at->format('d M, h:i A')); ?>

                                            </div>
                                        </div>
                                    </div>
                                    <?php if($log->description): ?>
                                        <div class="mt-2 text-muted small fst-italic border-top pt-1">
                                            "<?php echo e(Str::limit($log->description, 20)); ?>"
                                        </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <img src="https://cdn-icons-png.flaticon.com/512/7486/7486744.png" width="60" class="mb-3 opacity-25">
                                    <p class="text-muted fw-bold">No Activity Logs Found</p>
                                    <small class="text-muted">Try changing the date filter or action type.</small>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            
            <div class="p-3 border-top d-flex justify-content-end">
                <?php echo e($logs->links('pagination::bootstrap-4')); ?>

            </div>
        </div>
    </div>

</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('backEnd.layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\bilaiGhor\resources\views\backEnd\purchases\logs.blade.php ENDPATH**/ ?>