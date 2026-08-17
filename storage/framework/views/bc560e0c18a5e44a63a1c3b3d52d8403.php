
<?php $__env->startSection('title', 'রিফান্ড ব্যবস্থাপনা'); ?>

<?php $__env->startSection('css'); ?>
<?php echo $__env->make('backEnd.refunds.partials.refund_styles', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid refund-shell refund-page">

    <div class="rf-page-header">
        <div>
            <h4>রিফান্ড ব্যবস্থাপনা <span class="rf-badge-count"><?php echo e($data->total()); ?></span></h4>
            <p class="rf-sub">সকল রিফান্ড অনুরোধ দেখুন, অনুমোদন ও প্রসেস করুন</p>
        </div>
    </div>

    <?php
        $statusLabels = [
            'pending' => 'মুলতুবি',
            'approved' => 'অনুমোদিত',
            'rejected' => 'প্রত্যাখ্যান',
            'processed' => 'সম্পন্ন',
        ];
    ?>

    <div class="rf-stat-grid">
        <a href="<?php echo e(route('admin.refunds.index')); ?>" class="rf-stat <?php echo e(!request('status') ? 'active' : ''); ?>">
            <div class="rf-stat-label">মোট</div>
            <div class="rf-stat-val"><?php echo e($statusCounts->sum()); ?></div>
        </a>
        <?php $__currentLoopData = ['pending', 'approved', 'rejected', 'processed']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $st): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <a href="<?php echo e(route('admin.refunds.index', ['status' => $st])); ?>" class="rf-stat <?php echo e($st); ?> <?php echo e(request('status') === $st ? 'active' : ''); ?>">
            <div class="rf-stat-label"><?php echo e($statusLabels[$st]); ?></div>
            <div class="rf-stat-val"><?php echo e($statusCounts[$st] ?? 0); ?></div>
        </a>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>

    <div class="rf-card">
        <div class="rf-card-head">
            <h6><i class="fas fa-filter"></i> ফিল্টার</h6>
        </div>
        <div class="rf-card-body">
            <form method="GET" action="<?php echo e(route('admin.refunds.index')); ?>" class="rf-filter-grid">
                <div>
                    <label class="rf-label">ইনভয়েস খুঁজুন</label>
                    <input type="text" name="order_invoice" class="rf-input"
                           placeholder="ইনভয়েস নম্বর..." value="<?php echo e(request('order_invoice')); ?>">
                </div>
                <div>
                    <label class="rf-label">স্ট্যাটাস</label>
                    <select name="status" class="rf-select">
                        <option value="">সব স্ট্যাটাস</option>
                        <?php $__currentLoopData = $statuses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $status): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($status); ?>" <?php echo e(request('status') == $status ? 'selected' : ''); ?>>
                                <?php echo e($statusLabels[$status] ?? ucfirst($status)); ?>

                            </option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
                <div class="rf-filter-actions">
                    <button type="submit" class="btn rf-btn-primary">
                        <i class="fas fa-search me-1"></i> ফিল্টার
                    </button>
                    <a href="<?php echo e(route('admin.refunds.index')); ?>" class="rf-btn-ghost">
                        <i class="fas fa-redo"></i> রিসেট
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="rf-card">
        <div class="rf-card-head">
            <h6><i class="fas fa-undo-alt"></i> রিফান্ড তালিকা</h6>
        </div>
        <div class="rf-card-body">
            <p class="d-lg-none rf-scroll-hint"><i class="fas fa-arrows-alt-h me-1"></i> বাকি কলাম দেখতে স্লাইড করুন</p>

            <div class="rf-table-rail">
                <table class="table rf-table mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>রিফান্ড</th>
                            <th>গ্রাহক</th>
                            <th>পরিমাণ</th>
                            <th>পদ্ধতি</th>
                            <th>স্ট্যাটাস</th>
                            <th>তারিখ</th>
                            <th class="text-end">অ্যাকশন</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__empty_1 = true; $__currentLoopData = $data; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $refund): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td><?php echo e($loop->iteration + ($data->currentPage() - 1) * $data->perPage()); ?></td>
                            <td>
                                <div class="rf-refund-id">#<?php echo e($refund->refund_id); ?></div>
                                <?php if($refund->order): ?>
                                <br><a href="<?php echo e(route('admin.order.invoice', ['invoice_id' => $refund->order->invoice_id])); ?>"
                                       target="_blank" class="rf-invoice-link">INV-<?php echo e($refund->order->invoice_id); ?></a>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="rf-customer-name"><?php echo e($refund->customer->name ?? 'অতিথি'); ?></span><br>
                                <span class="rf-meta-sub"><?php echo e($refund->customer->phone ?? '—'); ?></span>
                            </td>
                            <td>
                                <span class="rf-amount">৳<?php echo e(number_format($refund->amount + $refund->shipping_charge, 0)); ?></span>
                                <?php if($refund->shipping_charge > 0): ?>
                                <br><span class="rf-meta-sub">শিপিং সহ</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="text-capitalize rf-meta-sub"><?php echo e(str_replace('_', ' ', $refund->refund_method)); ?></span>
                            </td>
                            <td>
                                <?php if($refund->status == 'pending'): ?>
                                    <span class="rf-pill rf-pill-pending">মুলতুবি</span>
                                <?php elseif($refund->status == 'approved'): ?>
                                    <span class="rf-pill rf-pill-approved">অনুমোদিত</span>
                                <?php elseif($refund->status == 'rejected'): ?>
                                    <span class="rf-pill rf-pill-rejected">প্রত্যাখ্যান</span>
                                <?php else: ?>
                                    <span class="rf-pill rf-pill-processed">সম্পন্ন</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php echo e($refund->created_at->format('d M, Y')); ?><br>
                                <span class="rf-meta-sub"><?php echo e($refund->created_at->format('h:i A')); ?></span>
                            </td>
                            <td class="text-end">
                                <div class="rf-row-actions">
                                    <a href="<?php echo e(route('admin.refunds.show', $refund->id)); ?>" class="rf-act-btn rf-act-view" title="বিস্তারিত">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <?php if($refund->status == 'pending'): ?>
                                        <button type="button" class="rf-act-btn rf-act-approve" title="অনুমোদন"
                                                data-bs-toggle="modal" data-bs-target="#approveModal<?php echo e($refund->id); ?>">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <button type="button" class="rf-act-btn rf-act-reject" title="প্রত্যাখ্যান"
                                                data-bs-toggle="modal" data-bs-target="#rejectModal<?php echo e($refund->id); ?>">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    <?php elseif($refund->status == 'approved'): ?>
                                        <button type="button" class="rf-act-btn rf-act-process" title="পেমেন্ট প্রসেস"
                                                data-bs-toggle="modal" data-bs-target="#processModal<?php echo e($refund->id); ?>">
                                            <i class="fas fa-credit-card"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="8">
                                <div class="rf-empty">
                                    <i class="fas fa-inbox"></i>
                                    <p class="mb-0">কোনো রিফান্ড অনুরোধ নেই</p>
                                </div>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if($data->hasPages()): ?>
            <div class="rf-paginate">
                <?php echo e($data->links('pagination::bootstrap-4')); ?>

            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php $__currentLoopData = $data; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $refund): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
<div class="modal fade rf-modal" id="approveModal<?php echo e($refund->id); ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">রিফান্ড অনুমোদন</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?php echo e(route('admin.refunds.approve', $refund->id)); ?>" method="POST">
                <?php echo csrf_field(); ?>
                <div class="modal-body">
                    <p class="text-muted small mb-3">৳<?php echo e(number_format($refund->amount + $refund->shipping_charge, 0)); ?> রিফান্ড অনুমোদন করবেন?</p>
                    <label class="rf-label">অ্যাডমিন নোট (ঐচ্ছিক)</label>
                    <textarea name="admin_note" class="rf-textarea" rows="2"><?php echo e($refund->admin_note); ?></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn rf-btn-ghost" data-bs-dismiss="modal">বাতিল</button>
                    <button type="submit" class="btn rf-btn-primary">অনুমোদন</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade rf-modal" id="rejectModal<?php echo e($refund->id); ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-danger">রিফান্ড প্রত্যাখ্যান</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?php echo e(route('admin.refunds.reject', $refund->id)); ?>" method="POST">
                <?php echo csrf_field(); ?>
                <div class="modal-body">
                    <label class="rf-label">প্রত্যাখ্যানের কারণ <span class="text-danger">*</span></label>
                    <textarea name="admin_note" class="rf-textarea" rows="3" required><?php echo e($refund->admin_note); ?></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn rf-btn-ghost" data-bs-dismiss="modal">বাতিল</button>
                    <button type="submit" class="btn btn-danger rounded-pill px-4">প্রত্যাখ্যান</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade rf-modal" id="processModal<?php echo e($refund->id); ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">পেমেন্ট প্রসেস</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?php echo e(route('admin.refunds.process', $refund->id)); ?>" method="POST">
                <?php echo csrf_field(); ?>
                <div class="modal-body">
                    <label class="rf-label">ট্রানজেকশন আইডি <span class="text-danger">*</span></label>
                    <input type="text" name="transaction_id" class="rf-input mb-3" required>
                    <label class="rf-label">পদ্ধতি</label>
                    <select name="refund_method" class="rf-select mb-3">
                        <option value="bkash">bKash</option>
                        <option value="nagad">Nagad</option>
                        <option value="bank">ব্যাংক</option>
                        <option value="manual">নগদ/ম্যানুয়াল</option>
                    </select>
                    <label class="rf-label">অ্যাকাউন্ট</label>
                    <input type="text" name="refund_account" class="rf-input" value="<?php echo e($refund->refund_account); ?>">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn rf-btn-ghost" data-bs-dismiss="modal">বাতিল</button>
                    <button type="submit" class="btn rf-btn-primary">সম্পন্ন</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('backEnd.layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\bilaiGhor\resources\views\backEnd\refunds\index.blade.php ENDPATH**/ ?>