
<?php $__env->startSection('title', 'Shopping Cart'); ?>

<?php
    use Illuminate\Support\Str;

    $cartItems = Cart::instance('shopping')->content();
    $cartCount = Cart::instance('shopping')->count();

    $subtotal = Cart::instance('shopping')->subtotal();
    $subtotal = (float) str_replace([',', '.00'], '', $subtotal);

    $shipping   = Session::get('shipping') ? Session::get('shipping') : 0;
    $discount   = Session::get('discount', 0);
    $grandTotal = ($subtotal + $shipping) - $discount;
?>

<?php $__env->startPush('css'); ?>
<style>
/* BilaiGhor Cart Page Start */
:root {
    --bcart-primary: var(--bilai-primary, #F28C00);
    --bcart-primary-dark: var(--bilai-primary-dark, #c96f00);
    --bcart-brown:   var(--bilai-brown,  #3A1F0F);
    --bcart-cream:   var(--bilai-cream,  #FFF8EC);
    --bcart-card:    #FFFDF8;
    --bcart-border:  var(--bilai-border, #E8CDA5);
    --bcart-text:    var(--bilai-text,   #2B1A10);
    --bcart-muted:   var(--bilai-muted,  #77706A);
    --bcart-radius:  var(--bilai-radius-md, 12px);
}

.bilai-cart-page { background: #faf7f0; min-height: 70vh; padding: 20px 0 56px; }

/* Breadcrumb */
.bilai-cart-bc { display: flex; align-items: center; gap: 6px; font-size: 12.5px; margin-bottom: 20px; flex-wrap: wrap; }
.bilai-cart-bc a { color: var(--bcart-muted); text-decoration: none; }
.bilai-cart-bc a:hover { color: var(--bcart-primary); }
.bilai-cart-bc-sep { color: #c0b0a0; font-size: 11px; }
.bilai-cart-bc-active { color: var(--bcart-primary); font-weight: 600; }

/* Layout */
.bilai-cart-layout { display: grid; grid-template-columns: 1fr 340px; gap: 22px; align-items: start; }

/* Items table */
.bilai-cart-table-card { background: #fff; border: 1px solid var(--bcart-border); border-radius: var(--bcart-radius); overflow: hidden; }
.bilai-cart-table-wrap { overflow-x: auto; }
.bilai-cart-table { width: 100%; border-collapse: collapse; min-width: 640px; }
.bilai-cart-table thead { background: var(--bcart-brown); }
.bilai-cart-table th {
    color: #fff; font-size: 13px; font-weight: 600;
    padding: 14px 18px; text-align: left; white-space: nowrap;
}
.bilai-cart-table th:last-child, .bilai-cart-table td:last-child { text-align: center; }
.bilai-cart-table td { padding: 16px 18px; border-bottom: 1px solid #f0e8d8; vertical-align: middle; font-size: 13.5px; color: var(--bcart-text); }
.bilai-cart-table tbody tr:last-child td { border-bottom: none; }

.bilai-cart-prod { display: flex; align-items: center; gap: 13px; }
.bilai-cart-prod-img {
    width: 56px; height: 56px; border-radius: 8px; object-fit: cover;
    border: 1px solid var(--bcart-border); background: var(--bcart-cream); flex-shrink: 0;
}
.bilai-cart-prod-name { font-size: 13.5px; font-weight: 600; color: var(--bcart-text); margin: 0 0 3px; line-height: 1.4; }
.bilai-cart-prod-name a { color: inherit; text-decoration: none; }
.bilai-cart-prod-name a:hover { color: var(--bcart-primary); }
.bilai-cart-prod-meta { font-size: 11.5px; color: var(--bcart-muted); margin: 0; }

.bilai-cart-price-new { font-weight: 600; white-space: nowrap; }
.bilai-cart-price-old { display: block; font-size: 12px; color: #b6ab9c; text-decoration: line-through; white-space: nowrap; }

/* Qty control — beige side buttons, white center (Figma) */
.bilai-cart-qty { display: inline-flex; align-items: stretch; border-radius: 10px; overflow: hidden; background: #fff; }
.bilai-cart-qty button {
    width: 42px; height: 44px; border: none; background: #efe5d2;
    color: var(--bcart-text); font-weight: 700; cursor: pointer;
    display: flex; align-items: center; justify-content: center; transition: 0.15s;
}
.bilai-cart-qty button:hover { background: var(--bcart-primary); color: #fff; }
.bilai-cart-qty .bilai-cart-qty-val {
    width: 48px; display: flex; align-items: center; justify-content: center;
    background: #fff; font-size: 15px; font-weight: 600; color: var(--bcart-text);
}

.bilai-cart-line-total { font-weight: 700; white-space: nowrap; }

.bilai-cart-remove {
    background: transparent; border: none; color: var(--bcart-text);
    font-size: 15px; cursor: pointer; padding: 6px; transition: 0.15s;
}
.bilai-cart-remove:hover { color: #c0392b; }

/* Empty state */
.bilai-cart-empty { background: #fff; border: 1px solid var(--bcart-border); border-radius: var(--bcart-radius); text-align: center; padding: 48px 20px; }
.bilai-cart-empty-icon {
    width: 60px; height: 60px; border-radius: 50%; background: var(--bcart-cream);
    border: 1px solid var(--bcart-border); display: inline-flex; align-items: center;
    justify-content: center; font-size: 24px; color: var(--bcart-muted); margin-bottom: 12px;
}
.bilai-cart-empty h4 { font-size: 16px; font-weight: 700; color: var(--bcart-text); margin: 0 0 6px; }
.bilai-cart-empty p { font-size: 13px; color: var(--bcart-muted); margin: 0 0 18px; }

/* Summary card — deeper cream bg, wider spacing, dashed group separators (Figma) */
.bilai-cart-summary { background: #fdf3e3; border: none; border-radius: 16px; overflow: hidden; position: sticky; top: 90px; }
.bilai-cart-summary-head { background: var(--bcart-brown); color: #fff; font-size: 16px; font-weight: 700; padding: 18px 24px; }
.bilai-cart-summary-body { padding: 24px; }
.bilai-cart-sum-row { display: flex; justify-content: space-between; font-size: 15px; margin-bottom: 16px; color: var(--bcart-text); }
.bilai-cart-sum-row:last-of-type { margin-bottom: 0; }
.bilai-cart-sum-row span:first-child { color: #5d4f43; }
.bilai-cart-sum-sep { border: none; border-top: 1.5px dashed #d8c9b2; margin: 2px 0 18px; }
.bilai-cart-sum-row.total { font-size: 20px; font-weight: 800; }
.bilai-cart-sum-row.total span:first-child { color: var(--bcart-text); font-weight: 800; }
.bilai-cart-sum-row.total span:last-child { color: var(--bcart-primary); }

.bilai-cart-checkout-btn {
    display: flex; align-items: center; justify-content: center; gap: 8px;
    width: 100%; margin-top: 22px; padding: 15px;
    background: var(--bcart-primary); color: #fff !important;
    border: none; border-radius: 12px; font-size: 15.5px; font-weight: 700;
    text-decoration: none; cursor: pointer; transition: 0.2s;
}
.bilai-cart-checkout-btn:hover { background: var(--bcart-primary-dark); text-decoration: none; }
.bilai-cart-checkout-btn.disabled { background: #dccdb4; pointer-events: none; }

.bilai-cart-continue-btn {
    display: inline-flex; align-items: center; gap: 8px; padding: 11px 24px;
    background: var(--bcart-primary); color: #fff !important; border-radius: 100px;
    font-size: 13.5px; font-weight: 600; text-decoration: none; transition: 0.2s;
}
.bilai-cart-continue-btn:hover { background: var(--bcart-primary-dark); text-decoration: none; }

/* Recommended products */
.bilai-cart-rec { margin-top: 44px; }
.bilai-cart-rec-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 20px; flex-wrap: wrap; }
.bilai-cart-rec-title { font-size: 24px; font-weight: 800; color: var(--bcart-brown); margin: 0; }
.bilai-cart-rec-alldeals {
    padding: 8px 18px; background: #fff; border: 1px solid var(--bcart-border);
    border-radius: 8px; font-size: 13px; font-weight: 600; color: var(--bcart-text);
    text-decoration: none; transition: 0.15s;
}
.bilai-cart-rec-alldeals:hover { border-color: var(--bcart-primary); color: var(--bcart-primary); text-decoration: none; }
.bilai-cart-rec-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 18px; }

/* Responsive */
@media (max-width: 1199px) { .bilai-cart-rec-grid { grid-template-columns: repeat(3, 1fr); } }
@media (max-width: 991px) {
    .bilai-cart-layout { grid-template-columns: 1fr; }
    .bilai-cart-summary { position: static; }
}
@media (max-width: 767px) { .bilai-cart-rec-grid { grid-template-columns: repeat(2, 1fr); gap: 12px; } }
/* BilaiGhor Cart Page End */
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<div class="bilai-cart-page">
    <div class="container">

        
        <nav class="bilai-cart-bc" aria-label="breadcrumb">
            <a href="<?php echo e(route('home')); ?>">Home</a>
            <span class="bilai-cart-bc-sep">›</span>
            <span class="bilai-cart-bc-active">Cart</span>
        </nav>

        <div class="bilai-cart-layout">

            
            <div>
                <?php if($cartCount > 0): ?>
                <div class="bilai-cart-table-card">
                    <div class="bilai-cart-table-wrap">
                        <table class="bilai-cart-table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Price</th>
                                    <th>Quantity</th>
                                    <th>Total</th>
                                    <th>Delete</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $__currentLoopData = $cartItems; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <?php
                                        $__p   = \App\Models\Product::find($value->id);
                                        $__old = ($__p && $__p->old_price && $__p->old_price > $value->price) ? $__p->old_price : null;
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="bilai-cart-prod">
                                                <a href="<?php echo e(route('product', $value->options->slug)); ?>">
                                                    <img src="<?php echo e(asset($value->options->image)); ?>" class="bilai-cart-prod-img" alt="<?php echo e($value->name); ?>"
                                                         onerror="this.src='<?php echo e(asset('public/uploads/default/no-image.png')); ?>'">
                                                </a>
                                                <div>
                                                    <p class="bilai-cart-prod-name">
                                                        <a href="<?php echo e(route('product', $value->options->slug)); ?>"><?php echo e(Str::limit($value->name, 45)); ?></a>
                                                    </p>
                                                    <?php if($value->options->product_size || $value->options->product_color): ?>
                                                        <p class="bilai-cart-prod-meta">
                                                            <?php if($value->options->product_size): ?> Weight: <?php echo e($value->options->product_size); ?> <?php endif; ?>
                                                            <?php if($value->options->product_color): ?> | Color: <?php echo e($value->options->product_color); ?> <?php endif; ?>
                                                        </p>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="bilai-cart-price-new">৳<?php echo e(number_format($value->price, 0)); ?></span>
                                            <?php if($__old): ?><span class="bilai-cart-price-old">৳<?php echo e(number_format($__old, 0)); ?></span><?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="bilai-cart-qty" data-rowid="<?php echo e($value->rowId); ?>">
                                                <button type="button" class="bilai-cart-minus" aria-label="Decrease quantity">
                                                    
                                                    <i class="fa fa-minus" style="font-size:10px;"></i>
                                                </button>
                                                <span class="bilai-cart-qty-val"><?php echo e($value->qty); ?></span>
                                                <button type="button" class="bilai-cart-plus" aria-label="Increase quantity">
                                                    
                                                    <i class="fa fa-plus" style="font-size:10px;"></i>
                                                </button>
                                            </div>
                                        </td>
                                        <td><span class="bilai-cart-line-total">৳<?php echo e(number_format($value->price * $value->qty, 0)); ?></span></td>
                                        <td>
                                            <button type="button" class="bilai-cart-remove" data-id="<?php echo e($value->rowId); ?>" title="Remove item" aria-label="Remove item">
                                                
                                                <i class="fa fa-trash-o"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php else: ?>
                
                <div class="bilai-cart-empty">
                    <div class="bilai-cart-empty-icon">
                        
                        <i class="fa fa-shopping-basket"></i>
                    </div>
                    <h4>Your cart is empty</h4>
                    <p>Looks like you haven't added anything to your cart yet.</p>
                    <a href="<?php echo e(route('shop')); ?>" class="bilai-cart-continue-btn">
                        Continue Shopping <i class="fa fa-arrow-right"></i>
                    </a>
                </div>
                <?php endif; ?>
            </div>

            
            <aside>
                <div class="bilai-cart-summary">
                    <div class="bilai-cart-summary-head">Order Summary</div>
                    <div class="bilai-cart-summary-body">
                        <div class="bilai-cart-sum-row"><span>Subtotal</span><span>৳<?php echo e(number_format($subtotal, 0)); ?></span></div>
                        <div class="bilai-cart-sum-row"><span>Delivery Charge</span><span>৳<?php echo e(number_format($shipping, 0)); ?></span></div>
                        <hr class="bilai-cart-sum-sep">
                        <div class="bilai-cart-sum-row"><span>Discount</span><span>-৳<?php echo e(number_format($discount, 0)); ?></span></div>
                        
                        <div class="bilai-cart-sum-row"><span>Cash from Reward Points</span><span>-৳00</span></div>
                        <hr class="bilai-cart-sum-sep">
                        <div class="bilai-cart-sum-row total"><span>Total</span><span>৳<?php echo e(number_format($grandTotal, 0)); ?></span></div>

                        <a href="<?php echo e($cartCount > 0 ? route('customer.checkout') : 'javascript:void(0)'); ?>"
                           class="bilai-cart-checkout-btn <?php echo e($cartCount > 0 ? '' : 'disabled'); ?>"
                           <?php if($cartCount == 0): ?> aria-disabled="true" <?php endif; ?>>
                            Proceed to Checkout
                        </a>
                    </div>
                </div>
            </aside>
        </div>

        
        <?php if(isset($recommendedProducts) && $recommendedProducts->count() > 0): ?>
        <div class="bilai-cart-rec">
            <div class="bilai-cart-rec-head">
                <h2 class="bilai-cart-rec-title">Recommended Products</h2>
                <a href="<?php echo e(route('hotdeals')); ?>" class="bilai-cart-rec-alldeals">View All Deals</a>
            </div>
            <div class="bilai-cart-rec-grid">
                <?php $__currentLoopData = $recommendedProducts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php
                    $avgRating   = $value->reviews->avg('ratting');
                    $filledStars = floor($avgRating);
                    $hasHalf     = $avgRating - $filledStars >= 0.5;
                    $emptyStars  = 5 - $filledStars - ($hasHalf ? 1 : 0);
                    $discountPct = ($value->old_price && $value->old_price > $value->new_price)
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
                        <button class="bilai-wishlist-btn" type="button" aria-label="Add to wishlist">
                            <i class="far fa-heart"></i>
                        </button>
                    </div>
                    <div class="bilai-product-image">
                        <a href="<?php echo e(route('product', $value->slug)); ?>">
                            <img src="<?php echo e(asset($value->image ? $value->image->image : '')); ?>"
                                 alt="<?php echo e($value->name); ?>" loading="lazy" />
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
                            <?php if($discountPct > 0): ?>
                            <span class="bilai-discount-badge"><?php echo e($discountPct); ?>% OFF</span>
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
        <?php endif; ?>

    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('script'); ?>
<script>
$(function () {
    // Quantity increment (existing cart route; reload keeps totals + header badge in sync)
    $(document).on('click', '.bilai-cart-plus', function () {
        var rowId = $(this).closest('.bilai-cart-qty').data('rowid');
        $("#loading").show();
        $.get("<?php echo e(route('cart.increment')); ?>", { id: rowId }, function () { window.location.reload(); });
    });

    // Quantity decrement
    $(document).on('click', '.bilai-cart-minus', function () {
        var rowId = $(this).closest('.bilai-cart-qty').data('rowid');
        $("#loading").show();
        $.get("<?php echo e(route('cart.decrement')); ?>", { id: rowId }, function () { window.location.reload(); });
    });

    // Remove item
    $(document).on('click', '.bilai-cart-remove', function () {
        var id = $(this).data('id');
        if (!id) return;
        $("#loading").show();
        $.ajax({
            type: "GET",
            url: "<?php echo e(route('cart.remove')); ?>",
            data: { id: id },
            success: function () { window.location.reload(); },
            error: function () { window.location.reload(); }
        });
    });
});
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('frontEnd.layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\projects\bilaiGhor\resources\views/frontEnd/layouts/pages/cart.blade.php ENDPATH**/ ?>