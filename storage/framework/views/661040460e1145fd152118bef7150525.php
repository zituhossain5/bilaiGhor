

<?php $__env->startSection('title', 'ল্যান্ডিং প্রোডাক্ট'); ?>
<?php $__env->startSection('page-title', 'ল্যান্ডিং প্রোডাক্ট'); ?>

<?php $__env->startSection('content'); ?>
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold text-dark mb-1">ল্যান্ডিং পেজের প্রোডাক্ট</h4>
            <p class="text-muted small mb-0">আপনার ল্যান্ডিং পেজে কোন প্রোডাক্ট দেখাবে ও প্রাইস সেট করুন</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?php echo e(url('/r/' . $landing->slug)); ?>" target="_blank" class="btn btn-outline-primary">
                <i class="fas fa-external-link-alt me-2"></i> প্রিভিউ
            </a>
            <a href="<?php echo e(route('reseller.landing.products.add')); ?>" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i> প্রোডাক্ট যোগ করুন
            </a>
            <a href="<?php echo e(route('reseller.landing.index')); ?>" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i> ল্যান্ডিং সেটিংস
            </a>
        </div>
    </div>

    <?php if(session('success')): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?php echo e(session('success')); ?>

            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if($products->isEmpty()): ?>
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5">
                <i class="fas fa-box-open fa-4x text-muted mb-3"></i>
                <h5 class="text-dark">কোন প্রোডাক্ট যোগ করা হয়নি</h5>
                <p class="text-muted mb-4">প্রোডাক্ট যোগ করুন এবং আপনার কাস্টম প্রাইস সেট করুন। আপনার ল্যান্ডিং পেজে শুধু এই প্রোডাক্টগুলো দেখা যাবে।</p>
                <a href="<?php echo e(route('reseller.landing.products.add')); ?>" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i> প্রোডাক্ট যোগ করুন
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 60px;">ছবি</th>
                                <th>প্রোডাক্ট</th>
                                <th>কস্ট প্রাইস (রিসেলার)</th>
                                <th>আপনার প্রাইস</th>
                                <th>লাভ</th>
                                <th class="text-end" style="width: 120px;">অ্যাকশন</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__currentLoopData = $products; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <?php
                                    $basePrice = (float) ($p->reseller_price ?? 0);
                                    $customPrice = (float) ($p->pivot->custom_price ?? 0);
                                    $profit = max(0, $customPrice - $basePrice);
                                ?>
                                <tr>
                                    <td>
                                        <?php $img = $p->image && $p->image->image ? $p->image->image : 'public/uploads/default.webp'; ?>
                                        <img src="<?php echo e(asset($img)); ?>" alt="" class="rounded" style="width: 50px; height: 50px; object-fit: cover;">
                                    </td>
                                    <td>
                                        <strong><?php echo e($p->name); ?></strong>
                                    </td>
                                    <td>৳<?php echo e(number_format($basePrice, 0)); ?></td>
                                    <td>
                                        <form action="<?php echo e(route('reseller.landing.products.update-price')); ?>" method="POST" class="d-inline-flex align-items-center gap-2">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="product_id" value="<?php echo e($p->id); ?>">
                                            <input type="number" name="custom_price" value="<?php echo e($customPrice); ?>" min="0" step="1" class="form-control form-control-sm" style="width: 100px;">
                                            <button type="submit" class="btn btn-sm btn-outline-primary" title="আপডেট">
                                                <i class="fas fa-save"></i>
                                            </button>
                                        </form>
                                    </td>
                                    <td class="text-success fw-bold">৳<?php echo e(number_format($profit, 0)); ?></td>
                                    <td class="text-end">
                                        <form action="<?php echo e(route('reseller.landing.products.remove', $p->id)); ?>" method="POST" class="d-inline" onsubmit="return confirm('প্রোডাক্ট রিমুভ করবেন?');">
                                            <?php echo csrf_field(); ?>
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="রিমুভ">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('reseller.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\bilaiGhor\resources\views\reseller\landing\products.blade.php ENDPATH**/ ?>