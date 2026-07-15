@extends('backEnd.layouts.master')
@section('title', 'Stock Movements')

@section('css')
    @include('backEnd.inventory._style')
@endsection

@section('content')
<div class="container-fluid mb-5">

    <div class="d-sm-flex align-items-center justify-content-between mb-4 mt-2">
        <h1 class="h3 mb-0 text-gray-800 fw-bold">📜 Stock Movements</h1>
        <span class="text-muted small">Audit trail — records cannot be edited or deleted from here</span>
    </div>

    <div class="card mb-4">
        <div class="card-body py-3">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Product</label>
                    <select name="product_id" class="form-select">
                        <option value="">All products</option>
                        @foreach($products as $p)
                            <option value="{{ $p->id }}" @selected(request('product_id') == $p->id)>{{ Str::limit($p->name, 48) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Movement Type</label>
                    <select name="movement_type" class="form-select">
                        <option value="">All</option>
                        @foreach($types as $t)
                            <option value="{{ $t }}" @selected(request('movement_type') === $t)>{{ ucwords(str_replace('_', ' ', $t)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">From</label>
                    <input type="date" name="from_date" value="{{ request('from_date') }}" class="form-control">
                </div>
                <div class="col-md-2">
                    <label class="form-label">To</label>
                    <input type="date" name="to_date" value="{{ request('to_date') }}" class="form-control">
                </div>
                <div class="col-md-1">
                    <label class="form-label">Order#</label>
                    <input type="number" name="order_id" value="{{ request('order_id') }}" class="form-control">
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button class="btn btn-primary w-100" type="submit"><i class="fe-filter me-1"></i>Filter</button>
                    <a href="{{ route('admin.inventory.movements') }}" class="btn btn-light" title="Reset"><i class="fe-x"></i></a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Product</th>
                        <th>Type</th>
                        <th>Reference</th>
                        <th class="text-end">Stock In</th>
                        <th class="text-end">Stock Out</th>
                        <th class="text-end">On Hand After</th>
                        <th class="text-end">Reserved After</th>
                        <th class="text-end">Available After</th>
                        <th>Admin/User</th>
                        <th>Reason</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($movements as $m)
                    <tr>
                        <td class="text-nowrap">{{ $m->created_at->format('M j, Y H:i') }}</td>
                        <td>{{ Str::limit(optional($m->product)->name ?? ('#'.$m->product_id), 32) }}</td>
                        <td><span class="inv-badge {{ $m->on_hand_change > 0 ? 'inv-badge-in' : ($m->on_hand_change < 0 ? 'inv-badge-out' : 'inv-badge-low') }}">{{ ucwords(str_replace('_', ' ', $m->movement_type)) }}</span></td>
                        <td class="text-nowrap">
                            @if($m->order_id)
                                Order #{{ $m->order_id }}
                            @elseif($m->purchase_id)
                                <a href="{{ route('purchases.invoice', $m->purchase_id) }}">Purchase #{{ $m->purchase_id }}</a>
                            @else
                                {{ $m->reference_type ? ucfirst($m->reference_type) : '—' }}
                            @endif
                        </td>
                        <td class="text-end inv-num inv-qty-in">{{ $m->on_hand_change > 0 ? '+' . $m->on_hand_change : '' }}</td>
                        <td class="text-end inv-num inv-qty-out">{{ $m->on_hand_change < 0 ? $m->on_hand_change : '' }}</td>
                        <td class="text-end inv-num">{{ $m->new_on_hand ?? '—' }}</td>
                        <td class="text-end inv-num">{{ $m->new_reserved ?? '—' }}</td>
                        <td class="text-end inv-num">{{ is_null($m->new_on_hand) || is_null($m->new_reserved) ? '—' : ($m->new_on_hand - $m->new_reserved) }}</td>
                        <td>{{ optional($m->creator)->name ?? ($m->created_by ? 'User #'.$m->created_by : 'System') }}</td>
                        <td class="small">{{ Str::limit($m->reason, 44) ?: '—' }}@if($m->notes) <span class="text-muted" title="{{ $m->notes }}">(note)</span>@endif</td>
                    </tr>
                    @empty
                    <tr><td colspan="11" class="text-center text-muted py-4">No movements found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($movements->hasPages())
        <div class="card-body py-3">{{ $movements->links('pagination::bootstrap-4') }}</div>
        @endif
    </div>

</div>
@endsection
