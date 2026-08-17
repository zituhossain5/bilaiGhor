
<?php $__env->startSection('title','Edit Fund Transaction'); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid">

    
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-1">
                <i data-feather="edit-3" class="me-1"></i>
                Edit Fund Transaction / ফান্ড ট্রানজ্যাকশন এডিট
            </h4>
            <small class="text-muted">
                এখানে তুমি ফান্ড ট্রানজ্যাকশনের তথ্য আপডেট করতে পারো। (শুধুমাত্র Admin)
            </small>
        </div>

        <div>
            <a href="<?php echo e(route('admin.fund.index')); ?>" class="btn btn-sm btn-outline-secondary">
                <i data-feather="arrow-left" class="me-1"></i> Back to List
            </a>
        </div>
    </div>

    
    <div class="row">
        <div class="col-lg-6">
            <div class="card shadow-sm border-0" style="border-radius: 12px;">
                <div class="card-header border-0 bg-light" style="border-radius: 12px 12px 0 0;">
                    <strong>
                        <i data-feather="file-text" class="me-1" style="width:16px;height:16px;"></i>
                        Edit Fund Transaction
                    </strong>
                </div>
                <div class="card-body">

                    <form action="<?php echo e(route('admin.fund.update', $transaction->id)); ?>" method="POST">
                        <?php echo csrf_field(); ?>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Direction / ধরণ *</label>
                            <select name="direction" class="form-select <?php $__errorArgs = ['direction'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" required>
                                <option value="in" <?php echo e(old('direction', $transaction->direction) == 'in' ? 'selected' : ''); ?>>IN (+)</option>
                                <option value="out" <?php echo e(old('direction', $transaction->direction) == 'out' ? 'selected' : ''); ?>>OUT (-)</option>
                            </select>
                            <?php $__errorArgs = ['direction'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <span class="invalid-feedback"><?php echo e($message); ?></span>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Source</label>
                            <input type="text" 
                                   name="source" 
                                   class="form-control" 
                                   value="<?php echo e(old('source', $transaction->source)); ?>" 
                                   readonly>
                            <small class="text-muted">Source cannot be changed</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Amount (৳) *</label>
                            <input type="number" 
                                   step="0.01" 
                                   name="amount" 
                                   class="form-control <?php $__errorArgs = ['amount'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" 
                                   value="<?php echo e(old('amount', $transaction->amount)); ?>" 
                                   placeholder="0.00" 
                                   required>
                            <?php $__errorArgs = ['amount'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <span class="invalid-feedback"><?php echo e($message); ?></span>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Note (optional)</label>
                            <textarea name="note" 
                                      class="form-control" 
                                      rows="3" 
                                      placeholder="এই ট্রানজ্যাকশন সম্পর্কে বাড়তি নোট..."><?php echo e(old('note', $transaction->note)); ?></textarea>
                        </div>

                        <div class="mb-3">
                            <small class="text-muted">
                                Created: <?php echo e($transaction->created_at->format('d M Y, h:i A')); ?><br>
                                <?php if($transaction->updated_by): ?>
                                Last Updated: <?php echo e($transaction->updated_at->format('d M Y, h:i A')); ?>

                                <?php endif; ?>
                            </small>
                        </div>

                        <div class="d-flex justify-content-between">
                            <button type="submit" class="btn btn-primary">
                                <i data-feather="save" class="me-1" style="width:16px;height:16px;"></i>
                                Update Transaction
                            </button>

                            <a href="<?php echo e(route('admin.fund.index')); ?>" class="btn btn-outline-secondary">
                                Cancel
                            </a>
                        </div>

                    </form>

                </div>
            </div>
        </div>
    </div>

</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('backEnd.layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\bilaiGhor\resources\views\backEnd\fund\edit.blade.php ENDPATH**/ ?>