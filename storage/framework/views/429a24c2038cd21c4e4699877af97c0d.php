<?php
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Models\Order;

$customer            = Auth::guard('customer')->user();
$customerId          = $customer->id;
$pendingOrdersCount  = Order::where('customer_id', $customerId)->whereNotIn('order_status', ['6', '11'])->count();
$profileImage        = $customer->image ? asset($customer->image) : null;
$customerInitial     = strtoupper(substr($customer->name ?? 'U', 0, 1));
?>


<?php $__env->startSection('title', 'My Wishlist | ' . ($customer->name ?? 'Account')); ?>

<?php $__env->startPush('css'); ?>
<style>
/* BilaiGhor Wishlist Page Start */
:root {
    --bilai-wl-primary:      var(--bilai-primary, #F28C00);
    --bilai-wl-primary-dark: var(--bilai-primary-dark, #c96f00);
    --bilai-wl-brown:        var(--bilai-brown, #3A1F0F);
    --bilai-wl-head:         #241307;
    --bilai-wl-card:         #FFFDF8;
    --bilai-wl-cream:        var(--bilai-cream, #FFF8EC);
    --bilai-wl-border:       var(--bilai-border, #E8CDA5);
    --bilai-wl-text:         var(--bilai-text, #2B1A10);
    --bilai-wl-muted:        var(--bilai-muted, #77706A);
    --bilai-wl-radius:       14px;
}

.bilai-wl-page { background: #f5f5f0; min-height: 72vh; padding: 20px 0 52px; }

/* Breadcrumb */
.bilai-wl-bc { display: flex; align-items: center; gap: 5px; font-size: 12.5px; margin-bottom: 18px; flex-wrap: wrap; }
.bilai-wl-bc a { color: var(--bilai-wl-muted); text-decoration: none; }
.bilai-wl-bc a:hover { color: var(--bilai-wl-primary); }
.bilai-wl-bc-sep    { color: #c0b0a0; font-size: 11px; }
.bilai-wl-bc-active { color: var(--bilai-wl-primary); font-weight: 600; }

/* Layout */
.bilai-wl-layout { display: grid; grid-template-columns: 248px 1fr; gap: 20px; align-items: start; }

/* ── Shared sidebar (same look as other account pages) ── */
.bilai-wl-sidebar { position: sticky; top: 90px; display: flex; flex-direction: column; gap: 14px; }
.bilai-card { background: #fff; border: 1px solid var(--bilai-wl-border); border-radius: var(--bilai-wl-radius); overflow: hidden; }
.bilai-dash-profile-box { padding: 18px 16px 16px; }
.bilai-dash-profile-row { display: flex; align-items: center; gap: 12px; }
.bilai-dash-avatar { width: 56px; height: 56px; border-radius: 50%; object-fit: cover; border: 2px solid var(--bilai-wl-border); flex-shrink: 0; }
.bilai-dash-avatar-placeholder { width: 56px; height: 56px; border-radius: 50%; background: var(--bilai-wl-primary); color: #fff; font-size: 22px; font-weight: 700; display: flex; align-items: center; justify-content: center; flex-shrink: 0; text-transform: uppercase; }
.bilai-dash-profile-info { flex: 1; min-width: 0; }
.bilai-dash-profile-name { font-size: 14px; font-weight: 700; color: var(--bilai-wl-text); margin: 0 0 2px; line-height: 1.3; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; text-transform: capitalize; }
.bilai-dash-profile-sub { font-size: 12px; color: var(--bilai-wl-muted); margin: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.bilai-dash-nav { padding: 6px 0 8px; }
.bilai-dash-nav-title { font-size: 11px; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; color: var(--bilai-wl-muted); padding: 12px 16px 8px; margin: 0; }
.bilai-dash-nav-item { display: flex; align-items: center; gap: 10px; padding: 9px 16px; color: var(--bilai-wl-text); font-size: 13.5px; font-weight: 500; text-decoration: none; border-left: 3px solid transparent; transition: background 0.12s, color 0.12s; line-height: 1.3; }
.bilai-dash-nav-item:hover { background: var(--bilai-wl-cream); color: var(--bilai-wl-primary); text-decoration: none; }
.bilai-dash-nav-item.active { background: var(--bilai-wl-cream); color: var(--bilai-wl-primary); border-left-color: var(--bilai-wl-primary); font-weight: 600; }
.bilai-dash-nav-icon { width: 16px; text-align: center; flex-shrink: 0; font-size: 13px; opacity: 0.7; }
.bilai-dash-nav-item.active .bilai-dash-nav-icon, .bilai-dash-nav-item:hover .bilai-dash-nav-icon { opacity: 1; }
.bilai-dash-nav-badge { margin-left: auto; background: #e53935; color: #fff; font-size: 10px; font-weight: 700; padding: 1px 6px; border-radius: 100px; line-height: 1.5; }
.bilai-dash-nav-sep { border: none; border-top: 1px solid var(--bilai-wl-border); margin: 4px 0; }
.bilai-dash-nav-item--logout { color: #c0392b; }
.bilai-dash-nav-item--logout .bilai-dash-nav-icon { opacity: 0.8; }
.bilai-dash-nav-item--logout:hover { background: #fff5f5; color: #a93226; }

/* ── Wishlist table ── */
.bilai-wl-title { font-size: 19px; font-weight: 800; color: var(--bilai-wl-text); margin: 0 0 16px; }
.bilai-wl-tablewrap { background: var(--bilai-wl-card); border: 1px solid var(--bilai-wl-border); border-radius: var(--bilai-wl-radius); overflow: hidden; }
.bilai-wl-scroll { overflow-x: auto; }
.bilai-wl-table { width: 100%; border-collapse: collapse; min-width: 640px; }
.bilai-wl-table thead th {
    background: var(--bilai-wl-head); color: #fff;
    font-size: 13px; font-weight: 600; text-align: left;
    padding: 14px 18px; white-space: nowrap;
}
.bilai-wl-table tbody td { padding: 16px 18px; border-bottom: 1px solid var(--bilai-wl-border); vertical-align: middle; }
.bilai-wl-table tbody tr:last-child td { border-bottom: none; }

.bilai-wl-product { display: flex; align-items: center; gap: 13px; min-width: 260px; }
.bilai-wl-img { width: 52px; height: 52px; padding: 5px; border-radius: 8px; border: 1px solid var(--bilai-wl-border); object-fit: contain; background: #fff; flex-shrink: 0; }
.bilai-wl-name { font-size: 13.5px; font-weight: 600; color: var(--bilai-wl-text); margin: 0 0 3px; line-height: 1.45; }
.bilai-wl-name a { color: inherit; text-decoration: none; }
.bilai-wl-name a:hover { color: var(--bilai-wl-primary); }
.bilai-wl-sub { font-size: 12px; color: var(--bilai-wl-muted); margin: 0; }

.bilai-wl-price-new { font-size: 14px; font-weight: 700; color: var(--bilai-wl-text); }
.bilai-wl-price-old { display: block; font-size: 12px; color: var(--bilai-wl-muted); text-decoration: line-through; }

.bilai-wl-badge { display: inline-block; font-size: 11.5px; font-weight: 600; padding: 5px 12px; border-radius: 100px; white-space: nowrap; }
.bilai-wl-badge--in  { background: #e8f5e9; color: #2e7d32; }
.bilai-wl-badge--out { background: #fdecea; color: #c0392b; }

.bilai-wl-actions { display: flex; align-items: center; gap: 10px; justify-content: flex-end; }
.bilai-wl-cart-btn {
    display: inline-flex; align-items: center; gap: 7px;
    padding: 9px 18px; font-size: 12.5px; font-weight: 700;
    color: var(--bilai-wl-primary); background: transparent;
    border: 1.5px solid var(--bilai-wl-primary); border-radius: 8px;
    cursor: pointer; text-decoration: none; transition: 0.15s; white-space: nowrap;
}
.bilai-wl-cart-btn:hover { background: var(--bilai-wl-primary); color: #fff; text-decoration: none; }
.bilai-wl-cart-btn[disabled] { opacity: 0.45; cursor: not-allowed; pointer-events: none; }
.bilai-wl-remove {
    width: 34px; height: 34px; border-radius: 8px; flex-shrink: 0;
    background: transparent; border: 1px solid var(--bilai-wl-border);
    color: var(--bilai-wl-muted); font-size: 13px; cursor: pointer; transition: 0.15s;
}
.bilai-wl-remove:hover { border-color: #c0392b; color: #c0392b; background: #fff5f5; }

/* Empty state */
.bilai-wl-empty { background: var(--bilai-wl-card); border: 1px solid var(--bilai-wl-border); border-radius: var(--bilai-wl-radius); text-align: center; padding: 48px 20px; }
.bilai-wl-empty-ic { width: 56px; height: 56px; border-radius: 50%; background: var(--bilai-wl-cream); border: 1px solid var(--bilai-wl-border); display: inline-flex; align-items: center; justify-content: center; font-size: 22px; color: var(--bilai-wl-muted); margin-bottom: 12px; }
.bilai-wl-empty h4 { font-size: 15px; font-weight: 700; color: var(--bilai-wl-text); margin: 0 0 5px; }
.bilai-wl-empty p { font-size: 13px; color: var(--bilai-wl-muted); margin: 0 0 18px; }
.bilai-wl-browse-btn {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 12px 26px; background: var(--bilai-wl-primary); color: #fff;
    border-radius: 10px; font-size: 13.5px; font-weight: 700; text-decoration: none; transition: 0.2s;
}
.bilai-wl-browse-btn:hover { background: var(--bilai-wl-primary-dark); color: #fff; text-decoration: none; }

/* Recommended products */
.bilai-wl-rec { margin-top: 34px; }
.bilai-wl-rec-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 16px; }
.bilai-wl-rec-title { font-size: 21px; font-weight: 800; color: var(--bilai-wl-text); margin: 0; }
.bilai-wl-rec-alldeals { font-size: 13px; font-weight: 600; color: var(--bilai-wl-text); background: var(--bilai-wl-cream); border: 1px solid var(--bilai-wl-border); padding: 9px 16px; border-radius: 8px; text-decoration: none; transition: 0.15s; }
.bilai-wl-rec-alldeals:hover { color: var(--bilai-wl-primary); border-color: var(--bilai-wl-primary); text-decoration: none; }
.bilai-wl-rec-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 18px; }

/* Responsive */
@media (max-width: 1199px) { .bilai-wl-layout { grid-template-columns: 228px 1fr; gap: 16px; } .bilai-wl-rec-grid { grid-template-columns: repeat(3, 1fr); } }
@media (max-width: 991px)  { .bilai-wl-layout { grid-template-columns: 1fr; } .bilai-wl-sidebar { position: static; } .bilai-wl-rec-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 575px)  { .bilai-wl-rec-grid { grid-template-columns: 1fr; } }
/* BilaiGhor Wishlist Page End */
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<div class="bilai-wl-page">
    <div class="container">

        
        <nav class="bilai-wl-bc" aria-label="breadcrumb">
            <a href="<?php echo e(route('home')); ?>">Home</a>
            <span class="bilai-wl-bc-sep">›</span>
            <a href="<?php echo e(route('customer.account')); ?>">My Account</a>
            <span class="bilai-wl-bc-sep">›</span>
            <span class="bilai-wl-bc-active">Wishlist</span>
        </nav>

        <div class="bilai-wl-layout">

            
            <aside class="bilai-wl-sidebar">
                <div class="bilai-card">
                    <div class="bilai-dash-profile-box">
                        <div class="bilai-dash-profile-row">
                            <?php if($profileImage): ?>
                                <img src="<?php echo e($profileImage); ?>" onerror="this.style.display='none';this.nextElementSibling.style.display='flex';" class="bilai-dash-avatar" alt="<?php echo e($customer->name); ?>">
                                <div class="bilai-dash-avatar-placeholder" style="display:none;"><?php echo e($customerInitial); ?></div>
                            <?php else: ?>
                                <div class="bilai-dash-avatar-placeholder"><?php echo e($customerInitial); ?></div>
                            <?php endif; ?>
                            <div class="bilai-dash-profile-info">
                                <p class="bilai-dash-profile-name"><?php echo e($customer->name ?? 'Customer'); ?></p>
                                <p class="bilai-dash-profile-sub"><?php echo e($customer->phone ?? $customer->email ?? ''); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bilai-card">
                    <nav class="bilai-dash-nav">
                        <p class="bilai-dash-nav-title">My Account</p>
                        <a href="<?php echo e(route('customer.account')); ?>" class="bilai-dash-nav-item">
                            
                            <span class="bilai-dash-nav-icon"><i class="fa fa-home"></i></span> Dashboard
                        </a>
                        <a href="<?php echo e(route('customer.orders')); ?>" class="bilai-dash-nav-item">
                            
                            <span class="bilai-dash-nav-icon"><i class="fa fa-shopping-bag"></i></span> Orders
                            <?php if($pendingOrdersCount > 0): ?><span class="bilai-dash-nav-badge"><?php echo e($pendingOrdersCount); ?></span><?php endif; ?>
                        </a>
                        <a href="<?php echo e(route('customer.profile_edit')); ?>" class="bilai-dash-nav-item">
                            
                            <span class="bilai-dash-nav-icon"><i class="fa fa-user"></i></span> Profile
                        </a>
                        <a href="<?php echo e(route('customer.wishlist')); ?>" class="bilai-dash-nav-item active">
                            
                            <span class="bilai-dash-nav-icon"><i class="fa fa-heart-o"></i></span> Wishlist
                        </a>
                        <a href="<?php echo e(route('customer.addresses')); ?>" class="bilai-dash-nav-item">
                            
                            <span class="bilai-dash-nav-icon"><i class="fa fa-map-marker"></i></span> Addresses
                        </a>
                        <a href="#" class="bilai-dash-nav-item">
                            
                            <span class="bilai-dash-nav-icon"><i class="fa fa-ticket"></i></span> Coupon
                        </a>
                        <a href="<?php echo e(route('customer.rewards')); ?>" class="bilai-dash-nav-item">
                            
                            <span class="bilai-dash-nav-icon"><i class="fa fa-star-o"></i></span> Reward Points
                        </a>
                        <a href="<?php echo e(route('customer.order_track')); ?>" class="bilai-dash-nav-item">
                            
                            <span class="bilai-dash-nav-icon"><i class="fa fa-truck"></i></span> Track Order
                        </a>
                        <a href="<?php echo e(route('customer.refunds')); ?>" class="bilai-dash-nav-item">
                            
                            <span class="bilai-dash-nav-icon"><i class="fa fa-undo"></i></span> Return Request
                        </a>
                        <a href="<?php echo e(route('complaint')); ?>" class="bilai-dash-nav-item">
                            
                            <span class="bilai-dash-nav-icon"><i class="fa fa-headphones"></i></span> Support Ticket
                        </a>
                        <hr class="bilai-dash-nav-sep">
                        <a href="<?php echo e(route('customer.logout')); ?>"
                           onclick="event.preventDefault(); document.getElementById('bilai-wl-logout-form').submit();"
                           class="bilai-dash-nav-item bilai-dash-nav-item--logout">
                            
                            <span class="bilai-dash-nav-icon"><i class="fa fa-sign-out"></i></span> Logout
                        </a>
                        <form id="bilai-wl-logout-form" action="<?php echo e(route('customer.logout')); ?>" method="POST" style="display:none;"><?php echo csrf_field(); ?></form>
                    </nav>
                </div>
            </aside>

            
            <main>
                <h2 class="bilai-wl-title">My Wishlist</h2>

                <?php if($wishlistItems->count() > 0): ?>
                    <div class="bilai-wl-tablewrap">
                        <div class="bilai-wl-scroll">
                            <table class="bilai-wl-table">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Price</th>
                                        <th>Stock Status</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $__currentLoopData = $wishlistItems; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <?php
                                            $p           = $item->product;
                                            $inStock     = (int) ($p->stock ?? 0) > 0;
                                            $hasVariants = $p->prosizes->isNotEmpty() || $p->procolors->isNotEmpty();
                                        ?>
                                        <tr data-wishlist-row="<?php echo e($p->id); ?>">
                                            <td>
                                                <div class="bilai-wl-product">
                                                    <img class="bilai-wl-img" src="<?php echo e(asset($p->image ? $p->image->image : '')); ?>" alt="<?php echo e($p->name); ?>">
                                                    <div>
                                                        <p class="bilai-wl-name"><a href="<?php echo e(route('product', $p->slug)); ?>"><?php echo e(Str::limit($p->name, 65)); ?></a></p>
                                                        <?php if($p->subcategory): ?>
                                                            <p class="bilai-wl-sub"><?php echo e($p->subcategory->subcategoryName); ?></p>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="bilai-wl-price-new">&#2547;<?php echo e($p->new_price); ?></span>
                                                <?php if($p->old_price && $p->old_price > $p->new_price): ?>
                                                    <span class="bilai-wl-price-old">&#2547;<?php echo e($p->old_price); ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if($inStock): ?>
                                                    <span class="bilai-wl-badge bilai-wl-badge--in">Available in Stock</span>
                                                <?php else: ?>
                                                    <span class="bilai-wl-badge bilai-wl-badge--out">Stock Out</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="bilai-wl-actions">
                                                    <?php if(!$inStock): ?>
                                                        <button type="button" class="bilai-wl-cart-btn" disabled>
                                                            
                                                            <i class="fa-solid fa-cart-shopping"></i> Add to Cart
                                                        </button>
                                                    <?php elseif($hasVariants): ?>
                                                        
                                                        <a href="<?php echo e(route('product', $p->slug)); ?>" class="bilai-wl-cart-btn">
                                                            
                                                            <i class="fa-solid fa-cart-shopping"></i> Add to Cart
                                                        </a>
                                                    <?php else: ?>
                                                        <form action="<?php echo e(route('cart.store')); ?>" method="POST" style="display:inline;">
                                                            <?php echo csrf_field(); ?>
                                                            <input type="hidden" name="id" value="<?php echo e($p->id); ?>">
                                                            <input type="hidden" name="qty" value="1">
                                                            <button type="submit" class="bilai-wl-cart-btn cart_store" data-id="<?php echo e($p->id); ?>">
                                                                
                                                                <i class="fa-solid fa-cart-shopping"></i> Add to Cart
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                    <button type="button" class="bilai-wl-remove" data-remove-id="<?php echo e($p->id); ?>" aria-label="Remove from wishlist">
                                                        
                                                        <i class="fa fa-trash-o"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php else: ?>
                    
                    <div class="bilai-wl-empty">
                        
                        <div class="bilai-wl-empty-ic"><i class="fa fa-heart-o"></i></div>
                        <h4>Your wishlist is empty.</h4>
                        <p>Save products you love and find them here later.</p>
                        <a href="<?php echo e(route('shop')); ?>" class="bilai-wl-browse-btn">Browse Products <i class="fa fa-arrow-right"></i></a>
                    </div>
                <?php endif; ?>
            </main>
        </div>

        
        <?php if(isset($recommendedProducts) && $recommendedProducts->count() > 0): ?>
        <div class="bilai-wl-rec">
            <div class="bilai-wl-rec-head">
                <h2 class="bilai-wl-rec-title">Recommended Products</h2>
                <a href="<?php echo e(route('hotdeals')); ?>" class="bilai-wl-rec-alldeals">View All Deals</a>
            </div>
            <div class="bilai-wl-rec-grid">
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
                        <button class="bilai-wishlist-btn" type="button" data-product-id="<?php echo e($value->id); ?>" aria-label="Toggle wishlist" aria-pressed="false">
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
/* BilaiGhor Wishlist Page — remove rows via the shared toggle endpoint */
(function () {
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.bilai-wl-remove[data-remove-id]');
        if (!btn) { return; }
        e.preventDefault();
        btn.disabled = true;

        fetch('<?php echo e(route('customer.wishlist.toggle')); ?>', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '<?php echo e(csrf_token()); ?>', 'Accept': 'application/json' },
            body: JSON.stringify({ product_id: btn.dataset.removeId })
        })
        .then(function (res) { return res.json(); })
        .then(function (j) {
            if (!j.success) {
                if (window.toastr) { toastr.error(j.message || 'Could not remove item.'); }
                btn.disabled = false;
                return;
            }
            var row = btn.closest('tr[data-wishlist-row]');
            if (row) { row.remove(); }
            if (window.toastr) { toastr.success('Product removed from wishlist.'); }
            // Last row gone → reload to show the empty state (and refresh hearts below).
            if (!document.querySelector('tr[data-wishlist-row]')) {
                window.location.reload();
            }
        })
        .catch(function () {
            if (window.toastr) { toastr.error('Could not remove item. Please try again.'); }
            btn.disabled = false;
        });
    });
}());
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('frontEnd.layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\bilaiGhor\resources\views\frontEnd\layouts\customer\wishlist.blade.php ENDPATH**/ ?>