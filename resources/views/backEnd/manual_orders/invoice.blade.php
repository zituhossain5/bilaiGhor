@extends('backEnd.layouts.master')
@section('title', 'Receipt ' . ($order->invoice_number ?: $order->invoice_id))

@section('css')
<style>
body { background: #f4f6f9; }
.mi-actions { display: flex; gap: 8px; flex-wrap: wrap; justify-content: flex-end; margin-bottom: 12px; }
.mi-page { display: flex; justify-content: center; }
.mi-doc {
    width: 360px;
    max-width: 100%;
    margin: 0 auto 24px;
    padding: 18px 20px 14px;
    background: #fff;
    color: #222;
    border: 1px solid #e5e5e5;
    border-radius: 2px;
    font-family: Arial, Helvetica, sans-serif;
    font-size: 11px;
    line-height: 1.35;
}
.mi-top { display: grid; grid-template-columns: 116px 1fr; gap: 12px; align-items: start; margin-bottom: 10px; }
.mi-logo img { width: 82px; max-height: 74px; object-fit: contain; }
.mi-title { text-align: right; }
.mi-title h1 { margin: 0 0 4px; font-size: 24px; line-height: 1; font-weight: 500; color: #1f1f1f; }
.mi-title p { margin: 0 0 3px; font-size: 11px; color: #111; }
.mi-customer {
    background: #eeeeee;
    border-radius: 8px;
    padding: 10px;
    margin-bottom: 10px;
}
.mi-box-title { margin: 0 0 7px; font-size: 12px; font-weight: 700; }
.mi-detail-row { display: grid; grid-template-columns: 52px 1fr; gap: 8px; margin-bottom: 3px; }
.mi-detail-row:last-child { margin-bottom: 0; }
.mi-label { color: #222; }
.mi-value { color: #111; overflow-wrap: anywhere; }
.mi-table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
.mi-table th {
    padding: 0 0 5px;
    border-bottom: 1px solid #bfbfbf;
    font-size: 11px;
    font-weight: 400;
    color: #555;
    text-align: left;
}
.mi-table td {
    padding: 6px 0;
    border-bottom: 1px dotted #d2d2d2;
    vertical-align: top;
    font-size: 11px;
}
.mi-table .mi-product-col { width: 50%; }
.mi-table .mi-num { text-align: right; white-space: nowrap; }
.mi-product-name { font-weight: 600; color: #222; overflow-wrap: anywhere; }
.mi-product-variant { margin-top: 1px; color: #555; font-size: 10px; }
.mi-summary { width: 70%; margin-left: auto; margin-bottom: 10px; }
.mi-row { display: flex; justify-content: space-between; gap: 10px; padding: 4px 0; font-size: 11px; }
.mi-row.total { margin-top: 3px; padding: 8px 12px; background: #eeeeee; border-radius: 3px; font-weight: 700; }
.mi-thanks { text-align: center; margin: 14px 0 9px; font-size: 11px; }
.mi-footer { display: grid; grid-template-columns: 1fr auto; gap: 10px; align-items: end; }
.mi-social { display: flex; flex-wrap: wrap; gap: 10px 14px; align-items: center; font-size: 10px; color: #111; }
.mi-social span { display: inline-flex; align-items: center; gap: 4px; }
.mi-social i { width: 14px; height: 14px; border-radius: 50%; background: #111; color: #fff; display: inline-flex; align-items: center; justify-content: center; font-size: 8px; }
.mi-qr { text-align: right; }
.mi-qr img { width: 82px; height: 82px; object-fit: contain; background: #fff; padding: 4px; border: 1px solid #dedede; }
.mi-verify-url { margin-top: 3px; max-width: 100px; font-size: 7px; line-height: 1.2; color: #555; overflow-wrap: anywhere; text-align: right; }

@page { size: A5 portrait; margin: 7mm; }
@media print {
    .navbar-custom, .left-side-menu, .right-bar, .footer, .mi-actions, .no-print { display: none !important; }
    .content-page, .content, #wrapper, .container-fluid { margin: 0 !important; padding: 0 !important; }
    body { background: #fff !important; }
    .mi-page { display: block; }
    .mi-doc {
        width: 76mm;
        margin: 0 auto;
        padding: 0;
        border: 0;
        border-radius: 0;
        font-size: 10px;
        box-shadow: none;
        page-break-inside: avoid;
    }
    .mi-title h1 { font-size: 22px; }
    .mi-customer { padding: 8px; }
    .mi-table td { padding: 5px 0; }
    .mi-thanks { margin: 10px 0 7px; }
    .mi-qr img { width: 22mm; height: 22mm; padding: 1mm; }
    .mi-verify-url { max-width: 30mm; font-size: 6px; }
}

@media (max-width: 575px) {
    .mi-doc { width: 100%; padding: 16px 14px; }
}
</style>
@endsection

@section('content')
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

<div class="container-fluid">
    <div class="mi-actions no-print">
        <a href="{{ route('admin.manual_orders.index') }}" class="btn btn-light">Back</a>
        <button type="button" onclick="window.print()" class="btn btn-primary">Print / Save PDF</button>
        @if((int) $order->order_status !== 11)
            <form action="{{ route('admin.manual_orders.cancel', $order) }}" method="POST" onsubmit="return confirm('Cancel this manual order and release reserved stock?')">
                @csrf
                <button class="btn btn-outline-danger">Cancel Order</button>
            </form>
        @endif
    </div>

    <div class="mi-page">
        <article class="mi-doc">
            <header class="mi-top">
                <div class="mi-logo">
                    @if($generalsetting && $generalsetting->dark_logo)
                        <img src="{{ asset($generalsetting->dark_logo) }}" alt="{{ $generalsetting->name }}">
                    @endif
                </div>
                <div class="mi-title">
                    <h1>Receipt</h1>
                    <p>Order ID: <strong>{{ $invoice }}</strong></p>
                    <p>Date: {{ $order->created_at?->format('d/m/Y') }}</p>
                </div>
            </header>

            <section class="mi-customer">
                <h2 class="mi-box-title">Customer Details:</h2>
                <div class="mi-detail-row">
                    <span class="mi-label">Name:</span>
                    <span class="mi-value">{{ $customerName }}</span>
                </div>
                <div class="mi-detail-row">
                    <span class="mi-label">Address:</span>
                    <span class="mi-value">{{ $customerAddress }}</span>
                </div>
                <div class="mi-detail-row">
                    <span class="mi-label">Phone:</span>
                    <span class="mi-value">{{ $customerPhone }}</span>
                </div>
            </section>

            <table class="mi-table">
                <thead>
                    <tr>
                        <th class="mi-product-col">Product Details</th>
                        <th class="mi-num">Qty.</th>
                        <th class="mi-num">Price</th>
                        <th class="mi-num">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($order->orderdetails as $item)
                        @php
                            $lineAmount = $item->line_total ?: (($item->sale_price * $item->qty) - ($item->line_discount ?? 0));
                        @endphp
                        <tr>
                            <td>
                                <div class="mi-product-name">{{ $item->product_name }}</div>
                                @if($item->manual_variant)
                                    <div class="mi-product-variant">{{ $item->manual_variant }}</div>
                                @endif
                            </td>
                            <td class="mi-num">{{ $item->qty }}</td>
                            <td class="mi-num">{{ number_format($item->sale_price, 0) }}</td>
                            <td class="mi-num">{{ number_format($lineAmount, 0) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <section class="mi-summary">
                <div class="mi-row"><span>Subtotal</span><span>৳{{ number_format($subtotal, 0) }}</span></div>
                <div class="mi-row"><span>Delivery Charge</span><span>৳{{ number_format($delivery, 0) }}</span></div>
                <div class="mi-row"><span>Discount</span><span>{{ $discount > 0 ? '৳'.number_format($discount, 0) : '---' }}</span></div>
                <div class="mi-row total"><span>TOTAL</span><span>৳{{ number_format($total, 0) }}</span></div>
            </section>

            <div class="mi-thanks">Thank You For Your Purchase</div>

            <footer class="mi-footer">
                <div class="mi-social">
                    <span><i class="fa fa-facebook"></i> {{ $facebook }}</span>
                    <span><i class="fa fa-whatsapp"></i> {{ $whatsapp }}</span>
                </div>
                <div class="mi-qr">
                    <img src="{{ $qrUrl }}" alt="Invoice verification QR">
                    <div class="mi-verify-url">{{ $verifyUrl }}</div>
                </div>
            </footer>
        </article>
    </div>
</div>

@if($printMode)
<script>window.addEventListener('load', function(){ window.print(); });</script>
@endif
@endsection
