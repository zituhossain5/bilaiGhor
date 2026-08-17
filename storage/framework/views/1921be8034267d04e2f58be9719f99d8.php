<?php
    $subtotal = Cart::instance('shopping')->subtotal();
    $subtotal=str_replace(',','',$subtotal);
    $subtotal=str_replace('.00', '',$subtotal);
    view()->share('subtotal',$subtotal);
    $shipping = Session::get('shipping')?Session::get('shipping'):0;
    $discount = Session::get('discount')?Session::get('discount'):0;
?>

<h5>Cart Summary</h5>
    <table class="table">
        <tbody>
            <tr>
                <td>Items</td>
                <td><?php echo e(Cart::instance('shopping')->count()); ?> (qty)</td>
            </tr>
            <tr>
                <td>Total</td>
                <td>৳<?php echo e($subtotal); ?></td>
            </tr>
            <tr>
                <td>Shipping</td>
                <td>৳<?php echo e($shipping); ?></td>
            </tr>
            <tr>
                <td>Discount</td>
                <td>৳<?php echo e($discount); ?></td>
            </tr>
            <tr>
                <td>Total</td>
                <td>৳<?php echo e(($subtotal+$shipping) - $discount); ?></td>
            </tr>
        </tbody>
    </table><?php /**PATH C:\laragon\www\bilaiGhor\resources\views\frontEnd\layouts\ajax\shipping_charge.blade.php ENDPATH**/ ?>