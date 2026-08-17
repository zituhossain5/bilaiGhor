
<?php $__env->startSection('title','Customer Manage'); ?>

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

    /* Customer Info Styling */
    .customer-name {
        font-weight: 600;
        color: #343a40;
        font-size: 14px;
    }
    .customer-contact {
        font-size: 12px;
        color: #98a6ad;
        display: block;
        margin-top: 2px;
    }

    /* Soft Badges */
    .badge-soft-success { background-color: rgba(10, 207, 151, 0.18); color: #0acf97; }
    .badge-soft-danger { background-color: rgba(250, 92, 124, 0.18); color: #fa5c7c; }
    .badge-pill { padding: 5px 10px; border-radius: 50rem; font-weight: 500; font-size: 11px; }

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
        cursor: pointer;
    }
    .action-btn:hover { background-color: #eef2f7; color: #343a40; }
    
    .btn-active:hover { background-color: rgba(10, 207, 151, 0.1); color: #0acf97; }
    .btn-inactive:hover { background-color: rgba(255, 188, 0, 0.1); color: #ffbc00; }
    .btn-view:hover { background-color: rgba(57, 175, 209, 0.1); color: #39afd1; }
    .btn-edit:hover { background-color: rgba(114, 124, 245, 0.1); color: #727cf5; }
    
    /* Login as User Button */
    .btn-login-as {
        color: #ff5b5b;
        background-color: rgba(255, 91, 91, 0.1);
    }
    .btn-login-as:hover {
        background-color: #ff5b5b;
        color: #fff;
        box-shadow: 0 2px 6px rgba(255, 91, 91, 0.3);
    }

    .btn-delete {
        color: #fa5c7c;
        background-color: rgba(250, 92, 124, 0.1);
    }
    .btn-delete:hover {
        background-color: #fa5c7c;
        color: #fff;
        box-shadow: 0 2px 6px rgba(250, 92, 124, 0.3);
    }

    .btn-delete-all {
        font-size: 13px;
        font-weight: 600;
        padding: 8px 14px;
        border-radius: 8px;
        border: 1px solid rgba(250, 92, 124, 0.35);
        color: #fa5c7c;
        background: rgba(250, 92, 124, 0.08);
        transition: all 0.2s;
    }
    .btn-delete-all:hover {
        background: #fa5c7c;
        color: #fff;
        border-color: #fa5c7c;
    }

    /* Pagination */
    .custom-paginate { margin-top: 20px; text-align: right; }
</style>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid">
    
    <div class="row mb-3 mt-3">
        <div class="col-12">
            <div class="page-title-box d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div>
                    <h4 class="page-title mb-1" style="font-weight: 700; color: #2d3436;">Customer List</h4>
                    <p class="text-muted font-size-13 mb-0">Manage your customers and their account status.</p>
                </div>
                <?php if($show_data->total() > 0): ?>
                <form method="post" action="<?php echo e(route('customers.destroy-all')); ?>" id="form-delete-all-customers" class="mb-0">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="confirm" id="customer-delete-all-confirm" value="">
                    <button type="button" class="btn-delete-all" onclick="submitDeleteAllCustomers()">
                        <i class="fe-trash-2 me-1"></i> Delete All Customers
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">

                    
                    <div id="customer-table-wrapper">

                        <div class="table-responsive">
                            <table id="datatable-buttons" class="table table-hover w-100 dt-responsive nowrap">
                                <thead>
                                    <tr>
                                        <th style="width: 50px;">SL</th>
                                        <th>Name</th>
                                        <th>Contact Info</th>
                                        <th>Status</th>
                                        <th class="text-end" style="width: 220px;">Action</th>
                                    </tr>
                                </thead>                
                                <tbody>
                                    <?php $__currentLoopData = $show_data; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key=>$value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <tr>
                                        <td><?php echo e($loop->iteration); ?></td>
                                        
                                        <td>
                                            <span class="customer-name"><?php echo e($value->name); ?></span>
                                        </td>

                                        <td>
                                            <div class="d-flex flex-column">
                                                <span class="text-dark"><i class="fe-phone me-1 text-muted"></i> <?php echo e($value->phone); ?></span>
                                                <?php if($value->email): ?>
                                                    <span class="customer-contact"><i class="fe-mail me-1"></i> <?php echo e($value->email); ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        
                                        <td>
                                            <?php if($value->status=='active'): ?>
                                                <span class="badge badge-pill badge-soft-success">Active</span> 
                                            <?php else: ?> 
                                                <span class="badge badge-pill badge-soft-danger"><?php echo e(ucfirst($value->status)); ?></span> 
                                            <?php endif; ?>
                                        </td>

                                        <td class="text-end">
                                            <div class="d-inline-flex gap-2">
                                                
                                                
                                                <?php if($value->status == 'active'): ?>
                                                    <form method="post" action="<?php echo e(route('customers.inactive')); ?>" class="d-inline"> 
                                                        <?php echo csrf_field(); ?>
                                                        <input type="hidden" value="<?php echo e($value->id); ?>" name="hidden_id">        
                                                        <button type="submit" class="action-btn btn-inactive" title="Deactivate">
                                                            <i class="fe-thumbs-down"></i>
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <form method="post" action="<?php echo e(route('customers.active')); ?>" class="d-inline">
                                                        <?php echo csrf_field(); ?>
                                                        <input type="hidden" value="<?php echo e($value->id); ?>" name="hidden_id">        
                                                        <button type="submit" class="action-btn btn-active" title="Activate">
                                                            <i class="fe-thumbs-up"></i>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>

                                                
                                                <a href="<?php echo e(route('customers.edit',$value->id)); ?>" class="action-btn btn-edit" title="Edit Info">
                                                    <i class="fe-edit"></i>
                                                </a>

                                                
                                                <a href="<?php echo e(route('customers.profile',['id'=>$value->id])); ?>" class="action-btn btn-view" title="View Profile">
                                                    <i class="fe-eye"></i>
                                                </a>

                                                
                                                <form method="post" action="<?php echo e(route('customers.adminlog')); ?>" class="d-inline" target="_blank">
                                                    <?php echo csrf_field(); ?>
                                                    <input type="hidden" value="<?php echo e($value->id); ?>" name="hidden_id">
                                                    <button type="submit" class="action-btn btn-login-as" title="Login as User">
                                                        <i class="fe-log-in"></i>
                                                    </button>
                                                </form>

                                                <form method="post" action="<?php echo e(route('customers.destroy', $value->id)); ?>" class="d-inline"
                                                      onsubmit="return confirm('এই কাস্টমার মুছে ফেলবেন? অর্ডার রেকর্ড থাকলেও কাস্টমার অ্যাকাউন্ট মুছে যাবে।');">
                                                    <?php echo csrf_field(); ?>
                                                    <?php echo method_field('DELETE'); ?>
                                                    <button type="submit" class="action-btn btn-delete" title="Delete Customer">
                                                        <i class="fe-trash-2"></i>
                                                    </button>
                                                </form>

                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="custom-paginate">
                            <?php echo e($show_data->links('pagination::bootstrap-4')); ?>

                        </div>

                    </div>
                    

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


<script>
function submitDeleteAllCustomers() {
    var text = prompt('সকল কাস্টমার মুছতে DELETE লিখুন:');
    if (text !== 'DELETE') {
        if (text !== null) {
            alert('ভুল টেক্সট। কাজটি বাতিল হয়েছে।');
        }
        return;
    }
    if (!confirm('সতর্কতা! সকল কাস্টমার চিরতরে মুছে যাবে। চালিয়ে যাবেন?')) {
        return;
    }
    document.getElementById('customer-delete-all-confirm').value = 'DELETE';
    document.getElementById('form-delete-all-customers').submit();
}

$(document).on('click', '.custom-paginate a', function (e) {
    e.preventDefault();
    let url = $(this).attr('href');
    
    // Add visual loading indicator
    $('#customer-table-wrapper').css('opacity','0.5');

    $.get(url, function (response) {
        let html = $(response).find('#customer-table-wrapper').html();
        $('#customer-table-wrapper').html(html);
    }).always(function () {
        $('#customer-table-wrapper').css('opacity','1');
    });
});
</script>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('backEnd.layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\bilaiGhor\resources\views\backEnd\customer\index.blade.php ENDPATH**/ ?>