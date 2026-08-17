<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo e($landing->title ?? ''); ?> - <?php echo e($landing->tagline ?? ''); ?>">
    <title><?php echo e($landing->title ?? 'রিসেলার স্টোর'); ?> | <?php echo e(config('app.name', 'Creative Design')); ?></title>
    <?php if(!empty($landing->favicon)): ?>
    <link rel="icon" type="<?php echo e(str_ends_with($landing->favicon, '.ico') ? 'image/x-icon' : 'image/png'); ?>" href="<?php echo e(asset($landing->favicon)); ?>">
    <?php endif; ?>

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.css" />

    <style>
        .swiper-button-next, .swiper-button-prev { color: #2563eb !important; background: rgba(255,255,255,0.8); padding: 20px; border-radius: 50%; }
        .swiper-button-next::after, .swiper-button-prev::after { font-size: 20px !important; font-weight: bold; }
        .hide-scroll::-webkit-scrollbar { display: none; }
        @keyframes marquee {
            0% { transform: translateX(0); }
            100% { transform: translateX(-50%); }
        }
        .animate-marquee {
            animation: marquee 20s linear infinite;
        }
    </style>
    <?php echo $__env->make('reseller.landing.partials.tracking-head', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
</head>
<body class="bg-gray-100 font-sans text-gray-800 pb-16 md:pb-0">
    <?php echo $__env->make('reseller.landing.partials.tracking-body', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    
    <?php echo $__env->make('reseller.landing.partials.top-bar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    
    <header class="bg-white shadow-sm sticky top-0 z-40">
        <div class="container mx-auto px-4 py-4 flex flex-wrap justify-between items-center gap-4">
            <a href="<?php echo e(landing_url($landing->slug, '')); ?>" class="flex items-center">
                <?php if($landing->logo): ?>
                    <img src="<?php echo e(asset($landing->logo)); ?>" alt="<?php echo e($landing->title); ?>" class="h-10 md:h-12 w-auto max-w-[180px] object-contain">
                <?php else: ?>
                    <span class="text-2xl md:text-3xl font-extrabold text-blue-700 tracking-tight"><?php echo e(Str::limit($landing->title ?? 'স্টোর', 20)); ?></span>
                <?php endif; ?>
            </a>

            <div class="flex-grow max-w-2xl hidden md:flex">
                <form action="<?php echo e(route('home')); ?>" method="GET" class="flex w-full border-2 border-blue-600 rounded-lg overflow-hidden">
                    <input type="hidden" name="search" value="1">
                    <input type="text" name="q" placeholder="প্রোডাক্ট সার্চ করুন..." class="w-full px-4 py-2 outline-none">
                    <button type="submit" class="bg-blue-600 text-white px-6 hover:bg-blue-700 transition"><i class="fa-solid fa-magnifying-glass"></i></button>
                </form>
            </div>

            <div class="flex items-center space-x-5 text-gray-600">
                <?php echo $__env->make('reseller.landing.partials.header-actions', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            </div>
        </div>

        <div class="p-3 md:hidden">
            <form action="<?php echo e(route('home')); ?>" method="GET" class="flex w-full border border-gray-300 rounded-lg overflow-hidden">
                <input type="hidden" name="search" value="1">
                <input type="text" name="q" placeholder="সার্চ করুন..." class="w-full px-3 py-2 outline-none text-sm">
                <button type="submit" class="bg-blue-600 text-white px-4"><i class="fa-solid fa-magnifying-glass"></i></button>
            </form>
        </div>
    </header>

    
    <?php if(!empty($landing->scrolling_text)): ?>
    <nav class="bg-blue-700 text-white overflow-hidden">
        <div class="container mx-auto px-4 py-3 flex items-center">
            <div class="flex-1 min-w-0 overflow-hidden">
                <div class="marquee-container flex items-center">
                    <div class="marquee-text flex items-center gap-6 whitespace-nowrap text-sm md:text-base">
                        <span class="inline-block animate-marquee"><?php echo e($landing->scrolling_text); ?></span>
                        <span class="inline-block animate-marquee" aria-hidden="true"><?php echo e($landing->scrolling_text); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </nav>
    <?php endif; ?>

    <div class="container mx-auto px-4 py-6 flex flex-col lg:flex-row gap-6">
        
        <aside class="w-full lg:w-1/4 hidden lg:block bg-white shadow-md rounded-lg p-4 h-fit">
            <h3 class="font-bold text-gray-800 mb-3 border-b pb-2"><i class="fa-solid fa-border-all mr-2 text-blue-600"></i> সব ক্যাটাগরি</h3>
            <ul class="space-y-1 text-gray-600 text-sm font-medium">
                <?php $__empty_1 = true; $__currentLoopData = ($categories ?? []); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <li class="border-b border-gray-100 last:border-0">
                        <div class="flex items-center">
                            <a href="<?php echo e(landing_url($landing->slug, 'category/'.$cat->slug)); ?>" class="hover:text-blue-600 transition flex items-center py-3 flex-1 min-w-0">
                                <span class="w-8 h-8 rounded bg-blue-100 flex items-center justify-center mr-3 flex-shrink-0 overflow-hidden p-0.5">
                                    <?php if(!empty($cat->icon) || !empty($cat->image)): ?>
                                        <img src="<?php echo e(asset($cat->icon ?? $cat->image)); ?>" alt="" class="w-full h-full object-contain" onerror="this.onerror=null; this.style.display='none'; this.nextElementSibling.classList.remove('hidden');">
                                        <i class="fa-solid fa-folder text-blue-600 text-sm hidden"></i>
                                    <?php else: ?>
                                        <i class="fa-solid fa-folder text-blue-600 text-sm"></i>
                                    <?php endif; ?>
                                </span>
                                <span class="flex-1"><?php echo e($cat->name); ?></span>
                            </a>
                            <?php if($cat->subcategories && $cat->subcategories->count() > 0): ?>
                                <button type="button" onclick="toggleSubcat('cat-<?php echo e($cat->id); ?>', this)" class="p-2 text-gray-400 hover:text-blue-600 transition flex-shrink-0" aria-label="টগল">
                                    <i class="fa-solid fa-chevron-right text-xs transition-transform duration-200 cat-toggle-icon"></i>
                                </button>
                            <?php endif; ?>
                        </div>
                        <?php if($cat->subcategories && $cat->subcategories->count() > 0): ?>
                            <ul id="cat-<?php echo e($cat->id); ?>" class="ml-11 mb-2 space-y-0.5 hidden">
                                <?php $__currentLoopData = $cat->subcategories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $sub): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <li>
                                        <a href="<?php echo e(landing_url($landing->slug, 'subcategory/'.$sub->slug)); ?>" class="block py-1.5 px-2 rounded hover:bg-blue-50 hover:text-blue-600 transition text-xs">
                                            <i class="fa-solid fa-angle-right text-gray-400 mr-2 w-3"></i>
                                            <?php echo e($sub->subcategoryName ?? $sub->name ?? 'Sub'); ?>

                                        </a>
                                    </li>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </ul>
                        <?php endif; ?>
                    </li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <li class="text-gray-500 text-sm py-2">ক্যাটাগরি নেই</li>
                <?php endif; ?>
            </ul>
            <a href="<?php echo e(landing_url($landing->slug, '')); ?>" class="block mt-4 text-blue-600 font-bold text-sm hover:underline">
                হোমে ফিরে যান <i class="fa-solid fa-angle-right ml-1"></i>
            </a>
        </aside>

        
        <main class="w-full lg:w-3/4">
            <?php if($landing->slider_images && count($landing->slider_images) > 0): ?>
                <div class="swiper mySwiper w-full h-[200px] md:h-[400px] rounded-xl overflow-hidden shadow-lg">
                    <div class="swiper-wrapper">
                        <?php $__currentLoopData = $landing->slider_images; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $idx => $img): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <div class="swiper-slide">
                                <div class="w-full h-full bg-cover bg-center" style="background-image: url('<?php echo e(asset($img)); ?>');">
                                    <div class="w-full h-full bg-black bg-opacity-40 flex flex-col justify-center px-8 md:px-16 text-white">
                                        <?php if($idx === 0): ?>
                                            <h2 class="text-2xl md:text-4xl font-bold mb-2"><?php echo e($landing->title); ?></h2>
                                            <?php if($landing->tagline): ?>
                                                <p class="mb-4 md:text-lg"><?php echo e($landing->tagline); ?></p>
                                            <?php endif; ?>
                                            <?php if($landing->phone): ?>
                                                <a href="tel:<?php echo e($landing->phone); ?>" class="bg-blue-600 hover:bg-blue-700 w-max px-6 py-2 rounded-full font-bold transition shadow-lg inline-flex items-center">
                                                    কল করুন <i class="fa-solid fa-arrow-right ml-1"></i>
                                                </a>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                    <?php if(count($landing->slider_images) > 1): ?>
                        <div class="swiper-button-next"></div>
                        <div class="swiper-button-prev"></div>
                        <div class="swiper-pagination"></div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="w-full h-[200px] md:h-[300px] rounded-xl overflow-hidden shadow-lg bg-gradient-to-r from-blue-700 to-blue-500 flex flex-col justify-center px-8 md:px-16 text-white">
                    <h2 class="text-2xl md:text-4xl font-bold mb-2"><?php echo e($landing->title ?? 'আমাদের স্টোর'); ?></h2>
                    <?php if($landing->tagline): ?>
                        <p class="mb-4 md:text-lg"><?php echo e($landing->tagline); ?></p>
                    <?php endif; ?>
                    <?php if($landing->phone): ?>
                        <a href="tel:<?php echo e($landing->phone); ?>" class="bg-white text-blue-700 hover:bg-gray-100 w-max px-6 py-2 rounded-full font-bold transition shadow-lg inline-flex items-center">
                            কল করুন <i class="fa-solid fa-phone ml-2"></i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            
            <?php if($landing->banner_image): ?>
                <div class="mt-6 rounded-xl overflow-hidden shadow-lg">
                    <img src="<?php echo e(asset($landing->banner_image)); ?>" alt="Banner" class="w-full h-auto max-h-[300px] object-cover">
                </div>
            <?php endif; ?>

            
            <div class="grid grid-cols-2 gap-4 mt-6">
                <div class="bg-gradient-to-r from-blue-600 to-blue-400 rounded-xl p-4 text-white flex items-center justify-between shadow">
                    <div>
                        <h4 class="font-bold text-lg">ফ্রি ডেলিভারি</h4>
                        <p class="text-xs text-blue-100">নির্দিষ্ট অর্ডারে</p>
                    </div>
                    <i class="fa-solid fa-truck-fast text-3xl opacity-50"></i>
                </div>
                <div class="bg-gradient-to-r from-orange-500 to-yellow-500 rounded-xl p-4 text-white flex items-center justify-between shadow">
                    <div>
                        <h4 class="font-bold text-lg">ক্যাশ অন ডেলিভারি</h4>
                        <p class="text-xs text-orange-100">সারা বাংলাদেশে</p>
                    </div>
                    <i class="fa-solid fa-money-bill-wave text-3xl opacity-50"></i>
                </div>
            </div>
        </main>
    </div>

    
    <?php if($products->count() > 0): ?>
    <section class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-6 border-b pb-2">
            <h2 class="text-xl md:text-2xl font-bold text-gray-800 border-b-4 border-blue-600 inline-block pb-1">রিসেলার হট প্রোডাক্টস</h2>
            <a href="<?php echo e(route('home')); ?>" class="text-blue-600 text-sm font-semibold hover:underline">সব দেখুন <i class="fa-solid fa-angle-right"></i></a>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4 md:gap-6">
            <?php $__currentLoopData = $products; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $product): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php
                    $img = $product->image && $product->image->image ? $product->image->image : 'public/uploads/default.webp';
                    $displayPrice = (float) (($customPrices[$product->id] ?? null) ?? $product->reseller_price ?? 0);
                ?>
                <div class="bg-white rounded-lg shadow hover:shadow-xl transition-shadow duration-300 relative group p-3">
                    <div class="relative overflow-hidden mb-3 rounded">
                        <a href="<?php echo e(landing_url($landing->slug, 'product/'.$product->slug)); ?>">
                            <img src="<?php echo e(asset($img)); ?>" alt="<?php echo e($product->name); ?>" class="w-full h-40 md:h-48 object-cover group-hover:scale-110 transition-transform duration-500">
                        </a>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-800 text-sm md:text-base leading-tight mb-2 line-clamp-2"><?php echo e($product->name); ?></h3>
                        <div class="bg-blue-50 p-2 rounded mb-3 border border-blue-100">
                            <div class="flex justify-between items-center">
                                <span class="font-bold text-blue-700 text-lg">৳<?php echo e(number_format($displayPrice, 0)); ?></span>
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <a href="<?php echo e(landing_url($landing->slug, 'product/'.$product->slug)); ?>" class="flex-1 bg-gray-200 text-gray-800 font-medium py-2 rounded text-sm hover:bg-gray-300 transition text-center"><i class="fa-solid fa-eye mr-1"></i> বিস্তারিত</a>
                            <form action="<?php echo e(landing_url($landing->slug, 'cart/add')); ?>" method="POST" class="flex-1">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="product_id" value="<?php echo e($product->id); ?>">
                                <input type="hidden" name="qty" value="1">
                                <button type="submit" class="w-full bg-blue-600 text-white font-medium py-2 rounded text-sm hover:bg-blue-700 transition"><i class="fa-solid fa-cart-plus mr-1"></i> অর্ডার</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    </section>
    <?php endif; ?>

    
    <footer class="relative mt-16 overflow-hidden">
        
        <div class="bg-gradient-to-b from-slate-900 via-slate-800 to-slate-900 text-gray-300">
            <div class="absolute inset-0 bg-[url('data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%234761c1\' fill-opacity=\'0.04\'%3E%3Cpath d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E')] opacity-50"></div>
            <div class="container mx-auto px-4 relative">
                <div class="pt-14 pb-12">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-10 lg:gap-12">
                        
                        <div class="space-y-4">
                            <?php if($landing->logo): ?>
                                <img src="<?php echo e(asset($landing->logo)); ?>" alt="<?php echo e($landing->title); ?>" class="h-14 object-contain">
                            <?php else: ?>
                                <h3 class="text-2xl font-bold text-white tracking-tight"><?php echo e($landing->title); ?></h3>
                            <?php endif; ?>
                            <p class="text-sm text-gray-400 leading-relaxed max-w-xs"><?php echo e($landing->tagline ?? 'আপনার বিশ্বস্ত শপিং পার্টনার।'); ?></p>
                            <?php if(!empty($landing->facebook_url) || !empty($landing->twitter_url) || !empty($landing->whatsapp_url) || !empty($landing->youtube_url) || !empty($landing->instagram_url)): ?>
                            <div class="flex flex-wrap gap-2 pt-1">
                                <?php if(!empty($landing->facebook_url)): ?>
                                    <a href="<?php echo e($landing->facebook_url); ?>" target="_blank" rel="noopener" class="w-10 h-10 flex items-center justify-center rounded-lg bg-white/5 text-gray-400 hover:bg-[#1877f2] hover:text-white transition" title="Facebook"><i class="fab fa-facebook-f"></i></a>
                                <?php endif; ?>
                                <?php if(!empty($landing->twitter_url)): ?>
                                    <a href="<?php echo e($landing->twitter_url); ?>" target="_blank" rel="noopener" class="w-10 h-10 flex items-center justify-center rounded-lg bg-white/5 text-gray-400 hover:bg-[#1da1f2] hover:text-white transition" title="Twitter"><i class="fab fa-twitter"></i></a>
                                <?php endif; ?>
                                <?php if(!empty($landing->whatsapp_url)): ?>
                                    <a href="<?php echo e((str_starts_with($landing->whatsapp_url ?? '', 'http') ? $landing->whatsapp_url : 'https://wa.me/' . preg_replace('/\D/', '', $landing->whatsapp_url))); ?>" target="_blank" rel="noopener" class="w-10 h-10 flex items-center justify-center rounded-lg bg-white/5 text-gray-400 hover:bg-[#25d366] hover:text-white transition" title="WhatsApp"><i class="fab fa-whatsapp"></i></a>
                                <?php endif; ?>
                                <?php if(!empty($landing->youtube_url)): ?>
                                    <a href="<?php echo e($landing->youtube_url); ?>" target="_blank" rel="noopener" class="w-10 h-10 flex items-center justify-center rounded-lg bg-white/5 text-gray-400 hover:bg-[#ff0000] hover:text-white transition" title="YouTube"><i class="fab fa-youtube"></i></a>
                                <?php endif; ?>
                                <?php if(!empty($landing->instagram_url)): ?>
                                    <a href="<?php echo e($landing->instagram_url); ?>" target="_blank" rel="noopener" class="w-10 h-10 flex items-center justify-center rounded-lg bg-white/5 text-gray-400 hover:bg-gradient-to-br hover:from-[#f09433] hover:via-[#dc2743] hover:to-[#bc1888] hover:text-white transition" title="Instagram"><i class="fab fa-instagram"></i></a>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                        </div>

                        
                        <div>
                            <h4 class="text-white font-semibold mb-5 text-sm uppercase tracking-widest">যোগাযোগ</h4>
                            <ul class="space-y-4 text-sm">
                                <?php if($landing->phone): ?>
                                    <li class="flex items-start gap-3 group">
                                        <span class="w-9 h-9 rounded-lg bg-white/5 flex items-center justify-center text-indigo-400 group-hover:bg-indigo-500/20 transition shrink-0"><i class="fa-solid fa-phone text-xs"></i></span>
                                        <a href="tel:<?php echo e($landing->phone); ?>" class="text-gray-400 hover:text-indigo-400 transition"><?php echo e($landing->phone); ?></a>
                                    </li>
                                <?php endif; ?>
                                <?php if($landing->email): ?>
                                    <li class="flex items-start gap-3 group">
                                        <span class="w-9 h-9 rounded-lg bg-white/5 flex items-center justify-center text-indigo-400 group-hover:bg-indigo-500/20 transition shrink-0"><i class="fa-solid fa-envelope text-xs"></i></span>
                                        <a href="mailto:<?php echo e($landing->email); ?>" class="text-gray-400 hover:text-indigo-400 transition break-all"><?php echo e($landing->email); ?></a>
                                    </li>
                                <?php endif; ?>
                                <?php if($landing->address): ?>
                                    <li class="flex items-start gap-3 group">
                                        <span class="w-9 h-9 rounded-lg bg-white/5 flex items-center justify-center text-indigo-400 group-hover:bg-indigo-500/20 transition shrink-0"><i class="fa-solid fa-location-dot text-xs"></i></span>
                                        <span class="text-gray-400"><?php echo e($landing->address); ?></span>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </div>

                        
                        <div>
                            <h4 class="text-white font-semibold mb-5 text-sm uppercase tracking-widest">দ্রুত লিংক</h4>
                            <ul class="space-y-3">
                                <li><a href="<?php echo e(route('page', 'terms-and-conditions')); ?>" class="text-gray-400 hover:text-indigo-400 hover:pl-2 transition-all inline-block">টার্মস অ্যান্ড কন্ডিশন</a></li>
                                <li><a href="<?php echo e(route('page', 'privacy-policy')); ?>" class="text-gray-400 hover:text-indigo-400 hover:pl-2 transition-all inline-block">প্রাইভেসি পলিসি</a></li>
                                <li><a href="<?php echo e(route('contact')); ?>" class="text-gray-400 hover:text-indigo-400 hover:pl-2 transition-all inline-block">কন্টাক্ট পেইজ</a></li>
                            </ul>
                        </div>

                        
                        <?php if(($landing->show_newsletter_footer ?? true) || ($landing->show_social_footer ?? true)): ?>
                        <div class="space-y-6">
                            <?php if($landing->show_newsletter_footer ?? true): ?>
                            <div>
                                <h4 class="text-white font-semibold mb-3 text-sm uppercase tracking-widest">Newsletter</h4>
                                <p class="text-gray-400 text-sm mb-4">অফার ও আপডেটের জন্য সাবস্ক্রাইব করুন।</p>
                                <form action="<?php echo e(route('reseller.landing.newsletter.subscribe', $landing->slug)); ?>" method="POST" class="space-y-3">
                                    <?php echo csrf_field(); ?>
                                    <input type="email" name="email" required placeholder="আপনার ইমেইল..." class="w-full px-4 py-3 bg-white/5 border border-white/10 rounded-xl text-white placeholder-gray-500 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30 outline-none transition">
                                    <button type="submit" class="w-full px-6 py-3 bg-indigo-600 hover:bg-indigo-500 text-white font-medium rounded-xl transition flex items-center justify-center gap-2 shadow-lg shadow-indigo-500/25">
                                        <i class="fa-solid fa-paper-plane"></i> Subscribe
                                    </button>
                                </form>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        
        <div class="bg-slate-950 border-t border-white/5">
            <div class="container mx-auto px-4 py-5">
                <p class="text-center text-sm text-gray-500">
                    &copy; <?php echo e(date('Y')); ?> <?php echo e($landing->title); ?>. Powered by <span class="text-gray-400"><?php echo e(config('app.name', 'Creative Design')); ?></span>.
                </p>
            </div>
        </div>
    </footer>

    
    <div id="categoryOverlay" class="fixed inset-0 bg-black bg-opacity-50 z-[60] hidden" onclick="toggleCategoryMenu()"></div>
    <div id="categoryDrawer" class="fixed top-0 left-0 w-3/4 max-w-[300px] h-full bg-white z-[70] transform -translate-x-full transition-transform duration-300 shadow-2xl overflow-y-auto">
        <div class="bg-blue-700 text-white p-4 flex justify-between items-center sticky top-0 z-10">
            <h3 class="font-bold text-lg"><i class="fa-solid fa-border-all mr-2"></i> সব ক্যাটাগরি</h3>
            <button type="button" onclick="toggleCategoryMenu()" class="text-white hover:text-red-300 text-2xl leading-none">&times;</button>
        </div>
        <ul class="py-2 text-gray-700 font-medium text-sm">
            <?php $__currentLoopData = ($categories ?? []); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <li class="border-b border-gray-100">
                    <div class="flex items-center">
                        <a href="<?php echo e(landing_url($landing->slug, 'category/'.$cat->slug)); ?>" onclick="toggleCategoryMenu()" class="flex-1 flex items-center px-4 py-3 hover:bg-blue-50 hover:text-blue-600 transition">
                        <span class="w-7 h-7 rounded bg-blue-100 flex items-center justify-center mr-3 flex-shrink-0 overflow-hidden p-0.5">
                            <?php if(!empty($cat->icon) || !empty($cat->image)): ?>
                                <img src="<?php echo e(asset($cat->icon ?? $cat->image)); ?>" alt="" class="w-full h-full object-contain" onerror="this.onerror=null; this.style.display='none'; this.nextElementSibling.classList.remove('hidden');">
                                <i class="fa-solid fa-folder text-blue-600 text-xs hidden"></i>
                            <?php else: ?>
                                <i class="fa-solid fa-folder text-blue-600 text-xs"></i>
                            <?php endif; ?>
                        </span>
                            <?php echo e($cat->name); ?>

                        </a>
                        <?php if($cat->subcategories && $cat->subcategories->count() > 0): ?>
                            <button type="button" onclick="event.stopPropagation(); toggleSubcat('mcat-<?php echo e($cat->id); ?>', this)" class="p-3 text-gray-400 hover:text-blue-600 transition">
                                <i class="fa-solid fa-chevron-right text-xs transition-transform duration-200 cat-toggle-icon"></i>
                            </button>
                        <?php endif; ?>
                    </div>
                    <?php if($cat->subcategories && $cat->subcategories->count() > 0): ?>
                        <ul id="mcat-<?php echo e($cat->id); ?>" class="bg-gray-50 py-1 hidden">
                            <?php $__currentLoopData = $cat->subcategories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $sub): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <li>
                                    <a href="<?php echo e(landing_url($landing->slug, 'subcategory/'.$sub->slug)); ?>" onclick="toggleCategoryMenu()" class="block py-2 px-4 pl-12 hover:bg-blue-50 hover:text-blue-600 transition text-xs">
                                        <i class="fa-solid fa-angle-right text-gray-400 mr-2"></i>
                                        <?php echo e($sub->subcategoryName ?? $sub->name ?? 'Sub'); ?>

                                    </a>
                                </li>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </ul>
                    <?php endif; ?>
                </li>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            <li><a href="<?php echo e(landing_url($landing->slug, '')); ?>" onclick="toggleCategoryMenu()" class="block px-4 py-3 text-blue-600 font-bold hover:bg-blue-50">হোম <i class="fa-solid fa-angle-right ml-1"></i></a></li>
        </ul>
    </div>

    
    <div class="fixed bottom-0 left-0 w-full bg-white shadow-[0_-5px_10px_rgba(0,0,0,0.05)] z-50 md:hidden border-t border-gray-200">
        <div class="flex justify-around items-center py-2 text-gray-500 text-[10px]">
            <?php echo $__env->make('reseller.landing.partials.mobile-bottom-nav', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        </div>
    </div>

    <?php if($landing->slider_images && count($landing->slider_images) > 1): ?>
    <script src="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.js"></script>
    <script>
        var swiper = new Swiper(".mySwiper", {
            spaceBetween: 30,
            centeredSlides: true,
            autoplay: { delay: 4500, disableOnInteraction: false },
            pagination: { el: ".swiper-pagination", clickable: true },
            navigation: {
                nextEl: ".swiper-button-next",
                prevEl: ".swiper-button-prev",
            },
        });
    </script>
    <?php endif; ?>
    <script>
        function toggleSubcat(id, btn) {
            var el = document.getElementById(id);
            var icon = btn ? btn.querySelector('i') : null;
            if (el && el.classList.contains('hidden')) {
                el.classList.remove('hidden');
                if (icon) icon.style.transform = 'rotate(90deg)';
            } else if (el) {
                el.classList.add('hidden');
                if (icon) icon.style.transform = '';
            }
        }
        function toggleCategoryMenu() {
            var overlay = document.getElementById('categoryOverlay');
            var drawer = document.getElementById('categoryDrawer');
            if (overlay.classList.contains('hidden')) {
                overlay.classList.remove('hidden');
                drawer.classList.remove('-translate-x-full');
                document.body.style.overflow = 'hidden';
            } else {
                overlay.classList.add('hidden');
                drawer.classList.add('-translate-x-full');
                document.body.style.overflow = '';
            }
        }
    </script>
</body>
</html>
<?php /**PATH C:\laragon\www\bilaiGhor\resources\views\reseller\landing\public.blade.php ENDPATH**/ ?>