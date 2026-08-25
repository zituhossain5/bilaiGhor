<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    @include('backEnd.manual_orders.partials.receipt-styles')
</head>
<body class="mi-standalone">
    @php
        $invoice = $order->invoice_number ?: $order->invoice_id;
        $subtotal = $order->orderdetails->sum(fn($item) => ($item->sale_price * $item->qty));
        $discount = (float) ($order->discount ?? 0);
        $delivery = (float) ($order->shipping_charge ?? 0);
        $total = (float) ($order->amount ?? max(0, $subtotal - $discount + $delivery));
        $customerName = $order->manual_customer_name ?: optional($order->shipping)->name;
        $customerAddress = $order->manual_customer_address ?: optional($order->shipping)->address;
        $customerPhone = $order->manual_customer_phone ?: optional($order->shipping)->phone;
        $facebook = $contact?->facebook ?: '/bilaighor.bd';
        $whatsapp = $contact?->phone ?: $customerPhone;
    @endphp
    @include('backEnd.manual_orders.partials.receipt')
</body>
</html>
