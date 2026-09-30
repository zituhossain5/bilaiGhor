@extends('backEnd.layouts.master')
@section('title','Search Misses')

@section('css')
<style>
    .card { border: none; box-shadow: 0 0 20px rgba(18,38,63,0.03); border-radius: 12px; overflow: hidden; margin-bottom: 24px; }
    .card-header { background: #fff; border-bottom: 1px solid #f1f5f7; padding: 20px 25px; display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
    .card-title { font-size: 16px; font-weight: 700; color: #2d3436; margin: 0; }
    .header-icon { width: 35px; height: 35px; background: rgba(114,124,245,0.1); color: #727cf5; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 16px; }
    .card-body { padding: 25px; }
    .stat-card { background: #fff; border-radius: 12px; padding: 18px 20px; box-shadow: 0 0 20px rgba(18,38,63,0.03); }
    .stat-title { font-size: 12px; color: #8391a2; font-weight: 600; text-transform: uppercase; margin-bottom: 4px; }
    .stat-value { font-size: 22px; font-weight: 700; color: #2d3436; margin: 0; }
    .table thead th { background-color: #f9fbfd; font-weight: 600; text-transform: uppercase; font-size: 11px; color: #8391a2; letter-spacing: 0.5px; border-bottom: 1px solid #eef2f7; padding: 12px 15px; }
    .table tbody td { vertical-align: middle; padding: 12px 15px; border-bottom: 1px solid #f1f5f7; color: #313b5e; font-size: 14px; }
    .query-text { font-weight: 600; word-break: break-word; }
    .count-pill { display: inline-block; min-width: 34px; padding: 4px 10px; border-radius: 50rem; background: rgba(114,124,245,0.12); color: #727cf5; font-weight: 600; font-size: 12px; text-align: center; }
    .form-select, .form-control { background-color: #fbfcff; border: 1px solid #eef2f7; padding: 8px 14px; border-radius: 8px; font-size: 14px; }
</style>
@endsection

@section('content')
<div class="container-fluid">
    <div class="row mb-3 mt-3">
        <div class="col-12">
            <h4 class="page-title mb-1" style="font-weight:700;color:#2d3436;">Search Misses</h4>
            <p class="text-muted mb-0">Storefront searches that found no products. Rename products or add them to the product's <strong>Meta Keywords</strong> field so these start matching, then mark them resolved.</p>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <div class="row">
        <div class="col-md-4 mb-3">
            <div class="stat-card">
                <p class="stat-title">Unmatched queries</p>
                <p class="stat-value">{{ number_format($totals['queries']) }}</p>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="stat-card">
                <p class="stat-title">Total failed searches</p>
                <p class="stat-value">{{ number_format($totals['searches']) }}</p>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="stat-card">
                <p class="stat-title">Seen in last 7 days</p>
                <p class="stat-value">{{ number_format($totals['week']) }}</p>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="header-icon"><i class="fe-search"></i></div>
            <h5 class="card-title me-auto">Queries</h5>
            <form method="GET" class="d-flex gap-2 flex-wrap">
                <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="Filter queries..." style="width:200px;">
                <select name="sort" class="form-select" style="width:auto;" onchange="this.form.submit()">
                    <option value="recent" @selected($sort === 'recent')>Most recent</option>
                    <option value="count" @selected($sort === 'count')>Most searched</option>
                </select>
                <button type="submit" class="btn btn-primary rounded-pill px-3">Filter</button>
            </form>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover w-100">
                    <thead>
                        <tr>
                            <th>Query</th>
                            <th style="width:110px;">Searches</th>
                            <th style="width:180px;">Last searched</th>
                            <th style="width:180px;">First seen</th>
                            <th class="text-end" style="width:200px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($misses as $miss)
                        <tr>
                            <td class="query-text">{{ $miss->query }}</td>
                            <td><span class="count-pill">{{ $miss->search_count }}</span></td>
                            <td>{{ optional($miss->last_searched_at)->format('d M Y, h:i A') }}</td>
                            <td>{{ $miss->created_at->format('d M Y') }}</td>
                            <td class="text-end">
                                <a href="{{ route('search', ['keyword' => $miss->query]) }}" target="_blank" class="btn btn-sm btn-light border rounded-pill">Try it</a>
                                <form action="{{ route('admin.reports.search_misses.destroy', $miss) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-success rounded-pill">Resolved</button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">No failed searches logged{{ request('q') ? ' for this filter' : ' yet' }}.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($misses->hasPages())
            <div class="mt-3">{{ $misses->links('pagination::bootstrap-5') }}</div>
            @endif
        </div>
    </div>
</div>
@endsection
