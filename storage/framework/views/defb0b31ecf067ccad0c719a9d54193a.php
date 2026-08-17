

<?php $__env->startSection('title','Newsletter Subscribers'); ?>

<?php $__env->startSection('content'); ?>
<meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">

<div class="card">
    <div class="card-header">
        <h4>Newsletter Subscribers</h4>
        <small class="text-muted">Emails submitted from footer newsletter form</small>
    </div>

    <div class="card-body">

        <div id="ajaxTable">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Email</th>
                        <th>Subscribed At</th>
                        <th width="120">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $subscribers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td><?php echo e($subscribers->firstItem() + $key); ?></td>
                        <td><?php echo e($row->email); ?></td>
                        <td><?php echo e($row->created_at->format('d M Y, h:i A')); ?></td>
                        <td>
                            <form action="<?php echo e(route('admin.newsletter.subscribers.delete', $row->id)); ?>"
                                  method="POST"
                                  class="deleteNewsletterForm"
                                  style="display:inline-block">
                                <?php echo csrf_field(); ?>
                                <?php echo method_field('DELETE'); ?>
                                <button class="btn btn-sm btn-danger" type="submit">Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="4" class="text-center">No subscribers yet</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <div class="d-flex justify-content-end">
                <?php echo e($subscribers->links('pagination::bootstrap-4')); ?>

            </div>
        </div>

    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
$(document).ready(function(){

    // Pagination
    $(document).on('click','.pagination a',function(e){
        e.preventDefault();
        let url = $(this).attr('href');
        $.get(url,function(data){
            let html = $(data).find('#ajaxTable').html();
            $('#ajaxTable').html(html);
        });
    });

    // Delete
    $(document).on('submit','.deleteNewsletterForm',function(e){
        e.preventDefault();
        if(!confirm('Are you sure to delete this subscriber?')) return;
        let form = $(this);
        $.ajax({
            url: form.attr('action'),
            type: 'POST',
            data: form.serialize(),
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'Accept': 'application/json'
            },
            success: function(){
                location.reload();
            }
        });
    });

});
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('backEnd.layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\bilaiGhor\resources\views\backEnd\newsletterSubscriber\index.blade.php ENDPATH**/ ?>