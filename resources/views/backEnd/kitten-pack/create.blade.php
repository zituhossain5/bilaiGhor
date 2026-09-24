@extends('backEnd.layouts.master')
@section('title','Add Kitten Pack')

@section('css')
    @include('backEnd.kitten-pack._styles')
@endsection

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between py-4">
                <div>
                    <h4 class="page-title mb-1 text-dark fw-bold">Add Kitten Pack</h4>
                    <p class="text-muted font-size-13 mb-0">Create a starter kit shown on the Kitten Packs page.</p>
                </div>
                <div class="page-title-right">
                    <a href="{{ route('admin.kitten-pack.index') }}" class="btn btn-light rounded-pill border shadow-sm px-4">
                        <i class="fe-arrow-left me-1"></i> Back
                    </a>
                </div>
            </div>
        </div>
    </div>

    <form action="{{ route('admin.kitten-pack.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        @include('backEnd.kitten-pack._form', ['pack' => null, 'submitLabel' => 'Save Pack'])
    </form>
</div>
@endsection

@section('script')
    @include('backEnd.kitten-pack._scripts')
@endsection
