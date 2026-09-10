@extends('backEnd.layouts.master')
@section('title', 'Inventory Reports')

@section('css')
    @include('backEnd.inventory._style')
@endsection

@section('content')
<div class="container-fluid mb-5">

    <div class="d-sm-flex align-items-center justify-content-between mb-4 mt-2">
        <h1 class="h3 mb-0 text-gray-800 fw-bold">📈 Inventory Reports</h1>
        <a href="{{ route('admin.reports.stock') }}" class="d-none d-sm-inline-block btn btn-sm btn-light shadow-sm">
            <i class="fe-download me-1"></i> Legacy Stock CSV
        </a>
    </div>

    <div class="card mb-4">
        <div class="card-body py-3">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">From</label>
                    <input type="date" name="from_date" value="{{ $from }}" class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="form-label">To</label>
                    <input type="date" name="to_date" value="{{ $to }}" class="form-control">
                </div>
                <div class="col-md-2">
                    <button class="btn btn-primary w-100" type="submit"><i class="fe-filter me-1"></i>Apply</button>
                </div>
            </form>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card admin-stat-card admin-stat-card--success h-100 py-2 border-start border-4 border-success">
                <div class="card-body stats-card">
                    <div class="stats-icon bg-soft-success"><i class="fe-download"></i></div>
                    <div>
                        <div class="stats-label text-success">Stock In (period)</div>
                        <div class="stats-value">{{ number_format($stockIn) }}</div>
                        <div class="stats-sub">purchases, opening stock &amp; restocks</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card admin-stat-card admin-stat-card--danger h-100 py-2 border-start border-4 border-danger">
                <div class="card-body stats-card">
                    <div class="stats-icon bg-soft-danger"><i class="fe-upload"></i></div>
                    <div>
                        <div class="stats-label text-danger">Sold Out (period)</div>
                        <div class="stats-value">{{ number_format($soldOut) }}</div>
                        <div class="stats-sub">units deducted by completed sales</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card admin-stat-card admin-stat-card--warning h-100 py-2 border-start border-4 border-warning">
                <div class="card-body stats-card">
                    <div class="stats-icon bg-soft-warning"><i class="fe-sliders"></i></div>
                    <div>
                        <div class="stats-label text-warning">Adjustments (period)</div>
                        <div class="stats-value">{{ number_format($adjustments) }}</div>
                        <div class="stats-sub">manual / damage / correction entries</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-5 mb-4">
            <div class="card">
                <div class="card-header">Movement Breakdown ({{ $from }} → {{ $to }})</div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>Type</th><th class="text-end">Movements</th><th class="text-end">On Hand Δ</th><th class="text-end">Reserved Δ</th></tr></thead>
                        <tbody>
                            @forelse($byType as $t)
                            <tr>
                                <td>{{ ucwords(str_replace('_', ' ', $t->movement_type)) }}</td>
                                <td class="text-end inv-num">{{ $t->movements }}</td>
                                <td class="text-end inv-num {{ $t->on_hand_delta > 0 ? 'inv-qty-in' : ($t->on_hand_delta < 0 ? 'inv-qty-out' : '') }}">{{ $t->on_hand_delta > 0 ? '+' : '' }}{{ (int) $t->on_hand_delta }}</td>
                                <td class="text-end inv-num">{{ $t->reserved_delta > 0 ? '+' : '' }}{{ (int) $t->reserved_delta }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="4" class="text-center text-muted py-4">No movements in this period.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-7 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    Inventory Valuation (current)
                    <span class="fw-bold">Total: ৳ {{ number_format($valuationTotal, 2) }}</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>Product</th><th class="text-end">On Hand</th><th class="text-end">Purchase Cost</th><th class="text-end">Value</th></tr></thead>
                        <tbody>
                            @forelse($valuation as $row)
                            @php $cost = (float) (optional($row->product)->purchase_price ?? 0); @endphp
                            <tr>
                                <td>{{ Str::limit(optional($row->product)->name ?? ('#'.$row->product_id), 40) }}</td>
                                <td class="text-end inv-num">{{ $row->on_hand }}</td>
                                <td class="text-end inv-num">৳{{ number_format($cost, 2) }}</td>
                                <td class="text-end inv-num fw-bold">৳{{ number_format($row->on_hand * $cost, 2) }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="4" class="text-center text-muted py-4">No tracked products.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($valuation->hasPages())
                <div class="card-body py-3">{{ $valuation->links('pagination::bootstrap-4') }}</div>
                @endif
            </div>
        </div>
    </div>

</div>
@endsection
