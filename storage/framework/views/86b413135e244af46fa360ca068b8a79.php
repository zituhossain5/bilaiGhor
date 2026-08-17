

<?php $__env->startSection('title','Contact Messages'); ?>

<?php $__env->startSection('content'); ?>
<div class="card">
    <div class="card-header">
        <h4>Contact Messages</h4>
    </div>

    <div class="card-body">
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Full Name</th>
                    <th>Mobile</th>
                    <th>Email</th>
                    <th>Subject</th>
                    <th>Message</th>
                    <th>Status</th>
                    <th width="120">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php $__currentLoopData = $messages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <tr>
                    <td><?php echo e($key+1); ?></td>
                    <td><?php echo e($row->full_name); ?></td>
                    <td><?php echo e($row->mobile); ?></td>
                    <td><?php echo e($row->email); ?></td>
                    <td><?php echo e($row->subject); ?></td>
                    <td><?php echo e(Str::limit($row->details, 50)); ?></td>
                    <td>
                        <?php if($row->status == 0): ?>
                            <span class="badge badge-warning">Pending</span>
                        <?php else: ?>
                            <span class="badge badge-success">Seen</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        
                        <form action="<?php echo e(route('admin.contact.messages.status',$row->id)); ?>" method="POST" style="display:inline">
                            <?php echo csrf_field(); ?>
                            <button class="btn btn-sm btn-info">
                                Status
                            </button>
                        </form>

                        
                        <form action="<?php echo e(route('admin.contact.messages.delete',$row->id)); ?>"
                              method="POST"
                              style="display:inline"
                              onsubmit="return confirm('Are you sure?')">
                            <?php echo csrf_field(); ?>
                            <?php echo method_field('DELETE'); ?>
                            <button class="btn btn-sm btn-danger">
                                Delete
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                <?php if($messages->count() == 0): ?>
                <tr>
                    <td colspan="8" class="text-center">No messages found</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('backEnd.layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\bilaiGhor\resources\views\backEnd\index.blade.php ENDPATH**/ ?>