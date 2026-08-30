@extends('backEnd.layouts.master')
@section('title', 'Edit Thana')
@section('content')
<div class="container-fluid py-4"><div class="card"><div class="card-body">
    <h5 class="mb-3">Edit Thana</h5>
    <form action="{{ route('admin.delivery.thanas.update') }}" method="POST">
        @csrf
        <input type="hidden" name="id" value="{{ $edit_data->id }}">
        <div class="mb-3">
            <label class="form-label">District *</label>
            <select name="district_id" class="form-select" required>
                @foreach($divisions as $division)
                    <optgroup label="{{ $division->name }}">
                        @foreach($division->districts as $district)
                            <option value="{{ $district->id }}" @selected(old('district_id', $edit_data->district_id) == $district->id)>{{ $district->name }}</option>
                        @endforeach
                    </optgroup>
                @endforeach
            </select>
        </div>
        @include('backEnd.delivery.thana_fields', ['data' => $edit_data])
        <button type="submit" class="btn btn-success">Update</button>
        <a href="{{ route('admin.delivery.thanas.index', $edit_data->district_id) }}" class="btn btn-light">Back</a>
    </form>
</div></div></div>
@endsection
