
<?php $__env->startSection('title','Attendance Management'); ?>

<?php $__env->startSection('css'); ?>
<style>
    /* --- Card & Filter Styles --- */
    .card-modern {
        border: none;
        border-radius: 12px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02), 0 2px 4px -1px rgba(0, 0, 0, 0.02);
        background: #fff;
    }
    .filter-container {
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        padding: 1.25rem;
        border-radius: 12px 12px 0 0;
    }
    
    /* --- Form Elements --- */
    .form-control-modern, .form-select-modern {
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 0.6rem 1rem;
        font-size: 0.875rem;
        background-color: #fff;
    }
    .form-control-modern:focus {
        border-color: #6366f1;
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
    }

    /* --- Table Styles --- */
    .table-modern th {
        background-color: #fff;
        color: #64748b;
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        padding: 1rem;
        border-bottom: 2px solid #f1f5f9;
    }
    .table-modern td {
        padding: 1rem;
        vertical-align: middle;
        font-size: 0.875rem;
        color: #334155;
        border-bottom: 1px solid #f1f5f9;
    }
    .table-modern tr:last-child td { border-bottom: none; }
    .table-modern tr:hover td { background-color: #f8fafc; }

    /* --- Badges --- */
    .badge-soft {
        padding: 5px 10px;
        border-radius: 6px;
        font-size: 0.75rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }
    .badge-present { background: #dcfce7; color: #166534; }
    .badge-absent { background: #fee2e2; color: #991b1b; }
    .badge-late { background: #fef3c7; color: #92400e; }
    .badge-half { background: #e0f2fe; color: #075985; }
    .badge-holiday { background: #f1f5f9; color: #475569; }
    .status-dot { width: 6px; height: 6px; border-radius: 50%; background: currentColor; }

    /* --- Action Buttons --- */
    .btn-icon {
        width: 32px; height: 32px;
        display: inline-flex; align-items: center; justify-content: center;
        border-radius: 8px; transition: all 0.2s;
    }
    .btn-icon:hover { transform: translateY(-2px); }
    .btn-edit { background: #e0e7ff; color: #4338ca; }
    .btn-delete { background: #fee2e2; color: #991b1b; }
</style>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid py-4">

    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 fw-bold text-dark">
                <i data-feather="clock" class="text-primary me-2"></i> Attendance
            </h4>
            <p class="text-muted small mb-0">Track and manage employee attendance records.</p>
        </div>
        <a href="<?php echo e(route('admin.attendances.create')); ?>" class="btn btn-primary px-4 py-2 rounded-pill shadow-sm">
            <i data-feather="plus-circle" class="me-1" style="width: 16px;"></i> Mark Attendance
        </a>
    </div>

    <div class="card card-modern">
        
        
        <div class="filter-container">
            <form method="GET" action="<?php echo e(route('admin.attendances.index')); ?>">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-muted text-uppercase mb-1">Employee</label>
                        <select name="employee_id" class="form-control select2 form-select-modern">
                            <option value="">All Employees</option>
                            <?php $__currentLoopData = $employees; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $emp): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($emp->id); ?>" <?php echo e(request('employee_id') == $emp->id ? 'selected' : ''); ?>>
                                    <?php echo e($emp->name); ?> (<?php echo e($emp->employee_id); ?>)
                                </option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold text-muted text-uppercase mb-1">Date</label>
                        <input type="date" name="date" class="form-control form-control-modern" value="<?php echo e(request('date')); ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold text-muted text-uppercase mb-1">Month</label>
                        <input type="month" name="month" class="form-control form-control-modern" value="<?php echo e(request('month')); ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold text-muted text-uppercase mb-1">Status</label>
                        <select name="status" class="form-select form-select-modern">
                            <option value="">All Status</option>
                            <option value="present" <?php echo e(request('status') == 'present' ? 'selected' : ''); ?>>Present</option>
                            <option value="absent" <?php echo e(request('status') == 'absent' ? 'selected' : ''); ?>>Absent</option>
                            <option value="late" <?php echo e(request('status') == 'late' ? 'selected' : ''); ?>>Late</option>
                            <option value="half_day" <?php echo e(request('status') == 'half_day' ? 'selected' : ''); ?>>Half Day</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex gap-2">
                        <button type="submit" class="btn btn-dark w-100 fw-bold">Filter</button>
                        <a href="<?php echo e(route('admin.attendances.index')); ?>" class="btn btn-light border px-3" title="Reset">
                            <i data-feather="refresh-cw" style="width:16px;"></i>
                        </a>
                    </div>
                </div>
            </form>
        </div>

        
        <div class="table-responsive">
            <table class="table table-modern mb-0">
                <thead>
                    <tr>
                        <th width="5%">#</th>
                        <th width="25%">Employee Details</th>
                        <th width="15%">Date</th>
                        <th width="15%">Check In</th>
                        <th width="15%">Check Out</th>
                        <th width="15%">Status</th>
                        <th width="10%" class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $attendances; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $attendance): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td class="text-muted"><?php echo e($loop->iteration + ($attendances->currentPage()-1)*$attendances->perPage()); ?></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="bg-light text-primary fw-bold rounded-circle d-flex align-items-center justify-content-center me-2" 
                                         style="width: 32px; height: 32px; font-size: 12px; border: 1px solid #e0e7ff;">
                                        <?php echo e(substr($attendance->employee->name, 0, 1)); ?>

                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark"><?php echo e($attendance->employee->name); ?></div>
                                        <div class="small text-muted">ID: <?php echo e($attendance->employee->employee_id); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="fw-medium text-dark"><?php echo e($attendance->attendance_date->format('d M, Y')); ?></td>
                            <td class="text-muted"><?php echo e($attendance->check_in ? \Carbon\Carbon::parse($attendance->check_in)->format('h:i A') : '-'); ?></td>
                            <td class="text-muted"><?php echo e($attendance->check_out ? \Carbon\Carbon::parse($attendance->check_out)->format('h:i A') : '-'); ?></td>
                            <td>
                                <?php if($attendance->status == 'present'): ?>
                                    <span class="badge-soft badge-present"><span class="status-dot"></span> Present</span>
                                <?php elseif($attendance->status == 'absent'): ?>
                                    <span class="badge-soft badge-absent"><span class="status-dot"></span> Absent</span>
                                <?php elseif($attendance->status == 'late'): ?>
                                    <span class="badge-soft badge-late"><span class="status-dot"></span> Late</span>
                                <?php elseif($attendance->status == 'half_day'): ?>
                                    <span class="badge-soft badge-half"><span class="status-dot"></span> Half Day</span>
                                <?php else: ?>
                                    <span class="badge-soft badge-holiday"><span class="status-dot"></span> Holiday</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <div class="d-flex justify-content-end gap-1">
                                    <a href="<?php echo e(route('admin.attendances.edit', $attendance->id)); ?>" class="btn-icon btn-edit" title="Edit">
                                        <i data-feather="edit-2" style="width:14px;"></i>
                                    </a>
                                    <form action="<?php echo e(route('admin.attendances.destroy', $attendance->id)); ?>" method="POST" class="d-inline" 
                                          onsubmit="return confirm('Are you sure you want to delete this record?');">
                                        <?php echo csrf_field(); ?>
                                        <?php echo method_field('DELETE'); ?>
                                        <button type="submit" class="btn-icon btn-delete" title="Delete">
                                            <i data-feather="trash-2" style="width:14px;"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <img src="https://cdn-icons-png.flaticon.com/512/7486/7486744.png" width="60" class="mb-3 opacity-25">
                                <p class="text-muted fw-bold mb-0">No attendance records found</p>
                                <small class="text-muted">Try adjusting the date or status filter.</small>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        
        <div class="p-4 border-top d-flex justify-content-between align-items-center bg-white rounded-bottom">
            <small class="text-muted">
                Showing <strong><?php echo e($attendances->firstItem()); ?></strong> to <strong><?php echo e($attendances->lastItem()); ?></strong> of <strong><?php echo e($attendances->total()); ?></strong> records
            </small>
            <div>
                <?php echo e($attendances->links('pagination::bootstrap-4')); ?>

            </div>
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
    });
</script>
<?php $__env->stopPush(); ?>
<?php echo $__env->make('backEnd.layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\bilaiGhor\resources\views\backEnd\attendances\index.blade.php ENDPATH**/ ?>