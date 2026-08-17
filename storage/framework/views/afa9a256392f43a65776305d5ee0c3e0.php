
<a href="<?php echo e(route('reseller.landing.order-track', $landing->slug)); ?>" class="hidden md:flex items-center space-x-2 hover:text-blue-600 transition" title="অর্ডার ট্রাকিং">
    <i class="fa-solid fa-truck-fast text-2xl"></i>
    <span class="text-sm font-semibold">অর্ডার ট্রাকিং</span>
</a>
<?php if(!empty($landing->phone)): ?>
    <a href="tel:<?php echo e($landing->phone); ?>" class="flex items-center space-x-2 text-blue-600 hover:text-blue-700 font-semibold">
        <i class="fa-solid fa-phone text-xl"></i>
        <span class="hidden sm:inline">কল করুন</span>
    </a>
<?php endif; ?>
<?php /**PATH C:\laragon\www\bilaiGhor\resources\views\reseller\landing\partials\header-actions.blade.php ENDPATH**/ ?>