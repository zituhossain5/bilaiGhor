
<form action="" method="GET" class="bilai-cat-filter-form" id="bilaiCatFilterForm">
    
    <?php if(request('sort')): ?>
    <input type="hidden" name="sort" value="<?php echo e(request('sort')); ?>">
    <?php endif; ?>
    <?php if(!empty($activeSubcatSlug)): ?>
    <input type="hidden" name="subcategory" value="<?php echo e($activeSubcatSlug); ?>">
    <?php endif; ?>
    <?php if($activeBrandId): ?>
    <input type="hidden" name="brand" value="<?php echo e($activeBrandId); ?>">
    <?php endif; ?>

    
    <div class="bilai-cat-filter-block">
        <div class="bilai-cat-filter-title">Filter by Price</div>
        <div id="bilai-price-range" class="bilai-price-slider"></div>
        <div class="bilai-cat-price-display">
            Price: <strong>&#2547;<span id="bilai-min-val"><?php echo e(request('min_price', $min_price)); ?></span></strong>
            &nbsp;–&nbsp;
            <strong>&#2547;<span id="bilai-max-val"><?php echo e(request('max_price', $max_price)); ?></span></strong>
        </div>
        <input type="hidden" name="min_price" id="bilai_min_price" value="<?php echo e(request('min_price', $min_price)); ?>">
        <input type="hidden" name="max_price" id="bilai_max_price" value="<?php echo e(request('max_price', $max_price)); ?>">
    </div>

    
    <?php if($brands->count() > 0): ?>
    <div class="bilai-cat-filter-block">
        <div class="bilai-cat-filter-title bilai-cat-filter-toggle" data-target="bilai-brand-list">
            Brand <i class="fas fa-chevron-up bilai-cat-toggle-icon"></i>
        </div>
        <div class="bilai-cat-filter-body" id="bilai-brand-list">
            <?php if($brands->count() > 1): ?>
            <div class="bilai-cat-filter-search">
                <input type="text" class="bilai-brand-search-input" id="bilaiBrandSearch"
                       placeholder="Find a Brand" autocomplete="off" aria-label="Search brands">
                <i class="fas fa-search"></i>
            </div>
            <?php endif; ?>
            <ul class="bilai-cat-attr-link-list" id="bilaiBrandList">
                <?php $__currentLoopData = $brands; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $brand): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php
                    $isBrandActive = (string)$activeBrandId === (string)$brand->id;
                    $brandParams    = array_merge(request()->except(['brand', 'page']), $isBrandActive ? [] : ['brand' => $brand->id]);
                    $brandUrl       = $filterBaseUrl . '?' . http_build_query($brandParams);
                ?>
                <li>
                    <a href="<?php echo e($brandUrl); ?>" class="bilai-cat-attr-link <?php echo e($isBrandActive ? 'active' : ''); ?>">
                        <span class="bilai-cat-attr-name"><?php echo e($brand->name); ?></span>
                        <span class="bilai-cat-count-badge"><?php echo e(str_pad($brandCountMap[$brand->id] ?? 0, 2, '0', STR_PAD_LEFT)); ?></span>
                    </a>
                </li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </ul>
        </div>
    </div>
    <?php endif; ?>

    
    <?php if($weights->count() > 0): ?>
    <div class="bilai-cat-filter-block">
        <div class="bilai-cat-filter-title bilai-cat-filter-toggle" data-target="bilai-weight-list">
            Weight <i class="fas fa-chevron-up bilai-cat-toggle-icon"></i>
        </div>
        <div class="bilai-cat-filter-body" id="bilai-weight-list">
            <ul class="bilai-cat-check-list">
                <?php $__currentLoopData = $weights; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $w): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <li>
                    <label class="bilai-cat-check-label">
                        <input type="checkbox" name="weight[]" value="<?php echo e($w->id); ?>"
                            class="bilai-cat-auto-submit"
                            <?php if(in_array($w->id, $selectedWeights)): ?> checked <?php endif; ?>>
                        <span class="bilai-cat-check-name"><?php echo e($w->name); ?></span>
                        <span class="bilai-cat-count-badge"><?php echo e(str_pad($weightCountMap[$w->id] ?? 0, 2, '0', STR_PAD_LEFT)); ?></span>
                    </label>
                </li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </ul>
        </div>
    </div>
    <?php endif; ?>

    
    <?php if($lifeStages->count() > 0): ?>
    <div class="bilai-cat-filter-block">
        <div class="bilai-cat-filter-title bilai-cat-filter-toggle" data-target="bilai-lifestage-list">
            Life Stage <i class="fas fa-chevron-up bilai-cat-toggle-icon"></i>
        </div>
        <div class="bilai-cat-filter-body" id="bilai-lifestage-list">
            <ul class="bilai-cat-check-list">
                <?php $__currentLoopData = $lifeStages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ls): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <li>
                    <label class="bilai-cat-check-label">
                        <input type="checkbox" name="life_stage[]" value="<?php echo e($ls->id); ?>"
                            class="bilai-cat-auto-submit"
                            <?php if(in_array($ls->id, $selectedLifeStages)): ?> checked <?php endif; ?>>
                        <span class="bilai-cat-check-name"><?php echo e($ls->name); ?></span>
                        <span class="bilai-cat-count-badge"><?php echo e(str_pad($lifeStageCountMap[$ls->id] ?? 0, 2, '0', STR_PAD_LEFT)); ?></span>
                    </label>
                </li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </ul>
        </div>
    </div>
    <?php endif; ?>

    
    <?php if($flavors->count() > 0): ?>
    <div class="bilai-cat-filter-block">
        <div class="bilai-cat-filter-title bilai-cat-filter-toggle" data-target="bilai-flavor-list">
            Flavor <i class="fas fa-chevron-up bilai-cat-toggle-icon"></i>
        </div>
        <div class="bilai-cat-filter-body" id="bilai-flavor-list">
            <ul class="bilai-cat-check-list">
                <?php $__currentLoopData = $flavors; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $fl): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <li>
                    <label class="bilai-cat-check-label">
                        <input type="checkbox" name="flavor[]" value="<?php echo e($fl->id); ?>"
                            class="bilai-cat-auto-submit"
                            <?php if(in_array($fl->id, $selectedFlavors)): ?> checked <?php endif; ?>>
                        <span class="bilai-cat-check-name"><?php echo e($fl->name); ?></span>
                        <span class="bilai-cat-count-badge"><?php echo e(str_pad($flavorCountMap[$fl->id] ?? 0, 2, '0', STR_PAD_LEFT)); ?></span>
                    </label>
                </li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </ul>
        </div>
    </div>
    <?php endif; ?>

</form>
<?php /**PATH D:\projects\bilaiGhor\resources\views/frontEnd/layouts/partials/listing-filter-sidebar.blade.php ENDPATH**/ ?>