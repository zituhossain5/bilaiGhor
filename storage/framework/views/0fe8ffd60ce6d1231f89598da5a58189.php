
<?php $__env->startSection('title', $subcategory->subcategoryName); ?>
<?php $__env->startPush('css'); ?>
    <link rel="stylesheet" href="<?php echo e(asset('public/frontEnd/css/jquery-ui.css')); ?>" />
<?php $__env->stopPush(); ?>
<?php $__env->startPush('seo'); ?>
    <meta name="app-url" content="<?php echo e(route('subcategory', $subcategory->slug)); ?>" />
    <meta name="robots" content="index, follow" />
    <meta name="description" content="<?php echo e($subcategory->meta_description); ?>" />
    <meta name="keywords" content="<?php echo e($subcategory->slug); ?>" />
    <meta name="twitter:card" content="product" />
    <meta name="twitter:site" content="<?php echo e($subcategory->subcategoryName); ?>" />
    <meta name="twitter:title" content="<?php echo e($subcategory->subcategoryName); ?>" />
    <meta name="twitter:description" content="<?php echo e($subcategory->meta_description); ?>" />
    <meta name="twitter:creator" content="bilaighor.bd" />
    <meta property="og:url" content="<?php echo e(route('subcategory', $subcategory->slug)); ?>" />
    <meta name="twitter:image" content="<?php echo e(asset($subcategory->image)); ?>" />
    <meta property="og:title" content="<?php echo e($subcategory->subcategoryName); ?>" />
    <meta property="og:type" content="product" />
    <meta property="og:url" content="<?php echo e(route('subcategory', $subcategory->slug)); ?>" />
    <meta property="og:image" content="<?php echo e(asset($subcategory->image)); ?>" />
    <meta property="og:description" content="<?php echo e($subcategory->meta_description); ?>" />
    <meta property="og:site_name" content="<?php echo e($subcategory->subcategoryName); ?>" />
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<div class="bilai-cat-page">
    <div class="container">

        
        <nav class="bilai-cat-breadcrumb" aria-label="breadcrumb">
            <a href="<?php echo e(route('home')); ?>">Home</a>
            <span class="bilai-cat-breadcrumb-sep"><i class="fas fa-chevron-right"></i></span>
            <?php if($category): ?>
            <a href="<?php echo e(route('category', $category->slug)); ?>"><?php echo e($category->name); ?></a>
            <span class="bilai-cat-breadcrumb-sep"><i class="fas fa-chevron-right"></i></span>
            <?php endif; ?>
            <span class="bilai-cat-breadcrumb-current"><?php echo e($subcategory->subcategoryName); ?></span>
        </nav>

        
        <?php if($siblings->count() > 0): ?>
        <div class="bilai-cat-sub-row">
            <?php $__currentLoopData = $siblings; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $sibling): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php
                $isActive = $sibling->slug === $subcategory->slug;
            ?>
            <a href="<?php echo e(route('subcategory', $sibling->slug)); ?>"
               class="bilai-cat-sub-card <?php echo e($isActive ? 'active' : ''); ?>">
                <div class="bilai-cat-sub-img-box">
                    <?php if($sibling->image): ?>
                    <img src="<?php echo e(asset($sibling->image)); ?>" alt="<?php echo e($sibling->subcategoryName); ?>" loading="lazy">
                    <?php else: ?>
                    <i class="fas fa-tag"></i>
                    <?php endif; ?>
                </div>
                <span><?php echo e($sibling->subcategoryName); ?></span>
            </a>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
        <?php endif; ?>

        
        <div class="bilai-cat-layout">

            
            <aside class="bilai-cat-sidebar">
                <?php echo $__env->make('frontEnd.layouts.partials.listing-filter-sidebar', ['filterBaseUrl' => route('subcategory', $subcategory->slug)], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
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


<?php if($subcategory->full_description): ?>
<div class="bilai-seo-accordion">
    <div class="container">
        <button class="bilai-seo-toggle" id="bilaiSeoToggle" type="button" aria-expanded="false">
            <span>View Full Description</span>
            <i class="fas fa-chevron-down bilai-seo-icon"></i>
        </button>
        <div class="bilai-seo-body" id="bilaiSeoBody" style="display:none;">
            <div class="bilai-seo-content">
                <?php echo $subcategory->full_description; ?>

            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php $__env->stopSection(); ?>

<?php $__env->startPush('script'); ?>
    <?php echo $__env->make('frontEnd.layouts.partials.listing-js', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php echo $__env->make('frontEnd.layouts.partials.listing-analytics-js', [
        'listName' => $subcategory->subcategoryName,
        'listSlug' => $subcategory->slug,
        'fbEvent'  => 'ViewSubcategory',
    ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('frontEnd.layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\bilaiGhor\resources\views\frontEnd\layouts\pages\subcategory.blade.php ENDPATH**/ ?>