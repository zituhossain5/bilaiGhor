
<?php $__env->startSection('title', 'নতুন অর্ডার স্ট্যাটাস'); ?>

<?php $__env->startSection('css'); ?>
<?php echo $__env->make('backEnd.orderstatus.partials.os_styles', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid order-status-shell order-status-page">

    <div class="os-page-header">
        <div>
            <h4>নতুন অর্ডার স্ট্যাটাস</h4>
            <p class="os-sub">অর্ডার ট্র্যাকিংয়ের জন্য নতুন স্ট্যাটাস যোগ করুন</p>
        </div>
        <a href="<?php echo e(route('orderstatus.index')); ?>" class="os-btn-ghost">
            <i class="fas fa-arrow-left"></i> তালিকায় ফিরুন
        </a>
    </div>

    <form action="<?php echo e(route('orderstatus.store')); ?>" method="POST" data-parsley-validate>
        <?php echo csrf_field(); ?>
        <div class="row os-form-layout g-3">
            <div class="col-lg-8">
                <div class="os-card">
                    <div class="os-card-head">
                        <span class="os-card-icon"><i class="fas fa-flag"></i></span>
                        <h6>স্ট্যাটাস বিবরণ</h6>
                    </div>
                    <div class="os-card-body">
                        <div class="mb-0">
                            <label for="name" class="os-label">স্ট্যাটাস নাম <span class="text-danger">*</span></label>
                            <input type="text" class="os-input <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                   name="name" value="<?php echo e(old('name')); ?>" id="name"
                                   placeholder="যেমন: পেন্ডিং, প্রসেসিং, ডেলিভার্ড" required>
                            <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <div class="invalid-feedback d-block"><?php echo e($message); ?></div>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="os-card">
                    <div class="os-card-head">
                        <span class="os-card-icon"><i class="fas fa-cog"></i></span>
                        <h6>কনফিগারেশন</h6>
                    </div>
                    <div class="os-card-body">
                        <div class="os-toggle-box">
                            <div>
                                <h6>সক্রিয় স্ট্যাটাস</h6>
                                <p>এই স্ট্যাটাস ব্যবহারযোগ্য রাখুন</p>
                            </div>
                            <label class="os-switch">
                                <input type="checkbox" name="status" value="1" checked>
                                <span class="os-slider"></span>
                            </label>
                        </div>
                        <?php $__errorArgs = ['status'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <div class="text-danger small mb-3"><?php echo e($message); ?></div>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>

                        <button type="submit" class="btn os-btn-save">
                            <i class="fas fa-check me-1"></i> সংরক্ষণ করুন
                        </button>
                    </div>
                </div>

                <div class="os-tip">
                    <i class="fas fa-info-circle"></i>
                    <span>স্পষ্ট নাম ব্যবহার করুন — গ্রাহক ও অ্যাডমিন উভয়ই অর্ডার অগ্রগতি বুঝতে পারবে।</span>
                </div>
            </div>
        </div>
    </form>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('script'); ?>
<script src="<?php echo e(asset('public/backEnd/')); ?>/assets/libs/parsleyjs/parsley.min.js"></script>
<script src="<?php echo e(asset('public/backEnd/')); ?>/assets/js/pages/form-validation.init.js"></script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('backEnd.layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\bilaiGhor\resources\views\backEnd\orderstatus\create.blade.php ENDPATH**/ ?>