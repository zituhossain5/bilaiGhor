@extends('backEnd.layouts.master')
@section('title', 'জোন যোগ')
@section('content')
<div class="container-fluid py-4">
    <div class="card">
        <div class="card-body">
            <h5 class="mb-3">নতুন জোন — {{ $district->division->name ?? '' }} — {{ $district->name }}</h5>
            <form action="{{ route('admin.delivery.zones.store') }}" method="POST">
                @csrf
                <input type="hidden" name="district_id" value="{{ $district->id }}">
                <div class="mb-3"><label class="form-label">জেলা</label><input type="text" class="form-control" value="{{ ($district->division->name ?? '') . ' — ' . $district->name }}" disabled></div>
                <div class="mb-3"><label class="form-label">জোনের নাম *</label><input type="text" name="name" value="{{ old('name') }}" class="form-control" required maxlength="190"></div>
                <div class="mb-3"><label class="form-label">বাংলা নাম (ঐচ্ছিক)</label><input type="text" name="name_bn" value="{{ old('name_bn') }}" class="form-control" maxlength="190"></div>
                <div class="mb-3"><label class="form-label">পোস্ট কোড (ঐচ্ছিক)</label><input type="text" name="post_code" value="{{ old('post_code') }}" class="form-control" maxlength="20"></div>
                <div class="mb-3"><label class="form-label">ডেলিভারি চার্জ (৳)</label><input type="number" name="delivery_charge" value="{{ old('delivery_charge', 0) }}" class="form-control" min="0" step="0.01"></div>
                <div class="mb-3"><label class="form-label">সাজানো</label><input type="number" name="sort_order" value="{{ old('sort_order', 0) }}" class="form-control"></div>
                <div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="status" value="1" id="st" checked><label class="form-check-label" for="st">সক্রিয়</label></div>
                <button type="submit" class="btn btn-success">সেভ</button>
                <a href="{{ route('admin.delivery.zones.index', $district->id) }}" class="btn btn-light">ফিরে যান</a>
            </form>
        </div>
    </div>
</div>
@endsection
