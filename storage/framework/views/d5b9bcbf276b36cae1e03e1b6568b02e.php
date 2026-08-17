

<?php $__env->startSection('title', $seo->meta_title ?? 'Home'); ?>

<?php $__env->startPush('css'); ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&display=swap" rel="stylesheet">
<?php $__env->stopPush(); ?>

<?php $__env->startPush('seo'); ?>
<meta name="app-url" content="<?php echo e(url('/')); ?>" />
<meta name="robots" content="index, follow" />

<meta name="description" content="<?php echo e($seo->meta_description ?? ''); ?>" />
<meta name="keywords" content="<?php echo e($seo->meta_tags ?? ''); ?>" />

<!-- Open Graph data -->
<meta property="og:title" content="<?php echo e($seo->meta_title ?? ''); ?>" />
<meta property="og:type" content="website" />
<meta property="og:url" content="<?php echo e(url()->current()); ?>" />
<meta property="og:image" content="<?php echo e(asset($generalsetting->og_baner ?? 'public/logo.png')); ?>" />
<meta property="og:description" content="<?php echo e($seo->meta_description ?? ''); ?>" />
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>

<section class="bilai-hero-section">
    <div class="container">
        <div class="main_slider owl-carousel bilai-hero-owl">
            <?php $__currentLoopData = $sliders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="slider-item">
                <div class="bilai-hero-slide">
                    <div class="bilai-hero-content" style="background-image: url('<?php echo e(asset('public/frontEnd/images/sliderContent.png')); ?>');">
                        <?php if($value->title): ?>
                            <?php if($key === 0): ?>
                            <h1 class="bilai-hero-title">
                                <?php echo e($value->title); ?>

                                <?php if($value->highlight_text): ?>
                                <span class="bilai-hero-highlight"><?php echo e($value->highlight_text); ?></span>
                                <?php endif; ?>
                            </h1>
                            <?php else: ?>
                            <h2 class="bilai-hero-title">
                                <?php echo e($value->title); ?>

                                <?php if($value->highlight_text): ?>
                                <span class="bilai-hero-highlight"><?php echo e($value->highlight_text); ?></span>
                                <?php endif; ?>
                            </h2>
                            <?php endif; ?>
                        <?php endif; ?>
                        <?php if($value->description): ?>
                        <p class="bilai-hero-desc"><?php echo e($value->description); ?></p>
                        <?php endif; ?>
                        <?php if($value->button_text): ?>
                        <a href="<?php echo e($value->button_link ?: $value->link); ?>" class="bilai-hero-btn">
                            <?php echo e($value->button_text); ?> &rarr;
                        </a>
                        <?php endif; ?>
                    </div>
                    <div class="bilai-hero-image">
                        <img src="<?php echo e(asset($value->image)); ?>"
                             alt="<?php echo e($value->image_alt ?: ($value->title ?: 'Hero Slide')); ?>"
                             loading="<?php echo e($key === 0 ? 'eager' : 'lazy'); ?>" />
                    </div>
                </div>
            </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    </div>
</section>


<section class="bilai-promo-strip">
    <div class="container">
        <div class="bilai-promo-grid">
            <div class="bilai-promo-item">
                <span class="bilai-promo-icon">
                    <img src="<?php echo e(asset('public/frontEnd/images/truck.svg')); ?>" width='42' height='42'%3E%3Crect width='42' height='42' fill='%23dccab2' rx='8'/%3E%3C/svg%3E" alt="Free Delivery Icon" width="42" height="42">
                </span>
                <span class="bilai-promo-text">ঢাকায় ফ্রী ডেলিভারী <strong>১৫০০ টাকার উপর অর্ডারে</strong></span>
            </div>
            <div class="bilai-promo-item">
                <span class="bilai-promo-icon">
                    <img src="<?php echo e(asset('public/frontEnd/images/percent.svg')); ?>" width='42' height='42'%3E%3Crect width='42' height='42' fill='%23dccab2' rx='8'/%3E%3C/svg%3E" alt="Discount Icon" width="42" height="42">                </span>
                <span class="bilai-promo-text"><strong>৫% ডিসকাউন্ট</strong> প্রথম অর্ডারে</span>
            </div>
            <div class="bilai-promo-item">
                <span class="bilai-promo-icon">
                    <img src="<?php echo e(asset('public/frontEnd/images/redeem.svg')); ?>" width='42' height='42'%3E%3Crect width='42' height='42' fill='%23dccab2' rx='8'/%3E%3C/svg%3E" alt="Reedem Icon" width="42" height="42">                </span>
                <span class="bilai-promo-text">প্রতি অর্ডারে <strong>রিডিম পয়েন্ট জিতুন</strong></span>
            </div>
            <div class="bilai-promo-item">
                <span class="bilai-promo-icon">
                    <img src="<?php echo e(asset('public/frontEnd/images/return.svg')); ?>" width='42' height='42'%3E%3Crect width='42' height='42' fill='%23dccab2' rx='8'/%3E%3C/svg%3E" alt="Return" width="42" height="42">                </span>
                <span class="bilai-promo-text"><strong>সহজ রিটার্ন</strong></span>
            </div>
        </div>
    </div>
</section>


<section class="bottoads_area">
    <div class="container">
        <div class="row">
            <div class="col-sm-12">
                <div class="bottoads_inner">
                    <?php $__currentLoopData = $sliderbottomads; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div class="ads_item">
                            <a href="<?php echo e($value->link); ?>">
                                <img src="<?php echo e(asset($value->image)); ?>"
                                     alt="Ads"
                                     class="img-fluid"
                                     loading="lazy" />
                            </a>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            </div>
        </div>
    </div>
</section>




<section class="bilai-cat-section">
    <div class="container">
        <div class="bilai-cat-header">
            <h2 class="bilai-cat-title">Shop by Category</h2>
            <a href="#" class="bilai-cat-more">See More</a>
        </div>
        <div class="bilai-cat-grid-home">
            <?php $__currentLoopData = $menucategories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <a href="<?php echo e(route('category', $value->slug)); ?>" class="bilai-cat-card">
                <div class="bilai-cat-img">
                    <img src="<?php echo e(asset($value->image)); ?>"
                         alt="<?php echo e($value->name); ?>"
                         loading="lazy" />
                </div>
                <span class="bilai-cat-name"><?php echo e($value->name); ?></span>
            </a>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    </div>
</section>


<section class="bilai-weekly-deals">
    <div class="container">
        <div class="bilai-deal-header">
            <div class="bilai-deal-header-left">
                <h2 class="bilai-deal-title">Weekly Deals</h2>
                <div class="bilai-deal-timer" id="simple_timer"></div>
            </div>
            <a href="<?php echo e(route('hotdeals')); ?>" class="bilai-deal-view-all">View All Deals</a>
        </div>

        <div class="product_slider owl-carousel">
            <?php $__currentLoopData = $hotdeal_top; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="bilai-product-card">
                <div class="bilai-product-top">
                    <span class="bilai-stock-badge">Limited Stock</span>
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
                </div>

                <div class="bilai-product-meta">
                    <h3 class="bilai-product-title">
                        <a href="<?php echo e(route('product', $value->slug)); ?>"><?php echo e(Str::limit($value->name, 55)); ?></a>
                    </h3>

                    <div class="bilai-product-cat-rating">
                        <?php if($value->category): ?>
                        <p class="bilai-product-category"><?php echo e($value->category->name); ?></p>
                        <?php endif; ?>
                        <?php
                            $averageRating = $value->reviews->avg('ratting');
                            $filledStars   = floor($averageRating);
                            $hasHalfStar   = $averageRating - $filledStars >= 0.5;
                            $emptyStars    = 5 - $filledStars - ($hasHalfStar ? 1 : 0);
                        ?>
                        <div class="bilai-product-rating">
                            <?php for($i = 0; $i < $filledStars; $i++): ?>
                                <i class="fas fa-star"></i>
                            <?php endfor; ?>
                            <?php if($hasHalfStar): ?>
                                <i class="fas fa-star-half-alt"></i>
                            <?php endif; ?>
                            <?php for($i = 0; $i < $emptyStars; $i++): ?>
                                <i class="far fa-star"></i>
                            <?php endfor; ?>
                        </div>
                    </div>

                    <div class="bilai-product-price">
                        <div class="bilai-price-row">
                            <span class="bilai-price-new">৳ <?php echo e($value->new_price); ?></span>
                            <?php if($value->old_price): ?>
                            <del class="bilai-price-old">৳ <?php echo e($value->old_price); ?></del>
                            <?php endif; ?>
                        </div>
                        <?php if($value->old_price): ?>
                        <?php $discount = round((($value->old_price - $value->new_price) * 100) / $value->old_price); ?>
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
    </div>
</section>


<?php if($best_seller_top->isNotEmpty()): ?>
<section class="bilai-best-sellers">
    <div class="container">
        <div class="bilai-section-header">
             <h2 class="bilai-deal-title">Best Sellers</h2>
            <a href="<?php echo e(route('hotdeals')); ?>" class="bilai-section-view-all">View More</a>
        </div>

        <div class="product_slider owl-carousel">
            <?php $__currentLoopData = $best_seller_top; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="bilai-product-card">
                <div class="bilai-product-top">
                    <span class="bilai-stock-badge">Best Seller</span>
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
                </div>

                <?php if($value->best_seller_sold_count > 0): ?>
                <div class="bilai-sold-count"><?php echo e($value->best_seller_sold_count); ?> Sold</div>
                <?php endif; ?>

                <div class="bilai-product-meta">
                    <h3 class="bilai-product-title">
                        <a href="<?php echo e(route('product', $value->slug)); ?>"><?php echo e(Str::limit($value->name, 55)); ?></a>
                    </h3>

                    <div class="bilai-product-cat-rating">
                        <?php if($value->category): ?>
                        <p class="bilai-product-category"><?php echo e($value->category->name); ?></p>
                        <?php endif; ?>
                        <?php
                            $averageRating = $value->reviews->avg('ratting');
                            $filledStars   = floor($averageRating);
                            $hasHalfStar   = $averageRating - $filledStars >= 0.5;
                            $emptyStars    = 5 - $filledStars - ($hasHalfStar ? 1 : 0);
                        ?>
                        <div class="bilai-product-rating">
                            <?php for($i = 0; $i < $filledStars; $i++): ?>
                                <i class="fas fa-star"></i>
                            <?php endfor; ?>
                            <?php if($hasHalfStar): ?>
                                <i class="fas fa-star-half-alt"></i>
                            <?php endif; ?>
                            <?php for($i = 0; $i < $emptyStars; $i++): ?>
                                <i class="far fa-star"></i>
                            <?php endfor; ?>
                        </div>
                    </div>

                    <div class="bilai-product-price">
                        <div class="bilai-price-row">
                            <span class="bilai-price-new">৳ <?php echo e($value->new_price); ?></span>
                            <?php if($value->old_price): ?>
                            <del class="bilai-price-old">৳ <?php echo e($value->old_price); ?></del>
                            <?php endif; ?>
                        </div>
                        <?php if($value->old_price): ?>
                        <?php $discount = round((($value->old_price - $value->new_price) * 100) / $value->old_price); ?>
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
    </div>
</section>
<?php endif; ?>


<section>
    <div class="">
        <div class="row">
            <?php $__currentLoopData = $homepageads; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $homeads): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="col-md-12">
                <a href="<?php echo e($homeads->link); ?>?sold=show">
                    <img class="img-fluid w-100"
                         src="<?php echo e(asset($homeads->image)); ?>"
                         alt="Homepage Ads"
                         loading="lazy" />
                </a>
            </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    </div>
</section>


<?php if(isset($new_arrival_products) && $new_arrival_products->isNotEmpty()): ?>
<section class="bilai-new-arrivals-section">
    <div class="container">
        <div class="bilai-na-header">
            <h2 class="bilai-na-title">New Arrivals</h2>
            <a href="<?php echo e(route('shop')); ?>" class="bilai-na-more-btn">View More</a>
        </div>
        <div class="bilai-na-grid">
            <?php $__currentLoopData = $new_arrival_products; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php
                $reviewCount = $value->reviews->count();
                $avgRating   = $reviewCount > 0 ? $value->reviews->avg('rating') : 0;
                $fullStars   = floor($avgRating);
                $halfStar    = ($avgRating - $fullStars) >= 0.5;
                $emptyStars  = 5 - $fullStars - ($halfStar ? 1 : 0);
                $discount    = ($value->old_price > 0 && $value->new_price > 0 && $value->old_price > $value->new_price)
                                ? round((($value->old_price - $value->new_price) / $value->old_price) * 100)
                                : 0;
                $hasVariants = $value->prosizes->isNotEmpty() || $value->procolors->isNotEmpty();
            ?>
            <div class="bilai-na-card">
                
                <img src="<?php echo e(asset('public/frontEnd/images/NewIcon.svg')); ?>" class="bilai-na-badge" alt="New" width="54" height="54">

                
                <div class="bilai-na-left">
                    <button class="bilai-na-wishlist" type="button" data-product-id="<?php echo e($value->id); ?>" aria-label="Toggle wishlist" aria-pressed="false">
                        <i class="far fa-heart"></i>
                    </button>
                    <a href="<?php echo e(route('product', $value->slug)); ?>" class="bilai-na-img-wrap">
                        <?php if($value->image): ?>
                        <img src="<?php echo e(asset($value->image->image)); ?>" alt="<?php echo e($value->name); ?>" loading="lazy">
                        <?php else: ?>
                        <img src="<?php echo e(asset('public/no-image.png')); ?>" alt="<?php echo e($value->name); ?>" loading="lazy">
                        <?php endif; ?>
                    </a>
                </div>

                
                <div class="bilai-na-info">

                    
                    <h3 class="bilai-na-name">
                        <a href="<?php echo e(route('product', $value->slug)); ?>"><?php echo e(Str::limit($value->name, 55)); ?></a>
                    </h3>

                    
                    <div class="bilai-na-cat-rating">
                        <?php if($value->category): ?>
                        <p class="bilai-na-category"><?php echo e($value->category->name); ?></p>
                        <?php else: ?>
                        <p class="bilai-na-category"></p>
                        <?php endif; ?>
                        <div class="bilai-na-stars">
                            <?php for($i = 0; $i < $fullStars; $i++): ?><i class="fas fa-star"></i><?php endfor; ?>
                            <?php if($halfStar): ?><i class="fas fa-star-half-alt"></i><?php endif; ?>
                            <?php for($i = 0; $i < $emptyStars; $i++): ?><i class="far fa-star"></i><?php endfor; ?>
                        </div>
                    </div>

                    
                    <div class="bilai-na-price-row">
                        <div class="bilai-na-price-left">
                            <span class="bilai-na-price">৳ <?php echo e($value->new_price); ?></span>
                            <?php if($value->old_price > 0): ?>
                            <del class="bilai-na-old-price">৳ <?php echo e($value->old_price); ?></del>
                            <?php endif; ?>
                        </div>
                        <?php if($discount > 0): ?>
                        <span class="bilai-na-discount"><?php echo e($discount); ?>% OFF</span>
                        <?php endif; ?>
                    </div>

                    
                    <div class="bilai-na-actions">
                        <?php if($hasVariants): ?>
                        <a href="<?php echo e(route('product', $value->slug)); ?>" class="bilai-na-cart-btn" title="Add to Cart">
                            <i class="fas fa-shopping-cart"></i>
                        </a>
                        <a href="<?php echo e(route('product', $value->slug)); ?>" class="bilai-na-buy-btn">Buy Now</a>
                        <?php else: ?>
                        <form action="<?php echo e(route('cart.store')); ?>" method="POST" class="bilai-na-cart-form">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="id" value="<?php echo e($value->id); ?>">
                            <input type="hidden" name="name" value="<?php echo e($value->name); ?>">
                            <input type="hidden" name="price" value="<?php echo e($value->new_price); ?>">
                            <input type="hidden" name="quantity" value="1">
                            <button type="submit" class="bilai-na-cart-btn" title="Add to Cart">
                                <i class="fas fa-shopping-cart"></i>
                            </button>
                        </form>
                        <a href="<?php echo e(route('product', $value->slug)); ?>" class="bilai-na-buy-btn">Buy Now</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    </div>
</section>
<?php endif; ?>


<section class="bilai-cat-situation-section">
    <div class="container">
        <div class="bilai-cat-situation-header">
            <h2 class="bilai-deal-title">How is Your Cat Doing?</h2>
            <p class="bilai-cat-situation-subtitle">Tell us your cat's situation, we will find you the right product.</p>
        </div>
        <div class="bilai-cat-situation-grid">
            <a href="#" class="bilai-situation-card">
                <div class="bilai-situation-icon">
                    <img src="<?php echo e(asset('public/frontEnd/images/catDoingIcon1.svg')); ?>" alt="New Kitten" width="56" height="56" loading="lazy">
                </div>
                <div class="bilai-situation-body">
                    <h3 class="bilai-situation-title">I Just Got New Kitten</h3>
                    <p class="bilai-situation-desc">Bringing home a new kitten? You'll get a starter kit, food, and litter box — all in one package.</p>
                    <span class="bilai-situation-btn">View Starter Kit &rarr;</span>
                </div>
            </a>
            <a href="#" class="bilai-situation-card">
                <div class="bilai-situation-icon">
                    <img src="<?php echo e(asset('public/frontEnd/images/catDoingIcon2.svg')); ?>" alt="Cat Not Eating" width="56" height="56" loading="lazy">
                </div>
                <div class="bilai-situation-body">
                    <h3 class="bilai-situation-title">My Cat is Not Eating</h3>
                    <p class="bilai-situation-desc">Is your cat not eating? Check out wet food and appetite-boosting treats.</p>
                    <span class="bilai-situation-btn">Find the Solution &rarr;</span>
                </div>
            </a>
            <a href="#" class="bilai-situation-card">
                <div class="bilai-situation-icon">
                    <img src="<?php echo e(asset('public/frontEnd/images/catDoingIcon3.svg')); ?>" alt="Bored Cat" width="56" height="56" loading="lazy">
                </div>
                <div class="bilai-situation-body">
                    <h3 class="bilai-situation-title">My Bilai is Getting Bored</h3>
                    <p class="bilai-situation-desc">Does you bilai stay alone and do nothing? Check out interactive toys and enrichment packs.</p>
                    <span class="bilai-situation-btn">See Toys &rarr;</span>
                </div>
            </a>
            <a href="#" class="bilai-situation-card">
                <div class="bilai-situation-icon">
                    <img src="<?php echo e(asset('public/frontEnd/images/catDoingIcon4.svg')); ?>" alt="Grooming" width="56" height="56" loading="lazy">
                </div>
                <div class="bilai-situation-body">
                    <h3 class="bilai-situation-title">I Want to Keep the House Clean</h3>
                    <p class="bilai-situation-desc">Grooming brushes, shampoo, everything you need to keep your bilai clean.</p>
                    <span class="bilai-situation-btn">See Grooming Kits &rarr;</span>
                </div>
            </a>
        </div>
    </div>
</section>


<?php if($homeproducts && $homeproducts->count() > 0): ?>
    <?php $__currentLoopData = $homeproducts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $homecat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <section class="homeproduct bilai-category-products">
            <div class="container">
                <div class="bilai-section-header">
                    <h2 class="bilai-deal-title"><?php echo e($homecat->name); ?></h2>
                    <a href="<?php echo e(route('category', $homecat->slug)); ?>" class="bilai-section-view-all">View More</a>
                </div>
                <div class="product_slider owl-carousel">
                    <?php $__currentLoopData = $homecat->products; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php
                        $averageRating = $value->reviews->avg('ratting');
                        $filledStars   = floor($averageRating);
                        $hasHalfStar   = $averageRating - $filledStars >= 0.5;
                        $emptyStars    = 5 - $filledStars - ($hasHalfStar ? 1 : 0);
                        $catDiscount   = ($value->old_price && $value->old_price > $value->new_price)
                                         ? round((($value->old_price - $value->new_price) * 100) / $value->old_price)
                                         : 0;
                    ?>
                    <div class="bilai-product-card">
                        <div class="bilai-product-top">
                            <?php if($catDiscount > 0): ?>
                            <span class="bilai-stock-badge"><?php echo e($catDiscount); ?>% OFF</span>
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
                                    <?php if($hasHalfStar): ?><i class="fas fa-star-half-alt"></i><?php endif; ?>
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
                                <?php if($catDiscount > 0): ?>
                                <span class="bilai-discount-badge"><?php echo e($catDiscount); ?>% OFF</span>
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
                    </div><!-- end .bilai-product-card -->
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            </div>
        </section>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
<?php endif; ?>


<section class="bilai-why-section">
    <div class="container">
        <div class="bilai-why-card" style="background-image: url('<?php echo e(asset('public/frontEnd/images/sliderContent.png')); ?>');">
            <div class="bilai-why-header">
                <h2 class="bilai-why-main-title">Why Choose <span class="bilai-why-highlight">Bilai Ghor ?</span></h2>
                <p class="bilai-why-subtitle">Because it's not just products, but trust to all the cat parents<br>and cat lovers in Bangladesh</p>
            </div>
            <div class="bilai-why-grid">
                <div class="bilai-why-item">
                    <div class="bilai-why-icon">
                        <img src="<?php echo e(asset('public/frontEnd/images/whyBilaighorIcon1.svg')); ?>" alt="Only for Cats" width="36" height="36" loading="lazy">
                    </div>
                    <div class="bilai-why-body">
                        <h3 class="bilai-why-item-title">Only for Cats</h3>
                        <p class="bilai-why-item-desc">We only sell cat products. We don't sell any other pet products. So our focus is on your cat.</p>
                    </div>
                </div>
                <div class="bilai-why-item">
                    <div class="bilai-why-icon">
                        <img src="<?php echo e(asset('public/frontEnd/images/whyBilaighorIcon2.svg')); ?>" alt="Trusted and genuine product" width="36" height="36" loading="lazy">
                    </div>
                    <div class="bilai-why-body">
                        <h3 class="bilai-why-item-title">Trusted and genuine product</h3>
                        <p class="bilai-why-item-desc">Every product is sourced directly from an authorized distributor. There is no risk of counterfeit or expired products.</p>
                    </div>
                </div>
                <div class="bilai-why-item">
                    <div class="bilai-why-icon">
                        <img src="<?php echo e(asset('public/frontEnd/images/whyBilaighorIcon3.svg')); ?>" alt="Help in Bengali" width="36" height="36" loading="lazy">
                    </div>
                    <div class="bilai-why-body">
                        <h3 class="bilai-why-item-title">Help in Bengali</h3>
                        <p class="bilai-why-item-desc">Our customer support team speaks completely in Bengali. No English or complications — solve your problems in simple Bengali.</p>
                    </div>
                </div>
                <div class="bilai-why-item">
                    <div class="bilai-why-icon">
                        <img src="<?php echo e(asset('public/frontEnd/images/whyBilaighorIcon4.svg')); ?>" alt="Safe and fast delivery" width="36" height="36" loading="lazy">
                    </div>
                    <div class="bilai-why-body">
                        <h3 class="bilai-why-item-title">Safe and fast delivery</h3>
                        <p class="bilai-why-item-desc">Products are carefully packaging. Delivery within 2–3 days in Dhaka, 3–5 days throughout Bangladesh.</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="bilai-why-photos">
            <img src="<?php echo e(asset('public/frontEnd/images/bilaiparaImg1.png')); ?>" alt="Cat parent with cats" loading="lazy">
            <img src="<?php echo e(asset('public/frontEnd/images/bilaiparaImg2.png')); ?>" alt="Cat parent with cats" loading="lazy">
        </div>
        <div class="bilai-why-cta">
            <a href="#" class="bilai-why-btn">Visit Our Bilai Para &rarr;</a>
        </div>
    </div>
</section>


<section>
    <div class="">
        <div class="row">
            <?php $__currentLoopData = $homepageads2; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $homeads2): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="col-md-12">
                <a href="<?php echo e($homeads2->link); ?>?sold=show">
                    <img class="img-fluid w-100"
                         src="<?php echo e(asset($homeads2->image)); ?>"
                         alt="Homepage Ads 2"
                         loading="lazy" />
                </a>
            </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    </div>
</section>






<!-- <?php if(isset($brands) && $brands->count() > 0): ?>
<section class="homeproduct brand-section">
    <div class="container">
        <div class="row">
            <div class="col-sm-12">
                <div class="sec_title">
                    <h3 class="section-title-header">
                        <span class="section-title-name">Brands</span>
                    </h3>
                </div>
            </div>

            <div class="col-sm-12">
                <div class="row brand-grid">

                    <?php $__currentLoopData = $brands; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $brand): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div class="col-lg-2 col-md-3 col-sm-4 col-6 mb-4">
                            <a href="<?php echo e(route('brand.products', $brand->slug)); ?>"
                               class="brand-item text-center">

                                <div class="brand-img">
                                    <img src="<?php echo e(asset($brand->image)); ?>"
                                         alt="<?php echo e($brand->name); ?>"
                                         class="img-fluid"
                                         loading="lazy">
                                </div>

                                <div class="brand-name">
                                    <?php echo e($brand->name); ?>

                                </div>

                            </a>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                </div>
            </div>
        </div>
    </div>
</section>
<?php endif; ?> -->




<?php if(isset($blogs) && $blogs->count() > 0): ?>
<section class="bilai-blog-section">
    <div class="container">
        <div class="bilai-blog-header">
            <h2 class="bilai-testimonial-title">What You Should Know As<br>A Cat Parent</h2>
            <a href="<?php echo e(route('blogs')); ?>" class="bilai-blog-more-btn">Read More Blogs</a>
        </div>
        <div class="bilai-blog-grid">
            <?php $__currentLoopData = $blogs->take(3); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $blog): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="bilai-blog-card">
                <a href="<?php echo e(route('blog.details', $blog->slug)); ?>" class="bilai-blog-img-wrap">
                    <img
                        src="<?php echo e($blog->image ? url('public/'.$blog->image) : url('public/no-image.png')); ?>"
                        alt="<?php echo e($blog->title); ?>"
                        loading="lazy"
                    >
                    <div class="bilai-blog-img-overlay">
                        <div class="bilai-blog-brand">
                            <img src="<?php echo e(asset(optional($generalsetting)->dark_logo ?? 'public/logo.png')); ?>" alt="<?php echo e(optional($generalsetting)->name ?? 'Bilai Ghor'); ?>">
                            <span><?php echo e(optional($generalsetting)->name ?? 'Bilai Ghor'); ?></span>
                        </div>
                        <div class="bilai-blog-img-actions">
                            <span><i class="fas fa-share-alt"></i></span>
                            <span><i class="far fa-comment"></i> <?php echo e($blog->views ?? 0); ?></span>
                        </div>
                    </div>
                </a>
                <div class="bilai-blog-body">
                    <div class="bilai-blog-meta">
                        <span class="bilai-blog-cat">Blog</span>
                        <span class="bilai-blog-date"><?php echo e($blog->created_at->format('jS F, Y')); ?></span>
                    </div>
                    <h5 class="bilai-blog-title">
                        <a href="<?php echo e(route('blog.details', $blog->slug)); ?>"><?php echo e(Str::limit($blog->title, 55)); ?></a>
                    </h5>
                    <p class="bilai-blog-desc"><?php echo e(Str::limit($blog->short_description, 100)); ?></p>
                    <a href="<?php echo e(route('blog.details', $blog->slug)); ?>" class="bilai-blog-read-btn">Continue Reading</a>
                </div>
            </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    </div>
</section>
<?php endif; ?>




<?php if(isset($testimonials) && $testimonials->count() > 0): ?>
<section class="bilai-testimonial-section">
    <div class="bilai-testimonial-bg" style="background-image: url('<?php echo e(asset('public/frontEnd/images/testiBg.png')); ?>');">
        <div class="container">
            <div class="bilai-testimonial-header">
                <h2 class="bilai-testimonial-title">What Bilai Parent Says <br> About Us</h2>
            </div>
            <div class="bilai-testimonial-grid">
                <?php $__currentLoopData = $testimonials; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $t): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="bilai-testimonial-card">
                    <div class="bilai-testimonial-avatar">
                        <?php if($t->image): ?>
                        <img src="<?php echo e(asset('public/'.$t->image)); ?>" alt="<?php echo e($t->name); ?>" loading="lazy">
                        <?php else: ?>
                        <div class="bilai-testimonial-avatar-placeholder">
                            <i class="fas fa-user"></i>
                        </div>
                        <?php endif; ?>
                    </div>
                    <h4 class="bilai-testimonial-name"><?php echo e($t->name); ?></h4>
                    <?php if($t->location): ?>
                    <p class="bilai-testimonial-location"><?php echo e($t->location); ?></p>
                    <?php endif; ?>
                    <div class="bilai-testimonial-stars">
                        <?php for($i = 1; $i <= 5; $i++): ?>
                            <?php if($i <= $t->rating): ?>
                            <i class="fas fa-star"></i>
                            <?php else: ?>
                            <i class="far fa-star"></i>
                            <?php endif; ?>
                        <?php endfor; ?>
                    </div>
                    <p class="bilai-testimonial-message">"<?php echo e($t->message); ?>"</p>
                </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
            
        </div>
    </div>
    
        <div class="container">
            <div class="bilai-testimonial-cta">
                    <div class="bilai-testimonial-cta-left">
                        <h3 class="bilai-testimonial-cta-title">আপনার বিলাইর ছবি শেয়ার করুন</h3>
                        <p class="bilai-testimonial-cta-desc">যে কোন প্রজাতি বিলাই নিয়ে তোলা আপনার ছবি আমাদের ওয়েবসাইটে আপনার বিলাইর ছবি বিখ্যাত করার সুযোগ। (গর্ব প্রচার)</p>
                    </div>
                    <a href="#" class="bilai-testimonial-cta-btn">বিলাইগর প্যারেন্ট &rarr;</a>
                
            </div>
        </div>
</section>
<?php endif; ?>









<style>
/* ===== CLEAR BRAND LOGO SECTION ===== */
.brand-section {
    background: #ffffff;
}

/* brand card */
.brand-section .brand-item {
    display: block;
    background: #ffffff;
    border-radius: 10px;
    padding: 20px 15px;
    text-decoration: none;
    border: 1px solid #eaeaea;
    transition: all 0.3s ease;
}

.brand-section .brand-item:hover {
    box-shadow: 0 10px 25px rgba(0,0,0,0.08);
    transform: translateY(-4px);
}

/* logo container */
.brand-section .brand-img {
    height: 95px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #ffffff; /* white bg for clarity */
}

/* LOGO IMAGE – FULL CLEAR */
.brand-section .brand-img img {
    max-height: 80px;
    max-width: 100%;
    object-fit: contain;

    /* IMPORTANT FOR CLEAR LOGO */
    filter: none !important;
    opacity: 1 !important;
    image-rendering: -webkit-optimize-contrast;
    image-rendering: crisp-edges;
}

/* brand name */
.brand-section .brand-name {
    margin-top: 10px;
    font-size: 14px;
    font-weight: 600;
    color: #000;
    text-align: center;
}

/* mobile */
@media (max-width: 576px) {
    .brand-section .brand-img {
        height: 75px;
    }
    .brand-section .brand-img img {
        max-height: 55px;
    }
}

</style>


















<style>
.blog-home-card {
    background: #fff;
    border-radius: 10px;
    overflow: hidden;
    border: 1px solid #eee;
    height: 100%;
    transition: all .3s ease;
}

.blog-home-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 12px 25px rgba(0,0,0,0.08);
}

.blog-home-img img {
    width: 100%;
    height: 220px;
    object-fit: cover;
}

.blog-home-content {
    padding: 16px;
}

.blog-home-meta {
    font-size: 13px;
    color: #777;
    margin-bottom: 6px;
}

.blog-home-title a {
    font-size: 17px;
    font-weight: 600;
    color: #222;
    text-decoration: none;
}

.blog-home-title a:hover {
    color: #0d6efd;
}

.read-more-link {
    display: inline-block;
    margin-top: 8px;
    font-size: 14px;
    font-weight: 600;
    color: #0d6efd;
    text-decoration: none;
}

.read-more-link:hover {
    text-decoration: underline;
}

/* ===== VENDOR SHOPS SECTION ===== */
.vendor-shops-section {
    background: #ffffff;
}

.vendor-shop-item {
    display: block;
    position: relative;
    background: #ffffff;
    border-radius: 10px;
    overflow: hidden;
    text-decoration: none;
    border: 1px solid #eaeaea;
    transition: all 0.3s ease;
    height: 100%;
}

.vendor-shop-item:hover {
    box-shadow: 0 10px 25px rgba(0,0,0,0.08);
    transform: translateY(-4px);
    text-decoration: none;
}

/* Background Banner */
.shop-banner-bg {
    position: relative;
    width: 100%;
    height: 100px;
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
}

/* Shop Content Wrapper */
.shop-content-wrapper {
    position: relative;
    padding: 15px;
    text-align: center;
    padding-top: 50px;
}

/* Logo Container */
.shop-logo-container {
    position: relative;
    margin-top: -50px;
    margin-bottom: 12px;
    display: flex;
    justify-content: center;
}

.shop-logo-circle {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: #ffffff;
    border: 4px solid #ffffff;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    position: relative;
}

.shop-logo-circle img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.shop-logo-initial {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 32px;
    font-weight: bold;
    color: #fff;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

/* Verified Badge */
.shop-verified-badge {
    position: absolute;
    bottom: 0;
    right: 0;
    width: 24px;
    height: 24px;
    background: #0d6efd;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 3px solid #ffffff;
    box-shadow: 0 2px 6px rgba(0,0,0,0.15);
}

.shop-verified-badge i {
    color: #ffffff;
    font-size: 12px;
}

/* Shop Details */
.shop-details {
    margin-bottom: 12px;
}

.shop-title {
    font-size: 15px;
    font-weight: 600;
    color: #222;
    margin: 0 0 4px 0;
    line-height: 1.3;
}

.shop-type {
    font-size: 11px;
    color: #666;
    margin: 0 0 8px 0;
}

.shop-rating-stars {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 2px;
    margin-bottom: 0;
}

.shop-rating-stars i {
    font-size: 11px;
    color: #ffc107;
}

.shop-rating-stars .far.fa-star {
    color: #ddd;
}

.shop-review-text {
    font-size: 10px;
    color: #777;
    margin-left: 4px;
}

/* Visit Store Button */
.shop-visit-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    width: 100%;
    padding: 8px 12px;
    background: #f0f0f0;
    border-radius: 20px;
    color: #333;
    font-size: 12px;
    font-weight: 600;
    transition: all 0.3s ease;
    margin-top: 8px;
}

.vendor-shop-item:hover .shop-visit-btn {
    background: #0d6efd;
    color: #ffffff;
}

.visit-btn-icon {
    width: 24px;
    height: 24px;
    background: #ffffff;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
}

.visit-btn-icon i {
    font-size: 10px;
    color: #333;
    transition: all 0.3s ease;
}

.vendor-shop-item:hover .visit-btn-icon {
    background: rgba(255,255,255,0.2);
}

.vendor-shop-item:hover .visit-btn-icon i {
    color: #ffffff;
}

/* Responsive */
@media (max-width: 768px) {
    .shop-banner-bg {
        height: 80px;
    }
    
    .shop-logo-circle {
        width: 70px;
        height: 70px;
    }
    
    .shop-content-wrapper {
        padding-top: 40px;
    }
    
    .shop-title {
        font-size: 14px;
    }
    
    .shop-type {
        font-size: 10px;
    }
}

@media (max-width: 576px) {
    .shop-banner-bg {
        height: 70px;
    }
    
    .shop-logo-circle {
        width: 60px;
        height: 60px;
    }
    
    .shop-content-wrapper {
        padding: 12px;
        padding-top: 35px;
    }
    
    .shop-logo-initial {
        font-size: 28px;
    }
}
</style>








<?php $__env->stopSection(); ?>


<?php $__env->startPush('script'); ?>
<script src="<?php echo e(asset('public/frontEnd/js/jquery.syotimer.min.js')); ?>"></script>
<script>
    $("#simple_timer").syotimer({
        date: new Date(2015, 0, 1),
        layout: "dhms",
        doubleNumbers: true,
        effectType: "opacity",
        periodUnit: "d",
        periodic: true,
        periodInterval: 7,
    });
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('frontEnd.layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\bilaiGhor\resources\views/frontEnd/layouts/pages/index.blade.php ENDPATH**/ ?>