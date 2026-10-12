<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    @include('backEnd.manual_orders.partials.receipt-styles')
    {{-- The PDF renderer (dompdf) draws lines about 1.5x taller than browsers do. These keep the
         PDF's rows, header and totals the same height as the printed page, so both split pages
         at the same row (App\Services\ReceiptLayout). --}}
    <style>
        .mi-table td { height: auto; }
        .mi-table td, .mi-table td span { line-height: 9.4px; }
        .mi-dense .mi-table td, .mi-dense .mi-table td span { line-height: 7px; }
        .mi-table th { height: auto; line-height: 7px; }
        .mi-summary td { height: auto; line-height: 9.4px; }
        .mi-summary .mi-total-row td { height: auto; line-height: 11px; }
        .mi-summary .mi-payment td { line-height: 8px; }
        .mi-customer-line { line-height: 8.6px; }
        .mi-address { line-height: 7.6px; }
        .mi-continued { height: auto; line-height: 8px; }
        .mi-footer-text { line-height: 7px; }
        .mi-thanks { line-height: 8px; }
    </style>
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
