
<?php $__env->startSection('title', 'Support Ticket'); ?>

<?php $__env->startSection('content'); ?>
<?php
    $submittedTicket = session('ticket_number');
    $supportPhone = optional($contact)->hotline ?: optional($contact)->phone;
    $supportEmail = optional($contact)->email;
?>

<style>
    .support-ticket-page {
        --st-brown: #2a1505;
        --st-brown-heading: #503311;
        --st-orange: #e8861a;
        --st-cream: #fbf5e6;
        --st-surface: #fdfcf8;
        --st-pill: #f8eee0;
        --st-border: #dccab2;
        --st-border-subtle: #edd9a6;
        --st-body: #4f4f4f;
        --st-muted: #8e6331;
        --st-on-dark: #f0e6d8;
        background: var(--st-surface);
        padding: 100px 20px 120px;
        font-family: "DM Sans", sans-serif;
        color: var(--st-body);
    }

    .support-ticket-page *,
    .support-ticket-page *::before,
    .support-ticket-page *::after {
        box-sizing: border-box;
        letter-spacing: 0;
    }

    .support-ticket-container {
        width: 100%;
        max-width: 1240px;
        margin: 0 auto;
    }

    .support-ticket-intro {
        width: min(100%, 844px);
        margin: 0 auto 60px;
        text-align: center;
    }

    .support-ticket-kicker {
        display: inline-flex;
        min-height: 44px;
        align-items: center;
        justify-content: center;
        margin-bottom: 30px;
        padding: 10px 20px;
        border: 1px solid var(--st-border-subtle);
        border-radius: 30px;
        background: var(--st-pill);
        color: var(--st-body);
        font-size: 16px;
        line-height: 24px;
    }

    .support-ticket-title {
        margin: 0 0 20px;
        color: var(--st-brown);
        font-family: "Mochiy Pop One", sans-serif;
        font-size: 36px;
        font-weight: 400;
        line-height: 48px;
        white-space: normal;
        overflow-wrap: break-word;
    }

    .support-ticket-subtitle {
        margin: 0;
        color: var(--st-body);
        font-size: 16px;
        line-height: 24px;
        white-space: normal;
        overflow-wrap: break-word;
    }

    .support-ticket-shell {
        display: grid;
        grid-template-columns: 496px minmax(0, 744px);
        align-items: stretch;
        width: 100%;
        min-height: 950px;
    }

    .support-ticket-info {
        padding: 40px;
        border-radius: 30px 0 0 30px;
        background: var(--st-brown);
        color: var(--st-on-dark);
    }

    .support-ticket-info-title {
        margin: 0 0 30px;
        color: var(--st-orange);
        font-size: 16px;
        font-weight: 400;
        line-height: 16px;
    }

    .support-ticket-info-block {
        padding-bottom: 30px;
        border-bottom: 1px dotted rgba(240, 230, 216, .65);
        margin-bottom: 30px;
    }

    .support-ticket-info-label {
        margin: 0 0 10px;
        color: var(--st-on-dark);
        font-size: 14px;
        line-height: 21px;
    }

    .support-ticket-number {
        margin: 0;
        color: var(--st-on-dark);
        font-size: 24px;
        line-height: 36px;
        overflow-wrap: anywhere;
    }

    .support-ticket-number.is-pending {
        max-width: 260px;
        font-size: 16px;
        line-height: 24px;
        color: rgba(240, 230, 216, .8);
    }

    .support-ticket-times {
        display: flex;
        gap: 37px;
    }

    .support-ticket-time-label {
        margin: 0 0 10px;
        color: var(--st-on-dark);
        font-size: 16px;
        line-height: 16px;
    }

    .support-ticket-time-value {
        margin: 0;
        color: var(--st-orange);
        font-size: 18px;
        font-weight: 600;
        line-height: 18px;
    }

    .support-ticket-security-title,
    .support-ticket-contact-line {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .support-ticket-security-title {
        margin-bottom: 20px;
    }

    .support-ticket-info i {
        width: 20px;
        color: var(--st-orange);
        font-size: 16px;
        text-align: center;
    }

    .support-ticket-security-title span,
    .support-ticket-security-copy,
    .support-ticket-contact-heading,
    .support-ticket-contact-line {
        color: var(--st-on-dark);
        font-size: 16px;
        line-height: 16px;
    }

    .support-ticket-security-copy {
        margin: 0;
        line-height: 24px;
        white-space: normal;
        overflow-wrap: anywhere;
    }

    .support-ticket-contact-heading {
        margin: 0 0 20px;
        white-space: normal;
    }

    .support-ticket-contact-line {
        color: var(--st-on-dark);
        line-height: 20px;
        text-decoration: none;
        overflow-wrap: anywhere;
    }

    .support-ticket-contact-line + .support-ticket-contact-line {
        margin-top: 16px;
    }

    .support-ticket-form-panel {
        min-width: 0;
        padding: 40px;
        border: 1px solid var(--st-border);
        border-left: 0;
        border-radius: 0 30px 30px 0;
        background: var(--st-cream);
    }

    .support-ticket-form-title {
        margin: 0 0 30px;
        color: var(--st-brown-heading);
        font-size: 24px;
        font-weight: 600;
        line-height: 36px;
    }

    .support-ticket-success {
        margin-bottom: 30px;
        padding: 16px 18px;
        border: 1px solid #9ac58c;
        border-radius: 10px;
        background: #eef8e9;
        color: #285b28;
        font-size: 15px;
        line-height: 24px;
    }

    .support-ticket-success strong {
        display: block;
        color: #1f481f;
        font-size: 18px;
    }

    .support-ticket-fields {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 30px;
    }

    .support-ticket-field {
        min-width: 0;
    }

    .support-ticket-field.is-full {
        grid-column: 1 / -1;
    }

    .support-ticket-label {
        display: block;
        margin-bottom: 10px;
        color: var(--st-body);
        font-size: 16px;
        font-weight: 600;
        line-height: 24px;
    }

    .support-ticket-required {
        color: #ff0f0f;
    }

    .support-ticket-control {
        display: block;
        width: 100%;
        height: 64px;
        padding: 20px;
        border: 1px solid var(--st-border);
        border-radius: 10px;
        outline: 0;
        background: var(--st-surface);
        color: var(--st-body);
        font-family: "DM Sans", sans-serif;
        font-size: 16px;
        line-height: 24px;
        transition: border-color .2s, box-shadow .2s;
    }

    .support-ticket-control::placeholder {
        color: var(--st-border);
        opacity: 1;
    }

    .support-ticket-control:focus {
        border-color: var(--st-orange);
        box-shadow: 0 0 0 3px rgba(232, 134, 26, .14);
    }

    textarea.support-ticket-control {
        height: 160px;
        min-height: 160px;
        resize: vertical;
    }

    .support-ticket-control.is-invalid,
    .support-ticket-upload.is-invalid {
        border-color: #dc3545;
    }

    .support-ticket-error {
        display: block;
        margin-top: 7px;
        color: #c62828;
        font-size: 13px;
        line-height: 18px;
    }

    .support-ticket-upload {
        position: relative;
        display: flex;
        width: 100%;
        height: 160px;
        align-items: center;
        justify-content: center;
        padding: 20px;
        border: 1px solid var(--st-border);
        border-radius: 10px;
        background: var(--st-surface);
        cursor: pointer;
        transition: border-color .2s, background .2s;
    }

    .support-ticket-upload:hover,
    .support-ticket-upload.is-dragging {
        border-color: var(--st-orange);
        background: #fffaf0;
    }

    .support-ticket-upload input {
        position: absolute;
        width: 1px;
        height: 1px;
        opacity: 0;
        pointer-events: none;
    }

    .support-ticket-upload-content {
        display: flex;
        max-width: 100%;
        flex-direction: column;
        align-items: center;
        gap: 10px;
        text-align: center;
    }

    .support-ticket-upload-content i {
        width: 24px;
        height: 24px;
        color: var(--st-body);
        font-size: 22px;
        line-height: 24px;
    }

    .support-ticket-upload-text {
        color: var(--st-body);
        font-size: 16px;
        line-height: 24px;
        overflow-wrap: anywhere;
    }

    .support-ticket-upload-text strong {
        color: var(--st-orange);
        font-weight: 400;
    }

    .support-ticket-submit-wrap {
        margin-top: 30px;
        text-align: center;
    }

    .support-ticket-submit {
        display: flex;
        width: 100%;
        min-height: 59px;
        align-items: center;
        justify-content: center;
        padding: 16px 10px;
        border: 0;
        border-radius: 12px;
        background: var(--st-orange);
        color: var(--st-surface);
        font-family: "DM Sans", sans-serif;
        font-size: 18px;
        font-weight: 600;
        line-height: 27px;
        cursor: pointer;
        transition: background .2s;
    }

    .support-ticket-submit:hover,
    .support-ticket-submit:focus {
        background: #cf7312;
        color: var(--st-surface);
    }

    .support-ticket-terms {
        margin: 20px 0 0;
        color: var(--st-muted);
        font-size: 14px;
        line-height: 21px;
    }

    @media (max-width: 1100px) {
        .support-ticket-shell {
            grid-template-columns: minmax(310px, 40%) minmax(0, 60%);
        }

        .support-ticket-info,
        .support-ticket-form-panel {
            padding: 32px;
        }
    }

    @media (max-width: 991px) {
        .support-ticket-page {
            padding: 64px 20px 80px;
        }

        .support-ticket-shell {
            grid-template-columns: 1fr;
            min-height: 0;
        }

        .support-ticket-info {
            border-radius: 30px 30px 0 0;
        }

        .support-ticket-form-panel {
            border-top: 0;
            border-left: 1px solid var(--st-border);
            border-radius: 0 0 30px 30px;
        }
    }

    @media (max-width: 640px) {
        .support-ticket-page {
            padding: 48px 12px 64px;
        }

        .support-ticket-intro {
            margin-bottom: 40px;
        }

        .support-ticket-title {
            font-size: 28px;
            line-height: 40px;
            word-break: normal;
        }

        .support-ticket-info,
        .support-ticket-form-panel {
            padding: 28px 20px;
        }

        .support-ticket-info {
            border-radius: 20px 20px 0 0;
        }

        .support-ticket-form-panel {
            border-radius: 0 0 20px 20px;
        }

        .support-ticket-fields {
            grid-template-columns: 1fr;
            gap: 24px;
        }

        .support-ticket-field.is-full {
            grid-column: auto;
        }

        .support-ticket-control {
            padding: 16px;
        }

        .support-ticket-terms {
            white-space: normal;
        }
    }
</style>

<main class="support-ticket-page">
    <div class="support-ticket-container">
        <header class="support-ticket-intro">
            <span class="support-ticket-kicker">Customer Support</span>
            <h1 class="support-ticket-title">Your Problem, Our Priority</h1>
            <p class="support-ticket-subtitle">Fill out the form below — you will receive a ticket number upon submission and our team will be in touch as soon as possible.</p>
        </header>

        <div class="support-ticket-shell">
            <aside class="support-ticket-info" aria-label="Support ticket information">
                <h2 class="support-ticket-info-title">Support Ticket</h2>

                <div class="support-ticket-info-block">
                    <p class="support-ticket-info-label">Ticket Number</p>
                    <p class="support-ticket-number <?php echo e($submittedTicket ? '' : 'is-pending'); ?>">
                        <?php echo e($submittedTicket ?: 'Generated after submission'); ?>

                    </p>
                </div>

                <div class="support-ticket-info-block">
                    <div class="support-ticket-times">
                        <div>
                            <p class="support-ticket-time-label">Avg. Reply</p>
                            <p class="support-ticket-time-value">1 Hour</p>
                        </div>
                        <div>
                            <p class="support-ticket-time-label">Max Time</p>
                            <p class="support-ticket-time-value">24 Hours</p>
                        </div>
                    </div>
                </div>

                <div class="support-ticket-info-block">
                    <div class="support-ticket-security-title">
                        <i class="fas fa-lock" aria-hidden="true"></i>
                        <span>Secure &amp; Confidential</span>
                    </div>
                    <p class="support-ticket-security-copy">Your information is never shared with third parties.</p>
                </div>

                <div>
                    <p class="support-ticket-contact-heading">Contact Directly</p>
                    <?php if($supportPhone): ?>
                        <a class="support-ticket-contact-line" href="tel:<?php echo e(preg_replace('/[^0-9+]/', '', $supportPhone)); ?>">
                            <i class="fas fa-phone" aria-hidden="true"></i>
                            <span><?php echo e($supportPhone); ?></span>
                        </a>
                    <?php endif; ?>
                    <?php if($supportEmail): ?>
                        <a class="support-ticket-contact-line" href="mailto:<?php echo e($supportEmail); ?>">
                            <i class="fas fa-envelope" aria-hidden="true"></i>
                            <span><?php echo e($supportEmail); ?></span>
                        </a>
                    <?php endif; ?>
                </div>
            </aside>

            <section class="support-ticket-form-panel">
                <h2 class="support-ticket-form-title">Enter Ticket Details</h2>

                <?php if(session('success')): ?>
                    <div class="support-ticket-success" role="status">
                        <?php echo e(session('success')); ?>

                        <strong>Ticket Number: <?php echo e($submittedTicket); ?></strong>
                    </div>
                <?php endif; ?>

                <form action="<?php echo e(route('complaint.store')); ?>" method="POST" enctype="multipart/form-data" novalidate>
                    <?php echo csrf_field(); ?>

                    <div class="support-ticket-fields">
                        <div class="support-ticket-field">
                            <label class="support-ticket-label" for="ticket-name">Your Name <span class="support-ticket-required">*</span></label>
                            <input id="ticket-name" class="support-ticket-control <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" type="text" name="name" value="<?php echo e(old('name', optional($customer)->name)); ?>" placeholder="MD. Hossain" autocomplete="name" required>
                            <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="support-ticket-error"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>

                        <div class="support-ticket-field">
                            <label class="support-ticket-label" for="ticket-phone">Mobile Number <span class="support-ticket-required">*</span></label>
                            <input id="ticket-phone" class="support-ticket-control <?php $__errorArgs = ['phone'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" type="tel" name="phone" value="<?php echo e(old('phone', optional($customer)->phone)); ?>" placeholder="01xxxx-xxxxx" inputmode="numeric" autocomplete="tel" maxlength="11" required>
                            <?php $__errorArgs = ['phone'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="support-ticket-error"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>

                        <div class="support-ticket-field">
                            <label class="support-ticket-label" for="ticket-email">Email (Optional)</label>
                            <input id="ticket-email" class="support-ticket-control <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" type="email" name="email" value="<?php echo e(old('email', optional($customer)->email)); ?>" placeholder="you@example.com" autocomplete="email">
                            <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="support-ticket-error"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>

                        <div class="support-ticket-field">
                            <label class="support-ticket-label" for="ticket-order">Order ID (If Any)</label>
                            <input id="ticket-order" class="support-ticket-control <?php $__errorArgs = ['order_reference'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" type="text" name="order_reference" value="<?php echo e(old('order_reference')); ?>" placeholder="#BG - xxxxx" maxlength="55">
                            <?php $__errorArgs = ['order_reference'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="support-ticket-error"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>

                        <div class="support-ticket-field is-full">
                            <label class="support-ticket-label" for="ticket-details">Details <span class="support-ticket-required">*</span></label>
                            <textarea id="ticket-details" class="support-ticket-control <?php $__errorArgs = ['description'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" name="description" placeholder="Write your note or the complaint" required><?php echo e(old('description')); ?></textarea>
                            <?php $__errorArgs = ['description'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="support-ticket-error"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>

                        <div class="support-ticket-field is-full">
                            <label class="support-ticket-label" for="ticket-image">Photo as Proof (Optional)</label>
                            <label class="support-ticket-upload <?php $__errorArgs = ['image'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" id="ticket-upload-area" for="ticket-image">
                                <input id="ticket-image" type="file" name="image" accept="image/jpeg,image/png,image/webp">
                                <span class="support-ticket-upload-content">
                                    <i class="fas fa-upload" aria-hidden="true"></i>
                                    <span class="support-ticket-upload-text" id="ticket-upload-text">Drag photo here or <strong>browse</strong></span>
                                </span>
                            </label>
                            <?php $__errorArgs = ['image'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="support-ticket-error"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                    </div>

                    <div class="support-ticket-submit-wrap">
                        <button class="support-ticket-submit" type="submit">Submit your Ticket</button>
                        <p class="support-ticket-terms">By submitting, you are agreeing to our Terms of Service.</p>
                    </div>
                </form>
            </section>
        </div>
    </div>
</main>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('script'); ?>
<script>
(function () {
    var input = document.getElementById('ticket-image');
    var area = document.getElementById('ticket-upload-area');
    var text = document.getElementById('ticket-upload-text');
    if (!input || !area || !text) return;

    function showFile(file) {
        text.textContent = file ? file.name : 'Drag photo here or browse';
    }

    input.addEventListener('change', function () {
        showFile(input.files[0]);
    });

    ['dragenter', 'dragover'].forEach(function (eventName) {
        area.addEventListener(eventName, function (event) {
            event.preventDefault();
            area.classList.add('is-dragging');
        });
    });

    ['dragleave', 'drop'].forEach(function (eventName) {
        area.addEventListener(eventName, function (event) {
            event.preventDefault();
            area.classList.remove('is-dragging');
        });
    });

    area.addEventListener('drop', function (event) {
        if (!event.dataTransfer.files.length) return;
        input.files = event.dataTransfer.files;
        showFile(input.files[0]);
    });
})();
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('frontEnd.layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\bilaiGhor\resources\views/frontEnd/layouts/pages/complaint.blade.php ENDPATH**/ ?>