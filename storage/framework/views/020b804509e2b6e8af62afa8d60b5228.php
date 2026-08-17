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
<?php /**PATH C:\laragon\www\bilaiGhor\resources\views\backEnd\newsletterSubscriber\partials\table.blade.php ENDPATH**/ ?>