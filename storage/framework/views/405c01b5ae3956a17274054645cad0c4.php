<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice Print</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        @page {
            size: 80mm auto;
            margin: 3mm 4mm;
        }

        body {
            background: #ccc;
            font-family: 'Courier New', Courier, monospace;
            font-size: 12px;
            color: #000;
        }

        .no-print {
            text-align: center;
            padding: 10px;
            background: #222;
            position: sticky;
            top: 0;
            z-index: 99;
        }
        .no-print button {
            padding: 7px 28px;
            background: #28a745;
            color: #fff;
            border: none;
            cursor: pointer;
            font-size: 14px;
            border-radius: 4px;
        }

        .receipt {
            background: #fff;
            width: 302px;
            margin: 18px auto;
            padding: 8px 10px 12px;
            border: 1px solid #999;
        }

        /* Header */
        .rh { text-align: center; border-bottom: 1px solid #000; padding-bottom: 5px; margin-bottom: 5px; }
        .rh .shop { font-size: 14px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; }
        .rh p { font-size: 10px; margin-top: 2px; }

        /* Title */
        .rt { text-align: center; font-size: 12px; font-weight: 700; border-top: 1px solid #000; border-bottom: 1px solid #000; padding: 3px 0; margin: 4px 0; letter-spacing: 3px; }

        /* Meta */
        .rm { font-size: 11px; margin-bottom: 3px; }
        .rm .fl { display: flex; justify-content: space-between; margin-bottom: 2px; }

        /* Table */
        .rtbl { width: 100%; border-collapse: collapse; font-size: 10px; margin: 4px 0; }
        .rtbl thead th { border-top: 1px solid #000; border-bottom: 1px solid #000; padding: 2px 2px; font-weight: 700; text-align: left; }
        .rtbl thead th.r { text-align: right; }
        .rtbl tbody td { padding: 3px 2px; vertical-align: top; }
        .rtbl tbody tr:last-child td { border-bottom: 1px solid #000; }
        .rtbl .pname { font-weight: 700; }
        .rtbl .pmeta { font-size: 9px; color: #555; }

        /* Summary */
        .rs { display: flex; justify-content: space-between; font-size: 11px; padding: 2px 0; }
        .rtotal { display: flex; justify-content: space-between; font-size: 13px; font-weight: 700; border-top: 1px solid #000; border-bottom: 1px solid #000; padding: 4px 0; margin: 3px 0; }

        /* Payment */
        .rp { font-size: 11px; margin-top: 3px; }
        .rp .fl { display: flex; justify-content: space-between; padding: 2px 0; }
        .rp .ptotal { display: flex; justify-content: space-between; font-size: 13px; font-weight: 700; border-top: 1px solid #000; padding-top: 4px; margin-top: 3px; }

        /* Dividers */
        .dash { border: none; border-top: 1px dashed #666; margin: 5px 0; }

        /* Footer */
        .rf { text-align: center; border-top: 1px dashed #666; margin-top: 10px; padding-top: 7px; }
        .rf .ty { font-size: 14px; font-weight: 700; }
        .rf small { font-size: 9px; color: #666; font-style: italic; margin-top: 3px; display: block; }

        /* Print */
        @media print {
            body { background: #fff !important; }
            .no-print { display: none !important; }
            .receipt {
                width: 100% !important;
                margin: 0 !important;
                border: none !important;
                padding: 2mm 1mm !important;
                page-break-after: always;
                break-after: page;
            }
            .receipt:last-child {
                page-break-after: avoid;
                break-after: avoid;
            }
        }
    </style>
</head>
<body>

<div class="no-print">
    <button onclick="window.print()">&#128438; Print All</button>
</div>

<?php $__currentLoopData = $orders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $order): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
<?php
    $isReseller  = !empty($order->customer_payable_amount);
    $subtotal    = 0;
    foreach ($order->orderdetails as $od) { $subtotal += ($od->sale_price * $od->qty); }
    if ($isReseller && $order->customer_payable_amount) {
        $subtotal = $order->customer_payable_amount - $order->shipping_charge;
    }
    $finalTotal  = $isReseller ? $order->customer_payable_amount : $order->amount;
    $totalQty    = $order->orderdetails->sum('qty');
    $payMethod   = strtoupper($order->payment_gateway ?? ($order->payment ? $order->payment->payment_method : 'N/A'));
    $payStatus   = $order->payment_status ?? ($order->payment ? $order->payment->payment_status : 'pending');
    $trackingId  = $order->courier_tracking_id ?? $order->consignment_id ?? null;
    $courierType = $order->courier_type ?? ($trackingId ? 'steadfast' : null);
?>

<div class="receipt">

    
    <div class="rh">
        <div class="shop"><?php echo e($generalsetting->name); ?></div>
        <?php if($contact->address): ?><p><?php echo e($contact->address); ?></p><?php endif; ?>
        <?php if($contact->phone): ?><p>Phone: <?php echo e($contact->phone); ?></p><?php endif; ?>
        <?php if($contact->email): ?><p><?php echo e($contact->email); ?></p><?php endif; ?>
    </div>

    
    <div class="rt">POS Invoice</div>

    
    <div class="rm">
        <div class="fl">
            <span>Bill No. : <strong><?php echo e($order->invoice_id); ?></strong></span>
            <span><?php echo e($order->created_at->format('H:i')); ?> hrs</span>
        </div>
        <div class="fl"><span>Date &nbsp;&nbsp;: <strong><?php echo e($order->created_at->format('d-m-Y')); ?></strong></span></div>
        <?php if($order->shipping && $order->shipping->name): ?>
        <div class="fl"><span>Buyer &nbsp;&nbsp;: <strong><?php echo e($order->shipping->name); ?></strong></span></div>
        <?php endif; ?>
        <?php if($order->shipping && $order->shipping->phone): ?>
        <div class="fl"><span>Phone &nbsp;&nbsp;: <?php echo e($order->shipping->phone); ?></span></div>
        <?php endif; ?>
        <?php if($order->shipping && ($order->shipping->address || $order->shipping->area)): ?>
        <div class="fl"><span>Address : <?php echo e($order->shipping->address); ?><?php echo e($order->shipping->area ? ', '.$order->shipping->area : ''); ?></span></div>
        <?php endif; ?>
    </div>

    
    <table class="rtbl">
        <thead>
            <tr>
                <th style="width:14px;">#</th>
                <th>Product</th>
                <th style="width:22px;text-align:center;">Qty</th>
                <th style="width:44px;" class="r">Rate</th>
                <th style="width:48px;" class="r">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php $__currentLoopData = $order->orderdetails; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php
                if ($isReseller && $order->customer_payable_amount && $subtotal > 0) {
                    $tv   = $item->sale_price * $item->qty;
                    $dp   = (($tv / ($subtotal + $order->discount)) * $subtotal) / $item->qty;
                } else {
                    $dp = $item->sale_price;
                }
                $sz = $item->size ? ($item->size->sizeName ?? null) : null;
                if (!$sz && $item->product_size) {
                    $szm = \App\Models\Size::find($item->product_size);
                    $sz  = $szm ? ($szm->sizeName ?? null) : (is_numeric($item->product_size) ? null : $item->product_size);
                }
                $cl = $item->color ? ($item->color->colorName ?? null) : null;
                if (!$cl && $item->product_color && !is_numeric($item->product_color)) { $cl = $item->product_color; }
            ?>
            <tr>
                <td><?php echo e($loop->iteration); ?></td>
                <td>
                    <span class="pname"><?php echo e($item->product_name); ?></span>
                    <?php if($sz || $cl): ?>
                    <div class="pmeta"><?php if($sz): ?>Sz:<?php echo e($sz); ?><?php endif; ?> <?php if($sz && $cl): ?>| <?php endif; ?> <?php if($cl): ?><?php echo e($cl); ?><?php endif; ?></div>
                    <?php endif; ?>
                </td>
                <td style="text-align:center;"><?php echo e($item->qty); ?></td>
                <td style="text-align:right;"><?php echo e(number_format($dp, 2)); ?></td>
                <td style="text-align:right;"><?php echo e(number_format($dp * $item->qty, 2)); ?></td>
            </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </tbody>
    </table>

    
    <div class="rs"><span>Subtotal</span><span><?php echo e(number_format($subtotal, 2)); ?></span></div>
    <?php if($order->discount > 0): ?>
    <div class="rs"><span>Discount (–)</span><span><?php echo e(number_format($order->discount, 2)); ?></span></div>
    <?php endif; ?>
    <?php if($order->shipping_charge > 0): ?>
    <div class="rs"><span>Delivery (+)</span><span><?php echo e(number_format($order->shipping_charge, 2)); ?></span></div>
    <?php endif; ?>

    <div class="rtotal">
        <span>Total &nbsp; <?php echo e($totalQty); ?> <?php echo e($totalQty > 1 ? 'Nos' : 'No'); ?></span>
        <span>&#2547; <?php echo e(number_format($finalTotal, 2)); ?></span>
    </div>

    
    <div class="rp">
        <div class="fl"><span>Method &nbsp;&nbsp;:</span><span><strong><?php echo e($payMethod); ?></strong></span></div>
        <div class="fl">
            <span>Pay Status :</span>
            <span><strong>
                <?php if($payStatus=='paid'): ?> PAID
                <?php elseif($payStatus=='pending'): ?> PENDING
                <?php elseif($payStatus=='failed'): ?> FAILED
                <?php else: ?> <?php echo e(strtoupper($payStatus)); ?>

                <?php endif; ?>
            </strong></span>
        </div>
        <?php if($courierType): ?>
        <hr class="dash">
        <div class="fl"><span>Courier &nbsp;&nbsp;:</span><span><strong><?php echo e(ucfirst($courierType)); ?></strong></span></div>
        <?php if($trackingId): ?><div class="fl"><span>Tracking &nbsp;:</span><span><?php echo e($trackingId); ?></span></div><?php endif; ?>
        <?php if($order->courier_sent_at): ?><div class="fl"><span>Sent &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;:</span><span><?php echo e(\Carbon\Carbon::parse($order->courier_sent_at)->format('d M Y')); ?></span></div><?php endif; ?>
        <?php endif; ?>
        <div class="ptotal">
            <span>Total Paid</span>
            <span>&#2547; <?php echo e(number_format($finalTotal, 2)); ?></span>
        </div>
    </div>

    
    <hr class="dash">
    <div class="rs"><span>Order Status :</span><span><strong><?php echo e($order->status ? $order->status->name : 'Processing'); ?></strong></span></div>

    
    <div class="rf">
        <div class="ty">Thank You!</div>
        <div>Visit Again!</div>
        <small>* Computer generated invoice. No signature required.</small>
    </div>

</div>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

</body>
</html>
<?php /**PATH C:\laragon\www\bilaiGhor\resources\views\backEnd\order\print.blade.php ENDPATH**/ ?>