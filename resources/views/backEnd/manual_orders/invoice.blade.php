@extends('backEnd.layouts.master')
@section('title', 'Manual Invoice ' . ($order->invoice_number ?: $order->invoice_id))

@section('css')
<style>
body { background: #f4f6f9; }
.mi-actions { display: flex; gap: 8px; flex-wrap: wrap; justify-content: flex-end; margin-bottom: 14px; }
.mi-doc { max-width: 980px; margin: 0 auto 32px; background: #fff; border: 1px solid #e6d2b0; border-radius: 14px; overflow: hidden; color: #2b1a10; }
.mi-top { display: flex; justify-content: space-between; gap: 24px; padding: 28px 34px; background: #fff8ec; border-bottom: 1px solid #e6d2b0; }
.mi-logo img { max-width: 180px; max-height: 70px; object-fit: contain; }
.mi-title { text-align: right; }
.mi-title h1 { font-size: 34px; margin: 0; color: #3a1f0f; font-weight: 800; }
.mi-badge { display: inline-block; margin-top: 8px; padding: 5px 12px; border-radius: 999px; background: #f28c00; color: #fff; font-weight: 700; text-transform: capitalize; }
.mi-section { padding: 22px 34px; border-bottom: 1px solid #f0dfc6; }
.mi-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 28px; }
.mi-label { font-size: 11px; text-transform: uppercase; color: #9a6b35; font-weight: 800; letter-spacing: .08em; margin-bottom: 8px; }
.mi-text { margin: 0 0 5px; font-size: 14px; line-height: 1.55; }
.mi-table th { background: #fff8ec; color: #6b3d17; font-size: 12px; text-transform: uppercase; }
.mi-table td, .mi-table th { padding: 12px 14px; border-color: #f0dfc6; }
.mi-summary { margin-left: auto; max-width: 360px; }
.mi-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px dashed #dec39b; }
.mi-row.total { border: 0; background: #3a1f0f; color: #fff; padding: 12px 14px; border-radius: 8px; margin-top: 8px; font-weight: 800; }
.mi-footer { display: grid; grid-template-columns: 1fr auto; gap: 20px; align-items: end; padding: 22px 34px; }
.mi-qr { text-align: center; font-size: 12px; color: #6b3d17; }
.mi-qr img { width: 132px; height: 132px; border: 1px solid #ead2ad; border-radius: 8px; padding: 6px; background: #fff; }
@media print {
    .navbar-custom, .left-side-menu, .right-bar, .footer, .mi-actions, .no-print { display: none !important; }
    .content-page, .content, #wrapper { margin: 0 !important; padding: 0 !important; }
    body { background: #fff; }
    .mi-doc { max-width: none; margin: 0; border-radius: 0; border: none; }
}
@media (max-width: 767px) {
    .mi-top, .mi-grid, .mi-footer { grid-template-columns: 1fr; display: grid; }
    .mi-title { text-align: left; }
}
</style>
@endsection

@section('content')
@php
    $invoice = $order->invoice_number ?: $order->invoice_id;
    $paid = (float) ($order->paid_amount ?? optional($order->payment)->amount ?? 0);
    $due = (float) ($order->due_amount ?? max(0, $order->amount - $paid));
    $subtotal = $order->orderdetails->sum(fn($item) => ($item->sale_price * $item->qty));
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

    <article class="mi-doc">
        <header class="mi-top">
            <div class="mi-logo">
                @if($generalsetting && $generalsetting->dark_logo)
                    <img src="{{ asset($generalsetting->dark_logo) }}" alt="{{ $generalsetting->name }}">
                @endif
            </div>
            <div class="mi-title">
                <h1>Invoice</h1>
                <p class="mi-text"><strong>{{ $invoice }}</strong></p>
                <p class="mi-text">{{ $order->created_at?->format('d M Y, h:i A') }}</p>
                <span class="mi-badge">{{ $order->payment_status }}</span>
            </div>
        </header>

        <section class="mi-section mi-grid">
            <div>
                <div class="mi-label">Customer Details</div>
                <p class="mi-text"><strong>{{ $order->manual_customer_name ?: optional($order->shipping)->name }}</strong></p>
                <p class="mi-text">{{ $order->manual_customer_address ?: optional($order->shipping)->address }}</p>
                <p class="mi-text">Phone: {{ $order->manual_customer_phone ?: optional($order->shipping)->phone }}</p>
                @if($order->manual_customer_email)<p class="mi-text">Email: {{ $order->manual_customer_email }}</p>@endif
            </div>
            <div>
                <div class="mi-label">Payment Details</div>
                <p class="mi-text">Payment Method: <strong>{{ ucwords(str_replace('_', ' ', $order->payment_method)) }}</strong></p>
                @if($order->transaction_id)<p class="mi-text">Transaction ID: {{ $order->transaction_id }}</p>@endif
                <p class="mi-text">Order Source: {{ ucwords(str_replace('_', ' ', $order->order_source)) }}</p>
                <p class="mi-text">Order Status: {{ optional($order->status)->name ?: $order->order_status }}</p>
            </div>
        </section>

        <section class="table-responsive">
            <table class="table mi-table mb-0">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th class="text-center">Qty</th>
                        <th class="text-end">Unit Price</th>
                        <th class="text-end">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($order->orderdetails as $item)
                        <tr>
                            <td>
                                <strong>{{ $item->product_name }}</strong>
                                @if($item->manual_variant)<div class="text-muted small">{{ $item->manual_variant }}</div>@endif
                            </td>
                            <td class="text-center">{{ $item->qty }}</td>
                            <td class="text-end">৳{{ number_format($item->sale_price, 2) }}</td>
                            <td class="text-end">৳{{ number_format($item->line_total ?: (($item->sale_price * $item->qty) - ($item->line_discount ?? 0)), 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>

        <section class="mi-section">
            <div class="mi-summary">
                <div class="mi-row"><span>Subtotal</span><strong>৳{{ number_format($subtotal, 2) }}</strong></div>
                <div class="mi-row"><span>Delivery Charge</span><strong>৳{{ number_format($order->shipping_charge, 2) }}</strong></div>
                <div class="mi-row"><span>Discount</span><strong>৳{{ number_format($order->discount, 2) }}</strong></div>
                <div class="mi-row total"><span>Grand Total</span><strong>৳{{ number_format($order->amount, 2) }}</strong></div>
                <div class="mi-row"><span>Paid Amount</span><strong>৳{{ number_format($paid, 2) }}</strong></div>
                <div class="mi-row"><span>Due Amount</span><strong>৳{{ number_format($due, 2) }}</strong></div>
            </div>
        </section>

        <footer class="mi-footer">
            <div>
                <div class="mi-label">Bilai Ghor</div>
                @if($contact?->facebook)<p class="mi-text">Facebook: {{ $contact->facebook }}</p>@endif
                @if($contact?->phone)<p class="mi-text">Phone/WhatsApp: {{ $contact->phone }}</p>@endif
                <p class="mi-text">Website: {{ url('/') }}</p>
            </div>
            <div class="mi-qr">
                <img src="{{ $qrUrl }}" alt="Invoice verification QR">
                <div>Scan to verify invoice</div>
            </div>
        </footer>
    </article>
</div>
@if($printMode)
<script>window.addEventListener('load', function(){ window.print(); });</script>
@endif
@endsection
