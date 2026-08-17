<div class="modal-view quick-product">
	<button class="close-modal">x</button>
	<div class="quick-product-img">
		<img src="<?php echo e(asset($data->image->image)); ?>" alt="">
	</div>
	<div class="quick-product-content">
		<div class="product-details-cart">
            <p class="name"><?php echo e($data->name); ?></p>
             <p style="display: none;" class="product_star"><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i> (<?php echo e($data->reviews_count); ?> customer review)</p>
            <p class="details-price">৳<?php echo e($data->new_price); ?> <?php if($data->old_price): ?><del>৳<?php echo e($data->old_price); ?></del><?php endif; ?></p>
            <div class="details_short">
                <?php echo $data->short_description; ?>

            </div>
            <form action="<?php echo e(route('cart.store')); ?>" method="POST">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="id" value="<?php echo e($data->id); ?>">                
                
                <div class="qty-cart">
                    <div class="quantity">
                        <span class="minus">-</span>
                        <input type="text" name="qty" value="1"/>
                        <span class="plus">+</span>
                    </div>
                    <button type="submit" class="add-to-cart cart_store" data-id="<?php echo e($data->id); ?>">add to cart</button>
                </div>
            </form>
            <a href="<?php echo e(route('product',['id'=>$data->id])); ?>" style="display: none;" class="details-wishlist">Go To Details</a>
            <div class="col-12 mt-3 delivery_details">
                <table class="table">
                    <tbody>                                    
                        <tr>
                            <td class="potro_font">
                               Category: <?php echo e($data->category->name); ?>

                            </td>                                        
                        </tr>
                        <tr>
                            <td class="potro_font">
                               Brand: <?php echo e($data->brand ? $data->brand->name : ''); ?>

                            </td>                                        
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
	</div>
</div>
<script src="<?php echo e(asset('public/frontEnd/js/jquery-3.6.3.min.js')); ?>"></script>
<script>
	$('.close-modal').on('click',function(){
        $("#custom-modal").hide();
        $("#page-overlay").hide();
     });
</script>
<script>
    $(document).ready(function() {
        $('.minus').click(function () {
            var $input = $(this).parent().find('input');
            var count = parseInt($input.val()) - 1;
            count = count < 1 ? 1 : count;
            $input.val(count);
            $input.change();
            return false;
        });
        $('.plus').click(function () {
            var $input = $(this).parent().find('input');
            $input.val(parseInt($input.val()) + 1);
            $input.change();
            return false;
        });
    });
</script>
<!-- cart js start --><?php /**PATH C:\laragon\www\bilaiGhor\resources\views\frontEnd\layouts\ajax\quickview.blade.php ENDPATH**/ ?>