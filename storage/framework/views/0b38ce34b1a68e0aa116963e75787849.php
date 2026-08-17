
<?php $__env->startSection('title','Verify OTP'); ?>

<?php $__env->startPush('css'); ?>
<style>
/* BilaiGhor OTP Verification Start */
:root {
    --bilai-otp-primary:      var(--bilai-primary, #F28C00);
    --bilai-otp-primary-dark: var(--bilai-primary-dark, #c96f00);
    --bilai-otp-card-bg:      #FFFDF8;
    --bilai-otp-border:       var(--bilai-border, #E8CDA5);
    --bilai-otp-text:         var(--bilai-text, #2B1A10);
    --bilai-otp-muted:        var(--bilai-muted, #77706A);
}

.bilai-otp-page {
    background: #fdfaf3;
    min-height: 62vh;
    display: flex; align-items: center; justify-content: center;
    padding: 60px 16px 80px;
}
.bilai-otp-card {
    background: var(--bilai-otp-card-bg);
    border: 1px solid var(--bilai-otp-border);
    border-radius: 14px;
    width: 100%; max-width: 470px;
    padding: 40px 38px 34px;
    text-align: center;
}
.bilai-otp-title { font-size: 19px; font-weight: 700; color: var(--bilai-otp-text); margin: 0 0 6px; }
.bilai-otp-sub   { font-size: 13px; color: var(--bilai-otp-muted); margin: 0 0 24px; }

.bilai-otp-boxes { display: flex; justify-content: center; gap: 10px; margin-bottom: 8px; }
.bilai-otp-box {
    width: 50px; height: 52px;
    border: 1px solid var(--bilai-otp-border); border-radius: 8px;
    background: #fff; color: var(--bilai-otp-text);
    font-size: 19px; font-weight: 600; text-align: center;
    -moz-appearance: textfield;
}
.bilai-otp-box::-webkit-outer-spin-button,
.bilai-otp-box::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
.bilai-otp-box:focus {
    outline: none; border-color: var(--bilai-otp-primary);
    box-shadow: 0 0 0 3px rgba(242,140,0,0.10);
}
.bilai-otp-err { font-size: 12px; color: #e04b4b; margin: 8px 0 0; }

.bilai-otp-btn {
    width: 100%; height: 48px; margin-top: 20px;
    background: var(--bilai-otp-primary); color: #fff;
    border: none; border-radius: 8px;
    font-size: 14.5px; font-weight: 700; cursor: pointer; transition: 0.2s;
}
.bilai-otp-btn:hover { background: var(--bilai-otp-primary-dark); }

.bilai-otp-resend { margin-top: 16px; font-size: 13px; color: var(--bilai-otp-muted); }
.bilai-otp-resend-btn {
    background: none; border: none; padding: 0;
    color: var(--bilai-otp-primary); font-size: 13px; font-weight: 600; cursor: pointer;
}
.bilai-otp-resend-btn:hover { color: var(--bilai-otp-primary-dark); text-decoration: underline; }
.bilai-otp-resend-btn:disabled { color: var(--bilai-otp-muted); cursor: not-allowed; text-decoration: none; }

.bilai-otp-back {
    display: flex; align-items: center; justify-content: center; gap: 7px;
    margin-top: 18px; font-size: 13.5px; color: #2f6fdb; text-decoration: none;
}
.bilai-otp-back:hover { color: #1c53ad; text-decoration: none; }

@media (max-width: 480px) {
    .bilai-otp-card { padding: 30px 18px 26px; }
    .bilai-otp-boxes { gap: 7px; }
    .bilai-otp-box { width: 42px; height: 48px; font-size: 17px; }
}
/* BilaiGhor OTP Verification End */
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<div class="bilai-otp-page">
    <div class="bilai-otp-card">

        <h1 class="bilai-otp-title">Enter the OTP sent to your mobile</h1>
        <p class="bilai-otp-sub">We sent a <?php echo e($otpLength); ?>-digit code to <?php echo e($masked); ?>. It expires in 5 minutes.</p>

        <form action="<?php echo e(route('customer.forgot.otp.verify')); ?>" method="POST" id="bilai-otp-form">
            <?php echo csrf_field(); ?>
            
            <input type="hidden" name="otp" id="bilai-otp-value">

            <div class="bilai-otp-boxes" id="bilai-otp-boxes">
                <?php for($i = 0; $i < $otpLength; $i++): ?>
                    <input type="text" class="bilai-otp-box" inputmode="numeric" pattern="[0-9]*"
                           maxlength="1" autocomplete="one-time-code" aria-label="OTP digit <?php echo e($i + 1); ?>"
                           <?php if($i === 0): ?> autofocus <?php endif; ?>>
                <?php endfor; ?>
            </div>

            <?php $__errorArgs = ['otp'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="bilai-otp-err"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>

            <button type="submit" class="bilai-otp-btn">Verify OTP</button>
        </form>

        <div class="bilai-otp-resend">
            <form action="<?php echo e(route('customer.forgot.otp.resend')); ?>" method="POST" id="bilai-otp-resend-form">
                <?php echo csrf_field(); ?>
                <span id="bilai-otp-timer-text">Resend available in <strong id="bilai-otp-timer">120</strong>s</span>
                <button type="submit" class="bilai-otp-resend-btn" id="bilai-otp-resend-btn" disabled style="display:none;">Resend OTP</button>
            </form>
        </div>

        <a href="<?php echo e(route('customer.login')); ?>" class="bilai-otp-back">
            
            <i class="fa fa-long-arrow-left"></i> Back to Login Page
        </a>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('script'); ?>
<script>
/* BilaiGhor OTP Verification — boxes + resend countdown */
(function () {
    var boxes  = Array.prototype.slice.call(document.querySelectorAll('.bilai-otp-box'));
    var hidden = document.getElementById('bilai-otp-value');
    var form   = document.getElementById('bilai-otp-form');

    function digitsOnly(v) { return (v || '').replace(/\D/g, ''); }
    function collect() { return boxes.map(function (b) { return b.value; }).join(''); }

    boxes.forEach(function (box, i) {
        box.addEventListener('input', function () {
            this.value = digitsOnly(this.value).slice(0, 1);
            if (this.value && i < boxes.length - 1) { boxes[i + 1].focus(); }
        });

        box.addEventListener('keydown', function (e) {
            if (e.key === 'Backspace' && !this.value && i > 0) { boxes[i - 1].focus(); }
            if (e.key === 'ArrowLeft'  && i > 0)               { boxes[i - 1].focus(); }
            if (e.key === 'ArrowRight' && i < boxes.length - 1) { boxes[i + 1].focus(); }
        });

        // Pasting the whole code into any box fills them all.
        box.addEventListener('paste', function (e) {
            e.preventDefault();
            var text = digitsOnly((e.clipboardData || window.clipboardData).getData('text'));
            if (!text) { return; }
            boxes.forEach(function (b, j) { b.value = text[j] || ''; });
            var next = Math.min(text.length, boxes.length - 1);
            boxes[next].focus();
        });
    });

    form.addEventListener('submit', function () { hidden.value = collect(); });

    // Resend countdown — the server also enforces its own cooldown.
    var seconds  = 120;
    var timerEl  = document.getElementById('bilai-otp-timer');
    var textEl   = document.getElementById('bilai-otp-timer-text');
    var btn      = document.getElementById('bilai-otp-resend-btn');

    var tick = setInterval(function () {
        seconds -= 1;
        if (seconds <= 0) {
            clearInterval(tick);
            textEl.style.display = 'none';
            btn.style.display = 'inline';
            btn.disabled = false;
            return;
        }
        timerEl.textContent = seconds;
    }, 1000);
}());
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('frontEnd.layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\bilaiGhor\resources\views\frontEnd\layouts\customer\forgot_otp.blade.php ENDPATH**/ ?>