@extends('backEnd.layouts.master')
@section('title', 'জোন এডিট')
@section('content')
<div class="container-fluid py-4">
    <div class="card">
        <div class="card-body">
            <h5 class="mb-3">জোন এডিট</h5>
            <form action="{{ route('admin.delivery.zones.update') }}" method="POST">
                @csrf
                <input type="hidden" name="id" value="{{ $edit_data->id }}">
                <div class="mb-3">
                    <label class="form-label">জেলা *</label>
                    <select name="district_id" class="form-select" required>
                        @foreach($divisions as $division)
                            <optgroup label="{{ $division->name }}">
                                @foreach($division->districts as $d)
                                    <option value="{{ $d->id }}" @selected(old('district_id', $edit_data->district_id) == $d->id)>{{ $d->name }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3"><label class="form-label">জোনের নাম *</label><input type="text" name="name" value="{{ old('name', $edit_data->name) }}" class="form-control" required maxlength="190"></div>
                <div class="mb-3"><label class="form-label">বাংলা নাম (ঐচ্ছিক)</label><input type="text" name="name_bn" value="{{ old('name_bn', $edit_data->name_bn) }}" class="form-control" maxlength="190"></div>
                <div class="mb-3"><label class="form-label">পোস্ট কোড (ঐচ্ছিক)</label><input type="text" name="post_code" value="{{ old('post_code', $edit_data->post_code) }}" class="form-control" maxlength="20"></div>
                <div class="mb-3"><label class="form-label">ডেলিভারি চার্জ (৳)</label><input type="number" name="delivery_charge" value="{{ old('delivery_charge', $edit_data->delivery_charge) }}" class="form-control" min="0" step="0.01"></div>
                <div class="mb-3"><label class="form-label">সাজানো</label><input type="number" name="sort_order" value="{{ old('sort_order', $edit_data->sort_order) }}" class="form-control"></div>
                <div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="status" value="1" id="st" {{ $edit_data->status ? 'checked' : '' }}><label class="form-check-label" for="st">সক্রিয়</label></div>
                <button type="submit" class="btn btn-success">আপডেট</button>
                <a href="{{ route('admin.delivery.zones.index', $edit_data->district_id) }}" class="btn btn-light">ফিরে যান</a>
            </form>
        </div>
    </div>
</div>
@endsection
