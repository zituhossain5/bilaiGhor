@extends('backEnd.layouts.master')
@section('title', 'Steadfast Settings')

@section('css')
<link href="{{ asset('public/backEnd') }}/assets/libs/select2/css/select2.min.css" rel="stylesheet" type="text/css" />
<style>
    .sfs-card { background:#fff; border:1px solid #e5e7eb; border-radius:12px; margin-bottom:20px; }
    .sfs-card-head { padding:14px 18px; border-bottom:1px solid #f1f5f9; display:flex; align-items:center; justify-content:space-between; gap:10px; flex-wrap:wrap; }
    .sfs-card-head h5 { margin:0; font-size:15px; font-weight:700; color:#1e293b; }
    .sfs-card-body { padding:16px 18px; }
    .sfs-switch { display:flex; gap:10px; align-items:flex-start; padding:10px 0; border-bottom:1px dashed #eef2f7; }
    .sfs-switch:last-child { border-bottom:0; }
    .sfs-switch input[type=checkbox] { width:18px; height:18px; margin-top:2px; flex-shrink:0; }
    .sfs-switch label { font-weight:600; color:#1f2937; margin:0; cursor:pointer; }
    .sfs-help { font-size:12.5px; color:#64748b; margin-top:2px; }
    .sfs-note { background:#fffbeb; border:1px solid #fde68a; color:#78350f; border-radius:8px; padding:10px 12px; font-size:13px; }
    .sfs-tabs { display:flex; gap:6px; flex-wrap:wrap; }
    .sfs-tabs a { border:1px solid #e2e8f0; border-radius:999px; padding:4px 12px; font-size:12.5px; color:#334155; text-decoration:none; background:#fff; }
    .sfs-tabs a.active { background:#1e293b; border-color:#1e293b; color:#fff; }
    .sfs-table td, .sfs-table th { vertical-align:middle; font-size:13.5px; }
    .sfs-badge { display:inline-block; border-radius:999px; padding:2px 9px; font-size:11.5px; font-weight:600; white-space:nowrap; }
    .sfs-exact { background:#dcfce7; color:#166534; }
    .sfs-manual { background:#dbeafe; color:#1e40af; }
    .sfs-close { background:#fef3c7; color:#92400e; }
    .sfs-none, .sfs-unsynced { background:#fee2e2; color:#991b1b; }
    .sfs-station { font-weight:600; color:#0f172a; }
    .sfs-station small { font-weight:400; color:#64748b; }
    .sfs-picker { min-width:260px; }
    .sfs-actions { white-space:nowrap; }
    @media (max-width: 575.98px) {
        .sfs-card-body { padding:12px; }
        .sfs-picker { min-width:200px; }
    }
</style>
@endsection

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <h4 class="page-title">Steadfast Settings</h4>
            </div>
        </div>
    </div>

    @unless($ready)
        <div class="alert alert-warning">নতুন টেবিল এখনও তৈরি হয়নি — সার্ভারে <code>php artisan migrate</code> চালান।</div>
    @endunless

    {{-- Sending switches --}}
    <div class="sfs-card">
        <div class="sfs-card-head"><h5><i class="fas fa-sliders-h me-1"></i> পার্সেল পাঠানোর সেটিংস</h5></div>
        <div class="sfs-card-body">
            <div class="sfs-note mb-3">
                Steadfast এর অফিসিয়াল API (v1) তে <strong>এরিয়া</strong> বা <strong>ওজন</strong> এর কোনো ফিল্ড নেই।
                তাই এরিয়া ঠিকানার শেষে ইংরেজিতে, আর ওজন নোটে লেখা হয়। Steadfast পোর্টালের Weight ফিল্ড এতে বদলায় না।
            </div>
            <form method="POST" action="{{ route('admin.steadfast.settings.update') }}">
                @csrf
                <div class="sfs-switch">
                    <input type="checkbox" name="steadfast_area_in_address" id="s_area" value="1" @checked($settings['steadfast_area_in_address'] === '1') @disabled(!$ready)>
                    <div>
                        <label for="s_area">ঠিকানার শেষে Steadfast এরিয়া (ইংরেজি) যোগ করুন</label>
                        <div class="sfs-help">যেমন: "…Baharchara, Cox's Bazar Sadar, Cox's Bazar"। কাস্টমারের লেখা ঠিকানা থাকবে, বাংলা থানা/জেলা/বিভাগ বাদ যাবে। যে থানা মেলানো হয়নি সেটা আগের মতোই যাবে।</div>
                    </div>
                </div>
                <div class="sfs-switch">
                    <input type="checkbox" name="weight_required" id="s_wreq" value="1" @checked($settings['weight_required'] === '1') @disabled(!$ready)>
                    <div>
                        <label for="s_wreq">ওজন ছাড়া Steadfast এ পাঠানো যাবে না</label>
                        <div class="sfs-help">পাঠানোর ডায়লগে প্রতিটি অর্ডারের ওজন (kg) দিতে হবে। ওজন কখনো নিজে থেকে বসানো হয় না।</div>
                    </div>
                </div>
                <div class="sfs-switch">
                    <input type="checkbox" name="weight_in_note" id="s_wnote" value="1" @checked($settings['weight_in_note'] === '1') @disabled(!$ready)>
                    <div>
                        <label for="s_wnote">নোটের শেষে ওজন লিখুন ("Wt: 1.5 kg")</label>
                        <div class="sfs-help">অর্ডার নোট থাকবে; মোট ২৫০ অক্ষরের মধ্যে রাখা হয়।</div>
                    </div>
                </div>
                <div class="sfs-switch">
                    <div style="width:18px;"></div>
                    <div>
                        <label for="s_wmax">সর্বোচ্চ ওজন (kg)</label>
                        <input type="number" name="weight_max_kg" id="s_wmax" class="form-control form-control-sm mt-1" style="max-width:140px;" min="1" max="999" step="0.01"
                               value="{{ old('weight_max_kg', $settings['weight_max_kg']) }}" @disabled(!$ready)>
                        @error('weight_max_kg')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="sfs-switch">
                    <div style="width:18px;"></div>
                    <div>
                        <label>Steadfast এ "total_lot" হিসেবে কী যাবে</label>
                        <div class="form-check mt-1">
                            <input class="form-check-input" type="radio" name="steadfast_total_lot" id="s_lot_items" value="items" @checked($settings['steadfast_total_lot'] !== 'packages') @disabled(!$ready)>
                            <label class="form-check-label fw-normal" for="s_lot_items">মোট আইটেম সংখ্যা (আগের মতো, যেমন 50)</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="steadfast_total_lot" id="s_lot_pk" value="packages" @checked($settings['steadfast_total_lot'] === 'packages') @disabled(!$ready)>
                            <label class="form-check-label fw-normal" for="s_lot_pk">প্যাকেজ সংখ্যা (ডিফল্ট 1, পাঠানোর সময় বদলানো যায়)</label>
                        </div>
                    </div>
                </div>
                <div class="sfs-switch">
                    <input type="checkbox" name="steadfast_pickup_reminder" id="s_pick" value="1" @checked($settings['steadfast_pickup_reminder'] === '1') @disabled(!$ready)>
                    <div>
                        <label for="s_pick">ড্যাশবোর্ডে পিকআপ রিমাইন্ডার দেখান</label>
                        <div class="sfs-help">যেদিন পার্সেল পাঠানো হয়: "আজ N টি পার্সেল পাঠানো হয়েছে — Steadfast এ পিকআপ রিকোয়েস্ট দিন"। পিকআপ নিজে পোর্টালে দিতে হবে।</div>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary mt-2" @disabled(!$ready)><i class="fas fa-save me-1"></i> সেভ করুন</button>
            </form>
        </div>
    </div>

    {{-- Area mapping --}}
    <div class="sfs-card">
        <div class="sfs-card-head">
            <div>
                <h5><i class="fas fa-map-marked-alt me-1"></i> থানা → Steadfast এরিয়া</h5>
                <div class="sfs-help">
                    @if($lastSync) শেষ সিঙ্ক: {{ \Carbon\Carbon::parse($lastSync)->format('d M Y, h:i A') }} @else এখনও সিঙ্ক করা হয়নি @endif
                    · শুধু "মিলেছে" ও "ম্যানুয়াল" এরিয়া পাঠানো হয়; "যাচাই দরকার" গুলো ✓ চাপলে ব্যবহার হবে।
                </div>
            </div>
            <form method="POST" action="{{ route('admin.steadfast.sync') }}" onsubmit="this.querySelector('button').disabled=true;this.querySelector('button').innerHTML='<i class=\'fas fa-spinner fa-spin\'></i> সিঙ্ক হচ্ছে...';">
                @csrf
                <button type="submit" class="btn btn-sm btn-dark" @disabled(!$ready)><i class="fas fa-sync-alt me-1"></i> Sync Steadfast areas</button>
            </form>
        </div>
        <div class="sfs-card-body">
            @php
                $tab = fn ($key, $label, $n = null) => '<a href="' . e(route('admin.steadfast.settings', array_filter(['filter' => $key, 'q' => $search]))) . '" class="' . ($filter === $key ? 'active' : '') . '">' . e($label) . ($n !== null ? ' (' . $n . ')' : '') . '</a>';
                $c = fn ($k) => (int) ($counts[$k] ?? 0);
            @endphp
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <div class="sfs-tabs">
                    {!! $tab('review', 'যাচাই দরকার', $c('close') + $c('none') + $c('unsynced')) !!}
                    {!! $tab('close', 'কাছাকাছি', $c('close')) !!}
                    {!! $tab('none', 'মেলেনি', $c('none')) !!}
                    {!! $tab('exact', 'মিলেছে', $c('exact')) !!}
                    {!! $tab('manual', 'ম্যানুয়াল', $c('manual')) !!}
                    {!! $tab('all', 'সব', array_sum($counts)) !!}
                </div>
                <form method="GET" class="d-flex gap-2">
                    <input type="hidden" name="filter" value="{{ $filter }}">
                    <input type="search" name="q" value="{{ $search }}" class="form-control form-control-sm" placeholder="থানা / জেলা খুঁজুন" style="min-width:200px;">
                    <button class="btn btn-sm btn-outline-secondary" type="submit"><i class="fas fa-search"></i></button>
                </form>
            </div>

            @if($ready && !$stations)
                <div class="alert alert-info mb-0">প্রথমে <strong>Sync Steadfast areas</strong> চাপুন — Steadfast থেকে এরিয়া লিস্ট আসবে (শুধু পড়া হয়, কিছু পাঠানো হয় না) এবং থানাগুলো নিজে থেকে মেলানো হবে।</div>
            @elseif($rows->isEmpty())
                <div class="text-muted">এই তালিকায় কোনো থানা নেই।</div>
            @else
                <div class="table-responsive">
                    <table class="table table-sm sfs-table mb-0">
                        <thead>
                            <tr><th>থানা</th><th>জেলা</th><th>Steadfast এরিয়া</th><th>অবস্থা</th><th></th></tr>
                        </thead>
                        <tbody>
                        @foreach($rows as $row)
                            @php
                                $type = $row->match_type ?: 'unsynced';
                                $label = ['exact' => 'মিলেছে', 'manual' => 'ম্যানুয়াল', 'close' => 'যাচাই দরকার', 'none' => 'মেলেনি', 'unsynced' => 'মেলানো হয়নি'][$type] ?? $type;
                            @endphp
                            <tr data-thana="{{ $row->id }}" data-station="{{ $row->station_id }}" data-sf-districts='@json($row->sf_districts)'>
                                <td>{{ $row->thana }}</td>
                                <td>{{ $row->district }}</td>
                                <td class="sfs-station-cell">
                                    @if($row->station)
                                        <span class="sfs-station">{{ \App\Models\SteadfastPoliceStation::clean($row->station) }} <small>· {{ $row->station_district }}</small></span>
                                        @if(!$row->station_status)<span class="sfs-badge sfs-none ms-1">Steadfast এ নেই</span>@endif
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="sfs-type-cell">
                                    <span class="sfs-badge sfs-{{ $type }}">{{ $label }}</span>
                                    @if($type === 'close' && $row->score)<small class="text-muted ms-1">{{ (int) round($row->score * 100) }}%</small>@endif
                                </td>
                                <td class="sfs-actions text-end">
                                    @if($type === 'close' && $row->station_id)
                                        <button type="button" class="btn btn-sm btn-success sfs-confirm" title="এই এরিয়াই ঠিক"><i class="fas fa-check"></i></button>
                                    @endif
                                    <button type="button" class="btn btn-sm btn-outline-primary sfs-change">পরিবর্তন</button>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@section('script')
<script src="{{ asset('public/backEnd') }}/assets/libs/select2/js/select2.min.js"></script>
<script>
(function () {
    var STATIONS = @json($stations);
    var SAVE_URL = @json(route('admin.steadfast.mapping'));
    var TOKEN = @json(csrf_token());

    function esc(s) { return $('<div>').text(s == null ? '' : String(s)).html(); }

    // Option list: the row's own Steadfast district(s) first, then all the others.
    function optionsHtml(first, selected) {
        var order = (first || []).filter(function (d) { return STATIONS[d]; });
        Object.keys(STATIONS).forEach(function (d) { if (order.indexOf(d) === -1) order.push(d); });
        var h = '<option value=""></option><option value="none">— কোনো Steadfast এরিয়া মেলে না —</option>';
        order.forEach(function (d) {
            h += '<optgroup label="' + esc(d) + '">';
            STATIONS[d].forEach(function (s) {
                h += '<option value="' + s.id + '"' + (String(s.id) === String(selected) ? ' selected' : '') + '>' + esc(s.text) + '</option>';
            });
            h += '</optgroup>';
        });
        return h;
    }

    function save($tr, stationId) {
        $tr.find('button').prop('disabled', true);
        return $.post(SAVE_URL, { _token: TOKEN, thana_id: $tr.data('thana'), station_id: stationId === 'none' ? '' : stationId })
            .done(function (res) {
                toastr.success(res.message);
                $tr.find('.sfs-station-cell').html(res.label ? '<span class="sfs-station">' + esc(res.label) + '</span>' : '<span class="text-muted">— কোনো এরিয়া নেই</span>');
                $tr.find('.sfs-type-cell').html('<span class="sfs-badge sfs-manual">ম্যানুয়াল</span>');
                $tr.find('.sfs-confirm').remove();
                $tr.attr('data-station', stationId === 'none' ? '' : stationId);
            })
            .fail(function (xhr) {
                toastr.error((xhr.responseJSON && xhr.responseJSON.message) || 'সেভ করা যায়নি');
                var $cell = $tr.find('.sfs-station-cell');
                if ($cell.data('old') !== undefined) { $cell.html($cell.data('old')); }
            })
            .always(function () { $tr.find('button').prop('disabled', false); });
    }

    $(document).on('click', '.sfs-confirm', function () {
        var $tr = $(this).closest('tr');
        save($tr, $tr.attr('data-station'));
    });

    $(document).on('click', '.sfs-change', function () {
        var $tr = $(this).closest('tr');
        var $cell = $tr.find('.sfs-station-cell');
        if ($cell.find('select').length) return;
        var $sel = $('<select class="sfs-picker"></select>').html(optionsHtml($tr.data('sf-districts'), $tr.attr('data-station')));
        $cell.data('old', $cell.html()).empty().append($sel);
        $sel.select2({ placeholder: 'Steadfast এরিয়া খুঁজুন…', width: 'resolve' }).select2('open');
        var original = String($tr.attr('data-station') || '');
        var picked = false;
        $sel.on('select2:select', function () {
            var v = String($sel.val() || '');
            if (v === original) return;   // same area: nothing to save
            picked = true;
            $sel.select2('destroy');
            save($tr, v);
        });
        // Closed without a new choice: put the old cell back.
        $sel.on('select2:close', function () {
            setTimeout(function () {
                if (!picked && $sel.data('select2')) { $sel.select2('destroy'); $cell.html($cell.data('old')); }
            }, 0);
        });
    });
})();
</script>
@endsection
