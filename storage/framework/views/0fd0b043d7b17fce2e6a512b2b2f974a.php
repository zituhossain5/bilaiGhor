
<?php $__env->startSection('title', 'All Products'); ?>

<?php $__env->startPush('seo'); ?>
    <meta name="robots" content="index, follow" />
    <meta name="description" content="<?php echo e(optional($generalsetting)->tagline ?? 'Browse all approved products in one place.'); ?>" />
<?php $__env->stopPush(); ?>

<?php $__env->startPush('css'); ?>
    <link rel="stylesheet" href="<?php echo e(asset('public/frontEnd/css/jquery-ui.css')); ?>" />
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<div class="bilai-cat-page">
    <div class="container">

        
        <nav class="bilai-cat-breadcrumb" aria-label="breadcrumb">
            <a href="<?php echo e(route('home')); ?>">Home</a>
            <span class="bilai-cat-breadcrumb-sep"><i class="fas fa-chevron-right"></i></span>
            <span class="bilai-cat-breadcrumb-current">Shop</span>
        </nav>

        
        <div class="bilai-cat-layout">

            
            <aside class="bilai-cat-sidebar">
                <?php echo $__env->make('frontEnd.layouts.partials.listing-filter-sidebar', ['filterBaseUrl' => route('shop')], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            </aside>

            
            <div class="bilai-cat-main">

                
                <?php echo $__env->make('frontEnd.layouts.partials.listing-topbar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

                
                <?php if($products->count() > 0): ?>
                <div class="bilai-cat-grid">
                    <?php $__currentLoopData = $products; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php echo $__env->make('frontEnd.layouts.partials.product-card', ['value' => $value, 'key' => $key], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
                <?php else: ?>
                <div class="bilai-cat-empty">
                    <i class="fas fa-box-open"></i>
                    <p>No products found.</p>
                </div>
                <?php endif; ?>

                
                <?php if($products->hasPages()): ?>
                <div class="bilai-cat-pagination">
                    <?php echo e($products->links('pagination::bootstrap-4')); ?>

                </div>
                <?php endif; ?>

            </div>
        </div>

    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('script'); ?>
    <?php echo $__env->make('frontEnd.layouts.partials.listing-js', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php echo $__env->make('frontEnd.layouts.partials.listing-analytics-js', [
        'listName' => 'Shop',
        'listSlug' => 'shop',
        'fbEvent'  => 'ViewShop',
    ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('frontEnd.layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\bilaiGhor\resources\views\frontEnd\layouts\pages\shop.blade.php ENDPATH**/ ?>