
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
                <form action="" method="GET" class="bilai-cat-filter-form" id="bilaiCatFilterForm">
                    
                    <?php if(request('sort')): ?>
                    <input type="hidden" name="sort" value="<?php echo e(request('sort')); ?>">
                    <?php endif; ?>
                    <?php if($activeSubcatSlug): ?>
                    <input type="hidden" name="subcategory" value="<?php echo e($activeSubcatSlug); ?>">
                    <?php endif; ?>
                    <?php if($activeBrandId): ?>
                    <input type="hidden" name="brand" value="<?php echo e($activeBrandId); ?>">
                    <?php endif; ?>

                    
                    <div class="bilai-cat-filter-block">
                        <div class="bilai-cat-filter-title">Filter by Price</div>
                        <div class="bilai-cat-price-display">
                            Price: <strong>&#2547;<span id="bilai-min-val"><?php echo e(request('min_price', $min_price)); ?></span></strong>
                            &nbsp;—&nbsp;
                            <strong>&#2547;<span id="bilai-max-val"><?php echo e(request('max_price', $max_price)); ?></span></strong>
                        </div>
                        <div id="bilai-price-range" class="bilai-price-slider"></div>
                        <input type="hidden" name="min_price" id="bilai_min_price" value="<?php echo e(request('min_price', $min_price)); ?>">
                        <input type="hidden" name="max_price" id="bilai_max_price" value="<?php echo e(request('max_price', $max_price)); ?>">
                    </div>

                    
                    <?php if($brands->count() > 0): ?>
                    <div class="bilai-cat-filter-block">
                        <div class="bilai-cat-filter-title bilai-cat-filter-toggle" data-target="bilai-brand-list">
                            Brand <i class="fas fa-chevron-up bilai-cat-toggle-icon"></i>
                        </div>
                        <div class="bilai-cat-filter-body" id="bilai-brand-list">
                            <ul class="bilai-cat-attr-link-list">
                                <?php $__currentLoopData = $brands; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $brand): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <?php
                                    $isBrandActive = (string)$activeBrandId === (string)$brand->id;
                                    $brandParams    = array_merge(request()->except(['brand', 'page']), $isBrandActive ? [] : ['brand' => $brand->id]);
                                    $brandUrl       = route('category', $category->slug) . '?' . http_build_query($brandParams);
                                ?>
                                <li>
                                    <a href="<?php echo e($brandUrl); ?>" class="bilai-cat-attr-link <?php echo e($isBrandActive ? 'active' : ''); ?>">
                                        <span class="bilai-cat-attr-name"><?php echo e($brand->name); ?></span>
                                        <span class="bilai-cat-count-badge"><?php echo e(str_pad($brandCountMap[$brand->id] ?? 0, 2, '0', STR_PAD_LEFT)); ?></span>
                                    </a>
                                </li>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </ul>
                        </div>
                    </div>
                    <?php endif; ?>

                    
                    <?php if($weights->count() > 0): ?>
                    <div class="bilai-cat-filter-block">
                        <div class="bilai-cat-filter-title bilai-cat-filter-toggle" data-target="bilai-weight-list">
                            Weight <i class="fas fa-chevron-up bilai-cat-toggle-icon"></i>
                        </div>
                        <div class="bilai-cat-filter-body" id="bilai-weight-list">
                            <ul class="bilai-cat-check-list">
                                <?php $__currentLoopData = $weights; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $w): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <li>
                                    <label class="bilai-cat-check-label">
                                        <input type="checkbox" name="weight[]" value="<?php echo e($w->id); ?>"
                                            class="bilai-cat-auto-submit"
                                            <?php if(in_array($w->id, $selectedWeights)): ?> checked <?php endif; ?>>
                                        <span class="bilai-cat-check-name"><?php echo e($w->name); ?></span>
                                        <span class="bilai-cat-count-badge"><?php echo e(str_pad($weightCountMap[$w->id] ?? 0, 2, '0', STR_PAD_LEFT)); ?></span>
                                    </label>
                                </li>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </ul>
                        </div>
                    </div>
                    <?php endif; ?>

                    
                    <?php if($lifeStages->count() > 0): ?>
                    <div class="bilai-cat-filter-block">
                        <div class="bilai-cat-filter-title bilai-cat-filter-toggle" data-target="bilai-lifestage-list">
                            Life Stage <i class="fas fa-chevron-up bilai-cat-toggle-icon"></i>
                        </div>
                        <div class="bilai-cat-filter-body" id="bilai-lifestage-list">
                            <ul class="bilai-cat-check-list">
                                <?php $__currentLoopData = $lifeStages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ls): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <li>
                                    <label class="bilai-cat-check-label">
                                        <input type="checkbox" name="life_stage[]" value="<?php echo e($ls->id); ?>"
                                            class="bilai-cat-auto-submit"
                                            <?php if(in_array($ls->id, $selectedLifeStages)): ?> checked <?php endif; ?>>
                                        <span class="bilai-cat-check-name"><?php echo e($ls->name); ?></span>
                                        <span class="bilai-cat-count-badge"><?php echo e(str_pad($lifeStageCountMap[$ls->id] ?? 0, 2, '0', STR_PAD_LEFT)); ?></span>
                                    </label>
                                </li>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </ul>
                        </div>
                    </div>
                    <?php endif; ?>

                    
                    <?php if($flavors->count() > 0): ?>
                    <div class="bilai-cat-filter-block">
                        <div class="bilai-cat-filter-title bilai-cat-filter-toggle" data-target="bilai-flavor-list">
                            Flavor <i class="fas fa-chevron-up bilai-cat-toggle-icon"></i>
                        </div>
                        <div class="bilai-cat-filter-body" id="bilai-flavor-list">
                            <ul class="bilai-cat-check-list">
                                <?php $__currentLoopData = $flavors; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $fl): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <li>
                                    <label class="bilai-cat-check-label">
                                        <input type="checkbox" name="flavor[]" value="<?php echo e($fl->id); ?>"
                                            class="bilai-cat-auto-submit"
                                            <?php if(in_array($fl->id, $selectedFlavors)): ?> checked <?php endif; ?>>
                                        <span class="bilai-cat-check-name"><?php echo e($fl->name); ?></span>
                                        <span class="bilai-cat-count-badge"><?php echo e(str_pad($flavorCountMap[$fl->id] ?? 0, 2, '0', STR_PAD_LEFT)); ?></span>
                                    </label>
                                </li>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </ul>
                        </div>
                    </div>
                    <?php endif; ?>

                </form>
            </aside>

            
            <div class="bilai-cat-main">

                
                <div class="bilai-cat-topbar">
                    <p class="bilai-cat-count">
                        <?php if($products->total() > 0): ?>
                            Showing <?php echo e($products->firstItem()); ?>–<?php echo e($products->lastItem()); ?> of <?php echo e($products->total()); ?> results
                        <?php else: ?>
                            No products found
                        <?php endif; ?>
                    </p>
                    <form action="" method="GET" id="bilaiSortForm">
                        
                        <?php $__currentLoopData = request()->except(['sort', 'page']); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $val): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php if(is_array($val)): ?>
                                <?php $__currentLoopData = $val; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $v): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <input type="hidden" name="<?php echo e($key); ?>[]" value="<?php echo e($v); ?>">
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            <?php else: ?>
                            <input type="hidden" name="<?php echo e($key); ?>" value="<?php echo e($val); ?>">
                            <?php endif; ?>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        <select name="sort" class="bilai-cat-sort-select" id="bilaiSortSelect">
                            <option value="1" <?php if(request('sort')==1): ?> selected <?php endif; ?>>Sort by Latest</option>
                            <option value="2" <?php if(request('sort')==2): ?> selected <?php endif; ?>>Oldest First</option>
                            <option value="3" <?php if(request('sort')==3): ?> selected <?php endif; ?>>Price: High to Low</option>
                            <option value="4" <?php if(request('sort')==4): ?> selected <?php endif; ?>>Price: Low to High</option>
                            <option value="5" <?php if(request('sort')==5): ?> selected <?php endif; ?>>Name: A–Z</option>
                            <option value="6" <?php if(request('sort')==6): ?> selected <?php endif; ?>>Name: Z–A</option>
                        </select>
                    </form>
                </div>

                
                <?php if($products->count() > 0): ?>
                <div class="bilai-cat-grid">
                    <?php $__currentLoopData = $products; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php
                        $avgRating   = $value->reviews->avg('ratting');
                        $filledStars = floor($avgRating);
                        $hasHalf     = $avgRating - $filledStars >= 0.5;
                        $emptyStars  = 5 - $filledStars - ($hasHalf ? 1 : 0);
                        $discount    = ($value->old_price && $value->old_price > $value->new_price)
                                       ? round((($value->old_price - $value->new_price) * 100) / $value->old_price)
                                       : 0;
                    ?>
                    <div class="bilai-product-card">
                        <div class="bilai-product-top">
                            <?php if(!empty($value->product_badge)): ?>
                            <span class="bilai-card-badge"><?php echo e($value->product_badge); ?></span>
                            <?php else: ?>
                            <span></span>
                            <?php endif; ?>
                            <button class="bilai-wishlist-btn" type="button" data-product-id="<?php echo e($value->id); ?>" aria-label="Toggle wishlist" aria-pressed="false">
                                <i class="far fa-heart"></i>
                            </button>
                        </div>
                        <div class="bilai-product-image">
                            <a href="<?php echo e(route('product', $value->slug)); ?>">
                                <img src="<?php echo e(asset($value->image ? $value->image->image : '')); ?>"
                                     alt="<?php echo e($value->name); ?>"
                                     loading="<?php echo e($key === 0 ? 'eager' : 'lazy'); ?>" />
                            </a>
                            <?php if($value->sold && $value->sold > 0): ?>
                            <span class="bilai-cat-sold-pill"><?php echo e($value->sold); ?> Sold</span>
                            <?php endif; ?>
                        </div>
                        <div class="bilai-product-meta">
                            <h3 class="bilai-product-title">
                                <a href="<?php echo e(route('product', $value->slug)); ?>"><?php echo e(Str::limit($value->name, 55)); ?></a>
                            </h3>
                            <div class="bilai-product-cat-rating">
                                <?php if($value->category): ?>
                                <p class="bilai-product-category"><?php echo e($value->category->name); ?></p>
                                <?php endif; ?>
                                <div class="bilai-product-rating">
                                    <?php for($i = 0; $i < $filledStars; $i++): ?><i class="fas fa-star"></i><?php endfor; ?>
                                    <?php if($hasHalf): ?><i class="fas fa-star-half-alt"></i><?php endif; ?>
                                    <?php for($i = 0; $i < $emptyStars; $i++): ?><i class="far fa-star"></i><?php endfor; ?>
                                </div>
                            </div>
                            <div class="bilai-product-price">
                                <div class="bilai-price-row">
                                    <span class="bilai-price-new">&#2547; <?php echo e($value->new_price); ?></span>
                                    <?php if($value->old_price): ?>
                                    <del class="bilai-price-old">&#2547; <?php echo e($value->old_price); ?></del>
                                    <?php endif; ?>
                                </div>
                                <?php if($discount > 0): ?>
                                <span class="bilai-discount-badge"><?php echo e($discount); ?>% OFF</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="bilai-product-actions">
                            <?php if(!$value->prosizes->isEmpty() || !$value->procolors->isEmpty()): ?>
                                <a href="<?php echo e(route('product', $value->slug)); ?>" class="bilai-cart-btn">
                                    <i class="fa-solid fa-cart-shopping"></i>
                                </a>
                                <a href="<?php echo e(route('product', $value->slug)); ?>" class="bilai-buy-btn">Buy Now</a>
                            <?php else: ?>
                                <form action="<?php echo e(route('cart.store')); ?>" method="POST">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="id" value="<?php echo e($value->id); ?>" />
                                    <input type="hidden" name="qty" value="1" />
                                    <button type="submit" class="bilai-cart-btn cart_store" data-id="<?php echo e($value->id); ?>">
                                        <i class="fa-solid fa-cart-shopping"></i>
                                    </button>
                                </form>
                                <form action="<?php echo e(route('cart.store')); ?>" method="POST">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="id" value="<?php echo e($value->id); ?>" />
                                    <input type="hidden" name="qty" value="1" />
                                    <input type="hidden" name="order_now" value="1">
                                    <button type="submit" class="bilai-buy-btn">Buy Now</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.11.2/jquery-ui.min.js"></script>
    <script>
    $(function () {
        var minP   = <?php echo e($min_price ?? 0); ?>;
        var maxP   = <?php echo e($max_price ?? 10000); ?>;
        var curMin = <?php echo e(request('min_price') ?: ($min_price ?? 0)); ?>;
        var curMax = <?php echo e(request('max_price') ?: ($max_price ?? 10000)); ?>;

        $("#bilai-price-range").slider({
            range: true, step: 5, min: minP, max: maxP,
            values: [curMin, curMax],
            slide: function (event, ui) {
                $("#bilai-min-val").text(ui.values[0]);
                $("#bilai-max-val").text(ui.values[1]);
                $("#bilai_min_price").val(ui.values[0]);
                $("#bilai_max_price").val(ui.values[1]);
            },
            stop: function () { $("#bilaiCatFilterForm").submit(); }
        });
        $("#bilai-min-val").text(curMin);
        $("#bilai-max-val").text(curMax);

        $(".bilai-cat-auto-submit").on("change", function () {
            $("#bilaiCatFilterForm").submit();
        });

        $("#bilaiSortSelect").on("change", function () {
            $("#bilaiSortForm").submit();
        });

        $(".bilai-cat-filter-toggle").on("click", function () {
            var target = $(this).data("target");
            $("#" + target).slideToggle(200);
            $(this).find(".bilai-cat-toggle-icon").toggleClass("fa-chevron-up fa-chevron-down");
        });

        $("#bilaiSeoToggle").on("click", function () {
            var expanded = $(this).attr("aria-expanded") === "true";
            $(this).attr("aria-expanded", String(!expanded));
            $(this).find(".bilai-seo-icon").toggleClass("fa-chevron-down fa-chevron-up");
            $("#bilaiSeoBody").slideToggle(250);
        });
    });
    </script>

    
    <script type="text/javascript">
        window.dataLayer = window.dataLayer || [];
        (function () {
            var categoryName = <?php echo json_encode($category->name, 15, 512) ?>;
            var categorySlug = <?php echo json_encode($category->slug, 15, 512) ?>;
            var categoryItems = [
                <?php $__currentLoopData = $products; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                {
                    item_id: "<?php echo e($value->id); ?>",
                    item_name: <?php echo json_encode($value->name, 15, 512) ?>,
                    price: <?php echo e((float) $value->new_price); ?>,
                    item_brand: <?php echo json_encode(optional($value->brand)->name, 15, 512) ?>,
                    item_category: <?php echo json_encode(optional($value->category)->name ?? $category->name, 15, 512) ?>,
                    item_list_id: categorySlug,
                    item_list_name: categoryName,
                    index: <?php echo e($loop->iteration); ?>,
                    slug: <?php echo json_encode($value->slug, 15, 512) ?>,
                    currency: "BDT"
                }<?php if(!$loop->last): ?>,<?php endif; ?>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            ];
            if (categoryItems.length) {
                window.dataLayer.push({ ecommerce: null });
                window.dataLayer.push({
                    event: "view_item_list",
                    ecommerce: {
                        item_list_id: categorySlug, item_list_name: categoryName,
                        items: categoryItems.map(function (item) {
                            return { item_id: item.item_id, item_name: item.item_name, index: item.index,
                                price: item.price, item_brand: item.item_brand, item_category: item.item_category,
                                item_list_id: item.item_list_id, item_list_name: item.item_list_name, currency: item.currency };
                        })
                    }
                });
            }
            if (typeof fbq === "function") {
                fbq("trackCustom", "ViewCategory", {
                    content_category: categoryName,
                    content_ids: categoryItems.map(function (i) { return i.item_id; }),
                    currency: "BDT"
                });
            }
        })();
    </script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('frontEnd.layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\projects\bilaiGhor\resources\views/frontEnd/layouts/pages/category.blade.php ENDPATH**/ ?>