@extends('backEnd.layouts.master')
@section('title', 'Edit Manual Order ' . ($order->invoice_number ?: $order->invoice_id))

@section('css')
    @include('backEnd.manual_orders.partials.form-styles')
@endsection

@section('content')
<div class="container-fluid mo-create">
    <div class="page-title-box d-flex align-items-center justify-content-between">
        <div>
            <h4 class="page-title mb-1">Edit Manual Order</h4>
            <div class="text-muted">{{ $order->invoice_number ?: $order->invoice_id }}</div>
        </div>
        <a href="{{ route('admin.manual_orders.show', $order) }}" class="btn btn-light">Back to Invoice</a>
    </div>

    @include('backEnd.manual_orders.partials.form', ['isEdit' => true])
</div>
@endsection

@section('script')
    @include('backEnd.manual_orders.partials.form-script', ['isEdit' => true])
@endsection
