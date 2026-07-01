<?php $__env->startSection('title','Customer Register'); ?>
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
    align-items: flex-start;
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
/* Shared input styling — covers both new .bilai-auth-input and existing .custom-input inside the card */
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
.bilai-auth-input.no-icon,
.bilai-auth-card .custom-input.no-icon { padding-right: 14px; }
textarea.bilai-auth-card .custom-input,
.bilai-auth-card textarea.custom-input {
    height: auto; min-height: 80px; padding-top: 12px; padding-bottom: 12px; resize: vertical;
}
input[type="file"].bilai-auth-input,
.bilai-auth-card input[type="file"].custom-input { padding-left: 14px; height: auto; padding-top: 10px; padding-bottom: 10px; }
/* Legacy input-group wrappers used by hidden seller fields */
.bilai-auth-card .custom-input-group { position: relative; margin-bottom: 16px; }
.bilai-auth-card .custom-input-group label {
    display: block; font-size: 13px; font-weight: 600; color: var(--bilai-body-title, #2a1505); margin-bottom: 6px;
}
.bilai-auth-card .input-icon { position: absolute; left: 14px; top: 43px; color: #c0aa90; font-size: 14px; }
/* Eye toggle button */
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
/* Submit button */
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
/* Or divider */
.bilai-auth-divider {
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 20px 0;
    font-size: 13px;
    color: #b0a080;
}
.bilai-auth-divider::before,
.bilai-auth-divider::after { content: ''; flex: 1; height: 1px; background: var(--bilai-border, #dccab2); }
/* Social buttons */
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
/* Reseller / Seller checkbox labels */
.bilai-toggle-label {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 11px 14px;
    border: 1.5px solid var(--bilai-border, #dccab2);
    border-radius: 8px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 600;
    color: var(--bilai-body-title, #2a1505);
    background: #fdfaf7;
    transition: border-color 0.18s;
}
.bilai-toggle-label:hover { border-color: var(--bilai-primary, #e8861a); }
.bilai-toggle-label input[type="checkbox"] {
    width: 17px; height: 17px;
    accent-color: var(--bilai-primary, #e8861a);
    cursor: pointer; flex-shrink: 0;
}
@media (max-width: 480px) {
    .bilai-auth-card { padding: 24px 18px 28px; }
}
/* BilaiGhor Customer Auth End */
</style>

<section class="bilai-auth-bg">
    <div class="bilai-auth-card">

        
        <div class="bilai-auth-tabs">
            <a href="<?php echo e(route('customer.login')); ?>" class="bilai-auth-tab">Login</a>
            <a href="<?php echo e(route('customer.register')); ?>" class="bilai-auth-tab active">Register</a>
        </div>

        <form action="<?php echo e(route('customer.store')); ?>" method="POST" enctype="multipart/form-data" data-parsley-validate="">
            <?php echo csrf_field(); ?>

            
            <div class="bilai-auth-field">
                <label class="bilai-auth-label" for="name">
                    আপনার নাম <span id="owner_name_label" style="display:none;">(মালিকের নাম)</span>
                    <span class="text-danger">*</span>
                </label>
                <input type="text" id="name" name="name"
                       class="bilai-auth-input no-icon <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                       value="<?php echo e(old('name')); ?>"
                       placeholder="পুরো নাম লিখুন" required>
                <?php $__errorArgs = ['name'];
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
                <label class="bilai-auth-label" for="phone">মোবাইল নাম্বার <span class="text-danger">*</span></label>
                <input type="text" id="phone" name="phone"
                       class="bilai-auth-input no-icon <?php $__errorArgs = ['phone'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                       value="<?php echo e(old('phone')); ?>"
                       placeholder="017xxxxxxxx" required>
                <?php $__errorArgs = ['phone'];
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

            
            <div class="custom-input-group" id="email_field" style="display:none;">
                <label for="email">ইমেইল <span class="text-danger">*</span></label>
                <i class="fas fa-envelope input-icon"></i>
                <input type="email" id="email"
                       class="custom-input <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                       name="email" value="<?php echo e(old('email')); ?>"
                       placeholder="email@example.com">
                <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                    <span class="text-danger small mt-1 d-block"><?php echo e($message); ?></span>
                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            
            <div class="custom-input-group" id="shop_name_field" style="display:none;">
                <label for="shop_name">শপের নাম <span class="text-danger">*</span></label>
                <i class="fas fa-store input-icon"></i>
                <input type="text" id="shop_name"
                       class="custom-input <?php $__errorArgs = ['shop_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                       name="shop_name" value="<?php echo e(old('shop_name')); ?>"
                       placeholder="আপনার শপের নাম লিখুন">
                <?php $__errorArgs = ['shop_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                    <span class="text-danger small mt-1 d-block"><?php echo e($message); ?></span>
                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            
            <div class="custom-input-group" id="slug_field" style="display:none;">
                <label for="slug">শপ URL (Slug) <span class="text-danger">*</span></label>
                <i class="fas fa-link input-icon"></i>
                <input type="text" id="slug"
                       class="custom-input <?php $__errorArgs = ['slug'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                       name="slug" value="<?php echo e(old('slug')); ?>"
                       placeholder="my-shop-name">
                <small class="text-muted">শুধুমাত্র ইংরেজি অক্ষর, সংখ্যা ও হাইফেন (-)</small>
                <?php $__errorArgs = ['slug'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                    <span class="text-danger small mt-1 d-block"><?php echo e($message); ?></span>
                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            
            <div class="custom-input-group" id="address_field" style="display:none;">
                <label for="address">ঠিকানা</label>
                <i class="fas fa-map-marker-alt input-icon"></i>
                <textarea id="address"
                          class="custom-input <?php $__errorArgs = ['address'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                          name="address" rows="2"
                          placeholder="আপনার শপের ঠিকানা"><?php echo e(old('address')); ?></textarea>
                <?php $__errorArgs = ['address'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                    <span class="text-danger small mt-1 d-block"><?php echo e($message); ?></span>
                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            
            <div class="custom-input-group" id="logo_field" style="display:none;">
                <label for="logo">শপ লোগো</label>
                <input type="file" id="logo"
                       class="custom-input no-icon <?php $__errorArgs = ['logo'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                       name="logo" accept="image/*">
                <small class="text-muted">সর্বোচ্চ 2MB</small>
                <?php $__errorArgs = ['logo'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                    <span class="text-danger small mt-1 d-block"><?php echo e($message); ?></span>
                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            
            <div class="custom-input-group" id="banner_field" style="display:none;">
                <label for="banner">শপ ব্যানার</label>
                <input type="file" id="banner"
                       class="custom-input no-icon <?php $__errorArgs = ['banner'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                       name="banner" accept="image/*">
                <small class="text-muted">সর্বোচ্চ 3MB</small>
                <?php $__errorArgs = ['banner'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                    <span class="text-danger small mt-1 d-block"><?php echo e($message); ?></span>
                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            
            <div id="seller_verification_note" style="display:none;">
                <div style="padding:14px 16px;margin:0 0 12px;border-radius:8px;border:1px solid rgba(232,134,26,0.3);background:#fff8f0;color:#5a3a10;font-size:13px;line-height:1.55;">
                    <strong style="display:block;margin-bottom:4px;"><i class="fas fa-info-circle" style="color:var(--bilai-primary,#e8861a);margin-right:4px;"></i> একাউন্ট ভেরিফিকেশন</strong>
                    লগইন করার পর <strong>ভেন্ডর ড্যাশবোর্ড → ভেরিফিকেশন</strong> থেকে এনআইডি ও আপনার ছবি আপলোড করুন।
                </div>
            </div>

            
            <div class="bilai-auth-field">
                <label class="bilai-auth-label" for="password">পাসওয়ার্ড <span class="text-danger">*</span></label>
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
                        <i class="fa fa-eye"></i>
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

            
            <div class="bilai-auth-field" id="password_confirmation_field">
                <label class="bilai-auth-label" for="password_confirmation">পাসওয়ার্ড নিশ্চিত করুন</label>
                <div style="position:relative;">
                    <input type="password" id="password_confirmation" name="password_confirmation"
                           class="bilai-auth-input <?php $__errorArgs = ['password_confirmation'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                           placeholder="••••••••">
                    <button type="button" class="bilai-eye-btn" onclick="bilaiTogglePass('password_confirmation', this)" aria-label="Show password">
                        <i class="fa fa-eye"></i>
                    </button>
                </div>
                <?php $__errorArgs = ['password_confirmation'];
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

            
            <?php if(($generalsetting?->reseller_enabled ?? 1) == 1): ?>
            <div class="bilai-auth-field" style="margin-top:8px;">
                <label class="bilai-toggle-label">
                    <input type="checkbox" id="is_reseller" name="is_reseller" value="1"
                           <?php echo e(old('is_reseller') ? 'checked' : ''); ?>

                           onchange="toggleResellerFields()">
                    আমি রিসেলার একাউন্ট তৈরি করতে চাই
                </label>
            </div>

            
            <div class="custom-input-group" id="reseller_shop_name_field" style="display:none;">
                <label for="reseller_shop_name">Shop Name <span class="text-danger">*</span></label>
                <i class="fas fa-store input-icon"></i>
                <input type="text" id="reseller_shop_name"
                       class="custom-input <?php $__errorArgs = ['reseller_shop_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                       name="reseller_shop_name" value="<?php echo e(old('reseller_shop_name')); ?>"
                       placeholder="Enter your shop name">
                <?php $__errorArgs = ['reseller_shop_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                    <span class="text-danger small mt-1 d-block"><?php echo e($message); ?></span>
                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            
            <div id="reseller_verification_note" style="display:none;">
                <div style="padding:14px 16px;margin:0 0 12px;border-radius:8px;border:1px solid rgba(232,134,26,0.3);background:#fff8f0;color:#5a3a10;font-size:13px;line-height:1.55;">
                    <strong style="display:block;margin-bottom:4px;"><i class="fas fa-info-circle" style="color:var(--bilai-primary,#e8861a);margin-right:4px;"></i> একাউন্ট ভেরিফিকেশন</strong>
                    রেজিস্ট্রেশনের পর লগইন করে <strong>রিসেলার ড্যাশবোর্ড → ভেরিফিকেশন</strong> পেজ থেকে ভোটার আইডি ও আপনার ছবি আপলোড করুন।
                </div>
            </div>
            <?php endif; ?>

            
            <?php if(($generalsetting?->vendor_enabled ?? 1) == 1): ?>
            <div class="bilai-auth-field" style="margin-top:4px;">
                <label class="bilai-toggle-label">
                    <input type="checkbox" id="is_seller" name="is_seller" value="1"
                           <?php echo e(old('is_seller') ? 'checked' : ''); ?>

                           onchange="toggleSellerFields()">
                    আমি সেলার একাউন্ট তৈরি করতে চাই
                </label>
            </div>
            <?php endif; ?>

            
            <div style="margin-top:20px;">
                <button class="bilai-auth-btn" type="submit">রেজিস্ট্রেশন করুন</button>
            </div>

        </form>

        
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

function toggleResellerFields() {
    var isReseller = document.getElementById('is_reseller').checked;

    var resellerShopNameField = document.getElementById('reseller_shop_name_field');
    var resellerVerificationNote = document.getElementById('reseller_verification_note');
    var emailField = document.getElementById('email_field');

    if (resellerShopNameField) {
        resellerShopNameField.style.display = isReseller ? 'block' : 'none';
        var shopNameInput = document.getElementById('reseller_shop_name');
        if (shopNameInput) {
            if (isReseller) { shopNameInput.setAttribute('required', 'required'); }
            else { shopNameInput.removeAttribute('required'); }
        }
    }

    if (resellerVerificationNote) {
        resellerVerificationNote.style.display = isReseller ? 'block' : 'none';
    }

    if (emailField) {
        if (isReseller) {
            emailField.style.display = 'block';
            var emailInput = document.getElementById('email');
            if (emailInput) { emailInput.setAttribute('required', 'required'); }
        } else {
            var isSeller = document.getElementById('is_seller') ? document.getElementById('is_seller').checked : false;
            if (!isSeller) {
                emailField.style.display = 'none';
                var emailInput = document.getElementById('email');
                if (emailInput) { emailInput.removeAttribute('required'); }
            }
        }
    }

    // password_confirmation: manage required only (field is always visible)
    var passwordConfirmInput = document.getElementById('password_confirmation');
    if (passwordConfirmInput) {
        if (isReseller) {
            passwordConfirmInput.setAttribute('required', 'required');
        } else {
            var isSeller = document.getElementById('is_seller') ? document.getElementById('is_seller').checked : false;
            if (!isSeller) { passwordConfirmInput.removeAttribute('required'); }
        }
    }

    // Uncheck seller if reseller selected
    if (isReseller) {
        var sellerCheckbox = document.getElementById('is_seller');
        if (sellerCheckbox && sellerCheckbox.checked) {
            sellerCheckbox.checked = false;
            toggleSellerFields();
        }
    }
}

function toggleSellerFields() {
    var isSeller = document.getElementById('is_seller').checked;
    // password_confirmation_field removed — it is always visible now
    var fields = ['email_field', 'shop_name_field', 'slug_field', 'address_field', 'logo_field', 'banner_field'];
    var sellerNote = document.getElementById('seller_verification_note');

    fields.forEach(function(fieldId) {
        var field = document.getElementById(fieldId);
        if (field) {
            field.style.display = isSeller ? 'block' : 'none';
            var inputs = field.querySelectorAll('input, textarea');
            inputs.forEach(function(input) {
                if (isSeller && fieldId !== 'address_field' && fieldId !== 'logo_field' && fieldId !== 'banner_field') {
                    input.setAttribute('required', 'required');
                } else {
                    input.removeAttribute('required');
                }
            });
        }
    });

    if (sellerNote) { sellerNote.style.display = isSeller ? 'block' : 'none'; }

    var ownerLabel = document.getElementById('owner_name_label');
    if (ownerLabel) { ownerLabel.style.display = isSeller ? 'inline' : 'none'; }

    // password_confirmation: manage required only
    var passwordConfirmInput = document.getElementById('password_confirmation');
    if (passwordConfirmInput) {
        if (isSeller) {
            passwordConfirmInput.setAttribute('required', 'required');
        } else {
            var isReseller = document.getElementById('is_reseller') ? document.getElementById('is_reseller').checked : false;
            if (!isReseller) { passwordConfirmInput.removeAttribute('required'); }
        }
    }

    // Uncheck reseller if seller selected
    if (isSeller) {
        var resellerCheckbox = document.getElementById('is_reseller');
        if (resellerCheckbox && resellerCheckbox.checked) {
            resellerCheckbox.checked = false;
            toggleResellerFields();
        }
    }
}

// Auto-generate slug from shop name
document.addEventListener('DOMContentLoaded', function() {
    var shopNameInput = document.getElementById('shop_name');
    var slugInput = document.getElementById('slug');
    if (shopNameInput && slugInput) {
        shopNameInput.addEventListener('input', function() {
            slugInput.value = this.value.toLowerCase()
                .replace(/[^a-z0-9\s-]/g, '')
                .replace(/\s+/g, '-')
                .replace(/-+/g, '-')
                .trim();
        });
    }

    // Restore state on page load (e.g. after validation error)
    var sellerEl   = document.getElementById('is_seller');
    var resellerEl = document.getElementById('is_reseller');
    if (sellerEl && sellerEl.checked)     { toggleSellerFields(); }
    if (resellerEl && resellerEl.checked) { toggleResellerFields(); }
});
</script>

<?php $__env->stopSection(); ?>

<?php $__env->startPush('script'); ?>
<script src="<?php echo e(asset('public/frontEnd/js/parsley.min.js')); ?>"></script>
<script src="<?php echo e(asset('public/frontEnd/js/form-validation.init.js')); ?>"></script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('frontEnd.layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\projects\bilaiGhor\resources\views/frontEnd/layouts/customer/register.blade.php ENDPATH**/ ?>