@extends('backEnd.layouts.master')
@section('title', 'Create Manual Invoice')

@section('css')
    @include('backEnd.manual_orders.partials.form-styles')
@endsection

@section('content')
<div class="container-fluid mo-create">
    <div class="page-title-box d-flex align-items-center justify-content-between">
        <h4 class="page-title">Create Manual Invoice</h4>
        <a href="{{ route('admin.manual_orders.index') }}" class="btn btn-light">Back to List</a>
    </div>

    @include('backEnd.manual_orders.partials.form', ['isEdit' => false])
</div>
@endsection

@section('script')
    @include('backEnd.manual_orders.partials.form-script', ['isEdit' => false])
@endsection
