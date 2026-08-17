
<?php $__env->startSection('title','Customer Profile'); ?>

<?php $__env->startSection('css'); ?>
<link href="<?php echo e(asset('/public/backEnd/')); ?>/assets/libs/datatables.net-bs5/css/dataTables.bootstrap5.min.css" rel="stylesheet" type="text/css" />
<link href="<?php echo e(asset('/public/backEnd/')); ?>/assets/libs/datatables.net-responsive-bs5/css/responsive.bootstrap5.min.css" rel="stylesheet" type="text/css" />
<link href="<?php echo e(asset('/public/backEnd/')); ?>/assets/libs/datatables.net-buttons-bs5/css/buttons.bootstrap5.min.css" rel="stylesheet" type="text/css" />

<style>
    /* Profile Card Styling */
    .profile-card {
        border: none;
        box-shadow: 0 0 20px rgba(18, 38, 63, 0.03);
        border-radius: 12px;
        overflow: hidden;
    }
    .profile-header-bg {
        height: 100px;
        background: linear-gradient(to right, #727cf5, #0acf97);
        position: relative;
    }
    .profile-avatar {
        width: 100px;
        height: 100px;
        border: 4px solid #fff;
        border-radius: 50%;
        margin-top: -50px;
        background: #fff;
        object-fit: cover;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    .profile-name { font-size: 20px; font-weight: 700; color: #343a40; margin-top: 15px; }
    .profile-meta { color: #98a6ad; font-size: 13px; }

    /* Contact Buttons */
    .btn-contact {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin: 0 5px;
        transition: all 0.3s;
    }
    .btn-contact-call { background: rgba(10, 207, 151, 0.1); color: #0acf97; }
    .btn-contact-call:hover { background: #0acf97; color: #fff; }
    .btn-contact-email { background: rgba(250, 92, 124, 0.1); color: #fa5c7c; }
    .btn-contact-email:hover { background: #fa5c7c; color: #fff; }

    /* Info List */
    .info-list-item {
        padding: 12px 0;
        border-bottom: 1px solid #f1f5f7;
        display: flex;
        justify-content: space-between;
    }
    .info-list-item:last-child { border-bottom: none; }
    .info-label { font-weight: 600; color: #6c757d; font-size: 13px; }
    .info-value { color: #343a40; font-weight: 500; text-align: right; }

    /* Order Table */
    .table thead th {
        background-color: #f9fbfd;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 11px;
        color: #8391a2;
        letter-spacing: 0.5px;
        border-bottom: 1px solid #eef2f7;
    }
    .invoice-link { color: #727cf5; font-weight: 600; text-decoration: none; }
    
    /* Soft Badges */
    .badge-soft-success { background-color: rgba(10, 207, 151, 0.18); color: #0acf97; }
    .badge-soft-danger { background-color: rgba(250, 92, 124, 0.18); color: #fa5c7c; }
    .badge-soft-warning { background-color: rgba(255, 188, 0, 0.18); color: #ffbc00; }
    .badge-soft-info { background-color: rgba(57, 175, 209, 0.18); color: #39afd1; }
    .badge-pill { padding: 5px 10px; border-radius: 50rem; font-weight: 500; font-size: 11px; }
</style>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid">
    
    <div class="row mb-3 mt-3">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h4 class="page-title mb-0" style="font-weight: 700; color: #2d3436;">Customer Profile</h4>
            
            <div class="d-flex gap-2">
                <a href="<?php echo e(route('customers.index')); ?>" class="btn btn-light rounded-pill border shadow-sm">
                    <i class="fe-arrow-left me-1"></i> Back to List
                </a>
                
                
                <form method="post" action="<?php echo e(route('customers.adminlog')); ?>" target="_blank">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" value="<?php echo e($profile->id); ?>" name="hidden_id">        
                    <button type="submit" class="btn btn-info rounded-pill shadow-sm" title="Login as User">
                        <i class="fe-log-in me-1"></i> Login as User
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-4 col-xl-4">
            <div class="card profile-card text-center h-100">
                <div class="profile-header-bg"></div>
                <div class="card-body pt-0">
                    <img src="<?php echo e(asset($profile->image)); ?>" class="profile-avatar" alt="profile-image">
                    
                    <h4 class="profile-name"><?php echo e($profile->name); ?></h4>
                    <p class="profile-meta"><i class="fe-map-pin me-1"></i> <?php echo e($profile->area ?? 'Area'); ?>, <?php echo e($profile->district ?? 'District'); ?></p>

                    <div class="mt-3 mb-4">
                        <a href="tel:<?php echo e($profile->phone); ?>" class="btn-contact btn-contact-call" title="Call Now">
                            <i class="fe-phone"></i>
                        </a>
                        <a href="mailto:<?php echo e($profile->email); ?>" class="btn-contact btn-contact-email" title="Send Email">
                            <i class="fe-mail"></i>
                        </a>
                    </div>

                    <div class="text-start mt-4">
                        <h6 class="text-uppercase text-muted font-size-12 mb-3">Personal Information</h6>
                        
                        <div class="info-list-item">
                            <span class="info-label">Mobile</span>
                            <span class="info-value"><?php echo e($profile->phone); ?></span>
                        </div>
                        <div class="info-list-item">
                            <span class="info-label">Email</span>
                            <span class="info-value"><?php echo e($profile->email); ?></span>
                        </div>
                        <div class="info-list-item">
                            <span class="info-label">Address</span>
                            <span class="info-value"><?php echo e($profile->address); ?></span>
                        </div>
                        <div class="info-list-item">
                            <span class="info-label">District</span>
                            <span class="info-value"><?php echo e($profile->district); ?></span>
                        </div>
                        <div class="info-list-item">
                            <span class="info-label">Upazila/Area</span>
                            <span class="info-value"><?php echo e($profile->area); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-8 col-xl-8">
            <div class="card profile-card h-100">
                <div class="card-body">
                    
                    <ul class="nav nav-tabs nav-bordered mb-3">
                        <li class="nav-item">
                            <a href="#orders" data-bs-toggle="tab" aria-expanded="true" class="nav-link active">
                                <i class="fe-shopping-bag me-1"></i> Order History
                            </a>
                        </li>
                        
                    </ul>

                    <div class="tab-content">
                        <div class="tab-pane show active" id="orders">
                            <div class="table-responsive">
                                <table id="datatable-buttons" class="table table-hover dt-responsive nowrap w-100">
                                    <thead>
                                        <tr>
                                            <th>SL</th>
                                            <th>Invoice ID</th>
                                            <th>Date & Time</th>
                                            <th>Shipping Method</th>
                                            <th>Amount</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $__currentLoopData = $profile->orders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key=>$value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <tr>
                                            <td><?php echo e($loop->iteration); ?></td>
                                            
                                            <td>
                                                <span class="invoice-link">#<?php echo e($value->invoice_id); ?></span>
                                            </td>

                                            <td>
                                                <div class="d-flex flex-column">
                                                    <span class="fw-bold"><?php echo e(date('d M, Y', strtotime($value->created_at))); ?></span>
                                                    <small class="text-muted"><?php echo e(date('h:i A', strtotime($value->created_at))); ?></small>
                                                </div>
                                            </td>

                                            <td><?php echo e($value->shipping ? $value->shipping->name : 'N/A'); ?></td>

                                            <td>
                                                <span class="fw-bold text-dark">৳<?php echo e(number_format($value->amount, 2)); ?></span>
                                            </td>

                                            <td>
                                                <?php
                                                    $statusName = $value->status ? $value->status->name : 'Pending';
                                                    $badgeClass = 'badge-soft-warning'; // Default
                                                    
                                                    if(strtolower($statusName) == 'completed' || strtolower($statusName) == 'delivered') {
                                                        $badgeClass = 'badge-soft-success';
                                                    } elseif(strtolower($statusName) == 'cancelled') {
                                                        $badgeClass = 'badge-soft-danger';
                                                    } elseif(strtolower($statusName) == 'processing') {
                                                        $badgeClass = 'badge-soft-info';
                                                    }
                                                ?>
                                                <span class="badge badge-pill <?php echo e($badgeClass); ?>"><?php echo e($statusName); ?></span>
                                            </td>
                                        </tr>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div> </div> </div> </div> </div>
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
<?php echo $__env->make('backEnd.layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\bilaiGhor\resources\views\backEnd\customer\profile.blade.php ENDPATH**/ ?>