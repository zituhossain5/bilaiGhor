@extends('backEnd.layouts.master')
@section('title', 'Manual Orders')

@section('css')
<style>
.mo-page .card { border: 1px solid #e7edf5; border-radius: 10px; box-shadow: 0 8px 24px rgba(15,23,42,.05); }
.mo-page .table th { font-size: 12px; text-transform: uppercase; color: #667085; white-space: nowrap; }
.mo-page .table td { vertical-align: middle; }
.mo-status { display: inline-flex; align-items: center; padding: 4px 10px; border-radius: 999px; font-size: 12px; font-weight: 700; text-transform: capitalize; }
.mo-status.paid { background: #dcfce7; color: #166534; }
.mo-status.partial { background: #fef3c7; color: #92400e; }
.mo-status.unpaid { background: #fee2e2; color: #991b1b; }
</style>
@endsection

@section('content')
<div class="container-fluid mo-page">
    <div class="page-title-box d-flex align-items-center justify-content-between">
        <h4 class="page-title">Manual Orders</h4>
        <a href="{{ route('admin.manual_orders.create') }}" class="btn btn-primary">
            <i class="fe-plus"></i> Create Manual Invoice
        </a>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2">
                <div class="col-md-2">
                    <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control">
                </div>
                <div class="col-md-2">
                    <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control">
                </div>
                <div class="col-md-2">
                    <select name="source" class="form-select">
                        <option value="">All Sources</option>
                        @foreach($sources as $source)
                            <option value="{{ $source }}" @selected(request('source') === $source)>{{ ucwords(str_replace('_', ' ', $source)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="payment_method" class="form-select">
                        <option value="">All Methods</option>
                        @foreach($methods as $method)
                            <option value="{{ $method }}" @selected(request('payment_method') === $method)>{{ ucwords(str_replace('_', ' ', $method)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="payment_status" class="form-select">
                        <option value="">Payment Status</option>
                        @foreach(['paid', 'partial', 'unpaid'] as $status)
                            <option value="{{ $status }}" @selected(request('payment_status') === $status)>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="order_status" class="form-select">
                        <option value="">Order Status</option>
                        @foreach($statuses as $status)
                            <option value="{{ $status->id }}" @selected((string) request('order_status') === (string) $status->id)>{{ $status->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button class="btn btn-dark flex-fill">Filter</button>
                    <a href="{{ route('admin.manual_orders.index') }}" class="btn btn-light">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Invoice #</th>
                            <th>Date</th>
                            <th>Customer</th>
                            <th>Phone</th>
                            <th>Source</th>
                            <th class="text-end">Grand Total</th>
                            <th class="text-end">Paid</th>
                            <th class="text-end">Due</th>
                            <th>Method</th>
                            <th>Payment</th>
                            <th>Order</th>
                            <th>Created By</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($orders as $order)
                            @php($paymentState = \App\Services\OrderPaymentService::state($order))
                            <tr>
                                <td><strong>{{ $order->invoice_number ?: $order->invoice_id }}</strong></td>
                                <td>{{ $order->created_at?->format('d M Y') }}</td>
                                <td>{{ $order->manual_customer_name ?: optional($order->shipping)->name }}</td>
                                <td>{{ $order->manual_customer_phone ?: optional($order->shipping)->phone }}</td>
                                <td>{{ ucwords(str_replace('_', ' ', $order->order_source ?: 'manual')) }}</td>
                                <td class="text-end">৳{{ number_format($order->amount, 2) }}</td>
                                <td class="text-end">৳{{ number_format($paymentState['paid'], 2) }}</td>
                                <td class="text-end">৳{{ number_format($paymentState['due'], 2) }}</td>
                                <td>{{ ucwords(str_replace('_', ' ', $paymentState['method'] ?: 'N/A')) }}</td>
                                <td>
                                    @include('backEnd.order.partials.inline_status_select', [
                                        'order' => $order,
                                        'type' => 'payment',
                                        'paymentState' => $paymentState,
                                    ])
                                </td>
                                <td>
                                    @include('backEnd.order.partials.inline_status_select', [
                                        'order' => $order,
                                        'type' => 'order',
                                        'statuses' => $statuses,
                                    ])
                                </td>
                                <td>{{ optional($order->creator)->name ?: 'Admin' }}</td>
                                <td class="text-end">
                                    @if((int) $order->order_status !== \App\Services\InventoryService::CANCEL_STATUS)
                                        <a href="{{ route('admin.manual_orders.edit', $order) }}" class="btn btn-sm btn-outline-dark">Edit</a>
                                    @else
                                        <button type="button" class="btn btn-sm btn-outline-dark" disabled title="Cancelled orders are read-only">Edit</button>
                                    @endif
                                    <a href="{{ route('admin.manual_orders.show', $order) }}" class="btn btn-sm btn-outline-primary">Invoice</a>
                                    <a href="{{ route('admin.manual_orders.print', $order) }}" class="btn btn-sm btn-outline-secondary" target="_blank" rel="noopener">Print</a>
                                    @if((int) $order->order_status !== \App\Services\InventoryService::CANCEL_STATUS)
                                        <form action="{{ route('admin.manual_orders.cancel', $order) }}" method="POST" class="d-inline" onsubmit="return confirm('Cancel this manual order and release reserved stock?')">
                                            @csrf
                                            <button class="btn btn-sm btn-outline-danger">Cancel</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="13" class="text-center text-muted py-4">No manual orders found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $orders->links() }}</div>
        </div>
    </div>
</div>
@endsection
