
<?php $__env->startSection('title', 'OTP যাচাই'); ?>

<?php $__env->startSection('shell_class', 'app-shell--login'); ?>
<?php $__env->startSection('body_class', 'delivery-login-page'); ?>

<?php $__env->startSection('app_header'); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('header_title', ''); ?>

<?php $__env->startPush('css'); ?>
<style>
<?php echo $__env->make('delivery.auth.partials.card-styles', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
.otp-input {
    letter-spacing: 0.35em;
    font-size: 1.25rem;
    font-weight: 700;
    text-align: center;
}
.resend-hint { font-size: 0.82rem; color: #64748b; text-align: center; margin-top: 1rem; }
.resend-hint button {
    background: none;
    border: none;
    color: #2563eb;
    font-weight: 700;
    cursor: pointer;
    padding: 0;
    text-decoration: underline;
}
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<div class="login-screen">
    <div class="login-panel">
        <div class="login-brand" aria-hidden="true">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a3.375 3.375 0 106.75 0 3.375 3.375 0 00-6.75 0zM12 2.25c-2.429 0-4.548.956-6.168 2.523A9.978 9.978 0 002.25 12c0 5.385 4.365 9.75 9.75 9.75s9.75-4.365 9.75-9.75S17.385 2.25 12 2.25z"/>
            </svg>
        </div>
        <h1>OTP যাচাই</h1>
        <p class="login-lead">
            <strong><?php echo e($maskedPhone); ?></strong> নম্বরে পাঠানো ৬ অঙ্কের কোড লিখুন।
        </p>

        <form method="post" action="<?php echo e(route('delivery.password.verify.post')); ?>">
            <?php echo csrf_field(); ?>
            <div class="login-field">
                <label for="otp">ভেরিফিকেশন কোড</label>
                <div class="login-input-wrap">
                    <input id="otp" class="otp-input" type="text" name="otp" value="<?php echo e(old('otp')); ?>" placeholder="000000" required inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code">
                </div>
            </div>
            <button type="submit" class="login-submit">যাচাই করুন</button>
        </form>

        <p class="resend-hint">
            কোড পাননি?
            <form method="post" action="<?php echo e(route('delivery.password.resend')); ?>" style="display:inline;">
                <?php echo csrf_field(); ?>
                <button type="submit">আবার পাঠান</button>
            </form>
        </p>

        <a href="<?php echo e(route('delivery.password.request')); ?>" class="auth-back-link">← নম্বর পরিবর্তন</a>
        <a href="<?php echo e(route('delivery.login')); ?>" class="auth-back-link" style="margin-top:0.5rem;">← লগইন</a>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('delivery.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\bilaiGhor\resources\views\delivery\auth\verify-otp.blade.php ENDPATH**/ ?>