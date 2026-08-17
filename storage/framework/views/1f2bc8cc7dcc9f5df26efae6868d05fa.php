
<?php $__env->startSection('title','Rider wallet'); ?>
<?php $__env->startSection('content'); ?>
<div class="container-fluid">
    <div class="mb-3 d-flex justify-content-between">
        <div>
            <h4 class="m-0"><?php echo e($boy->name); ?> — Wallet ৳<?php echo e(number_format($boy->wallet_balance,2)); ?></h4>
            <small class="text-muted">Panel: /delivery/login — Phone: <?php echo e($boy->phone); ?></small>
        </div>
        <a href="<?php echo e(route('admin.delivery-boys.index')); ?>" class="btn btn-light btn-sm">Back</a>
    </div>
    <div class="row">
        <div class="col-md-5">
            <div class="card mb-3">
                <div class="card-header">Pay salary (credits wallet)</div>
                <div class="card-body">
                    <form method="post" action="<?php echo e(route('admin.delivery-boys.salary')); ?>">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="delivery_boy_id" value="<?php echo e($boy->id); ?>">
                        <div class="mb-2"><label class="form-label">Amount (৳)</label><input type="number" name="amount" class="form-control" min="1" step="0.01" required></div>
                        <div class="mb-2"><label class="form-label">Month (YYYY-MM)</label><input type="text" name="salary_month" class="form-control" value="<?php echo e(date('Y-m')); ?>" pattern="\d{4}-\d{2}" required></div>
                        <div class="mb-2"><label class="form-label">Note</label><input type="text" name="note" class="form-control"></div>
                        <button class="btn btn-success btn-sm">Credit salary</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-7">
            <div class="card">
                <div class="card-header">Transactions</div>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead><tr><th>Date</th><th>Type</th><th>±</th><th>Balance</th><th>Note</th></tr></thead>
                        <tbody>
                        <?php $__currentLoopData = $tx; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $t): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr>
                                <td><small><?php echo e($t->created_at); ?></small></td>
                                <td><?php echo e($t->type); ?></td>
                                <td><?php echo e($t->direction); ?> ৳<?php echo e(number_format($t->amount,2)); ?></td>
                                <td>৳<?php echo e(number_format($t->balance_after,2)); ?></td>
                                <td><small><?php echo e(\Illuminate\Support\Str::limit($t->note,40)); ?></small></td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tbody>
                    </table>
                </div>
                <div class="card-body"><?php echo e($tx->links()); ?></div>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('backEnd.layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\bilaiGhor\resources\views\backEnd\delivery_boys\wallet.blade.php ENDPATH**/ ?>