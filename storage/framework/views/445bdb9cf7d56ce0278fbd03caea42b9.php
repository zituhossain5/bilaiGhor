
<?php $__env->startSection('title', 'রিসেলার অর্ডার'); ?>

<?php $__env->startSection('css'); ?>
<?php echo $__env->make('backEnd.reseller_orders.partials.index_styles', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid reseller-orders-shell reseller-orders-page">

    <div class="ro-page-header">
        <div>
            <h4>রিসেলার অর্ডার <span class="ro-badge-count"><?php echo e($orders->total()); ?></span></h4>
            <div class="ro-sub">অসম্পূর্ণ / পেন্ডিং রিসেলার অর্ডার — সম্পন্ন অর্ডার মূল অর্ডার তালিকায় দেখা যাবে</div>
            <div class="ro-info-pill">
                <i class="fas fa-info-circle"></i>
                শুধুমাত্র অসম্পন্ন অর্ডার এখানে দেখানো হয়
            </div>
        </div>
    </div>

    <div class="ro-card">
        <div class="ro-card-head">
            <h6><i class="fas fa-filter"></i> ফিল্টার ও অনুসন্ধান</h6>
        </div>
        <div class="ro-card-body">
            <form method="GET" action="<?php echo e(route('admin.reseller-orders.index')); ?>" class="ro-filter-grid">
                <div>
                    <label class="ro-label">খুঁজুন</label>
                    <input type="text" name="search" class="form-control ro-input"
                        placeholder="ইনভয়েস, গ্রাহক, রিসেলার..." value="<?php echo e(request('search')); ?>">
                </div>
                <div>
                    <label class="ro-label">স্ট্যাটাস</label>
                    <select name="status" class="form-select ro-select">
                        <option value="">সব স্ট্যাটাস</option>
                        <?php $__currentLoopData = $orderStatuses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $status): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($status->id); ?>" <?php echo e(request('status') == $status->id ? 'selected' : ''); ?>>
                                <?php echo e($status->name); ?>

                            </option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
                <div>
                    <label class="ro-label">রিসেলার</label>
                    <select name="reseller_id" class="form-select ro-select">
                        <option value="">সব রিসেলার</option>
                        <?php $__currentLoopData = $resellers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $reseller): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($reseller->id); ?>" <?php echo e(request('reseller_id') == $reseller->id ? 'selected' : ''); ?>>
                                <?php echo e($reseller->name); ?> (<?php echo e($reseller->email); ?>)
                            </option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
                <div class="ro-filter-actions">
                    <button type="submit" class="btn ro-btn-primary">
                        <i class="fas fa-search me-1"></i> ফিল্টার
                    </button>
                    <a href="<?php echo e(route('admin.reseller-orders.index')); ?>" class="ro-btn-ghost">রিসেট</a>
                </div>
            </form>
        </div>
    </div>

    <div class="ro-card">
        <div class="ro-card-head">
            <h6><i class="fas fa-list-alt"></i> অর্ডার তালিকা</h6>
        </div>
        <div class="ro-card-body">

            <form id="bulkStatusForm" method="POST" action="<?php echo e(route('admin.reseller-orders.bulk-update-status')); ?>">
                <?php echo csrf_field(); ?>
                <div class="ro-bulk-bar">
                    <div>
                        <label class="ro-label mb-1">বাল্ক স্ট্যাটাস</label>
                        <select name="order_status" class="form-select ro-select" required>
                            <option value="">স্ট্যাটাস বেছে নিন</option>
                            <?php $__currentLoopData = $orderStatuses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $status): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($status->id); ?>"><?php echo e($status->name); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                    <button type="submit" class="btn ro-btn-bulk" id="bulkUpdateBtn" disabled>
                        <i class="fas fa-flag me-1"></i> সিলেক্টেড আপডেট
                    </button>
                    <span class="ro-selected-count" id="selectedCount">০টি সিলেক্ট</span>
                    <input type="hidden" name="order_ids" id="selectedOrderIds">
                </div>

                <p class="d-lg-none ro-scroll-hint"><i class="fas fa-arrows-alt-h me-1"></i> বাকি কলাম দেখতে বাম–ডানে স্লাইড করুন</p>

                <div class="ro-table-rail">
                    <table class="table ro-table mb-0">
                        <thead>
                            <tr>
                                <th><input type="checkbox" id="selectAll" class="form-check-input" aria-label="সব সিলেক্ট"></th>
                                <th>#</th>
                                <th>ইনভয়েস</th>
                                <th>তারিখ</th>
                                <th>রিসেলার</th>
                                <th>গ্রাহক</th>
                                <th>পণ্য</th>
                                <th>পরিমাণ</th>
                                <th>লাভ</th>
                                <th>স্ট্যাটাস</th>
                                <th>অ্যাকশন</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__empty_1 = true; $__currentLoopData = $orders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $order): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr>
                                <td>
                                    <input type="checkbox" class="form-check-input order-checkbox" value="<?php echo e($order->id); ?>">
                                </td>
                                <td><?php echo e($loop->iteration + ($orders->currentPage() - 1) * $orders->perPage()); ?></td>
                                <td>
                                    <a href="<?php echo e(route('admin.order.process', ['invoice_id' => $order->invoice_id])); ?>" class="ro-invoice-link">
                                        #<?php echo e($order->invoice_id); ?>

                                    </a>
                                </td>
                                <td>
                                    <?php echo e($order->created_at->format('d M, Y')); ?><br>
                                    <span class="ro-meta-sub"><?php echo e($order->created_at->format('h:i A')); ?></span>
                                </td>
                                <td>
                                    <?php if($order->user): ?>
                                        <span class="ro-meta-name"><?php echo e($order->user->name); ?></span><br>
                                        <span class="ro-meta-sub"><?php echo e($order->user->email); ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                        $custName = $order->shipping->name ?? $order->customer->name ?? '—';
                                        $custPhone = $order->shipping->phone ?? $order->customer->phone ?? '—';
                                    ?>
                                    <span class="ro-meta-name"><?php echo e($custName); ?></span><br>
                                    <span class="ro-meta-sub"><?php echo e($custPhone); ?></span>
                                </td>
                                <td>
                                    <?php if($order->orderdetails && $order->orderdetails->count() > 0): ?>
                                        <div class="ro-product-thumbs">
                                            <?php $__currentLoopData = $order->orderdetails->take(3); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $detail): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <?php
                                                    $productImage = null;
                                                    if ($detail->product && $detail->product->image) {
                                                        $productImage = $detail->product->image->image;
                                                    } elseif ($detail->image) {
                                                        $productImage = $detail->image->image;
                                                    }
                                                ?>
                                                <?php if($productImage): ?>
                                                    <img src="<?php echo e(asset($productImage)); ?>" alt="" class="ro-product-thumb"
                                                        title="<?php echo e($detail->product_name); ?> (×<?php echo e($detail->qty); ?>)">
                                                <?php else: ?>
                                                    <span class="ro-product-more"><i class="fas fa-box"></i></span>
                                                <?php endif; ?>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                            <?php if($order->orderdetails->count() > 3): ?>
                                                <span class="ro-product-more">+<?php echo e($order->orderdetails->count() - 3); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted small">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="ro-amount">৳<?php echo e(number_format($order->customer_payable_amount ?? $order->amount, 0)); ?></span>
                                </td>
                                <td>
                                    <span class="ro-profit-badge">৳<?php echo e(number_format($order->reseller_profit ?? 0, 0)); ?></span>
                                </td>
                                <td>
                                    <form method="POST" action="<?php echo e(route('admin.reseller-orders.update-status')); ?>" class="d-inline">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="order_id" value="<?php echo e($order->id); ?>">
                                        <select name="order_status" class="form-select ro-status-select" onchange="this.form.submit()">
                                            <?php $__currentLoopData = $orderStatuses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $status): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <option value="<?php echo e($status->id); ?>" <?php echo e($order->order_status == $status->id ? 'selected' : ''); ?>>
                                                    <?php echo e($status->name); ?>

                                                </option>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        </select>
                                    </form>
                                </td>
                                <td>
                                    <div class="ro-row-actions">
                                        <a href="<?php echo e(route('admin.order.invoice', ['invoice_id' => $order->invoice_id])); ?>"
                                           class="ro-act-btn ro-act-view" title="ইনভয়েস" target="_blank">
                                            <i class="fas fa-file-invoice"></i>
                                        </a>
                                        <a href="<?php echo e(route('admin.order.process', ['invoice_id' => $order->invoice_id])); ?>"
                                           class="ro-act-btn ro-act-process" title="প্রসেস">
                                            <i class="fas fa-cog"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr>
                                <td colspan="11">
                                    <div class="ro-empty">
                                        <i class="fas fa-inbox"></i>
                                        <p class="mb-0">কোনো রিসেলার অর্ডার পাওয়া যায়নি</p>
                                    </div>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if($orders->hasPages()): ?>
                <div class="ro-paginate">
                    <?php echo e($orders->withQueryString()->links('pagination::bootstrap-4')); ?>

                </div>
                <?php endif; ?>
            </form>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('script'); ?>
<script>
(function () {
    var selectAll = document.getElementById('selectAll');
    var bulkBtn = document.getElementById('bulkUpdateBtn');
    var countEl = document.getElementById('selectedCount');
    var idsEl = document.getElementById('selectedOrderIds');
    var bulkForm = document.getElementById('bulkStatusForm');

    if (!selectAll || !bulkForm) return;

    function updateSelectedOrders() {
        var selected = Array.from(document.querySelectorAll('.order-checkbox:checked')).map(function (cb) {
            return cb.value;
        });
        var count = selected.length;
        if (countEl) countEl.textContent = count + 'টি সিলেক্ট';
        if (idsEl) idsEl.value = JSON.stringify(selected);
        if (bulkBtn) bulkBtn.disabled = count === 0;
    }

    selectAll.addEventListener('change', function () {
        document.querySelectorAll('.order-checkbox').forEach(function (checkbox) {
            checkbox.checked = selectAll.checked;
        });
        updateSelectedOrders();
    });

    document.querySelectorAll('.order-checkbox').forEach(function (checkbox) {
        checkbox.addEventListener('change', function () {
            var all = document.querySelectorAll('.order-checkbox');
            var checked = document.querySelectorAll('.order-checkbox:checked');
            selectAll.checked = all.length > 0 && checked.length === all.length;
            updateSelectedOrders();
        });
    });

    bulkForm.addEventListener('submit', function (e) {
        var selected = JSON.parse(idsEl.value || '[]');
        if (selected.length === 0) {
            e.preventDefault();
            alert('অন্তত একটি অর্ডার সিলেক্ট করুন');
            return false;
        }
        bulkForm.querySelectorAll('input[name="order_ids[]"]').forEach(function (el) { el.remove(); });
        selected.forEach(function (id) {
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'order_ids[]';
            input.value = id;
            bulkForm.appendChild(input);
        });
    });

    updateSelectedOrders();
})();
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('backEnd.layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\bilaiGhor\resources\views\backEnd\reseller_orders\index.blade.php ENDPATH**/ ?>