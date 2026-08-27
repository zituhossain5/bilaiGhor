@extends('frontEnd.layouts.master')
@section('title', 'Verify Invoice')

@push('css')
<style>
.verify-wrap { min-height: 70vh; display: flex; align-items: center; justify-content: center; background: #fffdf8; padding: 48px 16px; }
.verify-card { width: min(560px, 100%); border: 1px solid #e6d2b0; border-radius: 16px; background: #fff; box-shadow: 0 18px 50px rgba(58,31,15,.08); padding: 32px; text-align: center; }
.verify-logo img { max-height: 72px; max-width: 220px; object-fit: contain; margin-bottom: 20px; }
.verify-pill { display: inline-flex; padding: 7px 14px; border-radius: 999px; background: #dcfce7; color: #166534; font-weight: 800; font-size: 13px; margin-bottom: 14px; }
.verify-card h1 { font-size: 26px; font-weight: 800; color: #3a1f0f; margin-bottom: 18px; }
.verify-grid { text-align: left; display: grid; gap: 10px; margin-top: 18px; }
.verify-row { display: flex; justify-content: space-between; gap: 18px; padding: 10px 0; border-bottom: 1px dashed #ead2ad; color: #6b3d17; }
.verify-row strong { color: #2b1a10; text-align: right; }
</style>
@endpush

@section('content')
@php
    $name = $order->manual_customer_name ?: optional($order->shipping)->name;
    $maskedName = $name ? mb_substr($name, 0, 1) . str_repeat('*', max(1, mb_strlen($name) - 2)) . mb_substr($name, -1) : 'Customer';
    $paymentState = $paymentState ?? \App\Services\OrderPaymentService::state($order);
@endphp
<section class="verify-wrap">
    <div class="verify-card">
        <div class="verify-logo">
            @if($generalsetting && $generalsetting->dark_logo)
                <img src="{{ asset($generalsetting->dark_logo) }}" alt="{{ $generalsetting->name }}">
            @endif
        </div>
        <div class="verify-pill">Valid Invoice</div>
        <h1>Verified Bilai Ghor Invoice</h1>
        <div class="verify-grid">
            <div class="verify-row"><span>Invoice</span><strong>{{ $order->invoice_number ?: $order->invoice_id }}</strong></div>
            <div class="verify-row"><span>Date</span><strong>{{ $order->created_at?->format('d M Y') }}</strong></div>
            <div class="verify-row"><span>Customer</span><strong>{{ $maskedName }}</strong></div>
            <div class="verify-row"><span>Grand Total</span><strong>৳{{ number_format($order->amount, 2) }}</strong></div>
            <div class="verify-row"><span>Paid Amount</span><strong>৳{{ number_format($paymentState['paid'], 2) }}</strong></div>
            <div class="verify-row"><span>Due Amount</span><strong>৳{{ number_format($paymentState['due'], 2) }}</strong></div>
            <div class="verify-row"><span>Payment Status</span><strong>{{ ucfirst($paymentState['status']) }}</strong></div>
            <div class="verify-row"><span>Order Source</span><strong>{{ ucwords(str_replace('_', ' ', $order->order_source)) }}</strong></div>
        </div>
    </div>
</section>
@endsection
