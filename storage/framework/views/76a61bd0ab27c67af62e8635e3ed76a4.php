<table class="table table-bordered table-hover align-middle">
    <thead class="table-light">
        <tr>
            <th>#</th>
            <th>Date</th>
            <th class="text-end">Sales</th>
            <th class="text-end">COGS</th>
            <th class="text-end">Expense</th>
            <th class="text-end">Net Profit</th>
        </tr>
    </thead>
    <tbody>
        <?php $__empty_1 = true; $__currentLoopData = $rows ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <tr>
                <td><?php echo e($loop->iteration); ?></td>
                <td><?php echo e($row->date); ?></td>
                <td class="text-end"><?php echo e(number_format($row->sales,2)); ?></td>
                <td class="text-end"><?php echo e(number_format($row->cogs,2)); ?></td>
                <td class="text-end"><?php echo e(number_format($row->expense,2)); ?></td>
                <td class="text-end fw-bold
                    <?php echo e($row->profit >= 0 ? 'text-success' : 'text-danger'); ?>">
                    <?php echo e(number_format($row->profit,2)); ?>

                </td>
            </tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <tr>
                <td colspan="6" class="text-center text-muted">
                    কোনো রেকর্ড পাওয়া যায়নি
                </td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

<?php if(isset($rows)): ?>
    <div class="mt-3">
        <?php echo e($rows->links()); ?>

    </div>
<?php endif; ?>
<?php /**PATH C:\laragon\www\bilaiGhor\resources\views\backEnd\reports\partials\profit_loss_table.blade.php ENDPATH**/ ?>