@extends('backEnd.layouts.master')
@section('title','Kitten Packs')

@section('css')
<style>
    .card { border: none; box-shadow: 0 0 20px rgba(18,38,63,0.03); border-radius: 12px; overflow: hidden; margin-bottom: 24px; }
    .card-header { background: #fff; border-bottom: 1px solid #f1f5f7; padding: 20px 25px; display: flex; align-items: center; gap: 10px; }
    .card-title { font-size: 16px; font-weight: 700; color: #2d3436; margin: 0; }
    .header-icon { width: 35px; height: 35px; background: rgba(114,124,245,0.1); color: #727cf5; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 16px; }
    .card-body { padding: 25px; }
    .table thead th { background-color: #f9fbfd; font-weight: 600; text-transform: uppercase; font-size: 11px; color: #8391a2; letter-spacing: 0.5px; border-bottom: 1px solid #eef2f7; padding: 12px 15px; }
    .table tbody td { vertical-align: middle; padding: 12px 15px; border-bottom: 1px solid #f1f5f7; color: #313b5e; font-size: 14px; }
    .pack-thumb { width: 64px; height: 64px; object-fit: contain; border-radius: 8px; background: #f9fbfd; border: 1px solid #eef2f7; }
    .badge-soft-success { background-color: rgba(10,207,151,0.18); color: #0acf97; }
    .badge-soft-danger { background-color: rgba(250,92,124,0.18); color: #fa5c7c; }
    .badge-soft-warning { background-color: rgba(255,188,0,0.18); color: #d9a300; }
    .badge-soft-secondary { background-color: rgba(131,145,162,0.18); color: #8391a2; }
    .badge-pill { padding: 5px 10px; border-radius: 50rem; font-weight: 500; font-size: 11px; }
    .action-btn { width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; border-radius: 50%; color: #6c757d; border: 1px solid transparent; background: #f9fbfd; transition: all 0.2s; }
    .btn-edit:hover { background-color: rgba(114,124,245,0.1); color: #727cf5; }
    .btn-delete:hover { background-color: rgba(250,92,124,0.1); color: #fa5c7c; }
    .form-select, .form-control { background-color: #fbfcff; border: 1px solid #eef2f7; padding: 10px 15px; border-radius: 8px; font-size: 14px; }
    .btn-submit { background: linear-gradient(45deg,#0acf97,#06b6d4); border: none; color: white; padding: 10px 24px; font-weight: 600; box-shadow: 0 4px 15px rgba(10,207,151,0.3); }
    .btn-submit:hover { color: #fff; }
</style>
@endsection

@section('content')
<div class="container-fluid">
    <div class="row mb-3 mt-3">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h4 class="page-title mb-0" style="font-weight:700;color:#2d3436;">Kitten Packs</h4>
            <div class="d-flex gap-2">
                <a href="{{ route('kitten.packs') }}" target="_blank" class="btn btn-light rounded-pill border shadow-sm px-4">
                    <i class="fe-external-link me-1"></i> View Page
                </a>
                <a href="{{ route('admin.kitten-pack.create') }}" class="btn btn-primary rounded-pill shadow-sm px-4">
                    <i class="fe-plus me-1"></i> Add New
                </a>
            </div>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="header-icon"><i class="fe-package"></i></div>
                    <h5 class="card-title">Packs</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover w-100">
                            <thead>
                                <tr>
                                    <th style="width:50px;">SL</th>
                                    <th>Image</th>
                                    <th>Name</th>
                                    <th>Tier</th>
                                    <th>Items</th>
                                    <th>Price</th>
                                    <th>Linked Product</th>
                                    <th>Sort</th>
                                    <th>Status</th>
                                    <th class="text-end" style="width:120px;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($packs as $key => $pack)
                                <tr>
                                    <td>{{ $key + 1 }}</td>
                                    <td>
                                        @if($pack->image)
                                            <img src="{{ asset('public/'.$pack->image) }}" class="pack-thumb" alt="{{ $pack->name }}">
                                        @else
                                            <div class="pack-thumb d-flex align-items-center justify-content-center text-muted"><i class="fe-image"></i></div>
                                        @endif
                                    </td>
                                    <td>
                                        <strong>{{ $pack->name }}</strong>
                                        @if($pack->badge)
                                            <span class="badge badge-pill badge-soft-warning ms-1">{{ $pack->badge }}</span>
                                        @endif
                                        @if($pack->theme === 'dark')
                                            <span class="badge badge-pill badge-soft-secondary ms-1">Dark</span>
                                        @endif
                                    </td>
                                    <td>{{ $pack->tier_label ?? '—' }}</td>
                                    <td>{{ $pack->items_count }} rows</td>
                                    <td>
                                        ৳{{ number_format($pack->price, 0) }}
                                        @if($pack->old_price)
                                            <del class="text-muted small">৳{{ number_format($pack->old_price, 0) }}</del>
                                        @endif
                                    </td>
                                    <td>
                                        @if($pack->product_id)
                                            <span class="badge badge-pill badge-soft-success">Linked</span>
                                        @else
                                            <span class="badge badge-pill badge-soft-danger" title="Buy Now falls back to WhatsApp">Not linked</span>
                                        @endif
                                    </td>
                                    <td>{{ $pack->sort_order }}</td>
                                    <td>
                                        @if($pack->status)
                                            <span class="badge badge-pill badge-soft-success">Active</span>
                                        @else
                                            <span class="badge badge-pill badge-soft-danger">Inactive</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <div class="d-inline-flex gap-2">
                                            <a href="{{ route('admin.kitten-pack.edit', $pack->id) }}" class="action-btn btn-edit" title="Edit">
                                                <i class="fe-edit"></i>
                                            </a>
                                            <a href="{{ route('admin.kitten-pack.delete', $pack->id) }}"
                                               onclick="return confirm('Delete this pack and all of its items?')"
                                               class="action-btn btn-delete" title="Delete">
                                                <i class="fe-trash-2"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="10" class="text-center text-muted py-4">No kitten packs yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="header-icon"><i class="fe-plus-square"></i></div>
                    <h5 class="card-title">"Add to Your Kit" Products</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.kitten-pack.addons.store') }}" method="POST">
                        @csrf
                        <label class="form-label fw-bold" style="font-size:13px;color:#636e72;">Pick the products shown in the add-on row</label>
                        <select name="product_ids[]" class="form-select" multiple size="10">
                            @php $selected = $addons->pluck('product_id')->all(); @endphp
                            @foreach(\App\Models\Product::where('status', 1)->orderBy('name')->get(['id','name']) as $product)
                                <option value="{{ $product->id }}" {{ in_array($product->id, $selected) ? 'selected' : '' }}>
                                    {{ $product->name }}
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted d-block mt-2">
                            Hold Ctrl (Cmd on Mac) to select more than one. Leave empty to fall back to the 4 most recent active products.
                        </small>
                        <button type="submit" class="btn btn-submit rounded-pill mt-3">
                            <i class="fe-save me-1"></i> Save Add-ons
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
