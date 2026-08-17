
<?php $__env->startSection('title', 'পাসওয়ার্ড রিসেট'); ?>

<?php $__env->startSection('shell_class', 'app-shell--login'); ?>
<?php $__env->startSection('body_class', 'delivery-login-page'); ?>

<?php $__env->startSection('app_header'); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('header_title', ''); ?>

<?php $__env->startPush('css'); ?>
<style>
<?php echo $__env->make('delivery.auth.partials.card-styles', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<div class="login-screen">
    <div class="login-panel">
        <div class="login-brand" aria-hidden="true">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z"/>
            </svg>
        </div>
        <h1>পাসওয়ার্ড ভুলে গেছেন?</h1>
        <p class="login-lead">নিবন্ধিত মোবাইল নম্বর দিন। ৬ অঙ্কের OTP SMS-এ পাঠানো হবে।</p>

        <form method="post" action="<?php echo e(route('delivery.password.email')); ?>">
            <?php echo csrf_field(); ?>
            <div class="login-field">
                <label for="fp-phone">মোবাইল নম্বর</label>
                <div class="login-input-wrap">
                    <svg class="icon-left" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                    <input id="fp-phone" type="tel" name="phone" value="<?php echo e(old('phone')); ?>" placeholder="01XXXXXXXXX" required autocomplete="username" inputmode="tel">
                </div>
            </div>
            <button type="submit" class="login-submit">OTP পাঠান</button>
        </form>

        <a href="<?php echo e(route('delivery.login')); ?>" class="auth-back-link">← লগইনে ফিরে যান</a>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('delivery.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\bilaiGhor\resources\views\delivery\auth\forgot-password.blade.php ENDPATH**/ ?>