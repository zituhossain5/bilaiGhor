<?php
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

$customer = Auth::guard('customer')->user();
$customerId = $customer->id;

// Site Name & Logo
$siteName = \App\Models\GeneralSetting::first();
$siteInitial = strtoupper(substr($siteName->name ?? 'G', 0, 1));
$siteDisplayName = Str::limit($siteName->name ?? 'GadgetShop', 8);
$generalsetting = $siteName;
$darkLogo = $siteName->dark_logo ?? null;

// Pending Orders Count for Badge
$pendingOrdersCount = \App\Models\Order::where('customer_id', $customerId)
    ->whereNotIn('order_status', ['6', '11'])
    ->count();

// Profile Image - Use direct image path
$profileImage = $customer->image ? asset($customer->image) : asset('public/uploads/default/no-image.png');

// Total Order Amount
$totalOrderAmount = \App\Models\Order::where('customer_id', $customerId)->sum('amount');
?>

<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title>রিফান্ড রিকোয়েস্ট | <?php echo e($siteName->name ?? 'Gadget Style'); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@300;400;500;600;700&display=swap');
        body { font-family: 'Hind Siliguri', sans-serif; background-color: #F0F2F5; }
        .sidebar-item:hover { background-color: #f3f4f6; color: #4f46e5; }
        .active-menu { background-color: #EEF2FF; color: #4f46e5; border-right: 3px solid #4f46e5; }
        
        /* Table Style */
        .custom-table th { background-color: #F9FAFB; color: #6B7280; font-weight: 600; font-size: 0.85rem; }
        .custom-table td { border-bottom: 1px solid #F3F4F6; padding: 16px; font-size: 0.9rem; }
        
        /* Mobile Menu Transition */
        #sidebar { transition: transform 0.3s ease-in-out; }
    </style>
</head>
<body class="flex min-h-screen relative">

    <div id="overlay" onclick="toggleSidebar()" class="fixed inset-0 bg-black bg-opacity-50 z-30 hidden lg:hidden"></div>

    <aside id="sidebar" class="fixed inset-y-0 left-0 z-40 w-64 bg-white border-r transform -translate-x-full lg:translate-x-0 lg:static lg:inset-auto lg:flex flex-col shrink-0 h-screen transition-transform duration-300">
        <div class="p-4 sm:p-6 flex items-center justify-between lg:justify-start gap-2 border-b border-gray-100">
            <?php if($darkLogo): ?>
                <a href="<?php echo e(route('home')); ?>" class="flex items-center gap-2 flex-1">
                    <img src="<?php echo e(asset($darkLogo)); ?>" alt="<?php echo e($siteName->name ?? 'Logo'); ?>" class="h-8 sm:h-10 w-auto max-w-full object-contain">
                </a>
            <?php else: ?>
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 bg-indigo-600 rounded-lg flex items-center justify-center text-white font-bold"><?php echo e($siteInitial); ?></div>
                    <h1 class="text-xl sm:text-2xl font-bold text-gray-800 tracking-tight"><?php echo e($siteDisplayName); ?></h1>
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
    </aside>

    <main class="flex-1 overflow-y-auto h-screen w-full">
        
        <header class="bg-white px-6 lg:px-8 py-4 flex justify-between items-center sticky top-0 z-20 shadow-sm border-b">
            <div class="lg:hidden mr-4">
                <button onclick="toggleSidebar()" class="text-gray-600 text-xl p-2"><i class="fas fa-bars"></i></button>
            </div>

            <div class="flex-1">
                <h2 class="text-xl font-bold text-gray-800">রিফান্ড রিকোয়েস্ট</h2>
                <p class="text-xs text-gray-400 mt-0.5 hidden sm:block">আপনার রিফান্ড রিকোয়েস্টের তালিকা</p>
            </div>

            <div class="flex items-center gap-4">
                <div class="hidden sm:flex bg-green-50 text-green-700 px-4 py-2 rounded-full items-center font-bold text-sm border border-green-100">
                    <i class="fas fa-wallet mr-2"></i> মোট: ৳<?php echo e(number_format($totalOrderAmount, 0)); ?>

                </div>
                
                <div class="relative cursor-pointer w-10 h-10 bg-gray-50 rounded-full flex items-center justify-center hover:bg-gray-100 transition">
                    <i class="far fa-bell text-gray-600"></i>
                </div>

                <img src="<?php echo e($profileImage); ?>" onerror="this.src='<?php echo e(asset('public/uploads/default/no-image.png')); ?>'" class="w-10 h-10 rounded-full border-2 border-white shadow-sm cursor-pointer" alt="Profile">
            </div>
        </header>

        <div class="p-4 lg:p-8 max-w-7xl mx-auto">
            
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-6 border-b border-gray-50 flex justify-between items-center">
                    <h3 class="text-lg font-bold text-gray-800">🔄 রিফান্ড রিকোয়েস্ট</h3>
                    <?php if($refunds->count() > 0): ?>
                        <span class="bg-indigo-50 text-indigo-600 px-3 py-1 rounded-full text-sm font-semibold"><?php echo e($refunds->total()); ?> টি রিকোয়েস্ট</span>
                    <?php endif; ?>
                </div>
                
                <?php if($refunds->count() > 0): ?>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left custom-table">
                            <thead>
                                <tr>
                                    <th class="pl-6 py-4">রিফান্ড আইডি</th>
                                    <th class="py-4">অর্ডার তথ্য</th>
                                    <th class="py-4">মোট রিফান্ড</th>
                                    <th class="py-4">স্ট্যাটাস</th>
                                    <th class="py-4">তারিখ</th>
                                    <th class="pr-6 py-4 text-right">অ্যাকশন</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $__currentLoopData = $refunds; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $refund): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <?php
                                        // Status Badge Classes
                                        $statusClass = '';
                                        $statusText = '';
                                        $statusIcon = '';
                                        
                                        if($refund->status == 'pending') {
                                            $statusClass = 'bg-orange-50 text-orange-600';
                                            $statusText = 'Pending';
                                            $statusIcon = 'fas fa-clock';
                                        } elseif($refund->status == 'approved') {
                                            $statusClass = 'bg-blue-50 text-blue-600';
                                            $statusText = 'Approved';
                                            $statusIcon = 'fas fa-check';
                                        } elseif($refund->status == 'rejected') {
                                            $statusClass = 'bg-red-50 text-red-600';
                                            $statusText = 'Rejected';
                                            $statusIcon = 'fas fa-times';
                                        } elseif($refund->status == 'processed') {
                                            $statusClass = 'bg-green-50 text-green-600';
                                            $statusText = 'Processed';
                                            $statusIcon = 'fas fa-check-double';
                                        }
                                    ?>
                                    <tr class="hover:bg-gray-50 transition">
                                        <td class="pl-6 font-bold text-indigo-600">#<?php echo e($refund->refund_id); ?></td>
                                        
                                        <td>
                                            <a href="<?php echo e(route('customer.invoice', ['id' => $refund->order->id])); ?>" class="text-indigo-600 hover:text-indigo-700 font-bold hover:underline">
                                                #<?php echo e($refund->order->invoice_id ?? $refund->order->id); ?>

                                            </a>
                                            <div class="text-xs text-gray-400 mt-0.5">Invoice ID</div>
                                        </td>

                                        <td class="font-bold text-gray-800">৳<?php echo e(number_format($refund->amount + $refund->shipping_charge, 2)); ?></td>

                                        <td>
                                            <span class="<?php echo e($statusClass); ?> px-3 py-1 rounded-full text-xs font-bold inline-flex items-center gap-1">
                                                <i class="<?php echo e($statusIcon); ?>"></i>
                                                <?php echo e($statusText); ?>

                                            </span>
                                        </td>

                                        <td class="text-gray-500">
                                            <div><?php echo e($refund->created_at->format('d M, Y')); ?></div>
                                            <div class="text-xs text-gray-400"><?php echo e($refund->created_at->format('h:i A')); ?></div>
                                        </td>

                                        <td class="pr-6 text-right">
                                            <div class="flex items-center justify-end gap-2">
                                                <a href="<?php echo e(route('customer.refunds.show', $refund->id)); ?>" class="text-indigo-600 hover:text-indigo-700 p-2 hover:bg-indigo-50 rounded-lg transition" title="বিস্তারিত দেখুন">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                
                                                <?php if($refund->status == 'pending'): ?>
                                                    <form action="<?php echo e(route('customer.refunds.cancel', $refund->id)); ?>" method="POST" class="inline" onsubmit="return confirm('আপনি কি এই রিফান্ড রিকোয়েস্টটি বাতিল করতে চান?');">
                                                        <?php echo csrf_field(); ?>
                                                        <?php echo method_field('DELETE'); ?>
                                                        <button type="submit" class="text-red-600 hover:text-red-700 p-2 hover:bg-red-50 rounded-lg transition" title="বাতিল করুন">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </tbody>
                        </table>
                    </div>

                    
                    <?php if($refunds->hasPages()): ?>
                        <div class="p-6 border-t border-gray-100 flex justify-center">
                            <?php echo e($refunds->links()); ?>

                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    
                    <div class="text-center py-16 px-4">
                        <div class="inline-flex items-center justify-center w-20 h-20 bg-gray-100 rounded-full mb-4">
                            <i class="fas fa-file-invoice-dollar text-4xl text-gray-300"></i>
                        </div>
                        <h5 class="text-lg font-bold text-gray-800 mb-2">কোনো রিফান্ড রিকোয়েস্ট নেই</h5>
                        <p class="text-gray-500 mb-6">আপনি এখনো কোনো রিফান্ড রিকোয়েস্ট করেননি।</p>
                        <a href="<?php echo e(route('customer.orders')); ?>" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2.5 rounded-lg font-semibold transition">
                            <i class="fas fa-box-open"></i>
                            অর্ডার দেখুন
                        </a>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </main>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('overlay');
            
            if (sidebar.classList.contains('-translate-x-full')) {
                sidebar.classList.remove('-translate-x-full');
                overlay.classList.remove('hidden');
            } else {
                sidebar.classList.add('-translate-x-full');
                overlay.classList.add('hidden');
            }
        }
    </script>
</body>
</html>
<?php /**PATH C:\laragon\www\bilaiGhor\resources\views\frontEnd\layouts\customer\refunds.blade.php ENDPATH**/ ?>