<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>যোগাযোগ | <?php echo e($landing->title); ?></title>
    <?php if(!empty($landing->favicon)): ?><link rel="icon" type="<?php echo e(str_ends_with($landing->favicon, '.ico') ? 'image/x-icon' : 'image/png'); ?>" href="<?php echo e(asset($landing->favicon)); ?>"><?php endif; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <?php echo $__env->make('reseller.landing.partials.tracking-head', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
</head>
<body class="bg-gray-100 font-sans text-gray-800 pb-20 md:pb-0">
    <?php echo $__env->make('reseller.landing.partials.tracking-body', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <div class="bg-gray-900 text-gray-300 text-xs py-2 px-4">
        <div class="container mx-auto flex justify-between">
            <?php if($landing->phone): ?><a href="tel:<?php echo e($landing->phone); ?>" class="hover:text-white"><i class="fa-solid fa-phone mr-1"></i> <?php echo e($landing->phone); ?></a><?php endif; ?>
            <?php if($landing->email): ?><a href="mailto:<?php echo e($landing->email); ?>" class="hover:text-white"><i class="fa-solid fa-envelope mr-1"></i> <?php echo e($landing->email); ?></a><?php endif; ?>
            <div class="flex gap-4">
                <a href="<?php echo e(landing_url($landing->slug, '')); ?>" class="hover:text-white transition">হোম</a>
                <span>|</span>
                <a href="<?php echo e(route('reseller.landing.contact', $landing->slug)); ?>" class="hover:text-white transition font-semibold">যোগাযোগ</a>
            </div>
        </div>
    </div>

    <header class="bg-white shadow-sm">
        <div class="container mx-auto px-4 py-4 flex justify-between items-center">
            <a href="<?php echo e(landing_url($landing->slug, '')); ?>">
                <?php if($landing->logo): ?><img src="<?php echo e(asset($landing->logo)); ?>" alt="" class="h-10 w-auto"><?php else: ?><span class="text-xl font-bold text-blue-700"><?php echo e($landing->title); ?></span><?php endif; ?>
            </a>
            <a href="<?php echo e(landing_url($landing->slug, '')); ?>" class="text-blue-600 font-semibold hover:text-blue-700"><i class="fa-solid fa-arrow-left mr-1"></i> হোমে ফিরে যান</a>
        </div>
    </header>

    <div class="container mx-auto px-4 py-8 max-w-4xl">
        <h1 class="text-xl md:text-2xl font-bold text-gray-800 mb-6 flex items-center">
            <i class="fa-solid fa-address-card mr-2 text-blue-600"></i> যোগাযোগ
        </h1>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            
            <div class="bg-white rounded-xl shadow-md p-6">
                <h2 class="font-bold text-gray-800 mb-4">আমাদের সাথে যোগাযোগ করুন</h2>
                <div class="space-y-4">
                    <?php if($landing->phone): ?>
                    <div class="flex items-start gap-4">
                        <span class="w-12 h-12 rounded-lg bg-blue-100 flex items-center justify-center text-blue-600 shrink-0"><i class="fa-solid fa-phone"></i></span>
                        <div>
                            <p class="font-semibold text-gray-800">ফোন</p>
                            <a href="tel:<?php echo e($landing->phone); ?>" class="text-gray-600 hover:text-blue-600"><?php echo e($landing->phone); ?></a>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if($landing->email): ?>
                    <div class="flex items-start gap-4">
                        <span class="w-12 h-12 rounded-lg bg-blue-100 flex items-center justify-center text-blue-600 shrink-0"><i class="fa-solid fa-envelope"></i></span>
                        <div>
                            <p class="font-semibold text-gray-800">ইমেইল</p>
                            <a href="mailto:<?php echo e($landing->email); ?>" class="text-gray-600 hover:text-blue-600"><?php echo e($landing->email); ?></a>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if($landing->address): ?>
                    <div class="flex items-start gap-4">
                        <span class="w-12 h-12 rounded-lg bg-blue-100 flex items-center justify-center text-blue-600 shrink-0"><i class="fa-solid fa-location-dot"></i></span>
                        <div>
                            <p class="font-semibold text-gray-800">ঠিকানা</p>
                            <p class="text-gray-600"><?php echo e($landing->address); ?></p>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            
            <div class="bg-white rounded-xl shadow-md p-6">
                <h2 class="font-bold text-gray-800 mb-4">মেসেজ পাঠান</h2>
                <?php if(session('success')): ?>
                    <div class="mb-4 p-4 bg-green-100 text-green-800 rounded-lg"><?php echo e(session('success')); ?></div>
                <?php endif; ?>
                <form action="<?php echo e(route('reseller.landing.contact.store', $landing->slug)); ?>" method="POST" class="space-y-4">
                    <?php echo csrf_field(); ?>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">নাম <span class="text-red-500">*</span></label>
                        <input type="text" name="full_name" value="<?php echo e(old('full_name')); ?>" required class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500" placeholder="আপনার নাম">
                        <?php $__errorArgs = ['full_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="text-red-500 text-xs mt-1"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">মোবাইল <span class="text-red-500">*</span></label>
                        <input type="text" name="mobile" value="<?php echo e(old('mobile')); ?>" required class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500" placeholder="01XXXXXXXXX">
                        <?php $__errorArgs = ['mobile'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="text-red-500 text-xs mt-1"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">ইমেইল</label>
                        <input type="email" name="email" value="<?php echo e(old('email')); ?>" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500" placeholder="email@example.com">
                        <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="text-red-500 text-xs mt-1"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">বিষয়</label>
                        <input type="text" name="subject" value="<?php echo e(old('subject')); ?>" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500" placeholder="বিষয়">
                        <?php $__errorArgs = ['subject'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="text-red-500 text-xs mt-1"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">মেসেজ <span class="text-red-500">*</span></label>
                        <textarea name="details" rows="4" required class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500" placeholder="আপনার মেসেজ লিখুন"><?php echo e(old('details')); ?></textarea>
                        <?php $__errorArgs = ['details'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="text-red-500 text-xs mt-1"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                    <button type="submit" class="w-full py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition">
                        <i class="fa-solid fa-paper-plane mr-2"></i> পাঠান
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="fixed bottom-0 left-0 w-full bg-white border-t z-50 md:hidden py-3 flex justify-center">
        <a href="<?php echo e(landing_url($landing->slug, '')); ?>" class="text-blue-600 font-semibold"><i class="fa-solid fa-house mr-1"></i> হোমে ফিরে যান</a>
    </div>
</body>
</html>
<?php /**PATH C:\laragon\www\bilaiGhor\resources\views\reseller\landing\contact.blade.php ENDPATH**/ ?>