@extends('backEnd.layouts.master')
@section('title', 'Product Short Names')

@section('css')
<style>
    .sn-card { border: none; border-radius: 12px; box-shadow: 0 0 20px rgba(18, 38, 63, 0.03); }
    .sn-card .card-body { padding: 22px; }
    .sn-table thead th { background: #f9fbfd; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .5px; color: #8391a2; border-bottom: 1px solid #eef2f7; padding: 10px 12px; }
    .sn-table tbody td { padding: 10px 12px; vertical-align: middle; border-bottom: 1px solid #f1f5f7; font-size: 13.5px; color: #313b5e; }
    .sn-full { max-width: 460px; }
    .sn-code { font-size: 11.5px; color: #8391a2; }
    .sn-arrow { color: #b0b7c3; }
    .sn-input-wrap { min-width: 260px; }
    .sn-count { font-size: 11px; color: #8391a2; }
    .sn-count.is-full { color: #dc3545; }
    .sn-table tr.is-skipped td { opacity: .55; }
</style>
@endsection

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex flex-wrap justify-content-between align-items-center gap-2">
                <h4 class="page-title mb-0">Product Short Names</h4>
                <a href="{{ route('inhouse.products.index') }}" class="btn btn-light btn-sm"><i class="fe-arrow-left me-1"></i> Back to products</a>
            </div>
        </div>
    </div>

    <div class="card sn-card">
        <div class="card-body">
            @if($rows->isEmpty())
                <div class="text-center py-4">
                    <h5 class="mb-1">Every product has a short name.</h5>
                    <p class="text-muted mb-0">Edit a product to change its short name.</p>
                </div>
            @else
                <p class="text-muted mb-3">
                    {{ $remaining }} product(s) don't have a short name.
                    @if($remaining > $rows->count()) Showing the first {{ $rows->count() }}; save them to see the next batch. @endif
                    Check each suggestion, edit it if needed (max {{ $max }} characters) and untick any product you want to skip.
                    Receipts print the full name, shortened, for products without one.
                </p>

                @if($errors->any())
                    <div class="alert alert-danger">{{ $errors->first() }}</div>
                @endif

                <form action="{{ route('products.short_names.store') }}" method="POST" id="short-names-form">
                    @csrf
                    <div class="table-responsive">
                        <table class="table sn-table mb-3">
                            <thead>
                                <tr>
                                    <th style="width:40px;"><input type="checkbox" class="form-check-input" id="sn-all" checked aria-label="Select all"></th>
                                    <th>Full name</th>
                                    <th style="width:24px;"></th>
                                    <th>Short name</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($rows as $row)
                                    <tr>
                                        <td><input type="checkbox" class="form-check-input sn-apply" name="apply[]" value="{{ $row['id'] }}" checked aria-label="Save {{ $row['name'] }}"></td>
                                        <td class="sn-full">
                                            <div>{{ $row['name'] }}</div>
                                            <div class="sn-code">{{ $row['code'] }}@unless($row['active']) · inactive @endunless</div>
                                        </td>
                                        <td class="sn-arrow">&rarr;</td>
                                        <td class="sn-input-wrap">
                                            <input type="text" class="form-control form-control-sm sn-input" name="names[{{ $row['id'] }}]"
                                                   value="{{ old('names.' . $row['id'], $row['suggestion']) }}" maxlength="{{ $max }}" autocomplete="off">
                                            <span class="sn-count"></span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('inhouse.products.index') }}" class="btn btn-light">Cancel</a>
                        <button type="submit" class="btn btn-primary"><i class="fe-save me-1"></i> Save ticked short names</button>
                    </div>
                </form>
            @endif
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
(function () {
    var form = document.getElementById('short-names-form');
    if (!form) return;
    var all = document.getElementById('sn-all');
    var boxes = form.querySelectorAll('.sn-apply');

    function sync(row) {
        var box = row.querySelector('.sn-apply');
        var input = row.querySelector('.sn-input');
        var count = row.querySelector('.sn-count');
        row.classList.toggle('is-skipped', !box.checked);
        count.textContent = input.value.length + '/' + input.maxLength;
        count.classList.toggle('is-full', input.value.length >= input.maxLength);
    }

    form.querySelectorAll('tbody tr').forEach(sync);
    form.addEventListener('input', function (e) { if (e.target.closest('tr')) sync(e.target.closest('tr')); });
    form.addEventListener('change', function (e) {
        if (e.target === all) {
            boxes.forEach(function (box) { box.checked = all.checked; sync(box.closest('tr')); });
        } else if (e.target.closest('tbody tr')) {
            sync(e.target.closest('tr'));
            all.checked = Array.prototype.every.call(boxes, function (box) { return box.checked; });
        }
    });
}());
</script>
@endsection
