<?php $__env->startSection('title','Customer Login'); ?>
<?php
    $generalsetting = \App\Models\GeneralSetting::first();
?>
<?php $__env->startSection('content'); ?>

<style>
/* BilaiGhor Customer Auth Start */
.bilai-auth-bg {
    min-height: 80vh;
    background: #fdf8f3;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 48px 15px;
}
.bilai-auth-card {
    background: #fff;
    border-radius: 16px;
    border: 1px solid var(--bilai-border, #dccab2);
    box-shadow: 0 4px 32px rgba(42,21,5,0.09);
    width: 100%;
    max-width: 420px;
    padding: 32px 36px 36px;
    font-family: var(--bilai-font-main, 'DM Sans', sans-serif);
}
.bilai-auth-tabs {
    display: flex;
    background: #f5ede0;
    border-radius: 10px;
    padding: 4px;
    margin-bottom: 28px;
    gap: 4px;
}
.bilai-auth-tab {
    flex: 1;
    text-align: center;
    padding: 9px 0;
    border-radius: 7px;
    font-size: 14px;
    font-weight: 600;
    color: var(--bilai-text, #4a3728);
    text-decoration: none !important;
    transition: background 0.18s, color 0.18s;
}
.bilai-auth-tab.active {
    background: #fff;
    color: var(--bilai-body-title, #2a1505);
    box-shadow: 0 1px 5px rgba(42,21,5,0.10);
}
.bilai-auth-tab:hover { color: var(--bilai-body-title, #2a1505); }
.bilai-auth-label {
    display: block;
    font-size: 13px;
    font-weight: 600;
    color: var(--bilai-body-title, #2a1505);
    margin-bottom: 6px;
}
.bilai-auth-field {
    position: relative;
    margin-bottom: 16px;
}
.bilai-auth-input,
.bilai-auth-card .custom-input {
    width: 100%;
    height: 46px;
    padding: 0 44px 0 14px;
    border: 1.5px solid var(--bilai-border, #dccab2);
    border-radius: 8px;
    font-size: 14px;
    color: var(--bilai-text, #4a3728);
    background: #fdfaf7;
    transition: border-color 0.18s;
    outline: none;
    box-shadow: none;
    font-family: var(--bilai-font-main, 'DM Sans', sans-serif);
}
.bilai-auth-input:focus,
.bilai-auth-card .custom-input:focus { border-color: var(--bilai-primary, #e8861a); background: #fff; box-shadow: none; }
.bilai-auth-input.is-invalid,
.bilai-auth-card .custom-input.is-invalid { border-color: #dc3545; }
.bilai-auth-input.no-icon { padding-right: 14px; }
.bilai-eye-btn {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: #c0aa90;
    cursor: pointer;
    padding: 2px;
    line-height: 1;
    font-size: 15px;
}
.bilai-eye-btn:focus { outline: none; }
.bilai-auth-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 18px;
    font-size: 13px;
}
.bilai-auth-check-label {
    display: flex;
    align-items: center;
    gap: 7px;
    cursor: pointer;
    color: var(--bilai-text, #4a3728);
    font-weight: 500;
}
.bilai-auth-check-label input[type="checkbox"] {
    width: 15px;
    height: 15px;
    accent-color: var(--bilai-primary, #e8861a);
    cursor: pointer;
    flex-shrink: 0;
}
.bilai-auth-forgot {
    color: var(--bilai-text, #4a3728);
    font-size: 13px;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 4px;
}
.bilai-auth-forgot:hover { color: var(--bilai-primary, #e8861a); }
.bilai-auth-btn {
    display: block;
    width: 100%;
    height: 48px;
    background: var(--bilai-body-title, #2a1505);
    color: #fff;
    border: none;
    border-radius: 10px;
    font-size: 15px;
    font-weight: 700;
    cursor: pointer;
    transition: opacity 0.18s;
    font-family: var(--bilai-font-main, 'DM Sans', sans-serif);
    letter-spacing: 0.3px;
}
.bilai-auth-btn:hover { opacity: 0.85; }
.bilai-auth-divider {
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 20px 0;
    font-size: 13px;
    color: #b0a080;
}
.bilai-auth-divider::before,
.bilai-auth-divider::after {
    content: '';
    flex: 1;
    height: 1px;
    background: var(--bilai-border, #dccab2);
}
.bilai-social-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    width: 100%;
    height: 44px;
    background: #fff;
    border: 1.5px solid var(--bilai-border, #dccab2);
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    color: var(--bilai-body-title, #2a1505);
    text-decoration: none !important;
    margin-bottom: 10px;
    transition: border-color 0.18s, background 0.18s;
    font-family: var(--bilai-font-main, 'DM Sans', sans-serif);
}
.bilai-social-btn:last-child { margin-bottom: 0; }
.bilai-social-btn:hover { border-color: var(--bilai-primary, #e8861a); background: #fdf8f3; color: var(--bilai-body-title, #2a1505); }
.bilai-social-btn .fab.fa-google { color: #ea4335; font-size: 16px; }
.bilai-social-btn .fab.fa-facebook-f { color: #1877f2; font-size: 16px; }
/* demo credentials block */
.bilai-demo-block {
    margin-top: 20px;
    padding-top: 16px;
    border-top: 1px dashed var(--bilai-border, #dccab2);
    font-size: 13px;
}
.bilai-demo-row {
    display: flex;
    align-items: center;
    gap: 6px;
    flex-wrap: wrap;
    margin-top: 6px;
}
.bilai-demo-row input.form-control {
    flex: 1;
    min-width: 0;
    height: 32px;
    font-size: 12px;
    border: 1px solid #ddd;
    border-radius: 6px;
}
.bilai-demo-use-btn {
    border: 1.5px solid var(--bilai-primary, #e8861a);
    color: var(--bilai-primary, #e8861a);
    background: transparent;
    font-weight: 600;
    padding: 4px 12px;
    border-radius: 6px;
    font-size: 12px;
    cursor: pointer;
    transition: background 0.15s, color 0.15s;
}
.bilai-demo-use-btn:hover { background: var(--bilai-primary, #e8861a); color: #fff; }
@media (max-width: 480px) {
    .bilai-auth-card { padding: 24px 18px 28px; }
}
/* BilaiGhor Customer Auth End */
</style>

<section class="bilai-auth-bg">
    <div class="bilai-auth-card">

        
        <div class="bilai-auth-tabs">
            <a href="<?php echo e(route('customer.login')); ?>" class="bilai-auth-tab active">Login</a>
            <a href="<?php echo e(route('customer.register')); ?>" class="bilai-auth-tab">Register</a>
        </div>

        
        <?php if(session('info')): ?>
        <div style="padding:10px 14px;margin-bottom:16px;background:#fff8f0;border:1px solid var(--bilai-primary,#e8861a);border-radius:8px;font-size:13px;color:#5a3a10;">
            <?php echo e(session('info')); ?>

        </div>
        <?php endif; ?>

        
        <form action="<?php echo e(route('customer.signin')); ?>" method="POST">
            <?php echo csrf_field(); ?>

            
            <div class="bilai-auth-field">
                <label class="bilai-auth-label" for="login">Email / Mobile <span class="text-danger">*</span></label>
                <input type="text" id="login" name="login"
                       class="bilai-auth-input no-icon <?php $__errorArgs = ['login'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                       value="<?php echo e(old('login')); ?>"
                       placeholder="0167-1518782">
                <?php $__errorArgs = ['login'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                    <span class="text-danger" style="font-size:12px;display:block;margin-top:4px;"><?php echo e($message); ?></span>
                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            
            <div class="bilai-auth-field">
                <label class="bilai-auth-label" for="password">Password <span class="text-danger">*</span></label>
                <div style="position:relative;">
                    <input type="password" id="password" name="password"
                           class="bilai-auth-input <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                           placeholder="••••••••" required>
                    <button type="button" class="bilai-eye-btn" onclick="bilaiTogglePass('password', this)" aria-label="Show password">
                        <i class="fa fa-eye" id="eye-password"></i>
                    </button>
                </div>
                <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                    <span class="text-danger" style="font-size:12px;display:block;margin-top:4px;"><?php echo e($message); ?></span>
                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            
            <div class="bilai-auth-row">
                <label class="bilai-auth-check-label">
                    <input type="checkbox" name="remember" <?php echo e(old('remember') ? 'checked' : ''); ?>>
                    Remember Password
                </label>
                <a href="<?php echo e(route('customer.forgot.password')); ?>" class="bilai-auth-forgot">
                    <i class="fa-solid fa-lock" style="font-size:11px;"></i> Forgot Password?
                </a>
            </div>

            
            <button type="submit" class="bilai-auth-btn">লগিন করুন</button>
        </form>

        
        <?php if(isset($demoMode) && $demoMode): ?>
        <div class="bilai-demo-block">
            <small class="text-muted d-block mb-1">রিসেলার ইউজার</small>
            <div class="bilai-demo-row">
                <input type="text" class="form-control form-control-sm bg-light" value="01631843149" readonly>
                <input type="text" class="form-control form-control-sm bg-light" value="12345678" readonly style="width:90px;flex:none;">
                <button type="button" class="bilai-demo-use-btn" onclick="bilaiDemoFill('01631843149','12345678')">Use</button>
            </div>
            <small class="text-muted d-block mb-1 mt-2">ভেন্ড্রর ইউজার</small>
            <div class="bilai-demo-row">
                <input type="text" class="form-control form-control-sm bg-light" value="01870829343" readonly>
                <input type="text" class="form-control form-control-sm bg-light" value="123456789" readonly style="width:90px;flex:none;">
                <button type="button" class="bilai-demo-use-btn" onclick="bilaiDemoFill('01870829343','123456789')">Use</button>
            </div>
        </div>
        <?php endif; ?>

        
        <div class="bilai-auth-divider">Or</div>

        
        <a href="<?php echo e(route('customer.social.redirect', 'google')); ?>" class="bilai-social-btn">
            <i class="fab fa-google"></i> Continue with Google
        </a>
        <a href="<?php echo e(route('customer.social.redirect', 'facebook')); ?>" class="bilai-social-btn">
            <i class="fab fa-facebook-f"></i> Continue with Facebook
        </a>

    </div>
</section>

<script>
function bilaiTogglePass(fieldId, btn) {
    var field = document.getElementById(fieldId);
    var icon  = btn.querySelector('i');
    if (field.type === 'password') {
        field.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        field.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}
function bilaiDemoFill(login, pass) {
    document.getElementById('login').value    = login;
    document.getElementById('password').value = pass;
}
</script>

<?php $__env->stopSection(); ?>

<?php $__env->startPush('script'); ?>
<script src="<?php echo e(asset('public/frontEnd/js/parsley.min.js')); ?>"></script>
<script src="<?php echo e(asset('public/frontEnd/js/form-validation.init.js')); ?>"></script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('frontEnd.layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\projects\bilaiGhor\resources\views/frontEnd/layouts/customer/login.blade.php ENDPATH**/ ?>