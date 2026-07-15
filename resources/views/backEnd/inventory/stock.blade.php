@extends('backEnd.layouts.master')
@section('title', 'Stock Overview')

@section('css')
    @include('backEnd.inventory._style')
@endsection

@section('content')
<div class="container-fluid mb-5">

    <div class="d-sm-flex align-items-center justify-content-between mb-4 mt-2">
        <h1 class="h3 mb-0 text-gray-800 fw-bold">📊 Stock Overview</h1>
        <a href="{{ route('admin.inventory.adjust') }}" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
            <i class="fe-sliders me-1"></i> Adjust Stock
        </a>
    </div>

    <div class="card mb-4">
        <div class="card-body py-3">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Search Product</label>
                    <input type="text" name="keyword" value="{{ request('keyword') }}" class="form-control" placeholder="Product name...">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Category</label>
                    <select name="category_id" class="form-select">
                        <option value="">All</option>
                        @foreach($categories as $c)
                            <option value="{{ $c->id }}" @selected(request('category_id') == $c->id)>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Brand</label>
                    <select name="brand_id" class="form-select">
                        <option value="">All</option>
                        @foreach($brands as $b)
                            <option value="{{ $b->id }}" @selected(request('brand_id') == $b->id)>{{ $b->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Stock Status</label>
                    <select name="status" class="form-select">
                        <option value="">All</option>
                        <option value="in"  @selected(request('status') === 'in')>In Stock</option>
                        <option value="low" @selected(request('status') === 'low')>Low Stock</option>
                        <option value="out" @selected(request('status') === 'out')>Out of Stock</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button class="btn btn-primary w-100" type="submit"><i class="fe-filter me-1"></i>Filter</button>
                    <a href="{{ route('admin.inventory.stock') }}" class="btn btn-light" title="Reset"><i class="fe-x"></i></a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Weight/Variant</th>
                        <th class="text-end">On Hand</th>
                        <th class="text-end">Reserved</th>
                        <th class="text-end">Available</th>
                        <th class="text-center">Low Stock Level</th>
                        <th>Status</th>
                        <th class="text-end">Cost</th>
                        <th class="text-end">Value</th>
                        <th>Last Restocked</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($stocks as $row)
                    @php
                        $p = $row->product;
                        $available = $row->available;
                        $cost = (float) ($p->purchase_price ?? 0);
                    @endphp
                    <tr>
                        <td>
                            <div class="fw-semibold">{{ Str::limit($p->name ?? ('#'.$row->product_id), 42) }}</div>
                            <div class="small text-muted">
                                {{ optional($p->category)->name }}
                                @if(optional($p->brand)->name) · {{ $p->brand->name }} @endif
                            </div>
                        </td>
                        <td>{{ optional($p->weight)->name ?? '—' }}</td>
                        <td class="text-end inv-num fw-bold">{{ $row->on_hand }}</td>
                        <td class="text-end inv-num">{{ $row->reserved }}</td>
                        <td class="text-end inv-num fw-bold">{{ $available }}</td>
                        <td class="text-center">
                            <form method="POST" action="{{ route('admin.inventory.threshold', $row->product_id) }}" class="d-inline-flex align-items-center gap-1">
                                @csrf
                                <input type="number" min="0" name="low_stock_threshold" value="{{ $row->low_stock_threshold }}" class="inv-threshold-input text-end">
                                <button class="btn btn-sm btn-light btn-action" type="submit" title="Save threshold"><i class="fe-check"></i></button>
                            </form>
                        </td>
                        <td>
                            @if($row->stock_status === 'out_of_stock')
                                <span class="inv-badge inv-badge-out">Out of Stock</span>
                            @elseif($row->stock_status === 'low_stock')
                                <span class="inv-badge inv-badge-low">Low Stock</span>
                            @else
                                <span class="inv-badge inv-badge-in">In Stock</span>
                            @endif
                        </td>
                        <td class="text-end inv-num">৳{{ number_format($cost, 0) }}</td>
                        <td class="text-end inv-num">৳{{ number_format($row->on_hand * $cost, 0) }}</td>
                        <td class="text-nowrap">{{ $row->last_restocked_at ? $row->last_restocked_at->format('M j, Y') : '—' }}</td>
                        <td class="text-center text-nowrap">
                            <a href="{{ route('admin.inventory.movements', ['product_id' => $row->product_id]) }}" class="btn btn-sm btn-light btn-action" title="View History"><i class="fe-list"></i></a>
                            <a href="{{ route('admin.inventory.adjust', ['product_id' => $row->product_id]) }}" class="btn btn-sm btn-light btn-action" title="Adjust Stock"><i class="fe-sliders"></i></a>
                            @if($p && $p->slug)
                            <a href="{{ route('product', $p->slug) }}" target="_blank" class="btn btn-sm btn-light btn-action" title="View Product"><i class="fe-external-link"></i></a>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="11" class="text-center text-muted py-4">No inventory records match your filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($stocks->hasPages())
        <div class="card-body py-3">{{ $stocks->links('pagination::bootstrap-4') }}</div>
        @endif
    </div>

</div>
@endsection
