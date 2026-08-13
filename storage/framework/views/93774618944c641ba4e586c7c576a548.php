
<div class="bilai-cat-topbar">
    <p class="bilai-cat-count">
        <?php if($products->total() > 0): ?>
            Showing <?php echo e($products->firstItem()); ?>–<?php echo e($products->lastItem()); ?> of <?php echo e($products->total()); ?> results
        <?php else: ?>
            No products found
        <?php endif; ?>
    </p>
    <form action="" method="GET" id="bilaiSortForm">
        
        <?php $__currentLoopData = request()->except(['sort', 'page']); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $val): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php if(is_array($val)): ?>
                <?php $__currentLoopData = $val; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $v): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <input type="hidden" name="<?php echo e($key); ?>[]" value="<?php echo e($v); ?>">
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            <?php else: ?>
            <input type="hidden" name="<?php echo e($key); ?>" value="<?php echo e($val); ?>">
            <?php endif; ?>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        <select name="sort" class="bilai-cat-sort-select" id="bilaiSortSelect">
            <option value="1" <?php if(request('sort')==1): ?> selected <?php endif; ?>>Sort by Latest</option>
            <option value="2" <?php if(request('sort')==2): ?> selected <?php endif; ?>>Oldest First</option>
            <option value="3" <?php if(request('sort')==3): ?> selected <?php endif; ?>>Price: High to Low</option>
            <option value="4" <?php if(request('sort')==4): ?> selected <?php endif; ?>>Price: Low to High</option>
            <option value="5" <?php if(request('sort')==5): ?> selected <?php endif; ?>>Name: A–Z</option>
            <option value="6" <?php if(request('sort')==6): ?> selected <?php endif; ?>>Name: Z–A</option>
        </select>
    </form>
</div>
<?php /**PATH C:\laragon\www\bilaiGhor\resources\views/frontEnd/layouts/partials/listing-topbar.blade.php ENDPATH**/ ?>