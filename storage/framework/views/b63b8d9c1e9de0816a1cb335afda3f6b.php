
<?php $__env->startSection('title', 'রিসেলার তালিকা'); ?>

<?php $__env->startSection('css'); ?>
<?php echo $__env->make('backEnd.reseller.partials.reseller_list_styles', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid reseller-list-shell">

    <div class="rs-page-header">
        <div>
            <h4>রিসেলার ব্যবস্থাপনা <span class="rs-badge-count"><?php echo e($stats['total']); ?></span></h4>
            <p class="rs-sub mb-0">নিবন্ধিত রিসেলার ও পার্টনারদের তালিকা, ওয়ালেট ও ভেরিফিকেশন</p>
        </div>
        <div class="rs-header-actions">
            <a href="<?php echo e(route('admin.reseller.verification.index')); ?>" class="rs-btn-ghost">
                <i class="fas fa-shield-alt"></i> ভেরিফিকেশন
            </a>
            <a href="<?php echo e(route('admin.reseller.withdrawals.index')); ?>" class="rs-btn-ghost">
                <i class="fas fa-wallet"></i> উইথড্র
            </a>
            <a href="<?php echo e(route('admin.reseller-deposits.index')); ?>" class="rs-btn-ghost">
                <i class="fas fa-piggy-bank"></i> ডিপোজিট
            </a>
        </div>
    </div>

    <div class="rs-stat-grid">
        <div class="rs-stat">
            <div class="rs-stat-label">মোট রিসেলার</div>
            <div class="rs-stat-val"><?php echo e($stats['total']); ?></div>
        </div>
        <div class="rs-stat active-stat">
            <div class="rs-stat-label">সক্রিয়</div>
            <div class="rs-stat-val"><?php echo e($stats['active']); ?></div>
        </div>
        <div class="rs-stat verified-stat">
            <div class="rs-stat-label">ভেরিফাইড</div>
            <div class="rs-stat-val"><?php echo e($stats['verified']); ?></div>
        </div>
        <div class="rs-stat pending-stat">
            <div class="rs-stat-label">ভেরিফিকেশন বাকি</div>
            <div class="rs-stat-val"><?php echo e($stats['pending']); ?></div>
        </div>
    </div>

    <div class="rs-card">
        <div class="rs-card-head">
            <h6><i class="fas fa-search"></i> খুঁজুন</h6>
        </div>
        <div class="rs-card-body">
            <form method="GET" action="<?php echo e(route('admin.resellers.index')); ?>" class="rs-filter-grid">
                <div>
                    <label class="rs-label">কীওয়ার্ড</label>
                    <input type="text" name="keyword" class="rs-input"
                           placeholder="নাম, শপ, ইমেইল..."
                           value="<?php echo e(request('keyword')); ?>">
                </div>
                <div class="rs-filter-actions">
                    <button type="submit" class="btn rs-btn-primary">
                        <i class="fas fa-search me-1"></i> খুঁজুন
                    </button>
                    <?php if(request('keyword')): ?>
                    <a href="<?php echo e(route('admin.resellers.index')); ?>" class="rs-btn-ghost">
                        <i class="fas fa-redo"></i> রিসেট
                    </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <div class="rs-card">
        <div class="rs-card-head">
            <h6><i class="fas fa-user-tie"></i> রিসেলার তালিকা</h6>
        </div>
        <div class="rs-card-body">
            <p class="d-lg-none rs-scroll-hint"><i class="fas fa-arrows-alt-h me-1"></i> বাকি কলাম দেখতে স্লাইড করুন</p>

            <div class="rs-table-rail">
                <table class="table rs-table mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>প্রোফাইল</th>
                            <th>শপ</th>
                            <th>যোগাযোগ</th>
                            <th>ওয়ালেট</th>
                            <th>ভেরিফিকেশন</th>
                            <th>স্ট্যাটাস</th>
                            <th class="text-end">অ্যাকশন</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__empty_1 = true; $__currentLoopData = $resellers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $reseller): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td class="text-muted"><?php echo e($loop->iteration + ($resellers->currentPage() - 1) * $resellers->perPage()); ?></td>
                            <td>
                                <div class="rs-shop-cell">
                                    <?php if(!empty($reseller->image)): ?>
                                        <img src="<?php echo e(asset($reseller->image)); ?>" alt="" class="rs-shop-avatar">
                                    <?php else: ?>
                                        <div class="rs-shop-placeholder"><?php echo e(strtoupper(substr($reseller->name, 0, 1))); ?></div>
                                    <?php endif; ?>
                                    <div>
                                        <div class="rs-shop-name"><?php echo e($reseller->name); ?></div>
                                        <div class="rs-shop-meta">ID #<?php echo e($reseller->id); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="fw-semibold text-dark"><?php echo e($reseller->shop_name ?? '—'); ?></span>
                            </td>
                            <td>
                                <div class="rs-contact-line mb-0">
                                    <i class="far fa-envelope"></i>
                                    <span title="<?php echo e($reseller->email); ?>"><?php echo e(Str::limit($reseller->email, 24)); ?></span>
                                </div>
                            </td>
                            <td>
                                <span class="rs-balance">৳<?php echo e(number_format($reseller->wallet_balance ?? 0, 2)); ?></span>
                            </td>
                            <td>
                                <?php if($reseller->verification_status == 'approved'): ?>
                                    <span class="rs-pill rs-badge-verified"><span class="rs-dot"></span> Verified</span>
                                <?php elseif($reseller->verification_status == 'rejected'): ?>
                                    <span class="rs-pill rs-badge-rejected"><span class="rs-dot"></span> Rejected</span>
                                <?php else: ?>
                                    <span class="rs-pill rs-badge-pending"><span class="rs-dot"></span> Pending</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if($reseller->status == 1): ?>
                                    <span class="rs-pill rs-badge-active">Active</span>
                                <?php else: ?>
                                    <span class="rs-pill rs-badge-inactive">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="rs-actions">
                                    <form method="post" action="<?php echo e(route('admin.resellers.toggle-status', $reseller->id)); ?>" class="d-inline">
                                        <?php echo csrf_field(); ?>
                                        <?php if($reseller->status == 1): ?>
                                            <button type="submit" class="rs-action-btn deactivate" title="নিষ্ক্রিয় করুন">
                                                <i class="fas fa-ban"></i>
                                            </button>
                                        <?php else: ?>
                                            <button type="submit" class="rs-action-btn activate" title="সক্রিয় করুন">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        <?php endif; ?>
                                    </form>
                                    <a href="<?php echo e(route('admin.resellers.edit', $reseller->id)); ?>" class="rs-action-btn edit" title="সম্পাদনা">
                                        <i class="fas fa-pen"></i>
                                    </a>
                                    <form method="post" action="<?php echo e(route('admin.resellers.destroy', $reseller->id)); ?>" class="d-inline"
                                          onsubmit="return confirm('এই রিসেলার মুছে ফেলবেন? সংশ্লিষ্ট অ্যাকাউন্টও মুছে যাবে।');">
                                        <?php echo csrf_field(); ?>
                                        <?php echo method_field('DELETE'); ?>
                                        <button type="submit" class="rs-action-btn delete" title="মুছুন">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="8">
                                <div class="rs-empty">
                                    <div><i class="fas fa-user-slash"></i></div>
                                    <p class="mb-0 fw-semibold">কোনো রিসেলার পাওয়া যায়নি</p>
                                    <?php if(request('keyword')): ?>
                                    <p class="small mb-0 mt-1">অন্য কীওয়ার্ড দিয়ে খুঁজুন অথবা ফিল্টার রিসেট করুন</p>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php if($resellers->hasPages() || $resellers->total() > 0): ?>
        <div class="rs-foot">
            <div class="rs-foot-meta">
                দেখানো হচ্ছে <strong><?php echo e($resellers->firstItem() ?? 0); ?></strong>–<strong><?php echo e($resellers->lastItem() ?? 0); ?></strong>
                / মোট <strong><?php echo e($resellers->total()); ?></strong> রিসেলার
            </div>
            <div><?php echo e($resellers->links('pagination::bootstrap-4')); ?></div>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php $__env->stopSection(); ?>


<?php echo $__env->make('backEnd.layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\bilaiGhor\resources\views\backEnd\reseller\index.blade.php ENDPATH**/ ?>