

<?php
    use Illuminate\Support\Str;
?>

<?php $__env->startSection('title','Contact Messages'); ?>

<?php $__env->startSection('content'); ?>
<meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">

<div class="card">
    <div class="card-header">
        <h4>Contact Messages</h4>
    </div>

    <div class="card-body">

        <div id="ajaxTable">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Full Name</th>
                        <th>Mobile</th>
                        <th>Email</th>
                        <th>Subject</th>
                        <th>Message</th>
                        <th width="120">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $messages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td><?php echo e($messages->firstItem() + $key); ?></td>
                        <td><?php echo e($row->full_name); ?></td>
                        <td><?php echo e($row->mobile); ?></td>
                        <td><?php echo e($row->email); ?></td>
                        <td><?php echo e($row->subject); ?></td>
                        <td><?php echo e(Str::limit($row->details, 50)); ?></td>
                        <td>
                            
                            <form action="<?php echo e(route('admin.contact.messages.delete',$row->id)); ?>"
                                  method="POST"
                                  class="deleteForm"
                                  style="display:inline-block">
                                <?php echo csrf_field(); ?>
                                <?php echo method_field('DELETE'); ?>
                                <button class="btn btn-sm btn-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="7" class="text-center">No messages found</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            
            <div class="d-flex justify-content-end">
           <?php echo e($messages->links('pagination::bootstrap-4')); ?>

            </div>
        </div>

    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
$(document).ready(function(){

    // ================= Pagination =================
    $(document).on('click','.pagination a',function(e){
        e.preventDefault();
        let url = $(this).attr('href');

        $.get(url,function(data){
            let html = $(data).find('#ajaxTable').html();
            $('#ajaxTable').html(html);
        });
    });

    // ================= Delete =================
    $(document).on('submit','.deleteForm',function(e){
        e.preventDefault();

        if(!confirm('Are you sure to delete?')) return;

        let form = $(this);

        $.ajax({
            url: form.attr('action'),
            type: 'POST',
            data: form.serialize(),
            success:function(){
                // reload current page data
                location.reload();
            }
        });
    });

});
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('backEnd.layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\bilaiGhor\resources\views\backEnd\contactMessage\index.blade.php ENDPATH**/ ?>