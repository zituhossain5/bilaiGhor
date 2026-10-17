@extends('backEnd.layouts.master')
@section('title', 'Import Products')

@section('css')
@include('backEnd.product.import.styles')
@endsection

@section('content')
<div class="container-fluid pi-page">
    <div class="page-title-box d-flex flex-wrap justify-content-between align-items-center gap-2">
        <h4 class="page-title mb-0">Import Products</h4>
        <a href="{{ route('inhouse.products.index') }}" class="btn btn-light btn-sm"><i class="fe-arrow-left me-1"></i> Back to products</a>
    </div>

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card pi-card h-100">
                <div class="card-body">
                    <div class="pi-step">1</div>
                    <h5 class="mb-2">Download the template</h5>
                    <p class="text-muted mb-3">
                        An Excel file with a Products sheet (one row per product), Variants and Wholesale sheets,
                        Instructions for every column, and dropdowns filled from your current categories, brands,
                        weights, flavors, colours and sizes.
                    </p>
                    <a href="{{ route('products.import.template') }}" class="btn btn-primary"><i class="fe-download me-1"></i> Download template</a>
                    <ul class="pi-tips mt-3 mb-0">
                        <li>Leave Short Name and the SEO columns empty: they are filled in for you.</li>
                        <li>Images: https:// links, or file names of images in a ZIP you upload with the file.</li>
                        <li>Nothing is saved until you check the preview and press Import.</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card pi-card">
                <div class="card-body">
                    <div class="pi-step">2</div>
                    <h5 class="mb-3">Upload the filled-in file</h5>

                    @if($errors->any())
                        <div class="alert alert-danger">
                            @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
                        </div>
                    @endif

                    <form action="{{ route('products.import.upload') }}" method="POST" enctype="multipart/form-data" id="pi-upload-form">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="pi-file">Excel file (.xlsx) <span class="text-danger">*</span></label>
                                <input type="file" name="file" id="pi-file" class="form-control" accept=".xlsx" required>
                                <small class="text-muted">Max 10 MB, up to 1,000 products.</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="pi-zip">Images (.zip, optional)</label>
                                <input type="file" name="images_zip" id="pi-zip" class="form-control" accept=".zip">
                                <small class="text-muted">Only needed when the file uses image file names. JPG, PNG, WebP or GIF, max 5 MB each.</small>
                            </div>
                        </div>

                        <label class="form-label mt-4 d-block">What should the import do?</label>
                        <div class="pi-modes">
                            @foreach($modes as $value => $label)
                                @php $locked = $value !== 'create' && !$canUpdate; @endphp
                                <label @class(['pi-mode', 'is-disabled' => $locked])>
                                    <input type="radio" name="mode" value="{{ $value }}" @checked(old('mode', 'create') === $value) @disabled($locked)>
                                    <span>
                                        <strong>{{ $label }}</strong>
                                        <small>
                                            @switch($value)
                                                @case('create') Adds new products. Rows whose SKU already exists are skipped. @break
                                                @case('update') Changes products found by SKU. Only filled-in cells change anything. @break
                                                @default New SKUs are added, existing SKUs are updated.
                                            @endswitch
                                            @if($locked)<em class="d-block">Needs the product edit permission.</em>@endif
                                        </small>
                                    </span>
                                </label>
                            @endforeach
                        </div>

                        <div class="mt-4">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="auto_create" value="1" id="pi-auto-create" @checked(old('auto_create'))>
                                <label class="form-check-label" for="pi-auto-create">
                                    <strong>Auto-create missing items</strong> — brands, weights, life stages, flavors, colours and sizes that are not in the shop yet
                                    are created (the preview lists them first). Off: they are errors. Categories are never created.
                                </label>
                            </div>
                            <div class="form-check" id="pi-update-stock-wrap">
                                <input class="form-check-input" type="checkbox" name="update_stock" value="1" id="pi-update-stock" @checked(old('update_stock'))>
                                <label class="form-check-label" for="pi-update-stock">
                                    <strong>Update stock from file</strong> (update modes only) — existing products get the Stock in the file,
                                    recorded in the stock log as a correction. Off: their stock is not touched.
                                </label>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end mt-4">
                            <button type="submit" class="btn btn-success" id="pi-submit"><i class="fe-upload me-1"></i> Upload and preview</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
(function () {
    var form = document.getElementById('pi-upload-form');
    var stock = document.getElementById('pi-update-stock');
    var wrap = document.getElementById('pi-update-stock-wrap');

    function syncMode() {
        var mode = (form.querySelector('input[name="mode"]:checked') || {}).value;
        var updating = mode === 'update' || mode === 'both';
        stock.disabled = !updating;
        wrap.classList.toggle('opacity-50', !updating);
        if (!updating) { stock.checked = false; }
    }

    form.addEventListener('change', syncMode);
    form.addEventListener('submit', function () {
        var button = document.getElementById('pi-submit');
        button.disabled = true;
        button.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Reading the file…';
    });
    syncMode();
}());
</script>
@endsection
