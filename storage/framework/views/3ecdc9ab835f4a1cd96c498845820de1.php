
<?php $__env->startSection('title','Testimonials'); ?>

<?php $__env->startSection('css'); ?>
<link href="<?php echo e(asset('/public/backEnd/')); ?>/assets/libs/datatables.net-bs5/css/dataTables.bootstrap5.min.css" rel="stylesheet" type="text/css" />
<link href="<?php echo e(asset('/public/backEnd/')); ?>/assets/libs/datatables.net-responsive-bs5/css/responsive.bootstrap5.min.css" rel="stylesheet" type="text/css" />
<style>
    .card { border: none; box-shadow: 0 0 20px rgba(18,38,63,0.03); border-radius: 12px; overflow: hidden; }
    .card-body { padding: 25px; }
    .table thead th { background-color: #f9fbfd; font-weight: 600; text-transform: uppercase; font-size: 11px; color: #8391a2; letter-spacing: 0.5px; border-bottom: 1px solid #eef2f7; padding: 12px 15px; }
    .table tbody td { vertical-align: middle; padding: 12px 15px; border-bottom: 1px solid #f1f5f7; color: #313b5e; font-size: 14px; }
    .avatar-img { width: 46px; height: 46px; border-radius: 50%; object-fit: cover; border: 2px solid #f1f5f7; }
    .badge-soft-success { background-color: rgba(10,207,151,0.18); color: #0acf97; }
    .badge-soft-danger { background-color: rgba(250,92,124,0.18); color: #fa5c7c; }
    .badge-pill { padding: 5px 10px; border-radius: 50rem; font-weight: 500; font-size: 11px; }
    .action-btn { width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; border-radius: 50%; color: #6c757d; border: 1px solid transparent; background: #f9fbfd; transition: all 0.2s; }
    .btn-edit:hover { background-color: rgba(114,124,245,0.1); color: #727cf5; }
    .btn-delete:hover { background-color: rgba(250,92,124,0.1); color: #fa5c7c; }
    .star-text { color: #f5a623; font-size: 13px; }
</style>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid">
    <div class="row mb-3 mt-3">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h4 class="page-title mb-0" style="font-weight:700;color:#2d3436;">Testimonials</h4>
            <a href="<?php echo e(route('admin.testimonial.create')); ?>" class="btn btn-primary rounded-pill shadow-sm px-4">
                <i class="fe-plus me-1"></i> Add New
            </a>
        </div>
    </div>

    <?php if(session('success')): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php echo e(session('success')); ?>

        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <table id="datatable-buttons" class="table table-hover w-100 dt-responsive nowrap">
                        <thead>
                            <tr>
                                <th style="width:50px;">SL</th>
                                <th>Photo</th>
                                <th>Name</th>
                                <th>Location</th>
                                <th>Rating</th>
                                <th>Sort</th>
                                <th>Status</th>
                                <th class="text-end" style="width:120px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__empty_1 = true; $__currentLoopData = $testimonials; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $t): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr>
                                <td><?php echo e($key + 1); ?></td>
                                <td>
                                    <?php if($t->image): ?>
                                        <img src="<?php echo e(asset('public/'.$t->image)); ?>" class="avatar-img" alt="">
                                    <?php else: ?>
                                        <div class="avatar-img d-flex align-items-center justify-content-center bg-light text-muted" style="border-radius:50%;font-size:20px;">
                                            <i class="fe-user"></i>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td><strong><?php echo e($t->name); ?></strong></td>
                                <td><?php echo e($t->location ?? '—'); ?></td>
                                <td>
                                    <span class="star-text">
                                        <?php for($i = 1; $i <= 5; $i++): ?>
                                            <?php echo e($i <= $t->rating ? '★' : '☆'); ?>

                                        <?php endfor; ?>
                                    </span>
                                </td>
                                <td><?php echo e($t->sort_order); ?></td>
                                <td>
                                    <?php if($t->status): ?>
                                        <span class="badge badge-pill badge-soft-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge badge-pill badge-soft-danger">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <div class="d-inline-flex gap-2">
                                        <a href="<?php echo e(route('admin.testimonial.edit', $t->id)); ?>" class="action-btn btn-edit" title="Edit">
                                            <i class="fe-edit"></i>
                                        </a>
                                        <a href="<?php echo e(route('admin.testimonial.delete', $t->id)); ?>"
                                           onclick="return confirm('Delete this testimonial?')"
                                           class="action-btn btn-delete" title="Delete">
                                            <i class="fe-trash-2"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr><td colspan="8" class="text-center text-muted py-4">No testimonials yet.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('script'); ?>
<script src="<?php echo e(asset('/public/backEnd/')); ?>/assets/libs/datatables.net/js/jquery.dataTables.min.js"></script>
<script src="<?php echo e(asset('/public/backEnd/')); ?>/assets/libs/datatables.net-bs5/js/dataTables.bootstrap5.min.js"></script>
<script src="<?php echo e(asset('/public/backEnd/')); ?>/assets/libs/datatables.net-responsive/js/dataTables.responsive.min.js"></script>
<script src="<?php echo e(asset('/public/backEnd/')); ?>/assets/libs/datatables.net-responsive-bs5/js/responsive.bootstrap5.min.js"></script>
<script src="<?php echo e(asset('/public/backEnd/')); ?>/assets/js/pages/datatables.init.js"></script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('backEnd.layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\bilaiGhor\resources\views\backEnd\testimonial\index.blade.php ENDPATH**/ ?>