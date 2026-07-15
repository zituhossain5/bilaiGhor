@extends('backEnd.layouts.master')
@section('title', 'Low Stock')

@section('css')
    @include('backEnd.inventory._style')
@endsection

@section('content')
<div class="container-fluid mb-5">

    <div class="d-sm-flex align-items-center justify-content-between mb-4 mt-2">
        <h1 class="h3 mb-0 text-gray-800 fw-bold">⚠️ Low Stock Products</h1>
        <a href="{{ route('purchases.index') }}" class="d-none d-sm-inline-block btn btn-sm btn-success shadow-sm">
            <i class="fe-shopping-bag me-1"></i> Create Purchase
        </a>
    </div>

    <div class="card">
        <div class="card-header">Products where Available ≤ Low Stock Threshold</div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th class="text-end">Available</th>
                        <th class="text-end">Threshold</th>
                        <th>Status</th>
                        <th>Supplier</th>
                        <th>Last Purchase</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                    @php
                        $p = $row->product;
                        $lp = $lastPurchases->get($row->product_id);
                    @endphp
                    <tr>
                        <td class="fw-semibold">{{ Str::limit($p->name ?? ('#'.$row->product_id), 46) }}</td>
                        <td class="text-end inv-num fw-bold">{{ $row->available }}</td>
                        <td class="text-end inv-num">{{ $row->low_stock_threshold }}</td>
                        <td>
                            @if($row->available <= 0)
                                <span class="inv-badge inv-badge-out">Out of Stock</span>
                            @else
                                <span class="inv-badge inv-badge-low">Low Stock</span>
                            @endif
                        </td>
                        <td>{{ optional(optional($lp?->purchase)->supplier)->name ?? '—' }}</td>
                        <td class="text-nowrap">{{ $lp?->created_at?->format('M j, Y') ?? '—' }}</td>
                        <td class="text-center text-nowrap">
                            <a href="{{ route('purchases.index') }}" class="btn btn-sm btn-success" title="Create purchase"><i class="fe-shopping-bag"></i></a>
                            <a href="{{ route('admin.inventory.adjust', ['product_id' => $row->product_id]) }}" class="btn btn-sm btn-light" title="Adjust"><i class="fe-sliders"></i></a>
                            @if($p && $p->slug)
                            <a href="{{ route('product', $p->slug) }}" target="_blank" class="btn btn-sm btn-light" title="View product"><i class="fe-external-link"></i></a>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">🎉 No products are low on stock.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($rows->hasPages())
        <div class="card-body py-3">{{ $rows->links('pagination::bootstrap-4') }}</div>
        @endif
    </div>

</div>
@endsection
