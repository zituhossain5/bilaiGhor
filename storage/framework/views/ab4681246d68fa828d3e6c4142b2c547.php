
<?php $__env->startSection('title','Customer Invoice'); ?>
<?php $__env->startSection('content'); ?>

<?php
    // ১. পেমেন্ট ইনফো নেওয়া (Latest payment)
    $payment = \App\Models\Payment::where('order_id', $order->id)->orderBy('id','desc')->first();

    // ২. স্ট্যাটাস লোয়ারকেস করা
    $gateway_status = $payment ? strtolower(trim($payment->payment_status)) : ''; 
    $payment_method = $payment ? strtolower(trim($payment->payment_method)) : strtolower(trim($order->payment_method ?? ''));
    
    $admin_status   = strtolower(trim($order->payment_status ?? ''));
    $order_status   = strtolower(trim($order->status ?? ''));

    // ৩. গ্র্যান্ড টোটাল
    $grand_total = $order->amount;
    $paid_amount = 0;

    // ======================================================
    // ⭐ ইনভয়েস ক্যালকুলেশন লজিক (Exact Logic from Account Page)
    // ======================================================

    // ১. পেমেন্ট রেকর্ড থেকে আসল টাকাটা বের করি
    if ($payment && !in_array($gateway_status, ['failed', 'cancel', 'cancelled', 'rejected'])) {
        $paid_amount = $payment->amount;
    }

    // ২. COD ফিক্স: COD হলে এবং অর্ডার কমপ্লিট না হলে টাকা ০ দেখাবে (ভুল এড়াতে)
    $is_cod = in_array($payment_method, ['cod', 'cash', 'cash_on_delivery', 'hand cash']);
    $is_order_completed = in_array($order_status, ['completed', 'delivered']) || in_array($admin_status, ['completed', 'delivered']);

    if ($is_cod && !$is_order_completed) {
        if ($paid_amount >= $grand_total) {
            $paid_amount = 0; 
        }
    }

    // ৩. ফোর্স ফুল পেইড (Admin Priority):
    if ($is_order_completed) {
        $paid_amount = $grand_total;
    } 
    elseif (($paid_amount == 0 || !$payment) && in_array($admin_status, ['paid', 'success', 'approved'])) {
        $paid_amount = $grand_total;
    }

    // ৪. ডিউ ক্যালকুলেশন
    $due_amount = max(0, $grand_total - $paid_amount);

    // ৫. স্ট্যাটাস চেক (ডিসপ্লে এর জন্য)
    $is_failed = false;
    if ($paid_amount == 0 && in_array($gateway_status, ['failed', 'cancel', 'cancelled'])) {
        $is_failed = true;
    }
?>

<style>
    .customer-invoice { margin: 25px 0; }
    .invoice_btn{ margin-bottom: 15px; }
    td{ font-size: 16px; }

   @page { size: a4;  margin: 0mm; background:#F9F9F9 }
   @media print {
        td{ font-size: 18px; }
        header,footer,.no-print { display: none !important; }
   }
</style>

<section class="customer-invoice">
    <div class="container">
        <div class="row">

            <div class="col-sm-6">
                <a href="<?php echo e(route('customer.orders')); ?>">
                    <strong><i class="fa-solid fa-arrow-left"></i> Back To Order</strong>
                </a>
            </div>

            <div class="col-sm-6 text-end">
                <button onclick="printFunction()" class="no-print invoice_btn btn btn-primary">
                    <i class="fa fa-print"></i> Print
                </button>
            </div>

            <div class="col-sm-12">

                <div class="invoice-innter" style="width: 900px;margin: 0 auto;background: #f9f9f9;overflow: hidden;padding: 30px;padding-top: 0;">

                    
                    <table style="width:100%">
                        <tr>
                            <td style="width: 40%; float: left; padding-top: 15px;">

                                <img src="<?php echo e(asset($generalsetting->white_logo)); ?>" style="margin-top:25px !important;width:150px">

                                <div style="margin: 20px 0;">
                                    <p style="font-size: 14px; color: #222; margin-bottom: 5px;">
                                        <strong>Payment Method:</strong> 
                                        <span style="text-transform: uppercase;"><?php echo e($payment_method); ?></span>
                                    </p>
                                    
                                    
                                    <p style="font-size: 14px; color: #222;">
                                        <strong>Status:</strong>
                                        <?php if($paid_amount >= $grand_total): ?>
                                            <span style="color: green; font-weight: bold; text-transform: uppercase;">PAID</span>
                                        <?php elseif($is_failed): ?>
                                            <span style="color: red; font-weight: bold; text-transform: uppercase;">FAILED</span>
                                        <?php elseif($paid_amount > 0): ?>
                                            <span style="color: #007bff; font-weight: bold; text-transform: uppercase;">PARTIAL PAID</span>
                                        <?php else: ?>
                                            <span style="color: red; font-weight: bold; text-transform: uppercase;">UNPAID</span>
                                        <?php endif; ?>
                                    </p>
                                </div>

                                <div class="invoice_form">
                                    <p><strong>Invoice From:</strong></p>
                                    <p><?php echo e($generalsetting->name); ?></p>
                                    <p><?php echo e($contact->phone); ?></p>
                                    <p><?php echo e($contact->email); ?></p>
                                    <p><?php echo e($contact->address); ?></p>
                                    
                                    <?php if(!empty($order->order_note) || !empty($order->note)): ?>
                                        <p style="font-size:16px; line-height:1.8; color:#222; margin-top: 10px;">
                                            <strong>Order Note:</strong> <?php echo e($order->order_note ?? $order->note); ?>

                                        </p>
                                    <?php endif; ?>
                                </div>
                            </td>

                            <td style="width:60%;float: left;">
                                <div class="invoice-bar" style="background:#00aef0; transform: skew(38deg); padding: 20px 60px; margin-left: 65px;">
                                    <p style="font-size: 30px; color: #fff; transform: skew(-38deg); text-align: right; font-weight: bold;">Invoice</p>
                                </div>

                                <div class="invoice-bar" style="background:#fff; transform: skew(36deg); width: 80%; margin-left: 182px; padding: 12px 32px; margin-top: 6px;text-align:right">
                                   <p style="transform: skew(-36deg);display:inline-block">Invoice Date: <strong><?php echo e($order->created_at->format('d-m-y')); ?></strong></p>
                                   <br>
                                   <p style="transform: skew(-36deg);display:inline-block">Invoice No: <strong><?php echo e($order->invoice_id); ?></strong></p>
                                </div>

                                <div class="invoice_to" style="padding-top: 20px;">
                                    <p><strong>Invoice To:</strong></p>
                                    <p><?php echo e($order->shipping?$order->shipping->name:''); ?></p>
                                    <p><?php echo e($order->shipping?$order->shipping->phone:''); ?></p>
                                    <p><?php echo e($order->shipping?$order->shipping->address:''); ?></p>
                                    <p><?php echo e($order->shipping?$order->shipping->area:''); ?></p>
                                </div>
                            </td>
                        </tr>
                    </table>

                    
                    <table class="table" style="margin-top: 30px;">
                        <thead style="background: #00aef0; color: #fff;">
                            <tr>
                                <th>SL</th>
                                <th>Product</th>
                                <th>Price</th>
                                <th>Qty</th>
                                <th>Total</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php $__currentLoopData = $order->orderdetails; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr>
                                <td><?php echo e($loop->iteration); ?></td>
                                <td>
                                    <?php echo e($value->product_name); ?> <br>
                                    <?php
                                        $sizeDisplay = null;
                                        $colorDisplay = null;
                                        if ($value->size) {
                                            $sizeDisplay = $value->size->sizeName ?? $value->size->size_name ?? $value->size->name ?? null;
                                        } elseif ($value->product_size) {
                                            $s = \App\Models\Size::find($value->product_size);
                                            $sizeDisplay = $s ? ($s->sizeName ?? $s->size_name ?? null) : null;
                                        }
                                        if ($value->color) {
                                            $colorDisplay = $value->color->getDisplayName() ?? $value->color->colorName ?? $value->color->color_name ?? $value->color->name ?? null;
                                        } elseif ($value->product_color) {
                                            $c = \App\Models\Color::find($value->product_color);
                                            $colorDisplay = $c ? ($c->getDisplayName() ?? $c->colorName ?? $c->color_name ?? null) : null;
                                        }
                                    ?>
                                    <?php if($sizeDisplay): ?> <small>Size: <?php echo e($sizeDisplay); ?></small> <?php endif; ?>
                                    <?php if($colorDisplay): ?> <small>Color: <?php echo e($colorDisplay); ?></small> <?php endif; ?>
                                </td>
                                <td>৳<?php echo e($value->sale_price); ?></td>
                                <td><?php echo e($value->qty); ?></td>
                                <td>৳<?php echo e($value->sale_price * $value->qty); ?></td>
                            </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tbody>
                    </table>

                    
                    <?php
                        $subtotal = ($order->amount + $order->discount) - $order->shipping_charge;
                        $shipping = $order->shipping_charge;
                        $discount = $order->discount;
                    ?>

                    <div class="invoice-bottom">
                        <table class="table" style="width: 300px; float: right; margin-bottom: 30px;">
                            <tbody style="background:#00aef0; color:#fff;">

                                <tr>
                                    <td><strong>SubTotal</strong></td>
                                    <td><strong>৳<?php echo e($subtotal); ?></strong></td>
                                </tr>

                                <tr>
                                    <td><strong>Shipping(+)</strong></td>
                                    <td><strong>৳<?php echo e($shipping); ?></strong></td>
                                </tr>

                                <tr>
                                    <td><strong>Discount(-)</strong></td>
                                    <td><strong>৳<?php echo e($discount); ?></strong></td>
                                </tr>

                                <tr>
                                    <td><strong>Total Amount</strong></td>
                                    <td><strong>৳<?php echo e($grand_total); ?></strong></td>
                                </tr>

                                
                                <?php if($paid_amount > 0 && $due_amount > 0): ?>
                                    
                                    <tr style="background:#27ae60;">
                                        <td><strong>Paid / Advance</strong></td>
                                        <td><strong>৳<?php echo e(number_format($paid_amount, 2)); ?></strong></td>
                                    </tr>
                                    <tr style="background:#c0392b;">
                                        <td><strong>Due Amount</strong></td>
                                        <td><strong>৳<?php echo e(number_format($due_amount, 2)); ?></strong></td>
                                    </tr>
                                <?php elseif($paid_amount >= $grand_total): ?>
                                    
                                    <tr style="background:#27ae60;">
                                        <td><strong>Paid Amount</strong></td>
                                        <td><strong>৳<?php echo e(number_format($paid_amount, 2)); ?></strong></td>
                                    </tr>
                                    <tr style="background:#2ecc71;">
                                        <td><strong>Due Amount</strong></td>
                                        <td><strong>৳0.00</strong></td>
                                    </tr>
                                <?php else: ?>
                                    
                                    <tr style="background:#e74c3c;">
                                        <td><strong>Paid Amount</strong></td>
                                        <td><strong>৳0.00</strong></td>
                                    </tr>
                                    <tr style="background:#c0392b;">
                                        <td><strong>Due Amount</strong></td>
                                        <td><strong>৳<?php echo e(number_format($grand_total, 2)); ?></strong></td>
                                    </tr>
                                <?php endif; ?>

                            </tbody>
                        </table>

                        <div class="terms-condition" style="overflow: hidden; width: 100%; text-align: center; padding: 20px 0;">
                            <h5 style="font-style: italic;">
                                <a href="<?php echo e(route('page',['slug'=>'terms-condition'])); ?>">Terms & Conditions</a>
                            </h5>
                            <p style="text-align: center; font-style: italic; font-size: 15px;">* This is a computer generated invoice.</p>
                        </div>
                    </div>

                </div> 
            </div>
        </div>
    </div>
</section>

<script>
    function printFunction() {
        window.print();
    }
</script>

<?php $__env->stopSection(); ?>
<?php echo $__env->make('frontEnd.layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\projects\bilaiGhor\resources\views/frontEnd/layouts/customer/invoice.blade.php ENDPATH**/ ?>