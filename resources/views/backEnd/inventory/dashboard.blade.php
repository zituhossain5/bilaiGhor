@extends('backEnd.layouts.master')
@section('title', 'Inventory Dashboard')

@section('css')
    @include('backEnd.inventory._style')
@endsection

@section('content')
<div class="container-fluid mb-5">

    <div class="d-sm-flex align-items-center justify-content-between mb-4 mt-2">
        <h1 class="h3 mb-0 text-gray-800 fw-bold">📦 Inventory Dashboard</h1>
        <a href="{{ route('admin.inventory.adjust') }}" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
            <i class="fe-sliders me-1"></i> Adjust Stock
        </a>
    </div>

    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card h-100 py-2 border-start border-4 border-primary">
                <div class="card-body stats-card">
                    <div class="stats-icon bg-soft-primary"><i class="fe-box"></i></div>
                    <div>
                        <div class="stats-label text-primary">Tracked Products</div>
                        <div class="stats-value">{{ number_format($totals->products) }}</div>
                        <div class="stats-sub">under inventory control</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card h-100 py-2 border-start border-4 border-success">
                <div class="card-body stats-card">
                    <div class="stats-icon bg-soft-success"><i class="fe-layers"></i></div>
                    <div>
                        <div class="stats-label text-success">On-Hand Units</div>
                        <div class="stats-value">{{ number_format($totals->on_hand) }}</div>
                        <div class="stats-sub">physically in stock</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card h-100 py-2 border-start border-4 border-info">
                <div class="card-body stats-card">
                    <div class="stats-icon bg-soft-info"><i class="fe-clock"></i></div>
                    <div>
                        <div class="stats-label text-info">Reserved Units</div>
                        <div class="stats-value">{{ number_format($totals->reserved) }}</div>
                        <div class="stats-sub">committed to active orders</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card h-100 py-2 border-start border-4 border-success">
                <div class="card-body stats-card">
                    <div class="stats-icon bg-soft-success"><i class="fe-check-circle"></i></div>
                    <div>
                        <div class="stats-label text-success">Available Units</div>
                        <div class="stats-value">{{ number_format($totals->available) }}</div>
                        <div class="stats-sub">on hand − reserved</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card h-100 py-2 border-start border-4 border-warning">
                <div class="card-body stats-card">
                    <div class="stats-icon bg-soft-warning"><i class="fe-alert-triangle"></i></div>
                    <div>
                        <div class="stats-label text-warning">Low Stock</div>
                        <div class="stats-value">{{ number_format($lowStockCount) }}</div>
                        <div class="stats-sub"><a href="{{ route('admin.inventory.low_stock') }}">view products →</a></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card h-100 py-2 border-start border-4 border-danger">
                <div class="card-body stats-card">
                    <div class="stats-icon bg-soft-danger"><i class="fe-x-circle"></i></div>
                    <div>
                        <div class="stats-label text-danger">Out of Stock</div>
                        <div class="stats-value">{{ number_format($outOfStockCount) }}</div>
                        <div class="stats-sub">available ≤ 0</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-6 col-md-12 mb-4">
            <div class="card h-100 py-2 border-start border-4 border-primary">
                <div class="card-body stats-card">
                    <div class="stats-icon bg-soft-primary"><i class="fe-dollar-sign"></i></div>
                    <div>
                        <div class="stats-label text-primary">Inventory Value</div>
                        <div class="stats-value">৳ {{ number_format($inventoryValue, 2) }}</div>
                        <div class="stats-sub">physical on-hand quantity x latest purchase cost</div>
                        <div class="stats-sub mt-1">
                            Available: &#2547;{{ number_format($accounting['inventory_available_cost'], 2) }}
                            + Reserved: &#2547;{{ number_format($accounting['inventory_reserved_cost'], 2) }}
                        </div>
                        <div class="stats-sub mt-1">
                            Available retail value: &#2547;{{ number_format($accounting['available_retail_value'], 2) }} (not cost)
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-7 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    Recent Stock Movements
                    <a href="{{ route('admin.inventory.movements') }}" class="small">View all</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>Date</th><th>Product</th><th>Type</th><th class="text-end">On Hand Δ</th><th class="text-end">After</th></tr></thead>
                        <tbody>
                            @forelse($recentMovements as $m)
                            <tr>
                                <td class="text-nowrap">{{ $m->created_at->format('M j, H:i') }}</td>
                                <td>{{ Str::limit(optional($m->product)->name ?? ('#'.$m->product_id), 34) }}</td>
                                <td><span class="inv-badge {{ $m->on_hand_change > 0 ? 'inv-badge-in' : ($m->on_hand_change < 0 ? 'inv-badge-out' : 'inv-badge-low') }}">{{ str_replace('_', ' ', $m->movement_type) }}</span></td>
                                <td class="text-end inv-num {{ $m->on_hand_change > 0 ? 'inv-qty-in' : ($m->on_hand_change < 0 ? 'inv-qty-out' : '') }}">{{ $m->on_hand_change > 0 ? '+' : '' }}{{ $m->on_hand_change }}</td>
                                <td class="text-end inv-num">{{ $m->new_on_hand }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="5" class="text-center text-muted py-4">No movements yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-5 mb-4">
            <div class="card">
                <div class="card-header">Recently Restocked</div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>Product</th><th class="text-end">On Hand</th><th class="text-end">Restocked</th></tr></thead>
                        <tbody>
                            @forelse($recentRestocks as $r)
                            <tr>
                                <td>{{ Str::limit(optional($r->product)->name ?? ('#'.$r->product_id), 30) }}</td>
                                <td class="text-end inv-num">{{ $r->on_hand }}</td>
                                <td class="text-end text-nowrap">{{ $r->last_restocked_at->format('M j, Y') }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="3" class="text-center text-muted py-4">Nothing restocked yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
