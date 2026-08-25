@extends('backEnd.layouts.master')
@section('title', 'Receipt ' . ($order->invoice_number ?: $order->invoice_id))

@section('css')
@include('backEnd.manual_orders.partials.receipt-styles')
<style>
body { background: #f4f6f9; }
.mi-actions { display: flex; gap: 8px; flex-wrap: wrap; justify-content: flex-end; margin-bottom: 12px; }
.mi-page { width: 100%; max-width: 100%; margin: 0 auto; }
.mi-page .mi-doc { margin-bottom: 24px; border: 1px solid #e5e5e5; border-radius: 2px; }
@media print {
    .navbar-custom, .left-side-menu, .right-bar, .footer, .mi-actions, .no-print { display: none !important; }
    .content-page, .content, #wrapper, .container-fluid { margin: 0 !important; padding: 0 !important; }
    .mi-page { display: block; width: 100%; max-width: 100%; margin: 0; }
    .mi-page .mi-doc { margin: 0; border: 0; border-radius: 0; }
}
@media (max-width: 575px) {
    .mi-doc { width: 100%; max-width: 80mm; }
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
        <a href="{{ route('admin.manual_orders.download', $order) }}" class="btn btn-primary">Download PDF</a>
        @if((int) $order->order_status !== 11)
            <form action="{{ route('admin.manual_orders.cancel', $order) }}" method="POST" onsubmit="return confirm('Cancel this manual order and release reserved stock?')">
                @csrf
                <button class="btn btn-outline-danger">Cancel Order</button>
            </form>
        @endif
    </div>

    <div class="mi-page">
        @include('backEnd.manual_orders.partials.receipt')
    </div>
</div>
@endsection
