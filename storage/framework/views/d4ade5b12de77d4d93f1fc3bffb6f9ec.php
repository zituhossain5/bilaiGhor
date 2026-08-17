
<?php $__env->startSection('title','Employee Management'); ?>

<?php $__env->startSection('css'); ?>
<style>
    /* --- Modern Variables --- */
    :root {
        --primary-color: #4f46e5;
        --secondary-text: #64748b;
        --border-color: #e2e8f0;
    }

    /* --- Card Styles --- */
    .card-modern {
        border: none;
        border-radius: 16px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02), 0 2px 4px -1px rgba(0, 0, 0, 0.02);
        background: #fff;
    }
    
    /* --- Filter Section --- */
    .filter-container {
        background: #f8fafc;
        border: 1px solid var(--border-color);
        border-radius: 12px;
        padding: 1.25rem;
        margin-bottom: 1.5rem;
    }
    .form-control-clean, .form-select-clean {
        background: #fff;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        font-size: 0.875rem;
        padding: 0.6rem 1rem;
    }
    .form-control-clean:focus { border-color: var(--primary-color); box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1); }

    /* --- Table Styling --- */
    .table-responsive { overflow-x: auto; }
    .employee-table { width: 100%; border-collapse: separate; border-spacing: 0; }
    .employee-table th {
        background: #f1f5f9;
        color: var(--secondary-text);
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        padding: 1rem 1.5rem;
        border-bottom: 1px solid var(--border-color);
        letter-spacing: 0.5px;
    }
    .employee-table td {
        padding: 1rem 1.5rem;
        vertical-align: middle;
        border-bottom: 1px solid var(--border-color);
        background: #fff;
    }
    .employee-table tr:hover td { background: #fafafa; }
    .employee-table tr:last-child td { border-bottom: none; }

    /* --- Avatar & User Info --- */
    .user-card { display: flex; align-items: center; gap: 12px; }
    .user-avatar {
        width: 40px; height: 40px;
        border-radius: 50%;
        background: #e0e7ff;
        color: #4338ca;
        display: flex; align-items: center; justify-content: center;
        font-weight: 700; font-size: 14px;
        border: 2px solid #fff;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    }
    .user-info h6 { margin: 0; font-size: 0.9rem; font-weight: 600; color: #1e293b; }
    .user-info span { font-size: 0.75rem; color: #64748b; }
    .emp-id-badge {
        font-size: 0.7rem; background: #f1f5f9; color: #475569;
        padding: 2px 6px; border-radius: 4px; font-weight: 600; margin-left: 6px;
    }

    /* --- Status Badges --- */
    .status-badge {
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        display: inline-flex; align-items: center; gap: 5px;
    }
    .status-active { background: #dcfce7; color: #166534; }
    .status-inactive { background: #fef3c7; color: #92400e; }
    .status-terminated { background: #fee2e2; color: #991b1b; }
    .status-dot { width: 6px; height: 6px; border-radius: 50%; background: currentColor; }

    /* --- Action Buttons --- */
    .btn-icon {
        width: 32px; height: 32px;
        display: inline-flex; align-items: center; justify-content: center;
        border-radius: 8px; transition: all 0.2s;
        border: 1px solid transparent;
    }
    .btn-icon:hover { transform: translateY(-2px); border-color: var(--border-color); }
    .btn-view { color: #0ea5e9; background: #e0f2fe; }
    .btn-edit { color: #6366f1; background: #e0e7ff; }
    .btn-delete { color: #ef4444; background: #fee2e2; }
</style>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid py-4">

    
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <h4 class="mb-1 fw-bold text-dark">
                <i data-feather="users" class="text-primary me-2"></i> Employee Management
            </h4>
            <p class="text-muted small mb-0">Manage all your employees, departments, and payroll info.</p>
        </div>
        <a href="<?php echo e(route('admin.employees.create')); ?>" class="btn btn-primary px-4 py-2 rounded-pill shadow-sm">
            <i data-feather="plus" class="me-1" style="width: 16px;"></i> Add New Employee
        </a>
    </div>

    <div class="card-modern">
        
        
        <div class="p-4 border-bottom">
            <form method="GET" action="<?php echo e(route('admin.employees.index')); ?>">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0 text-muted"><i data-feather="search" style="width:16px;"></i></span>
                            <input type="text" name="keyword" class="form-control form-control-clean border-start-0 ps-0" placeholder="Search by name, email or ID..." value="<?php echo e(request('keyword')); ?>">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <select name="department" class="form-select form-select-clean">
                            <option value="">All Departments</option>
                            <?php $__currentLoopData = $departments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $dept): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($dept); ?>" <?php echo e(request('department') == $dept ? 'selected' : ''); ?>><?php echo e($dept); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="status" class="form-select form-select-clean">
                            <option value="">All Status</option>
                            <option value="active" <?php echo e(request('status') == 'active' ? 'selected' : ''); ?>>Active</option>
                            <option value="inactive" <?php echo e(request('status') == 'inactive' ? 'selected' : ''); ?>>Inactive</option>
                            <option value="terminated" <?php echo e(request('status') == 'terminated' ? 'selected' : ''); ?>>Terminated</option>
                        </select>
                    </div>
                    <div class="col-md-4 d-flex gap-2">
                        <button type="submit" class="btn btn-dark px-4 flex-grow-1">Filter</button>
                        <a href="<?php echo e(route('admin.employees.index')); ?>" class="btn btn-light border px-3" title="Reset">
                            <i data-feather="refresh-cw" style="width:16px;"></i>
                        </a>
                    </div>
                </div>
            </form>
        </div>

        
        <div class="table-responsive">
            <table class="employee-table">
                <thead>
                    <tr>
                        <th width="30%">Employee Details</th>
                        <th width="20%">Role & Dept</th>
                        <th width="15%">Contact</th>
                        <th width="15%">Salary</th>
                        <th width="10%">Status</th>
                        <th width="10%" class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $employees; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $employee): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            
                            <td>
                                <div class="user-card">
                                    <div class="user-avatar">
                                        <?php echo e(substr($employee->name, 0, 1)); ?>

                                    </div>
                                    <div class="user-info">
                                        <h6>
                                            <?php echo e($employee->name); ?>

                                            <span class="emp-id-badge">#<?php echo e($employee->employee_id); ?></span>
                                        </h6>
                                        <span><?php echo e($employee->email); ?></span>
                                    </div>
                                </div>
                            </td>

                            
                            <td>
                                <div class="d-flex flex-column">
                                    <span class="fw-bold text-dark fs-6"><?php echo e($employee->designation ?? 'N/A'); ?></span>
                                    <span class="text-muted small"><i data-feather="briefcase" style="width:10px;" class="me-1"></i> <?php echo e($employee->department ?? 'General'); ?></span>
                                </div>
                            </td>

                            
                            <td>
                                <span class="text-muted small fw-medium">
                                    <?php echo e($employee->phone ?? 'N/A'); ?>

                                </span>
                            </td>

                            
                            <td>
                                <span class="fw-bold text-dark">৳<?php echo e(number_format($employee->basic_salary, 2)); ?></span>
                                <div class="text-muted" style="font-size: 10px;">Basic</div>
                            </td>

                            
                            <td>
                                <?php if($employee->status == 'active'): ?>
                                    <span class="status-badge status-active"><span class="status-dot"></span> Active</span>
                                <?php elseif($employee->status == 'inactive'): ?>
                                    <span class="status-badge status-inactive"><span class="status-dot"></span> Inactive</span>
                                <?php else: ?>
                                    <span class="status-badge status-terminated"><span class="status-dot"></span> Terminated</span>
                                <?php endif; ?>
                            </td>

                            
                            <td class="text-end">
                                <div class="d-flex justify-content-end gap-2">
                                    <a href="<?php echo e(route('admin.employees.show', $employee->id)); ?>" class="btn-icon btn-view" title="View Details">
                                        <i data-feather="eye" style="width: 16px;"></i>
                                    </a>
                                    <a href="<?php echo e(route('admin.employees.edit', $employee->id)); ?>" class="btn-icon btn-edit" title="Edit Info">
                                        <i data-feather="edit-2" style="width: 16px;"></i>
                                    </a>
                                    <form action="<?php echo e(route('admin.employees.destroy', $employee->id)); ?>" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this employee?');">
                                        <?php echo csrf_field(); ?>
                                        <?php echo method_field('DELETE'); ?>
                                        <button type="submit" class="btn-icon btn-delete" title="Delete">
                                            <i data-feather="trash-2" style="width: 16px;"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <img src="https://cdn-icons-png.flaticon.com/512/7486/7486754.png" width="60" class="mb-3 opacity-25">
                                <p class="text-muted fw-bold mb-0">No Employees Found</p>
                                <small class="text-muted">Try adjusting your search or filters.</small>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        
        <div class="p-4 border-top d-flex justify-content-between align-items-center">
            <small class="text-muted">
                Showing <strong><?php echo e($employees->firstItem()); ?></strong> to <strong><?php echo e($employees->lastItem()); ?></strong> of <strong><?php echo e($employees->total()); ?></strong> results
            </small>
            <div>
                <?php echo e($employees->links('pagination::bootstrap-4')); ?>

            </div>
        </div>

    </div>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('backEnd.layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\bilaiGhor\resources\views\backEnd\employees\index.blade.php ENDPATH**/ ?>