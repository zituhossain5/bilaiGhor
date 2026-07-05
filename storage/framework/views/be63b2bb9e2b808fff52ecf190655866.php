<?php
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Models\Order;

$customer      = Auth::guard('customer')->user();
$customerId    = $customer->id;

$pendingOrders    = Order::where('customer_id', $customerId)->whereIn('order_status', ['1', '2'])->count();
$processingOrders = Order::where('customer_id', $customerId)->whereIn('order_status', ['3', '4', '5'])->count();
$deliveredOrders  = Order::where('customer_id', $customerId)->where('order_status', '6')->count();
$pendingOrdersCount = Order::where('customer_id', $customerId)->whereNotIn('order_status', ['6', '11'])->count();
$totalOrderAmount = Order::where('customer_id', $customerId)->sum('amount');

$profileImage    = $customer->image ? asset($customer->image) : null;
$customerInitial = strtoupper(substr($customer->name ?? 'U', 0, 1));
?>



<?php $__env->startSection('title', 'Dashboard | ' . ($customer->name ?? 'Account')); ?>

<?php $__env->startPush('css'); ?>
<style>
/* BilaiGhor Customer Dashboard Figma Fix Start */

:root {
    --bilai-dash-primary:      var(--bilai-primary,      #e8861a);
    --bilai-dash-primary-dark: var(--bilai-primary-dark, #c96f00);
    --bilai-dash-brown:        var(--bilai-brown,        #3a1f0f);
    --bilai-dash-cream:        #FFF8EC;
    --bilai-dash-card:         #FFFDF8;
    --bilai-dash-border:       #E8CDA5;
    --bilai-dash-text:         #2B1A10;
    --bilai-dash-muted:        #77706A;
    --bilai-dash-radius:       12px;
    --bilai-dash-radius-sm:    8px;
}

/* ── Page background ─────────────────────────────────────────────── */
.bilai-dash-page {
    background: #f5f5f0;
    min-height: 72vh;
    padding: 20px 0 52px;
}

/* ── Breadcrumb (inline, no heavy bar) ───────────────────────────── */
.bilai-dash-bc {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 12.5px;
    margin-bottom: 18px;
    flex-wrap: wrap;
}
.bilai-dash-bc a {
    color: var(--bilai-dash-muted);
    text-decoration: none;
}
.bilai-dash-bc a:hover { color: var(--bilai-dash-primary); }
.bilai-dash-bc-sep    { color: #c0b0a0; font-size: 11px; }
.bilai-dash-bc-active { color: var(--bilai-dash-primary); font-weight: 600; }

/* ── Two-column layout ───────────────────────────────────────────── */
.bilai-dash-layout {
    display: grid;
    grid-template-columns: 248px 1fr;
    gap: 20px;
    align-items: start;
}

/* ── Sidebar card ────────────────────────────────────────────────── */
.bilai-dash-sidebar-card {
    background: #fff;
    border: 1px solid var(--bilai-dash-border);
    border-radius: var(--bilai-dash-radius);
    overflow: hidden;
    position: sticky;
    top: 90px;
}

/* ── Profile box — horizontal: avatar left, name right ───────────── */
.bilai-dash-profile-box {
    padding: 18px 16px 14px;
}
.bilai-dash-profile-row {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 14px;
}
.bilai-dash-avatar {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid var(--bilai-dash-border);
    flex-shrink: 0;
}
.bilai-dash-avatar-placeholder {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    background: var(--bilai-dash-primary);
    color: #fff;
    font-size: 22px;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.bilai-dash-profile-info { flex: 1; min-width: 0; }
.bilai-dash-profile-name {
    font-size: 14px;
    font-weight: 700;
    color: var(--bilai-dash-text);
    margin: 0 0 2px;
    line-height: 1.3;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.bilai-dash-profile-sub {
    font-size: 12px;
    color: var(--bilai-dash-muted);
    margin: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

/* Divider */
.bilai-dash-profile-divider {
    border: none;
    border-top: 1px solid var(--bilai-dash-border);
    margin: 0 0 12px;
}

/* RP / TK row */
.bilai-dash-rp-row {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
}
.bilai-dash-rp-item {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 12px;
    font-weight: 600;
    color: var(--bilai-dash-text);
}
.bilai-dash-rp-icon { color: var(--bilai-dash-primary); font-size: 13px; }
.bilai-dash-rp-sep-icon {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background: var(--bilai-dash-cream);
    border: 1px solid var(--bilai-dash-border);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--bilai-dash-muted);
    font-size: 10px;
    flex-shrink: 0;
}

/* ── Sidebar nav ─────────────────────────────────────────────────── */
.bilai-dash-nav { padding: 4px 0 4px; }
.bilai-dash-nav-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 16px;
    color: var(--bilai-dash-text);
    font-size: 13.5px;
    font-weight: 500;
    text-decoration: none;
    border-left: 3px solid transparent;
    transition: background 0.12s, color 0.12s;
    line-height: 1.3;
}
.bilai-dash-nav-item:hover {
    background: var(--bilai-dash-cream);
    color: var(--bilai-dash-primary);
    text-decoration: none;
}
.bilai-dash-nav-item.active {
    background: var(--bilai-dash-cream);
    color: var(--bilai-dash-primary);
    border-left-color: var(--bilai-dash-primary);
    font-weight: 600;
}
.bilai-dash-nav-icon {
    width: 16px;
    text-align: center;
    flex-shrink: 0;
    font-size: 13px;
    opacity: 0.7;
}
.bilai-dash-nav-item.active .bilai-dash-nav-icon,
.bilai-dash-nav-item:hover .bilai-dash-nav-icon { opacity: 1; }
.bilai-dash-nav-badge {
    margin-left: auto;
    background: #e53935;
    color: #fff;
    font-size: 10px;
    font-weight: 700;
    padding: 1px 6px;
    border-radius: 100px;
    line-height: 1.5;
}
.bilai-dash-nav-sep {
    border: none;
    border-top: 1px solid var(--bilai-dash-border);
    margin: 4px 0;
}
.bilai-dash-nav-item--logout { color: #c0392b; }
.bilai-dash-nav-item--logout .bilai-dash-nav-icon { opacity: 0.8; }
.bilai-dash-nav-item--logout:hover {
    background: #fff5f5;
    color: #a93226;
}

/* ── Main content card ───────────────────────────────────────────── */
.bilai-dash-main-card {
    background: #fff;
    border: 1px solid var(--bilai-dash-border);
    border-radius: var(--bilai-dash-radius);
    padding: 22px 22px 24px;
}
.bilai-dash-welcome-title {
    font-size: 17px;
    font-weight: 700;
    color: var(--bilai-dash-text);
    margin: 0 0 5px;
}
.bilai-dash-welcome-text {
    font-size: 13px;
    color: var(--bilai-dash-muted);
    margin: 0 0 20px;
    line-height: 1.6;
}

/* ── Stats grid — icon at TOP-RIGHT ──────────────────────────────── */
.bilai-dash-stats-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 10px;
}
.bilai-dash-stat-card {
    background: var(--bilai-dash-cream);
    border: 1px solid var(--bilai-dash-border);
    border-radius: var(--bilai-dash-radius-sm);
    padding: 14px 14px 12px;
    display: flex;
    flex-direction: column;
    gap: 8px;
    transition: border-color 0.15s;
}
.bilai-dash-stat-card--pending {
    background: #fff5e6;
    border-color: #f5c97a;
}
/* Top row: label left, icon right */
.bilai-dash-stat-top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 6px;
}
.bilai-dash-stat-label {
    font-size: 12px;
    color: var(--bilai-dash-muted);
    margin: 0;
    line-height: 1.4;
    font-weight: 500;
}
.bilai-dash-stat-icon-wrap {
    width: 30px;
    height: 30px;
    border-radius: 6px;
    background: rgba(255,255,255,0.8);
    border: 1px solid var(--bilai-dash-border);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    color: var(--bilai-dash-muted);
    font-size: 13px;
}
.bilai-dash-stat-value {
    font-size: 22px;
    font-weight: 700;
    color: var(--bilai-dash-text);
    margin: 0;
    line-height: 1.1;
}

/* ── Responsive ──────────────────────────────────────────────────── */
@media (max-width: 1199px) {
    .bilai-dash-layout { grid-template-columns: 228px 1fr; gap: 16px; }
}
@media (max-width: 991px) {
    .bilai-dash-layout { grid-template-columns: 1fr; }
    .bilai-dash-sidebar-card { position: static; }
    .bilai-dash-stats-grid  { grid-template-columns: repeat(3, 1fr); }
}
@media (max-width: 767px) {
    .bilai-dash-stats-grid { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 480px) {
    .bilai-dash-main-card { padding: 16px 14px 18px; }
    .bilai-dash-stat-value { font-size: 18px; }
    .bilai-dash-stats-grid { gap: 8px; }
}

/* BilaiGhor Customer Dashboard Figma Fix End */
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>

<div class="bilai-dash-page">
    <div class="container">

        
        <nav class="bilai-dash-bc" aria-label="breadcrumb">
            <a href="<?php echo e(route('home')); ?>">Home</a>
            <span class="bilai-dash-bc-sep">›</span>
            <span>Profile</span>
            <span class="bilai-dash-bc-sep">›</span>
            <span class="bilai-dash-bc-active">Dashboard</span>
        </nav>

        <div class="bilai-dash-layout">

            
            <aside>
                <div class="bilai-dash-sidebar-card">

                    
                    <div class="bilai-dash-profile-box">
                        <div class="bilai-dash-profile-row">
                            <?php if($profileImage): ?>
                                <img src="<?php echo e($profileImage); ?>"
                                     onerror="this.style.display='none';this.nextElementSibling.style.display='flex';"
                                     class="bilai-dash-avatar" alt="<?php echo e($customer->name); ?>">
                                <div class="bilai-dash-avatar-placeholder" style="display:none;"><?php echo e($customerInitial); ?></div>
                            <?php else: ?>
                                <div class="bilai-dash-avatar-placeholder"><?php echo e($customerInitial); ?></div>
                            <?php endif; ?>
                            <div class="bilai-dash-profile-info">
                                <p class="bilai-dash-profile-name"><?php echo e($customer->name ?? 'Customer'); ?></p>
                                <p class="bilai-dash-profile-sub"><?php echo e($customer->phone ?? $customer->email ?? ''); ?></p>
                            </div>
                        </div>

                        <hr class="bilai-dash-profile-divider">

                        
                        <div class="bilai-dash-rp-row">
                            <span class="bilai-dash-rp-item">
                                
                                <span class="bilai-dash-rp-icon"><i class="fa fa-star"></i></span>
                                <span>0 RP</span>
                            </span>
                            
                            <span class="bilai-dash-rp-sep-icon"><i class="fa fa-exchange"></i></span>
                            <span class="bilai-dash-rp-item">
                                
                                <span class="bilai-dash-rp-icon"><i class="fa fa-money"></i></span>
                                <span>৳<?php echo e(number_format($totalOrderAmount, 0)); ?> TK</span>
                            </span>
                        </div>
                    </div>

                    
                    <nav class="bilai-dash-nav">

                        <a href="<?php echo e(route('customer.account')); ?>"
                           class="bilai-dash-nav-item <?php echo e(request()->is('customer/account') ? 'active' : ''); ?>">
                            
                            <span class="bilai-dash-nav-icon"><i class="fa fa-home"></i></span>
                            Dashboard
                        </a>

                        <a href="<?php echo e(route('customer.orders')); ?>"
                           class="bilai-dash-nav-item <?php echo e(request()->is('customer/orders') ? 'active' : ''); ?>">
                            
                            <span class="bilai-dash-nav-icon"><i class="fa fa-shopping-bag"></i></span>
                            Orders
                            <?php if($pendingOrdersCount > 0): ?>
                                <span class="bilai-dash-nav-badge"><?php echo e($pendingOrdersCount); ?></span>
                            <?php endif; ?>
                        </a>

                        <a href="<?php echo e(route('customer.profile_edit')); ?>"
                           class="bilai-dash-nav-item <?php echo e(request()->is('customer/profile-edit') ? 'active' : ''); ?>">
                            
                            <span class="bilai-dash-nav-icon"><i class="fa fa-user"></i></span>
                            Profile
                        </a>

                        
                        <a href="#" class="bilai-dash-nav-item">
                            
                            <span class="bilai-dash-nav-icon"><i class="fa fa-heart-o"></i></span>
                            Wishlist
                        </a>

                        
                        <a href="#" class="bilai-dash-nav-item">
                            
                            <span class="bilai-dash-nav-icon"><i class="fa fa-ticket"></i></span>
                            Coupon
                        </a>

                        
                        <a href="#" class="bilai-dash-nav-item">
                            
                            <span class="bilai-dash-nav-icon"><i class="fa fa-gift"></i></span>
                            Gift Cards
                        </a>

                        
                        <a href="#" class="bilai-dash-nav-item">
                            
                            <span class="bilai-dash-nav-icon"><i class="fa fa-star-o"></i></span>
                            Reward Points
                        </a>

                        <a href="<?php echo e(route('customer.order_track')); ?>"
                           class="bilai-dash-nav-item <?php echo e(request()->is('customer/order-track*') ? 'active' : ''); ?>">
                            
                            <span class="bilai-dash-nav-icon"><i class="fa fa-truck"></i></span>
                            Track Order
                        </a>

                        <a href="<?php echo e(route('customer.refunds')); ?>"
                           class="bilai-dash-nav-item <?php echo e(request()->is('customer/refunds*') ? 'active' : ''); ?>">
                            
                            <span class="bilai-dash-nav-icon"><i class="fa fa-undo"></i></span>
                            Return Request
                        </a>

                        <a href="<?php echo e(route('complaint')); ?>"
                           class="bilai-dash-nav-item <?php echo e(request()->is('complaint') ? 'active' : ''); ?>">
                            
                            <span class="bilai-dash-nav-icon"><i class="fa fa-headphones"></i></span>
                            Support Ticket
                        </a>

                        <hr class="bilai-dash-nav-sep">

                        <a href="<?php echo e(route('customer.logout')); ?>"
                           onclick="event.preventDefault(); document.getElementById('bilai-logout-form').submit();"
                           class="bilai-dash-nav-item bilai-dash-nav-item--logout">
                            
                            <span class="bilai-dash-nav-icon"><i class="fa fa-sign-out"></i></span>
                            Logout
                        </a>
                        <form id="bilai-logout-form" action="<?php echo e(route('customer.logout')); ?>" method="POST" style="display:none;">
                            <?php echo csrf_field(); ?>
                        </form>

                    </nav>
                </div>
            </aside>

            
            <main>

                
                <div class="bilai-dash-main-card">
                    <h2 class="bilai-dash-welcome-title">Welcome back, <?php echo e(ucfirst($customer->name ?? 'Customer')); ?>!</h2>
                    <p class="bilai-dash-welcome-text">From your account dashboard, you can easily view your orders, track reward points, manage your wishlist, and check your coupons.</p>

                    <div class="bilai-dash-stats-grid">

                        
                        <div class="bilai-dash-stat-card">
                            <div class="bilai-dash-stat-top">
                                <span class="bilai-dash-stat-label">Total RP</span>
                                <div class="bilai-dash-stat-icon-wrap">
                                    
                                    <i class="fa fa-cog"></i>
                                </div>
                            </div>
                            <p class="bilai-dash-stat-value">00</p>
                            
                        </div>

                        
                        <div class="bilai-dash-stat-card">
                            <div class="bilai-dash-stat-top">
                                <span class="bilai-dash-stat-label">Total Taka</span>
                                <div class="bilai-dash-stat-icon-wrap">
                                    
                                    <i class="fa fa-search"></i>
                                </div>
                            </div>
                            <p class="bilai-dash-stat-value"><?php echo e(number_format($totalOrderAmount, 0)); ?></p>
                        </div>

                        
                        <div class="bilai-dash-stat-card">
                            <div class="bilai-dash-stat-top">
                                <span class="bilai-dash-stat-label">Total Wishlist</span>
                                <div class="bilai-dash-stat-icon-wrap">
                                    
                                    <i class="fa fa-heart-o"></i>
                                </div>
                            </div>
                            <p class="bilai-dash-stat-value">00</p>
                            
                        </div>

                        
                        <div class="bilai-dash-stat-card">
                            <div class="bilai-dash-stat-top">
                                <span class="bilai-dash-stat-label">Delivered Order</span>
                                <div class="bilai-dash-stat-icon-wrap">
                                    
                                    <i class="fa fa-check-circle-o"></i>
                                </div>
                            </div>
                            <p class="bilai-dash-stat-value"><?php echo e($deliveredOrders); ?></p>
                        </div>

                        
                        <div class="bilai-dash-stat-card bilai-dash-stat-card--pending">
                            <div class="bilai-dash-stat-top">
                                <span class="bilai-dash-stat-label">Pending Order</span>
                                <div class="bilai-dash-stat-icon-wrap">
                                    
                                    <i class="fa fa-spinner"></i>
                                </div>
                            </div>
                            <p class="bilai-dash-stat-value"><?php echo e($pendingOrders); ?></p>
                        </div>

                        
                        <div class="bilai-dash-stat-card">
                            <div class="bilai-dash-stat-top">
                                <span class="bilai-dash-stat-label">Processing Order</span>
                                <div class="bilai-dash-stat-icon-wrap">
                                    
                                    <i class="fa fa-exchange"></i>
                                </div>
                            </div>
                            <p class="bilai-dash-stat-value"><?php echo e($processingOrders); ?></p>
                        </div>

                        
                        <div class="bilai-dash-stat-card">
                            <div class="bilai-dash-stat-top">
                                <span class="bilai-dash-stat-label">Active Coupon</span>
                                <div class="bilai-dash-stat-icon-wrap">
                                    
                                    <i class="fa fa-envelope-o"></i>
                                </div>
                            </div>
                            <p class="bilai-dash-stat-value">00</p>
                            
                        </div>

                    </div>
                </div>

                

                

            </main>
        </div>
    </div>
</div>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('frontEnd.layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\projects\bilaiGhor\resources\views/frontEnd/layouts/customer/account.blade.php ENDPATH**/ ?>