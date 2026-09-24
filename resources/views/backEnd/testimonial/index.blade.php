@extends('backEnd.layouts.master')
@section('title','Testimonials')

@section('css')
<style>
    .card { border: none; box-shadow: 0 0 20px rgba(18,38,63,0.03); border-radius: 12px; overflow: hidden; }
    .card-body { padding: 25px; }
    .badge-soft-success { background-color: rgba(10,207,151,0.18); color: #0acf97; }
    .badge-soft-danger { background-color: rgba(250,92,124,0.18); color: #fa5c7c; }
    .badge-soft-secondary { background-color: rgba(131,145,162,0.18); color: #8391a2; }
    .badge-pill { padding: 5px 10px; border-radius: 50rem; font-weight: 500; font-size: 11px; }
    .action-btn { width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; border-radius: 50%; color: #6c757d; border: 1px solid transparent; background: #f9fbfd; transition: all 0.2s; }
    .btn-edit:hover { background-color: rgba(114,124,245,0.1); color: #727cf5; }
    .btn-delete:hover { background-color: rgba(250,92,124,0.1); color: #fa5c7c; }
    .testimonial-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(230px, 1fr)); gap: 20px; }
    .testimonial-tile { border: 1px solid #eef2f7; border-radius: 12px; overflow: hidden; background: #fff; transition: box-shadow 0.2s, transform 0.2s; }
    .testimonial-tile:hover { box-shadow: 0 6px 20px rgba(18,38,63,0.08); transform: translateY(-3px); }
    .testimonial-thumb { height: 220px; background: #f9fbfd; display: flex; align-items: center; justify-content: center; border-bottom: 1px solid #f1f5f7; }
    .testimonial-thumb img { max-width: 100%; max-height: 100%; object-fit: contain; display: block; }
    .testimonial-thumb .no-image { color: #98a6ad; font-size: 28px; }
    .testimonial-meta { padding: 14px 16px; display: flex; align-items: center; justify-content: space-between; gap: 10px; }
    .testimonial-sort { font-size: 12px; color: #8391a2; font-weight: 600; }
    .empty-state { text-align: center; padding: 50px 20px; color: #8391a2; }
    .empty-state i { font-size: 40px; display: block; margin-bottom: 12px; }
</style>
@endsection

@section('content')
<div class="container-fluid">
    <div class="row mb-3 mt-3">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h4 class="page-title mb-0" style="font-weight:700;color:#2d3436;">Testimonials</h4>
            <a href="{{ route('admin.testimonial.create') }}" class="btn btn-primary rounded-pill shadow-sm px-4">
                <i class="fe-plus me-1"></i> Add New
            </a>
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
                <div class="card-body">
                    @if($testimonials->count())
                    <div class="testimonial-grid">
                        @foreach($testimonials as $t)
                        <div class="testimonial-tile">
                            <div class="testimonial-thumb">
                                @if($t->image)
                                    <img src="{{ asset('public/'.$t->image) }}" alt="Testimonial #{{ $t->id }}" loading="lazy">
                                @else
                                    <i class="fe-image no-image"></i>
                                @endif
                            </div>
                            <div class="testimonial-meta">
                                <div>
                                    <div class="testimonial-sort mb-1">Sort: {{ $t->sort_order }}</div>
                                    @if($t->status)
                                        <span class="badge badge-pill badge-soft-success">Active</span>
                                    @else
                                        <span class="badge badge-pill badge-soft-danger">Inactive</span>
                                    @endif
                                </div>
                                <div class="d-inline-flex gap-2">
                                    <a href="{{ route('admin.testimonial.edit', $t->id) }}" class="action-btn btn-edit" title="Edit">
                                        <i class="fe-edit"></i>
                                    </a>
                                    <a href="{{ route('admin.testimonial.delete', $t->id) }}"
                                       onclick="return confirm('Delete this testimonial?')"
                                       class="action-btn btn-delete" title="Delete">
                                        <i class="fe-trash-2"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <div class="empty-state">
                        <i class="fe-image"></i>
                        No testimonials yet. Click <strong>Add New</strong> to upload review screenshots.
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
