<?php if($products): ?>

<div class="search_product">
		<ul>
		<?php $__currentLoopData = $products; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
		<a href="<?php echo e(route('product',$value->slug)); ?>">
			<li>
					<div class="search_img">
						<img src="<?php echo e(asset($value->image?$value->image->image:'')); ?>" alt="<?php echo e($value->name); ?>">
					</div>
					<div class="search_content">
						<p class="name"><?php echo e($value->name); ?></p>                 
						<p  class="price">৳<?php echo e($value->new_price); ?> <?php if($value->old_price): ?><del>৳<?php echo e($value->old_price); ?></del><?php endif; ?></p>
					</div>
			</li>
		</a>
		<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
	</ul>
</div>
<?php endif; ?><?php /**PATH C:\laragon\www\bilaiGhor\resources\views\frontEnd\layouts\ajax\search.blade.php ENDPATH**/ ?>