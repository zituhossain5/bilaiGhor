
<?php $__env->startSection('title','Manage Roles'); ?>

<?php $__env->startSection('css'); ?>
<link href="<?php echo e(asset('/public/backEnd/')); ?>/assets/libs/datatables.net-bs5/css/dataTables.bootstrap5.min.css" rel="stylesheet" type="text/css" />
<link href="<?php echo e(asset('/public/backEnd/')); ?>/assets/libs/datatables.net-responsive-bs5/css/responsive.bootstrap5.min.css" rel="stylesheet" type="text/css" />
<link href="<?php echo e(asset('/public/backEnd/')); ?>/assets/libs/datatables.net-buttons-bs5/css/buttons.bootstrap5.min.css" rel="stylesheet" type="text/css" />
<link href="<?php echo e(asset('/public/backEnd/')); ?>/assets/libs/datatables.net-select-bs5/css/select.bootstrap5.min.css" rel="stylesheet" type="text/css" />

<style>
    /* Premium Card & Table */
    .card {
        border: none;
        box-shadow: 0 0 20px rgba(18, 38, 63, 0.03);
        border-radius: 12px;
        overflow: hidden;
    }
    .card-body { padding: 25px; }

    /* Table Styling */
    .table thead th {
        background-color: #f9fbfd;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 11px;
        color: #8391a2;
        letter-spacing: 0.5px;
        border-bottom: 1px solid #eef2f7;
        padding: 12px 15px;
    }
    .table tbody td {
        vertical-align: middle;
        padding: 15px;
        border-bottom: 1px solid #f1f5f7;
        color: #313b5e;
        font-size: 14px;
    }

    /* Role Badge Styling */
    .role-badge {
        font-weight: 600;
        padding: 5px 12px;
        border-radius: 5px;
        font-size: 12px;
        letter-spacing: 0.5px;
        text-transform: capitalize;
    }
    .role-admin { background-color: rgba(250, 92, 124, 0.15); color: #fa5c7c; border: 1px solid rgba(250, 92, 124, 0.2); }
    .role-user { background-color: rgba(114, 124, 245, 0.15); color: #727cf5; border: 1px solid rgba(114, 124, 245, 0.2); }
    .role-other { background-color: rgba(10, 207, 151, 0.15); color: #0acf97; border: 1px solid rgba(10, 207, 151, 0.2); }

    /* Action Buttons */
    .action-btn {
        width: 32px;
        height: 32px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        color: #6c757d;
        transition: all 0.2s;
        border: 1px solid transparent;
        background: #f9fbfd;
    }
    .action-btn:hover { background-color: #eef2f7; color: #343a40; }
    
    .btn-view:hover { background-color: rgba(57, 175, 209, 0.1); color: #39afd1; }
    .btn-edit:hover { background-color: rgba(114, 124, 245, 0.1); color: #727cf5; }
    .btn-delete:hover { background-color: rgba(250, 92, 124, 0.1); color: #fa5c7c; }
</style>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid">
    
    <div class="row mb-3 mt-3">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <div>
                <h4 class="page-title mb-0" style="font-weight: 700; color: #2d3436;">Roles & Permissions</h4>
                <p class="text-muted font-size-13 mb-0">Manage user roles and access control.</p>
            </div>
            <a href="<?php echo e(route('roles.create')); ?>" class="btn btn-primary rounded-pill shadow-sm px-4">
                <i class="fe-plus me-1"></i> Create New Role
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <table id="datatable-buttons" class="table table-hover w-100 dt-responsive nowrap">
                        <thead>
                            <tr>
                                <th style="width: 50px;">SL</th>
                                <th>Role Name</th>
                                <th class="text-end" style="width: 150px;">Action</th>
                            </tr>
                        </thead>                
                        <tbody>
                            <?php $__currentLoopData = $show_data; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>

                                <?php
                                    // Logic to check if role is Admin
                                    $isAdminRole = (strtolower($value->name) === 'admin');

                                    // Logic to check if logged-in user is Admin
                                    $isLoginAdmin = auth()->check() && method_exists(auth()->user(), 'hasRole')
                                                    ? auth()->user()->hasRole('Admin')
                                                    : false;
                                ?>

                                
                                <?php if($isAdminRole && !$isLoginAdmin): ?>
                                    <?php continue; ?>
                                <?php endif; ?>

                                <tr>
                                    <td><?php echo e($loop->iteration); ?></td>
                                    
                                    <td>
                                        <?php if(strtolower($value->name) == 'admin'): ?>
                                            <span class="role-badge role-admin"><i class="fe-shield me-1"></i> <?php echo e($value->name); ?></span>
                                        <?php elseif(strtolower($value->name) == 'user' || strtolower($value->name) == 'customer'): ?>
                                            <span class="role-badge role-user"><i class="fe-user me-1"></i> <?php echo e($value->name); ?></span>
                                        <?php else: ?>
                                            <span class="role-badge role-other"><i class="fe-check-circle me-1"></i> <?php echo e($value->name); ?></span>
                                        <?php endif; ?>
                                    </td>

                                    <td class="text-end">
                                        <div class="d-inline-flex gap-2">
                                            
                                            
                                            <a href="<?php echo e(route('roles.show', $value->id)); ?>" class="action-btn btn-view" title="View Permissions">
                                                <i class="fe-eye"></i>
                                            </a>

                                            
                                            <a href="<?php echo e(route('roles.edit', $value->id)); ?>" class="action-btn btn-edit" title="Edit Role">
                                                <i class="fe-edit"></i>
                                            </a>

                                            
                                            <form method="post" action="<?php echo e(route('roles.destroy')); ?>" class="d-inline">
                                                <?php echo csrf_field(); ?>
                                                <input type="hidden" value="<?php echo e($value->id); ?>" name="hidden_id">
                                                <button type="submit" class="action-btn btn-delete delete-confirm" title="Delete">
                                                    <i class="fe-trash-2"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tbody>
                    </table>
                </div> </div> </div></div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('script'); ?>
<script src="<?php echo e(asset('/public/backEnd/')); ?>/assets/libs/datatables.net/js/jquery.dataTables.min.js"></script>
<script src="<?php echo e(asset('/public/backEnd/')); ?>/assets/libs/datatables.net-bs5/js/dataTables.bootstrap5.min.js"></script>
<script src="<?php echo e(asset('/public/backEnd/')); ?>/assets/libs/datatables.net-responsive/js/dataTables.responsive.min.js"></script>
<script src="<?php echo e(asset('/public/backEnd/')); ?>/assets/libs/datatables.net-responsive-bs5/js/responsive.bootstrap5.min.js"></script>
<script src="<?php echo e(asset('/public/backEnd/')); ?>/assets/libs/datatables.net-buttons/js/dataTables.buttons.min.js"></script>
<script src="<?php echo e(asset('/public/backEnd/')); ?>/assets/libs/datatables.net-buttons-bs5/js/buttons.bootstrap5.min.js"></script>
<script src="<?php echo e(asset('/public/backEnd/')); ?>/assets/libs/datatables.net-buttons/js/buttons.html5.min.js"></script>
<script src="<?php echo e(asset('/public/backEnd/')); ?>/assets/libs/datatables.net-buttons/js/buttons.flash.min.js"></script>
<script src="<?php echo e(asset('/public/backEnd/')); ?>/assets/libs/datatables.net-buttons/js/buttons.print.min.js"></script>
<script src="<?php echo e(asset('/public/backEnd/')); ?>/assets/libs/datatables.net-keytable/js/dataTables.keyTable.min.js"></script>
<script src="<?php echo e(asset('/public/backEnd/')); ?>/assets/libs/datatables.net-select/js/dataTables.select.min.js"></script>
<script src="<?php echo e(asset('/public/backEnd/')); ?>/assets/libs/pdfmake/build/pdfmake.min.js"></script>
<script src="<?php echo e(asset('/public/backEnd/')); ?>/assets/libs/pdfmake/build/vfs_fonts.js"></script>
<script src="<?php echo e(asset('/public/backEnd/')); ?>/assets/js/pages/datatables.init.js"></script>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('backEnd.layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\bilaiGhor\resources\views\backEnd\roles\index.blade.php ENDPATH**/ ?>