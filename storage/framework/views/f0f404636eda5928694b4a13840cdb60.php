<?php
    use Illuminate\Support\Str;
    $customer = Auth::guard('customer')->user();
    $pendingOrdersCount = \App\Models\Order::where('customer_id', $customer->id)
        ->whereNotIn('order_status', ['6', '11'])
        ->count();
    
    // Get dark logo
    $logo = null;
    if(isset($generalsetting) && $generalsetting && !empty($generalsetting->dark_logo)) {
        $logo = $generalsetting->dark_logo;
    } else {
        $gs = \App\Models\GeneralSetting::where('status', 1)->first();
        if($gs && !empty($gs->dark_logo)) {
            $logo = $gs->dark_logo;
        }
    }
?>

<aside id="sidebar" class="fixed inset-y-0 left-0 z-40 w-64 bg-white border-r transform -translate-x-full lg:translate-x-0 lg:static lg:inset-auto lg:flex flex-col shrink-0 h-screen transition-transform duration-300">
    <div class="p-4 sm:p-6 flex items-center justify-between lg:justify-start gap-2 border-b border-gray-100">
        <?php if($logo): ?>
            <a href="<?php echo e(route('home')); ?>" class="flex items-center gap-2 flex-1">
                <img src="<?php echo e(asset($logo)); ?>" alt="<?php echo e($generalsetting->name ?? 'Logo'); ?>" class="h-8 sm:h-10 w-auto max-w-full object-contain">
            </a>
        <?php else: ?>
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 bg-indigo-600 rounded-lg flex items-center justify-center text-white font-bold">
                    <?php echo e(strtoupper(substr($generalsetting->name ?? 'G', 0, 1))); ?>

                </div>
                <h1 class="text-xl sm:text-2xl font-bold text-gray-800 tracking-tight">
                    <?php echo e(Str::limit($generalsetting->name ?? 'GadgetShop', 8)); ?>

                </h1>
            </div>
        <?php endif; ?>
        <button onclick="toggleSidebar()" class="lg:hidden text-gray-500 hover:text-red-500">
            <i class="fas fa-times text-xl"></i>
        </button>
    </div>

    <nav class="flex-1 px-0 text-gray-500 font-medium space-y-1 mt-2 overflow-y-auto">
        <a href="<?php echo e(route('customer.account')); ?>" class="<?php echo e(request()->is('customer/account')?'active-menu':'sidebar-item'); ?> flex items-center px-6 py-3.5 transition-colors">
            <i class="fas fa-home w-6"></i> ড্যাশবোর্ড
        </a>
        <a href="<?php echo e(route('customer.orders')); ?>" class="<?php echo e(request()->is('customer/orders')?'active-menu':'sidebar-item'); ?> flex items-center px-6 py-3.5 transition-colors">
            <i class="fas fa-box-open w-6"></i> আমার অর্ডার 
            <?php if($pendingOrdersCount > 0): ?>
                <span class="ml-auto bg-red-100 text-red-600 text-xs px-2 py-0.5 rounded-full"><?php echo e($pendingOrdersCount); ?></span>
            <?php endif; ?>
        </a>
        <a href="<?php echo e(route('customer.order_track')); ?>" class="<?php echo e(request()->is('customer/order-track*')?'active-menu':'sidebar-item'); ?> flex items-center px-6 py-3.5 transition-colors">
            <i class="fas fa-truck w-6"></i> ট্র্যাক অর্ডার
        </a>
        <a href="<?php echo e(route('customer.refunds')); ?>" class="<?php echo e(request()->is('customer/refunds*')?'active-menu':'sidebar-item'); ?> flex items-center px-6 py-3.5 transition-colors">
            <i class="fas fa-undo w-6"></i> রিফান্ড রিকোয়েস্ট
        </a>
        <a href="<?php echo e(route('complaint')); ?>" class="<?php echo e(request()->is('complaint') ? 'active-menu' : 'sidebar-item'); ?> flex items-center px-6 py-3.5 transition-colors">
            <i class="fas fa-headset w-6"></i> সাপোর্ট টিকেট
        </a>
        <a href="<?php echo e(route('customer.profile_edit')); ?>" class="<?php echo e(request()->is('customer/profile-edit')?'active-menu':'sidebar-item'); ?> flex items-center px-6 py-3.5 transition-colors">
            <i class="fas fa-user-cog w-6"></i> সেটিংস
        </a>
    </nav>

    <div class="p-6 border-t">
        <a href="<?php echo e(route('customer.logout')); ?>" 
           onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
           class="w-full flex items-center justify-center px-4 py-2.5 text-red-500 bg-red-50 hover:bg-red-100 rounded-lg font-bold transition">
            <i class="fas fa-sign-out-alt mr-2"></i> লগআউট
        </a>
        <form id="logout-form" action="<?php echo e(route('customer.logout')); ?>" method="POST" style="display: none;">
            <?php echo csrf_field(); ?>
        </form>
    </div>
</aside><?php /**PATH C:\laragon\www\bilaiGhor\resources\views\frontEnd\layouts\customer\sidebar.blade.php ENDPATH**/ ?>