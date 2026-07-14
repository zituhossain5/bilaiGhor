
<?php $__env->startSection('title', 'All Products'); ?>

<?php
    $sliderStep = max(1, min(500, ceil(($max_price - $min_price) / 80)));
?>

<?php $__env->startPush('seo'); ?>
    <meta name="robots" content="index, follow" />
    <meta name="description" content="<?php echo e(optional($generalsetting)->tagline ?? 'Browse all approved products in one place.'); ?>" />
<?php $__env->stopPush(); ?>

<?php $__env->startPush('css'); ?>
    <link rel="stylesheet" href="<?php echo e(asset('public/frontEnd/css/jquery-ui.css')); ?>" />
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
    <section class="product-section">
        <div class="container">
            <div class="sorting-section">
                <div class="row">
                    <div class="col-sm-6">
                        <div class="category-breadcrumb d-flex align-items-center flex-wrap gap-2">
                            <a href="<?php echo e(route('home')); ?>">Home</a>
                            <span>/</span>
                            <strong>All Products</strong>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="row">
                            <div class="col-sm-6">
                                <div class="showing-data">
                                    <?php if($products->total() > 0): ?>
                                        <span>Showing <?php echo e($products->firstItem()); ?>-<?php echo e($products->lastItem()); ?> of
                                            <?php echo e($products->total()); ?> Results</span>
                                    <?php else: ?>
                                        <span>No products found</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="filter_sort">
                                    <div class="filter_btn">
                                        <i class="fa fa-list-ul"></i>
                                    </div>
                                    <div class="page-sort">
                                        <form action="<?php echo e(url()->current()); ?>" method="GET" class="sort-form">
                                            <select name="sort" class="form-control form-select sort">
                                                <option value="1" <?php if(request('sort') == 1): echo 'selected'; endif; ?>>Product: Latest</option>
                                                <option value="2" <?php if(request('sort') == 2): echo 'selected'; endif; ?>>Product: Oldest</option>
                                                <option value="3" <?php if(request('sort') == 3): echo 'selected'; endif; ?>>Price: High To Low</option>
                                                <option value="4" <?php if(request('sort') == 4): echo 'selected'; endif; ?>>Price: Low To High</option>
                                                <option value="5" <?php if(request('sort') == 5): echo 'selected'; endif; ?>>Name: A-Z</option>
                                                <option value="6" <?php if(request('sort') == 6): echo 'selected'; endif; ?>>Name: Z-A</option>
                                            </select>
                                            <input type="hidden" name="min_price" value="<?php echo e(request('min_price', $min_price)); ?>" />
                                            <input type="hidden" name="max_price" value="<?php echo e(request('max_price', $max_price)); ?>" />
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-sm-3 filter_sidebar">
                    <div class="filter_close"><i class="fa fa-long-arrow-left"></i> Filter</div>
                    <form action="<?php echo e(url()->current()); ?>" method="GET" class="attribute-submit shop-filter-form">
                        <?php if(request()->filled('sort')): ?>
                            <input type="hidden" name="sort" value="<?php echo e(request('sort')); ?>" />
                        <?php endif; ?>
                        <div class="sidebar_item wraper__item">
                            <div class="accordion" id="shop_categories_sidebar">
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button" type="button" data-bs-toggle="collapse"
                                            data-bs-target="#collapseShopCat" aria-expanded="true">
                                            Categories
                                        </button>
                                    </h2>
                                    <div id="collapseShopCat" class="accordion-collapse collapse show"
                                        data-bs-parent="#shop_categories_sidebar">
                                        <div class="accordion-body cust_according_body">
                                            <ul class="mb-0">
                                                <?php $__currentLoopData = $menucategories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                    <li class="mb-1">
                                                        <a href="<?php echo e(route('category', $cat->slug)); ?>"><?php echo e($cat->name); ?></a>
                                                    </li>
                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="sidebar_item wraper__item">
                            <div class="accordion" id="shop_price_sidebar">
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button" type="button" data-bs-toggle="collapse"
                                            data-bs-target="#collapseShopPrice" aria-expanded="true">
                                            Price
                                        </button>
                                    </h2>
                                    <div id="collapseShopPrice" class="accordion-collapse collapse show"
                                        data-bs-parent="#shop_price_sidebar">
                                        <div class="accordion-body cust_according_body">
                                            <div class="category-filter-box category__wraper">
                                                <div class="category-filter-item">
                                                    <div class="filter-body">
                                                        <div class="slider-box">
                                                            <div class="filter-price-inputs">
                                                                <p class="min-price">৳<input type="text" name="min_price"
                                                                        id="min_price" readonly /></p>
                                                                <p class="max-price">৳<input type="text" name="max_price"
                                                                        id="max_price" readonly /></p>
                                                            </div>
                                                            <div id="price-range" class="slider form-attribute ui-slider-shop"></div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <button type="submit" class="btn btn-sm btn-primary w-100 mt-2 rounded-pill">Apply filter</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="col-sm-9">
                    <div class="category-product main_product_inner">
                        <?php $__currentLoopData = $products; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <div class="product_item wist_item wow zoomIn" data-wow-duration="1.5s"
                                data-wow-delay="0.<?php echo e($key); ?>s">
                                <div class="product_item_inner">
                                    <?php if($value->old_price): ?>
                                        <div class="sale-badge">
                                            <div class="sale-badge-inner">
                                                <div class="sale-badge-box">
                                                    <span class="sale-badge-text">
                                                        <?php
                                                            $discount = ((($value->old_price - $value->new_price) * 100) / $value->old_price);
                                                        ?>
                                                        <p><?php echo e(number_format($discount, 0)); ?>%</p>
                                                        ছাড়
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    <div class="pro_img">
                                        <a href="<?php echo e(route('product', $value->slug)); ?>">
                                            <img src="<?php echo e(asset($value->image ? $value->image->image : '')); ?>"
                                                alt="<?php echo e($value->name); ?>" />
                                        </a>
                                    </div>
                                    <div class="pro_des">
                                        <div class="pro_name">
                                            <a href="<?php echo e(route('product', $value->slug)); ?>">
                                                <?php echo e(Str::limit($value->name, 35)); ?>

                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <?php
                                    $averageRating = $value->reviews->avg('ratting') ?? 0;
                                    $filledStars = floor($averageRating);
                                    $hasHalfStar = $averageRating - $filledStars >= 0.5;
                                    $emptyStars = 5 - $filledStars - ($hasHalfStar ? 1 : 0);
                                ?>

                                <?php if($averageRating >= 0 && $averageRating <= 5): ?>
                                    <?php for($i = 0; $i < $filledStars; $i++): ?>
                                        <i class="fas fa-star"></i>
                                    <?php endfor; ?>
                                    <?php if($hasHalfStar): ?>
                                        <i class="fas fa-star-half-alt"></i>
                                    <?php endif; ?>
                                    <?php for($i = 0; $i < $emptyStars; $i++): ?>
                                        <i class="far fa-star"></i>
                                    <?php endfor; ?>
                                <?php else: ?>
                                    <span>&nbsp;</span>
                                <?php endif; ?>

                                <div class="pro_price">
                                    <p>
                                        <?php if($value->old_price): ?>
                                            <del>৳ <?php echo e($value->old_price); ?></del>
                                        <?php endif; ?>
                                        ৳ <?php echo e($value->new_price); ?>

                                    </p>
                                </div>

                                <?php if(!$value->prosizes->isEmpty() || !$value->procolors->isEmpty()): ?>
                                    <div class="pro_btn">
                                        <a href="<?php echo e(route('product', $value->slug)); ?>" class="order-btn-link order-btn">
                                            অর্ডার করুন
                                        </a>
                                        <a href="<?php echo e(route('product', $value->slug)); ?>" class="cart-icon-link cart-icon-btn">
                                            <i class="fa-solid fa-cart-shopping"></i>
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <div class="pro_btn">
                                        <form action="<?php echo e(route('cart.store')); ?>" method="POST">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="id" value="<?php echo e($value->id); ?>" />
                                            <input type="hidden" name="qty" value="1" />
                                            <input type="hidden" name="order_now" value="1" />
                                            <button type="submit" class="order-btn">
                                                অর্ডার করুন
                                            </button>
                                        </form>
                                        <form action="<?php echo e(route('cart.store')); ?>" method="POST">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="id" value="<?php echo e($value->id); ?>" />
                                            <input type="hidden" name="qty" value="1" />
                                            <button type="submit" class="cart-icon-btn cart_store"
                                                data-id="<?php echo e($value->id); ?>">
                                                <i class="fa-solid fa-cart-shopping"></i>
                                            </button>
                                        </form>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-sm-12">
                    <div class="custom_paginate">
                        <?php echo e($products->links('pagination::bootstrap-4')); ?>

                    </div>
                </div>
            </div>
        </div>
    </section>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('script'); ?>
    <script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.13.2/jquery-ui.min.js"></script>
    <script>
        $(".sort").change(function() {
            $('#loading').show();
            $(".sort-form").submit();
        });

        $(".form-attribute").on('change slide', function() {
            // slider stop handled below
        });

        $(function() {
            $("#price-range").slider({
                step: <?php echo e($sliderStep); ?>,
                range: true,
                min: <?php echo e($min_price); ?>,
                max: <?php echo e($max_price); ?>,
                values: [
                    <?php echo e(request()->filled('min_price') ? (float) request('min_price') : $min_price); ?>,
                    <?php echo e(request()->filled('max_price') ? (float) request('max_price') : $max_price); ?>

                ],
                slide: function(event, ui) {
                    $("#min_price").val(ui.values[0]);
                    $("#max_price").val(ui.values[1]);
                },
                stop: function() {
                    $(".shop-filter-form").submit();
                }
            });
            $("#min_price").val($("#price-range").slider("values", 0));
            $("#max_price").val($("#price-range").slider("values", 1));
        });
    </script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('frontEnd.layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\projects\bilaiGhor\resources\views/frontEnd/layouts/pages/shop.blade.php ENDPATH**/ ?>