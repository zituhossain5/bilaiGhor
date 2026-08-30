@extends('backEnd.layouts.master')
@section('title', 'Thanas')
@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <a href="{{ route('admin.delivery.districts.index', $district->division_id) }}" class="btn btn-sm btn-outline-secondary mb-1">&larr; Districts</a>
            <h4 class="fw-bold m-0">{{ $district->division->name ?? '' }} - {{ $district->name }}</h4>
            <small class="text-muted">Manage Thana delivery charges</small>
        </div>
        <a href="{{ route('admin.delivery.thanas.create', $district) }}" class="btn btn-primary"><i class="fe-plus"></i> Add Thana</a>
    </div>
    <div class="card"><div class="card-body"><div class="table-responsive">
        <table class="table table-bordered table-striped">
            <thead><tr><th>#</th><th>Thana</th><th>Post Code</th><th>Charge (Tk)</th><th>Order</th><th>Status</th><th class="text-end">Action</th></tr></thead>
            <tbody>
                @forelse($show_data as $value)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $value->name }} @if($value->name_bn)<small class="text-muted d-block">{{ $value->name_bn }}</small>@endif</td>
                        <td>{{ $value->post_code ?: '-' }}</td>
                        <td>{{ number_format($value->delivery_charge, 0) }}</td>
                        <td>{{ $value->sort_order }}</td>
                        <td>{{ $value->status ? 'Active' : 'Inactive' }}</td>
                        <td class="text-end">
                            <a href="{{ route('admin.delivery.thanas.edit', $value) }}" class="btn btn-sm btn-primary">Edit</a>
                            <form action="{{ route('admin.delivery.thanas.destroy') }}" method="POST" class="d-inline delete-form-delivery">
                                @csrf
                                <input type="hidden" name="hidden_id" value="{{ $value->id }}">
                                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">No Thana has been added for this district.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div></div></div>
</div>
@endsection
@section('script')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.querySelectorAll('.delete-form-delivery').forEach(function (form) {
    form.addEventListener('submit', function (event) {
        event.preventDefault();
        Swal.fire({title: 'Delete this Thana?', icon: 'warning', showCancelButton: true, confirmButtonText: 'Yes'})
            .then(function (result) { if (result.isConfirmed) form.submit(); });
    });
});
</script>
@endsection
