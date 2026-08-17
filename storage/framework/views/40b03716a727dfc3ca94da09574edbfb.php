

<?php $__env->startSection('title', 'প্রোডাক্ট যোগ করুন'); ?>
<?php $__env->startSection('page-title', 'প্রোডাক্ট যোগ করুন'); ?>

<?php $__env->startSection('content'); ?>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-1">ল্যান্ডিং পেজে প্রোডাক্ট যোগ করুন</h4>
            <p class="text-muted small mb-0">প্রোডাক্ট সিলেক্ট করুন এবং আপনার কাস্টম বিক্রয় মূল্য সেট করুন</p>
        </div>
        <a href="<?php echo e(route('reseller.landing.products')); ?>" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i> ফিরে যান
        </a>
    </div>

    <?php if($availableProducts->isEmpty()): ?>
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5">
                <i class="fas fa-check-circle fa-4x text-success mb-3"></i>
                <h5>সব প্রোডাক্ট ইতিমধ্যে যোগ করা হয়েছে</h5>
                <a href="<?php echo e(route('reseller.landing.products')); ?>" class="btn btn-primary mt-3">প্রোডাক্ট তালিকায় ফিরে যান</a>
            </div>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php $__currentLoopData = $availableProducts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $product): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php
                    $img = $product->image && $product->image->image ? $product->image->image : 'public/uploads/default.webp';
                    $basePrice = (float) ($product->reseller_price ?? 0);
                    $mainPrice = (float) ($product->new_price ?? $product->old_price ?? 0);
                ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card border-0 shadow-sm h-100">
                        <img src="<?php echo e(asset($img)); ?>" class="card-img-top" alt="<?php echo e($product->name); ?>" style="height: 180px; object-fit: cover;">
                        <div class="card-body">
                            <h6 class="card-title fw-bold text-dark"><?php echo e(Str::limit($product->name, 50)); ?></h6>
                            <p class="small text-muted mb-2">
                                রিসেলার কস্ট: <strong>৳<?php echo e(number_format($basePrice, 0)); ?></strong>
                                <?php if($mainPrice > 0): ?>
                                    | রিটেইল: <span class="text-decoration-line-through">৳<?php echo e(number_format($mainPrice, 0)); ?></span>
                                <?php endif; ?>
                            </p>
                            <form action="<?php echo e(route('reseller.landing.products.add.store')); ?>" method="POST">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="product_id" value="<?php echo e($product->id); ?>">
                                <div class="mb-3">
                                    <label class="form-label small fw-semibold">আপনার বিক্রয় মূল্য (৳)</label>
                                    <input type="number" name="custom_price" class="form-control" value="<?php echo e(max($basePrice, $mainPrice > 0 ? $mainPrice : $basePrice)); ?>" min="<?php echo e($basePrice); ?>" step="1" required placeholder="প্রাইস">
                                    <small class="text-muted">কমপক্ষে ৳<?php echo e(number_format($basePrice, 0)); ?> (কস্ট প্রাইস)</small>
                                </div>
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-plus me-2"></i> যোগ করুন
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    <?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('reseller.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\bilaiGhor\resources\views\reseller\landing\add-products.blade.php ENDPATH**/ ?>