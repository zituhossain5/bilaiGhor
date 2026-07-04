<?php
    $subtotal = Cart::instance('shopping')->subtotal();
    $subtotal = floatval(preg_replace('/[^\d.]/', '', $subtotal));
    $primaryColor = optional($generalsetting)->primary_color ?? '#007bff';
?>

<div class="sidebar-cart-header">
    <button type="button" class="sidebar-cart-close" onclick="closeSidebarCart()" aria-label="বন্ধ করুন">
        <i class="fa-solid fa-times"></i>
    </button>
    <h3 class="sidebar-cart-title">আপনার কার্ট</h3>
</div>

<div class="sidebar-cart-body">
    <?php if(Cart::instance('shopping')->count() > 0): ?>
        <?php $__currentLoopData = Cart::instance('shopping')->content(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div class="sidebar-cart-item">
            <div class="sidebar-cart-item-img">
                <a href="<?php echo e(route('product', $value->options->slug ?? '#')); ?>">
                    <img src="<?php echo e(asset($value->options->image ?? 'public/uploads/default.webp')); ?>" alt="<?php echo e($value->name); ?>">
                </a>
            </div>
            <div class="sidebar-cart-item-details">
                <a href="<?php echo e(route('product', $value->options->slug ?? '#')); ?>" class="sidebar-cart-item-title">
                    <?php echo e(Str::limit($value->name, 45)); ?>

                </a>
                <?php if(!empty($value->options->product_size) || !empty($value->options->product_color)): ?>
                    <p class="sidebar-cart-item-variant" style="font-size: 11px; color: #666; margin: 2px 0;">
                        <?php if(!empty($value->options->product_size)): ?> Size: <?php echo e($value->options->product_size); ?> <?php endif; ?>
                        <?php if(!empty($value->options->product_color)): ?> | Color: <?php echo e($value->options->product_color); ?> <?php endif; ?>
                    </p>
                <?php endif; ?>
                <p class="sidebar-cart-item-price">৳ <?php echo e($value->price); ?></p>
                <?php if(!empty($value->options->old_price) && $value->options->old_price > $value->price): ?>
                    <?php $savings = $value->options->old_price - $value->price; ?>
                    <p class="sidebar-cart-item-savings">৳ <?php echo e(number_format($savings, 0)); ?> ছাড়</p>
                <?php endif; ?>
                <div class="sidebar-cart-qty">
                    <button type="button" class="sidebar-qty-btn cart_decrement" data-id="<?php echo e($value->rowId); ?>">−</button>
                    <span class="sidebar-qty-num"><?php echo e($value->qty); ?></span>
                    <button type="button" class="sidebar-qty-btn cart_increment" data-id="<?php echo e($value->rowId); ?>">+</button>
                </div>
                <button type="button" class="sidebar-cart-item-remove cart_remove" data-id="<?php echo e($value->rowId); ?>" title="রিমুভ করুন">
                    <i class="fa-solid fa-trash-can"></i>
                </button>
            </div>
        </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    <?php else: ?>
        <div class="sidebar-cart-empty">
            <i class="fa-solid fa-cart-shopping"></i>
            <p>আপনার কার্ট খালি</p>
            <a href="<?php echo e(route('shop')); ?>" class="btn btn-sm btn-outline-primary mt-2">শপিং করুন</a>
        </div>
    <?php endif; ?>
</div>

<?php if(Cart::instance('shopping')->count() > 0): ?>
<div class="sidebar-cart-footer">
    <div class="sidebar-cart-total">
        <span class="sidebar-cart-total-label">সর্বমোট</span>
        <span class="sidebar-cart-total-amount">৳ <?php echo e(number_format($subtotal, 0)); ?></span>
    </div>
    <a href="<?php echo e(route('customer.checkout')); ?>" class="sidebar-cart-checkout-btn">অর্ডার করুন</a>
</div>
<?php endif; ?>
<?php /**PATH D:\projects\bilaiGhor\resources\views/frontEnd/layouts/ajax/sidebar-cart.blade.php ENDPATH**/ ?>