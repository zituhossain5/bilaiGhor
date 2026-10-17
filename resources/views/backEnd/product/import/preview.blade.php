@extends('backEnd.layouts.master')
@section('title', 'Import Products – Preview')

@section('css')
@include('backEnd.product.import.styles')
@endsection

@php
    use App\Services\ProductImport\ImportResultExport;

    $phase = $plan['phase'];
    $autoLabels = [
        'short_name' => 'Short name', 'meta_title' => 'Meta title', 'meta_description' => 'Meta description',
        'meta_keywords' => 'Meta keywords', 'meta_image' => 'Meta image', 'meta_image_alt' => 'Meta image alt',
        'image_alts' => 'Image alt texts', 'variant_alts' => 'Variant image alts', 'description' => 'Description → HTML',
        'stock' => 'Stock 0', 'product_type' => 'Type',
    ];
    $defaultFields = ['status', 'free_delivery', 'topsale', 'flashsale', 'best_seller', 'new_arrival', 'is_wholesale'];
    $money = fn ($v) => $v === null ? '—' : '৳' . number_format((float) $v, 0);
@endphp

@section('content')
<div class="container-fluid pi-page">
    <div class="page-title-box d-flex flex-wrap justify-content-between align-items-center gap-2">
        <h4 class="page-title mb-0">Import Products – {{ $phase === 'done' ? 'Result' : 'Preview' }}</h4>
        <div class="d-flex gap-2">
            <a href="{{ route('products.import') }}" class="btn btn-light btn-sm"><i class="fe-rotate-ccw me-1"></i> Upload another file</a>
            <a href="{{ route('products.import.result', $plan['id']) }}" class="btn btn-outline-secondary btn-sm"><i class="fe-download me-1"></i> Download result file</a>
        </div>
    </div>

    <div class="card pi-card mb-3">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between gap-3 mb-3">
                <div>
                    <div class="fw-semibold">{{ $plan['file'] }}</div>
                    <div class="text-muted small">
                        Mode: {{ $modes[$plan['options']['mode']] ?? $plan['options']['mode'] }}
                        · Auto-create missing items: {{ $plan['options']['auto_create'] ? 'on' : 'off' }}
                        · Update stock from file: {{ $plan['options']['update_stock'] ? 'on' : 'off' }}
                        @if($plan['has_zip']) · Images ZIP uploaded @endif
                    </div>
                </div>
            </div>

            <div class="pi-stats mb-3">
                <div class="pi-stat"><strong>{{ $summary['total'] }}</strong><span>products in file</span></div>
                <div class="pi-stat is-ok"><strong>{{ $summary['ok'] }}</strong><span>OK</span></div>
                <div class="pi-stat is-warning"><strong>{{ $summary['warning'] }}</strong><span>with warnings</span></div>
                <div class="pi-stat is-error"><strong>{{ $summary['error'] }}</strong><span>with errors (skipped)</span></div>
                @if($phase === 'done')
                    <div class="pi-stat is-ok"><strong>{{ $summary['created'] }}</strong><span>created</span></div>
                    <div class="pi-stat is-ok"><strong>{{ $summary['updated'] }}</strong><span>updated</span></div>
                    <div class="pi-stat is-error"><strong>{{ $summary['skipped'] + $summary['failed'] }}</strong><span>skipped / failed</span></div>
                @else
                    <div class="pi-stat"><strong>{{ $summary['create'] }}</strong><span>to create</span></div>
                    <div class="pi-stat"><strong>{{ $summary['update'] }}</strong><span>to update</span></div>
                @endif
            </div>

            @if($plan['to_create'])
                <div class="alert alert-warning py-2 mb-3">
                    <strong>Will be created</strong> (Auto-create missing items is on):
                    @foreach($plan['to_create'] as $type => $names)
                        <div><span class="fw-semibold">{{ $type }}:</span> {{ implode(', ', $names) }}</div>
                    @endforeach
                </div>
            @endif

            @if($plan['orphans'] || $plan['zip_errors'])
                <div class="alert alert-danger py-2 mb-3">
                    <strong>Rows that can't be linked to a product (skipped):</strong>
                    <ul class="pi-msgs mt-1">
                        @foreach($plan['orphans'] as $message)<li>{{ ImportResultExport::describe($message) }}</li>@endforeach
                        @foreach($plan['zip_errors'] as $error)<li>{{ $error }}</li>@endforeach
                    </ul>
                </div>
            @endif

            {{-- Progress (image checks, then import) --}}
            <div id="pi-progress-box" @class(['mb-3', 'd-none' => !in_array($phase, ['images', 'importing'], true)])>
                <div class="d-flex justify-content-between small mb-1">
                    <span id="pi-progress-label">{{ $phase === 'images' ? 'Checking image links…' : 'Importing…' }}</span>
                    <span id="pi-progress-count"></span>
                </div>
                <div class="pi-progress"><div id="pi-progress-bar"></div></div>
            </div>

            @if($phase === 'done')
                <div class="alert alert-success mb-0">
                    <strong>Import finished.</strong>
                    {{ $summary['created'] }} created, {{ $summary['updated'] }} updated, {{ $summary['skipped'] + $summary['failed'] }} skipped.
                    Download the result file to see each row's outcome; fix the skipped rows and upload just those again.
                </div>
            @elseif($summary['importable'] > 0)
                <div class="d-flex flex-wrap align-items-center gap-3">
                    <button type="button" class="btn btn-success" id="pi-import" @disabled($phase === 'images')>
                        <i class="fe-check me-1"></i>
                        {{ $phase === 'importing' ? 'Continue import' : 'Import ' . $summary['importable'] . ' product(s)' }}
                    </button>
                    <span class="text-muted small">Only rows without errors are imported. Each product is saved on its own, so one problem never affects the others.</span>
                </div>
            @else
                <div class="alert alert-danger mb-0">No product can be imported. Fix the errors below (or download the result file) and upload again.</div>
            @endif
        </div>
    </div>

    <div class="card pi-card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table pi-table mb-0">
                    <thead>
                        <tr>
                            <th>Status</th>
                            <th>Row</th>
                            <th>Product</th>
                            <th>Category</th>
                            <th class="text-end">Price</th>
                            <th class="text-end">Stock</th>
                            <th class="text-center">Variants</th>
                            <th class="text-center">Images</th>
                            <th>Auto-filled</th>
                            <th>Messages</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($plan['products'] as $entry)
                            @php
                                $result = $entry['result'];
                                $state = $entry['status'];
                                $statusText = ['ok' => 'OK', 'warning' => 'Warning', 'error' => 'Error'][$state];
                                if ($result) {
                                    $state = $result['status'] === 'failed' ? 'error' : 'ok';
                                    $statusText = ucfirst($result['status']);
                                }
                                $defaults = collect($entry['auto'])->only($defaultFields);
                                $imageCount = count($entry['images']) + collect($entry['variants'])->whereNotNull('image')->count();
                            @endphp
                            <tr class="is-{{ $state }}">
                                <td><span class="pi-badge {{ $state }}">{{ $statusText }}</span></td>
                                <td class="text-nowrap">{{ $entry['row'] ? 'Row ' . $entry['row'] : '—' }}</td>
                                <td style="min-width:220px;">
                                    <div class="fw-semibold">{{ $entry['name'] ?: '(no name)' }}</div>
                                    <span class="pi-badge {{ $entry['action'] }}">{{ $entry['action'] === 'create' ? 'New' : 'Update' }}</span>
                                    @if($entry['sku'] !== '')<span class="pi-sku ms-1">{{ $entry['sku'] }}</span>@endif
                                    @if($result && $result['product_id'])
                                        <a href="{{ route('products.edit', $result['product_id']) }}" class="small ms-1" target="_blank" rel="noopener">open</a>
                                    @endif
                                </td>
                                <td>{{ $entry['labels']['category'] ?? '' }}{{ !empty($entry['labels']['subcategory']) ? ' › ' . $entry['labels']['subcategory'] : '' }}</td>
                                <td class="text-end text-nowrap">{{ $money($entry['fields']['new_price'] ?? null) }}</td>
                                <td class="text-end">{{ $entry['stock'] ?? '—' }}</td>
                                <td class="text-center">{{ count($entry['variants']) ?: '—' }}</td>
                                <td class="text-center">{{ $imageCount ?: '—' }}</td>
                                <td style="min-width:300px;">
                                    @foreach(collect($entry['auto'])->except($defaultFields) as $field => $value)
                                        <span class="pi-auto" title="{{ is_scalar($value) ? $value : '' }}">{{ $autoLabels[$field] ?? $field }}</span>
                                    @endforeach
                                    @if($defaults->isNotEmpty())
                                        <span class="pi-auto" title="{{ $defaults->map(fn ($v, $k) => str_replace('_', ' ', $k) . ': ' . $v)->implode(', ') }}">Defaults ({{ $defaults->count() }})</span>
                                    @endif
                                </td>
                                <td style="min-width:260px;">
                                    @if($result)
                                        <div @class(['fw-semibold', 'text-danger' => $result['status'] === 'failed', 'text-success' => $result['status'] !== 'failed'])>{{ $result['text'] }}</div>
                                    @endif
                                    @if($entry['messages'])
                                        <ul class="pi-msgs">
                                            @foreach($entry['messages'] as $message)
                                                <li class="{{ $message['level'] }}">{{ ImportResultExport::describe($message) }}</li>
                                            @endforeach
                                        </ul>
                                    @elseif(!$result)
                                        <span class="text-muted">Ready</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
(function () {
    var phase = @json($phase);
    var urls = { images: @json(route('products.import.images', $plan['id'])), run: @json(route('products.import.run', $plan['id'])) };
    var token = @json(csrf_token());
    var box = document.getElementById('pi-progress-box');
    var bar = document.getElementById('pi-progress-bar');
    var label = document.getElementById('pi-progress-label');
    var count = document.getElementById('pi-progress-count');
    var importButton = document.getElementById('pi-import');
    var importTotal = null;

    function post(url) {
        return fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function (response) {
            if (!response.ok) { throw new Error('HTTP ' + response.status); }
            return response.json();
        });
    }

    function show(text, done, total) {
        box.classList.remove('d-none');
        label.textContent = text;
        count.textContent = total ? done + ' / ' + total : '';
        bar.style.width = total ? Math.round((done / total) * 100) + '%' : '0';
    }

    function fail(error) {
        label.textContent = 'Stopped: ' + error.message + '. Reload the page to continue where it stopped.';
        if (importButton) { importButton.disabled = false; }
    }

    function checkImages() {
        post(urls.images).then(function (p) {
            show('Checking image links…', p.images_total - p.pending_images, p.images_total);
            if (p.phase === 'images') {
                setTimeout(checkImages, p.busy ? 1500 : 50);
            } else {
                window.location.reload();
            }
        }).catch(fail);
    }

    function runImport() {
        post(urls.run).then(function (p) {
            if (importTotal === null) { importTotal = p.remaining + p.created + p.updated + p.failed; }
            var done = p.created + p.updated + p.failed;
            show('Importing… (' + p.created + ' created, ' + p.updated + ' updated' + (p.failed ? ', ' + p.failed + ' failed' : '') + ')', done, importTotal);
            if (p.phase !== 'done') {
                setTimeout(runImport, p.busy ? 1500 : 50);
            } else {
                window.location.reload();
            }
        }).catch(fail);
    }

    if (phase === 'images') {
        checkImages();
    }
    if (importButton) {
        importButton.addEventListener('click', function () {
            if (!confirm('Import the products without errors now?')) { return; }
            importButton.disabled = true;
            show('Importing…', 0, 0);
            runImport();
        });
    }
}());
</script>
@endsection
