
<?php $__env->startSection('title', $category->name); ?>
<?php $__env->startPush('css'); ?>
    <link rel="stylesheet" href="<?php echo e(asset('public/frontEnd/css/jquery-ui.css')); ?>" />
<?php $__env->stopPush(); ?>
<?php $__env->startPush('seo'); ?>
    <meta name="app-url" content="<?php echo e(route('category', $category->slug)); ?>" />
    <meta name="robots" content="index, follow" />
    <meta name="description" content="<?php echo e($category->meta_description); ?>" />
    <meta name="keywords" content="<?php echo e($category->slug); ?>" />
    <meta name="twitter:card" content="product" />
    <meta name="twitter:site" content="<?php echo e($category->name); ?>" />
    <meta name="twitter:title" content="<?php echo e($category->name); ?>" />
    <meta name="twitter:description" content="<?php echo e($category->meta_description); ?>" />
    <meta name="twitter:creator" content="bilaighor.bd" />
    <meta property="og:url" content="<?php echo e(route('category', $category->slug)); ?>" />
    <meta name="twitter:image" content="<?php echo e(asset($category->image)); ?>" />
    <meta property="og:title" content="<?php echo e($category->name); ?>" />
    <meta property="og:type" content="product" />
    <meta property="og:url" content="<?php echo e(route('category', $category->slug)); ?>" />
    <meta property="og:image" content="<?php echo e(asset($category->image)); ?>" />
    <meta property="og:description" content="<?php echo e($category->meta_description); ?>" />
    <meta property="og:site_name" content="<?php echo e($category->name); ?>" />
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<div class="bilai-cat-page">
    <div class="container">

        
        <nav class="bilai-cat-breadcrumb" aria-label="breadcrumb">
            <a href="<?php echo e(route('home')); ?>">Home</a>
            <span class="bilai-cat-breadcrumb-sep"><i class="fas fa-chevron-right"></i></span>
            <span class="bilai-cat-breadcrumb-current"><?php echo e($category->name); ?></span>
        </nav>

        
        <?php if($subcategories->count() > 0): ?>
        <div class="bilai-cat-sub-row">
            <?php $__currentLoopData = $subcategories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $subcat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php
                $isActiveSub = $activeSubcatSlug === $subcat->slug;
                // Build the filter URL: keep all current params except subcategory and page
                $otherParams = request()->except(['subcategory', 'page']);
                $subCardUrl  = $isActiveSub
                    ? route('category', $category->slug) . ($otherParams ? '?' . http_build_query($otherParams) : '')
                    : route('category', $category->slug) . '?' . http_build_query(array_merge($otherParams, ['subcategory' => $subcat->slug]));
            ?>
            <a href="<?php echo e($subCardUrl); ?>" class="bilai-cat-sub-card <?php echo e($isActiveSub ? 'active' : ''); ?>">
                <div class="bilai-cat-sub-img-box">
                    <?php if($subcat->image): ?>
                    <img src="<?php echo e(asset($subcat->image)); ?>" alt="<?php echo e($subcat->subcategoryName); ?>" loading="lazy">
                    <?php else: ?>
                    <i class="fas fa-tag"></i>
                    <?php endif; ?>
                </div>
                <span><?php echo e($subcat->subcategoryName); ?></span>
            </a>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
        <?php endif; ?>

        
        <div class="bilai-cat-layout">

            
            <aside class="bilai-cat-sidebar">
                <?php echo $__env->make('frontEnd.layouts.partials.listing-filter-sidebar', ['filterBaseUrl' => route('category', $category->slug)], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
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


<?php if($category->full_description): ?>
<div class="container">
<div class="bilai-seo-accordion">
        <button class="bilai-seo-toggle" id="bilaiSeoToggle" type="button" aria-expanded="false">
            <span>View Full Description</span>
            <i class="fas fa-chevron-down bilai-seo-icon"></i>
        </button>
        <div class="bilai-seo-body" id="bilaiSeoBody" style="display:none;">
            <div class="bilai-seo-content">
                <?php echo $category->full_description; ?>

            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php $__env->stopSection(); ?>

<?php $__env->startPush('script'); ?>
    <?php echo $__env->make('frontEnd.layouts.partials.listing-js', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php echo $__env->make('frontEnd.layouts.partials.listing-analytics-js', [
        'listName' => $category->name,
        'listSlug' => $category->slug,
        'fbEvent'  => 'ViewCategory',
    ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('frontEnd.layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\projects\bilaiGhor\resources\views/frontEnd/layouts/pages/category.blade.php ENDPATH**/ ?>