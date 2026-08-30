@extends('backEnd.layouts.master')
@section('title', 'Add Thana')
@section('content')
<div class="container-fluid py-4"><div class="card"><div class="card-body">
    <h5 class="mb-3">Add Thana - {{ $district->name }}</h5>
    <form action="{{ route('admin.delivery.thanas.store') }}" method="POST">
        @csrf
        <input type="hidden" name="district_id" value="{{ $district->id }}">
        @include('backEnd.delivery.thana_fields', ['data' => null])
        <button type="submit" class="btn btn-success">Save</button>
        <a href="{{ route('admin.delivery.thanas.index', $district) }}" class="btn btn-light">Back</a>
    </form>
</div></div></div>
@endsection
