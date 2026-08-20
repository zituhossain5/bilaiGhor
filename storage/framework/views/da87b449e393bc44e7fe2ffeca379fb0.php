

<?php $__env->startSection('title', 'Manual Payment Gateway'); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h4 class="mb-1">ম্যানুয়াল পেমেন্ট গেটওয়ে</h4>
            <p class="text-muted mb-0 small">এড করুন — চেকআউটে <code>manual_ইডি</code> হিসেবে দেখা যাবে; এনাবেল/ডিজেবল করুন।</p>
        </div>
        <a href="<?php echo e(route('paymentgeteway.manage')); ?>" class="btn btn-outline-secondary btn-sm">← অন্যান্য গেটওয়ে</a>
    </div>

    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h5 class="card-title mb-3">নতুন যোগ করুন</h5>
                    <form action="<?php echo e(route('manual-payment-gateway.store')); ?>" method="POST" enctype="multipart/form-data">
                        <?php echo csrf_field(); ?>
                        <div class="mb-3">
                            <label class="form-label">শিরোনাম <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control" value="<?php echo e(old('title')); ?>" required maxlength="191" placeholder="যেমন: বিকাশ (ব্যক্তিগত)">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">লোগো (ঐচ্ছিক)</label>
                            <input type="file" name="logo" class="form-control" accept=".jpg,.jpeg,.png,.gif,.webp,.svg,image/*">
                            <small class="text-muted">JPG, PNG, GIF, WebP, SVG — সর্বোচ্চ ~4 MB। চেকআউটে পেমেন্ট অপশনের পাশে দেখাবে।</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">ইনস্ট্রাকশন (কাস্টমার চেকআউটে দেখাবে)</label>
                            <textarea name="instructions" class="form-control" rows="6" maxlength="20000" placeholder="নাম্বার, টাকা পাঠানোর নিয়ম ইত্যাদি লিখুন।"><?php echo e(old('instructions')); ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">সাজানো অর্ডার</label>
                            <input type="number" name="sort_order" class="form-control" value="<?php echo e(old('sort_order', 0)); ?>" min="0" max="65535">
                        </div>
                        <div class="form-check form-switch mb-3">
                            <input type="hidden" name="status" value="0">
                            <input class="form-check-input" type="checkbox" name="status" value="1" id="new-status" checked>
                            <label class="form-check-label" for="new-status">এনাবেলড</label>
                        </div>
                        <button type="submit" class="btn btn-primary">সংরক্ষণ</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card shadow-sm border-0">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>লোগো</th>
                                    <th>শিরোনাম</th>
                                    <th>কোড</th>
                                    <th>সর্ট</th>
                                    <th>স্ট্যাটাস</th>
                                    <th class="text-end">একশন</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $__empty_1 = true; $__currentLoopData = $gateways; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $g): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                    <tr>
                                        <td><?php echo e($g->id); ?></td>
                                        <td>
                                            <?php if($g->logo): ?>
                                                <img src="<?php echo e($g->logo_asset_url); ?>" alt="" class="rounded border" style="max-height:40px;max-width:80px;object-fit:contain;">
                                            <?php else: ?>
                                                <span class="text-muted small">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo e($g->title); ?></td>
                                        <td><code>manual_<?php echo e($g->id); ?></code></td>
                                        <td><?php echo e($g->sort_order); ?></td>
                                        <td>
                                            <?php if($g->status): ?>
                                                <span class="badge bg-success">এনাবেলড</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">ডিজেবেলড</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editModal<?php echo e($g->id); ?>">এডিট</button>
                                            <form action="<?php echo e(route('manual-payment-gateway.destroy')); ?>" method="POST" class="d-inline"
                                                onsubmit="return confirm('মুছে ফেলতে চান? চেকআউট থেকে এই অপশন উঠে যাবে।');">
                                                <?php echo csrf_field(); ?>
                                                <input type="hidden" name="id" value="<?php echo e($g->id); ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger">ডিলিট</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">এখনো কোনো ম্যানুয়াল গেটওয়ে নেই।</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <?php $__currentLoopData = $gateways; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $g): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="modal fade" id="editModal<?php echo e($g->id); ?>" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content">
                        <form action="<?php echo e(route('manual-payment-gateway.update')); ?>" method="POST" enctype="multipart/form-data">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="id" value="<?php echo e($g->id); ?>">
                            <div class="modal-header">
                                <h5 class="modal-title">এডিট — <?php echo e($g->title); ?></h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <?php if($g->logo): ?>
                                    <div class="mb-3 d-flex align-items-center gap-3">
                                        <img src="<?php echo e($g->logo_asset_url); ?>" alt="" class="rounded border" style="max-height:56px;max-width:120px;object-fit:contain;">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="remove_logo" value="1" id="rmLogo<?php echo e($g->id); ?>">
                                            <label class="form-check-label text-danger small" for="rmLogo<?php echo e($g->id); ?>">লোগো মুছে ফেলুন</label>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                <div class="mb-3">
                                    <label class="form-label">নতুন লোগো (ঐচ্ছিক)</label>
                                    <input type="file" name="logo" class="form-control" accept=".jpg,.jpeg,.png,.gif,.webp,.svg,image/*">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">শিরোনাম</label>
                                    <input type="text" name="title" class="form-control" value="<?php echo e($g->title); ?>" required maxlength="191">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">ইনস্ট্রাকশন</label>
                                    <textarea name="instructions" class="form-control" rows="8" maxlength="20000"><?php echo e($g->instructions); ?></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">সাজানো অর্ডার</label>
                                    <input type="number" name="sort_order" class="form-control" value="<?php echo e($g->sort_order); ?>" min="0" max="65535">
                                </div>
                                <div class="form-check form-switch">
                                    <input type="hidden" name="status" value="0">
                                    <input class="form-check-input" type="checkbox" name="status" value="1" id="st<?php echo e($g->id); ?>" <?php echo e($g->status ? 'checked' : ''); ?>>
                                    <label class="form-check-label" for="st<?php echo e($g->id); ?>">এনাবেলড</label>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">বন্ধ</button>
                                <button type="submit" class="btn btn-primary">আপডেট</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('backEnd.layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\bilaiGhor\resources\views/backEnd/apiintegration/manual_payment_gateways.blade.php ENDPATH**/ ?>