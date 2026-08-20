
<?php $__env->startSection('title','Facebook Conversion API Settings'); ?>

<?php $__env->startSection('css'); ?>
<style>
    .card {
        border: none;
        box-shadow: 0 0 20px rgba(18, 38, 63, 0.03);
        border-radius: 12px;
        overflow: hidden;
    }
    .card-header {
        background: #fff;
        border-bottom: 1px solid #f1f5f7;
        padding: 20px 25px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .card-title {
        font-size: 16px;
        font-weight: 700;
        color: #2d3436;
        margin: 0;
    }
    .header-icon {
        width: 35px;
        height: 35px;
        background: rgba(10, 207, 151, 0.08);
        color: #0acf97;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
    }
    .form-label {
        font-weight: 600;
        font-size: 13px;
        color: #636e72;
        margin-bottom: 6px;
    }
    .form-control {
        background-color: #fbfcff;
        border: 1px solid #eef2f7;
        padding: 11px 14px;
        border-radius: 8px;
        font-size: 14px;
    }
    .form-control:focus {
        background-color: #fff;
        border-color: #0acf97;
        box-shadow: 0 0 0 3px rgba(10, 207, 151, 0.15);
    }
    .small-help {
        font-size: 12px;
        color: #95a5a6;
    }
    .btn-submit {
        background: linear-gradient(45deg, #0acf97, #06b6d4);
        border: none;
        color: white;
        padding: 10px 24px;
        font-weight: 600;
        letter-spacing: .4px;
        border-radius: 40px;
        box-shadow: 0 4px 14px rgba(10, 207, 151, 0.35);
    }
    .btn-submit:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 18px rgba(10, 207, 151, 0.45);
    }
    .form-switch .form-check-input {
        width: 3rem;
        height: 1.5rem;
    }
</style>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid">
    <div class="row mb-3 mt-3">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <div>
                <h4 class="page-title mb-0" style="font-weight: 700; color: #2d3436;">
                    Facebook Conversion API Settings
                </h4>
                <p class="text-muted font-size-13 mb-0">
                    এখানে Facebook CAPI এর Pixel ID এবং Access Token সংরক্ষণ করবেন।
                </p>
            </div>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-7 col-xl-6">
            <div class="card">
                <div class="card-header">
                    <div class="header-icon">
                        <i class="fe-share-2"></i>
                    </div>
                    <h5 class="card-title mb-0">Credentials Configuration</h5>
                </div>
                <div class="card-body">
                    <form action="<?php echo e(route('admin.facebook_capi.update')); ?>" method="POST">
                        <?php echo csrf_field(); ?>

                        <div class="mb-3">
                            <label class="form-label" for="pixel_id">
                                Facebook Pixel ID <span class="text-danger">*</span>
                            </label>
                            <input
                                type="text"
                                class="form-control <?php $__errorArgs = ['pixel_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                id="pixel_id"
                                name="pixel_id"
                                value="<?php echo e(old('pixel_id', $setting->pixel_id ?? '')); ?>"
                                placeholder="e.g. 123456789012345"
                                required
                            >
                            <small class="small-help">
                                Facebook Events Manager থেকে Pixel ID কপি করে এখানে পেস্ট করুন।
                            </small>
                            <?php $__errorArgs = ['pixel_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <div class="invalid-feedback"><?php echo e($message); ?></div>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="access_token">
                                Long-lived Access Token <span class="text-danger">*</span>
                            </label>
                            <textarea
                                class="form-control <?php $__errorArgs = ['access_token'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                id="access_token"
                                name="access_token"
                                rows="3"
                                placeholder="Paste your long-lived access token here"
                                required
                            ><?php echo e(old('access_token', $setting->access_token ?? '')); ?></textarea>
                            <small class="small-help">
                                Facebook Developer Tools থেকে generated CAPI এর long-lived access token এখানে রাখবেন।
                            </small>
                            <?php $__errorArgs = ['access_token'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <div class="invalid-feedback"><?php echo e($message); ?></div>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="test_event_code">
                                Test Event Code (optional)
                            </label>
                            <input
                                type="text"
                                class="form-control <?php $__errorArgs = ['test_event_code'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                id="test_event_code"
                                name="test_event_code"
                                value="<?php echo e(old('test_event_code', $setting->test_event_code ?? '')); ?>"
                                placeholder="e.g. TEST1234"
                            >
                            <small class="small-help">
                                Events Manager &gt; Test Events থেকে পাওয়া Test Event Code (যদি ব্যবহার করেন)।
                            </small>
                            <?php $__errorArgs = ['test_event_code'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <div class="invalid-feedback"><?php echo e($message); ?></div>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>

                        <div class="mb-4 form-check form-switch">
                            <input
                                class="form-check-input"
                                type="checkbox"
                                id="status"
                                name="status"
                                value="1"
                                <?php echo e(old('status', $setting->status ?? 1) ? 'checked' : ''); ?>

                            >
                            <label class="form-check-label" for="status">
                                Facebook CAPI Active রাখুন
                            </label>
                        </div>

                        <div class="text-end">
                            <button type="submit" class="btn btn-submit">
                                <i class="fe-save me-1"></i> Save Settings
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>


<?php echo $__env->make('backEnd.layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\bilaiGhor\resources\views/backEnd/settings/facebook_capi.blade.php ENDPATH**/ ?>