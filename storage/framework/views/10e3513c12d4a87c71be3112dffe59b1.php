
<?php $__env->startSection('title', 'ওয়ালেট'); ?>
<?php $__env->startSection('header_title', 'ওয়ালেট ও কমিশন'); ?>

<?php $__env->startSection('page_subtitle'); ?>
    <p>ব্যালেন্স, উথড্র’ অনুরোধ ও লেনদেনের ইতিহাস।</p>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="d-wallet-layout">
    <div class="d-wallet-col">
        <div class="d-card d-balance-card">
            <div class="d-muted-label">বর্তমান ব্যালেন্স</div>
            <div class="d-balance-big">৳<?php echo e(number_format($boy->wallet_balance, 2)); ?></div>
        </div>

        <?php if($pending->count()): ?>
        <div class="d-card">
            <div class="d-section-title-sm">অপেক্ষমান উথড্র’র</div>
            <?php $__currentLoopData = $pending; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="d-pending-row">
                    <span class="fw-amount">৳<?php echo e(number_format($p->amount, 2)); ?></span>
                    <span class="d-pending-meta"><?php echo e($p->payout_method); ?> · <?php echo e($p->payout_number); ?></span>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
        <?php endif; ?>
    </div>

    <div class="d-wallet-col">
        <div class="d-card d-form">
            <div class="d-section-title-sm">উথড্র’ অনুরোধ</div>
            <form method="post" action="<?php echo e(route('delivery.wallet.withdraw')); ?>">
                <?php echo csrf_field(); ?>
                <label class="d-label">টাকার পরিমাণ (৳)</label>
                <input type="number" name="amount" min="1" step="1" required>
                <label class="d-label" style="margin-top:12px;">পেমেন্ট মাধ্যম</label>
                <select name="payout_method" required>
                    <option value="bkash">bKash</option>
                    <option value="nagad">Nagad</option>
                    <option value="bank">Bank</option>
                </select>
                <label class="d-label" style="margin-top:12px;">নম্বর / অ্যাকাউন্ট</label>
                <input type="text" name="payout_number" required placeholder="01xxxxxxxxx">
                <label class="d-label" style="margin-top:12px;">নোট (ঐচ্ছিক)</label>
                <textarea name="note" rows="2" placeholder=""></textarea>
                <button type="submit" class="d-btn d-btn--primary" style="margin-top:14px;">অনুরোধ পাঠান</button>
            </form>
            <p class="d-form-note">অ্যাডমিন অনুমোদনের পর ব্যালেন্স থেকে কাটা হবে।</p>
        </div>
    </div>
</div>

<div class="d-card d-card--flush" style="margin-bottom:0;">
    <div class="d-section-bar">লেনদেন</div>

    <div class="d-table-wrap">
        <table class="d-table-desktop">
            <thead>
                <tr>
                    <th>ধরন</th>
                    <th>পরিমাণ</th>
                    <th>সময়</th>
                    <th>নোট</th>
                </tr>
            </thead>
            <tbody>
                <?php $__currentLoopData = $tx; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $t): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <tr>
                        <td><?php echo e($t->type); ?></td>
                        <td><strong><?php echo e($t->direction === 'credit' ? '+' : '-'); ?>৳<?php echo e(number_format($t->amount, 2)); ?></strong></td>
                        <td><?php echo e($t->created_at->format('d M Y, h:i A')); ?></td>
                        <td><?php echo e($t->note ?? '—'); ?></td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </tbody>
        </table>
    </div>

    <?php $__currentLoopData = $tx; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $t): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div class="d-tx-mobile">
            <div class="d-tx-line">
                <span><?php echo e($t->type); ?></span>
                <strong><?php echo e($t->direction === 'credit' ? '+' : '-'); ?>৳<?php echo e(number_format($t->amount, 2)); ?></strong>
            </div>
            <small class="d-tx-time"><?php echo e($t->created_at->format('d M Y, h:i A')); ?></small>
            <?php if($t->note): ?><small class="d-tx-note"><?php echo e($t->note); ?></small><?php endif; ?>
        </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

    <?php if($tx->isEmpty()): ?>
        <p class="d-empty" style="padding:24px 16px;margin:0;">এখনো কোনো লেনদেন নেই</p>
    <?php endif; ?>
</div>

<div class="d-pagination"><?php echo e($tx->links()); ?></div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('css'); ?>
<style>
    .d-wallet-layout { display: block; }
    @media (min-width: 768px) {
        .d-wallet-layout {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            align-items: start;
        }
    }
    .d-balance-card { text-align: center; }
    @media (min-width: 768px) { .d-balance-card { text-align: left; } }
    .d-balance-big {
        font-size: 2rem;
        font-weight: 800;
        color: var(--d-success);
        letter-spacing: -0.03em;
        margin-top: 6px;
    }
    .d-section-title-sm {
        font-weight: 800;
        font-size: 0.92rem;
        margin-bottom: 12px;
        padding-bottom: 8px;
        border-bottom: 1px solid #f1f5f9;
    }
    .d-section-bar {
        padding: 14px 18px;
        font-weight: 800;
        border-bottom: 1px solid var(--d-border);
        background: #fafbfc;
        font-size: 0.95rem;
    }
    .d-pending-row {
        padding: 12px 0;
        border-bottom: 1px solid #f1f5f9;
    }
    .d-pending-row:last-child { border-bottom: 0; }
    .fw-amount { font-weight: 800; font-size: 1rem; display: block; }
    .d-pending-meta { font-size: 0.8rem; color: var(--d-muted); }
    .d-form-note { font-size: 0.75rem; color: var(--d-muted); margin: 12px 0 0; }
    .d-tx-line { display: flex; justify-content: space-between; align-items: center; gap: 8px; }
    .d-tx-time { display: block; color: var(--d-muted); margin-top: 4px; font-size: 0.78rem; }
    .d-tx-note { display: block; color: var(--d-muted); margin-top: 6px; font-size: 0.82rem; }
</style>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('delivery.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\bilaiGhor\resources\views\delivery\wallet\index.blade.php ENDPATH**/ ?>