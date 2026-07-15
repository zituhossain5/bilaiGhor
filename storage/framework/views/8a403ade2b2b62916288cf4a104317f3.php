
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
<?php /**PATH D:\projects\bilaiGhor\resources\views/frontEnd/layouts/partials/product-card.blade.php ENDPATH**/ ?>