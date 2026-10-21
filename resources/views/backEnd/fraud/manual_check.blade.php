@use('App\Services\FraudCheck\FraudReport')
@use('App\Support\FraudPhone')
@extends('backEnd.layouts.master')
@section('title', 'Manual Fraud Check')

@section('css')
    @include('backEnd.fraud.partials.styles')
@endsection

@section('content')
@php
    $masked = ($settings['fraud_mask_phone'] ?? '0') === '1';
    $show = fn (string $p) => $masked ? FraudPhone::mask($p) : $p;
    $safeMin = (float) $settings['fraud_risk_safe_min'];
    $mediumMin = (float) $settings['fraud_risk_medium_min'];
    $tone = fn (?float $r) => $r === null ? 'none' : ($r >= $safeMin ? 'good' : ($r >= $mediumMin ? 'mid' : 'bad'));
@endphp

<div class="container-fluid fc-page" id="fc-page">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <h4 class="page-title">ম্যানুয়াল ফ্রড চেক</h4>
            </div>
        </div>
    </div>

    {{-- Search --}}
    <div class="fc-card fc-search">
        <div class="fc-card-body">
            <h5>মোবাইল নম্বর দিয়ে কাস্টমারের ডেলিভারি রেকর্ড দেখুন</h5>
            <form action="{{ route('manualFraud.check') }}" method="POST" class="fc-search-form" data-fc-check>
                @csrf
                <input type="text" name="mobile" inputmode="tel" autocomplete="off" maxlength="25" required
                       value="{{ old('mobile', isset($phone) && !$masked ? $phone : '') }}"
                       placeholder="01XXXXXXXXX" aria-label="মোবাইল নম্বর" aria-describedby="fc-hint">
                <button class="btn fc-btn" type="submit">সার্চ দিন</button>
            </form>
            <div class="fc-hint" id="fc-hint">বাংলা সংখ্যা ও +880 নম্বরও চলবে — যেমন ০১৭১২৩৪৫৬৭৮ বা +880 1712-345678</div>

            @if(!empty($recent))
                <div class="fc-chips">
                    <span>সাম্প্রতিক:</span>
                    @foreach($recent as $number)
                        <form action="{{ route('manualFraud.check') }}" method="POST" data-fc-check>
                            @csrf
                            <input type="hidden" name="mobile" value="{{ $number }}">
                            <button type="submit" class="fc-chip">{{ $show($number) }}</button>
                        </form>
                    @endforeach
                </div>
            @endif

            @if(session('error'))
                <div class="fc-alert fc-alert-error" role="alert">{{ session('error') }}</div>
            @endif
        </div>
    </div>

    {{-- Loading skeleton, shown while a check is on its way --}}
    <div class="fc-skeleton" aria-hidden="true">
        <div class="row">
            <div class="col-lg-5"><div class="fc-card fc-card-body"><div class="fc-sk" style="height:24px;width:60%"></div><div class="fc-sk mx-auto mt-3" style="height:110px;width:220px;border-radius:110px 110px 0 0"></div><div class="fc-sk mt-3" style="height:130px"></div></div></div>
            <div class="col-lg-7"><div class="fc-card fc-card-body"><div class="fc-sk" style="height:34px"></div>@for($i = 0; $i < 4; $i++)<div class="fc-sk mt-2" style="height:44px"></div>@endfor</div></div>
        </div>
        <p class="text-center small text-muted">BD Courier থেকে তথ্য আনা হচ্ছে…</p>
    </div>

    @isset($lookup)
    <div id="fc-results">
        @if(!$lookup['ok'])
            <div class="fc-card">
                <div class="fc-card-body">
                    <div class="fc-alert fc-alert-error mb-2" role="alert">
                        <div><strong>BD Courier:</strong> {{ $lookup['error'] }}</div>
                    </div>
                    <p class="small text-muted mb-0">এই নম্বরের ({{ $show($phone) }}) কোনো সংরক্ষিত ফলও নেই, তাই কিছু দেখানো যাচ্ছে না। একটু পরে আবার চেষ্টা করুন।</p>
                </div>
            </div>
        @else
            @php
                $overall = $report['overall'];
                $risk = $report['risk'];
                $counts = $report['counts'];
            @endphp

            @if($lookup['stale'])
                <div class="fc-alert fc-alert-warn" role="alert">
                    <div><strong>BD Courier থেকে নতুন তথ্য আনা যায়নি:</strong> {{ $lookup['error'] }}<br>
                    নিচে {{ $lookup['checked_at']->locale('bn')->diffForHumans() }} এর সংরক্ষিত ফল দেখানো হচ্ছে।</div>
                </div>
            @endif

            <div class="fc-source">
                <div>
                    উৎস: <strong>{{ $report['source'] }}</strong> ·
                    <span title="{{ $lookup['checked_at']->format('d M Y, h:i A') }}">{{ $lookup['checked_at']->locale('bn')->diffForHumans() }} চেক করা</span>
                    @if($lookup['from_cache'] && !$lookup['stale'])
                        <span class="text-muted">(সংরক্ষিত ফল — নতুন সার্চ খরচ হয়নি)</span>
                    @endif
                </div>
                <form action="{{ route('manualFraud.check') }}" method="POST" data-fc-check>
                    @csrf
                    <input type="hidden" name="mobile" value="{{ $phone }}">
                    <input type="hidden" name="refresh" value="1">
                    <button type="submit" class="fc-btn-outline" title="সংরক্ষিত ফল বাদ দিয়ে BD Courier থেকে নতুন করে আনুন (একটি সার্চ খরচ হবে)">⟳ রিফ্রেশ</button>
                </form>
            </div>

            <div class="row">
                {{-- Summary --}}
                <div class="col-lg-5">
                    <div class="fc-card">
                        <div class="fc-card-body">
                            <div class="fc-summary-head">
                                <span class="fc-phone">{{ $show($phone) }}</span>
                                <span class="fc-badge fc-risk-{{ $risk['level'] }}" data-risk="{{ $risk['level'] }}">{{ $risk['label'] }}</span>
                            </div>

                            <x-fraud.gauge :rate="$overall['rate']" :tone="$tone($overall['rate'])" label="সামগ্রিক সফলতার হার" />
                            <p class="fc-method">
                                {{ $overall['method'] === 'weighted' ? 'পার্সেল সংখ্যা অনুযায়ী ভারিত গড়' : 'কুরিয়ারগুলোর হারের গড়' }}
                                @if($overall['formula']) · {{ $overall['formula'] }} @endif
                            </p>

                            <div class="fc-tiles">
                                <div class="fc-tile fc-tile-good">
                                    <div class="fc-tile-label">সফল ডেলিভারি</div>
                                    <div class="fc-tile-value">{{ $counts['delivered_pct'] === null ? '—' : FraudReport::pct($counts['delivered_pct']) . '%' }} <small>{{ $counts['delivered'] }} টি</small></div>
                                    <div class="fc-tile-note">সংরক্ষিত হিসাব থেকে</div>
                                </div>
                                <div class="fc-tile">
                                    <div class="fc-tile-label">বাতিল / ফেরত</div>
                                    <div class="fc-tile-value" data-cancelled>{{ $counts['cancelled_pct'] === null ? '—' : FraudReport::pct($counts['cancelled_pct']) . '%' }} <small>{{ $counts['cancelled'] }} টি</small></div>
                                    <div class="fc-tile-note">সংরক্ষিত হিসাব থেকে</div>
                                </div>
                                <div class="fc-tile">
                                    <div class="fc-tile-label">SteadFast পার্সেল</div>
                                    <div class="fc-tile-value" data-volume>{{ $report['volume']['text'] ?? 'তথ্য নেই' }}</div>
                                    <div class="fc-tile-note">{{ $report['volume'] ? ($report['volume']['is_range'] ? 'BD Courier যেমন দিয়েছে (আনুমানিক)' : 'মোট পার্সেল') : 'SteadFast-এ এই নম্বরের রেকর্ড নেই' }}</div>
                                </div>
                                <a href="#fc-reports" @class(['fc-tile', 'fc-tile-bad' => $report['report_count'] > 0]) data-reports="{{ $report['report_count'] }}"
                                   @if(!$report['report_count']) aria-disabled="true" onclick="return false" @endif>
                                    <div class="fc-tile-label">ফ্রড / মার্চেন্ট রিপোর্ট</div>
                                    <div class="fc-tile-value">{{ $report['report_count'] }}</div>
                                    <div class="fc-tile-note">{{ $report['report_count'] ? 'তালিকা দেখতে চাপুন' : 'কোনো রিপোর্ট নেই' }}</div>
                                </a>
                            </div>

                            <div class="fc-risk-box fc-risk-{{ $risk['level'] }}">
                                <strong>{{ $risk['label'] }}</strong>
                                {{ $risk['text'] }}
                                @if($risk['raised_by_reports'])
                                    <div class="mt-1 small">ফ্রড রিপোর্ট থাকায় ঝুঁকি কমপক্ষে "মাঝারি" ধরা হয়েছে।</div>
                                @endif
                            </div>

                            @if($report['verdict'] && ($report['verdict']['label'] || $report['verdict']['reasons']))
                                <div class="fc-verdict">
                                    BD Courier-এর মূল্যায়ন: <strong>{{ $report['verdict']['label'] }}</strong>@if($report['verdict']['action']) — {{ $report['verdict']['action'] }}@endif
                                    @if($report['verdict']['reasons'])
                                        <ul>@foreach($report['verdict']['reasons'] as $reason)<li>{{ $reason }}</li>@endforeach</ul>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="col-lg-7">
                    {{-- Courier breakdown --}}
                    <div class="fc-card">
                        <div class="fc-card-body">
                            <h6 class="fc-card-title">কুরিয়ার অনুযায়ী হিসাব</h6>

                            @if($report['couriers'])
                                <div class="fc-table-wrap">
                                <table class="fc-table">
                                    <thead>
                                        <tr>
                                            <th>কুরিয়ার</th>
                                            <th>মোট</th>
                                            <th>সফল</th>
                                            <th>বাতিল</th>
                                            <th>সফলতার হার</th>
                                            <th>উৎস</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($report['couriers'] as $c)
                                            @php
                                                $old = $c['snapshot'] || $c['mismatch'];
                                                $snapLabel = $c['snapshot'] ? 'পুরনো হিসাব · ' . FraudPhone::banglaDate($c['snapshot']) : null;
                                            @endphp
                                            <tr data-courier="{{ $c['key'] }}">
                                                <td>
                                                    <span class="fc-courier">
                                                        @if($c['logo'])<img src="{{ $c['logo'] }}" alt="" loading="lazy">@endif
                                                        {{ $c['name'] }}
                                                    </span>
                                                </td>
                                                <td data-label="মোট">
                                                    <span>
                                                        @if($c['total'] !== null)<span @class(['fc-num', 'fc-old-counts' => $old])>{{ $c['total'] }}</span>@endif
                                                        @if($c['range'] !== null)
                                                            <span class="fc-range" data-range>{{ $c['range'] }}</span>
                                                            <x-fraud.info-tip text="BD Courier যেমন দিয়েছে — নির্দিষ্ট সংখ্যা নয়, তাই মোট যোগফলে ধরা হয়নি।" />
                                                        @elseif($c['total'] === null)
                                                            —
                                                        @endif
                                                    </span>
                                                </td>
                                                <td data-label="সফল"><span @class(['fc-num fc-num-good', 'fc-old-counts' => $old])>{{ $c['delivered'] ?? '—' }}</span></td>
                                                <td data-label="বাতিল"><span @class(['fc-num fc-num-bad', 'fc-old-counts' => $old])>{{ $c['cancelled'] ?? '—' }}</span></td>
                                                <td data-label="সফলতার হার" class="fc-rate">
                                                    <div class="fc-tone-{{ $tone($c['rate']) }}">
                                                        <div class="fc-rate-text" data-rate>{{ $c['rate'] === null ? 'হার দেওয়া নেই' : FraudReport::pct($c['rate']) . '%' }}</div>
                                                        @if($c['rate'] !== null)<div class="fc-bar"><span style="width: {{ max(0, min(100, $c['rate'])) }}%"></span></div>@endif
                                                    </div>
                                                </td>
                                                <td data-label="উৎস">
                                                    <span class="fc-src">
                                                        {{ $c['source'] }}
                                                        @if(!$snapLabel && $c['mismatch'])
                                                            <x-fraud.info-tip text="সংখ্যা ও হার ভিন্ন সময়ের (Counts and rate come from different times) — হার যেমন এসেছে তেমনই দেখানো হয়েছে।" data-mismatch />
                                                        @endif
                                                    </span>
                                                    @if($snapLabel)
                                                        <br><span class="fc-snap" data-snapshot data-bs-toggle="tooltip"
                                                            title="সংখ্যাগুলো {{ FraudPhone::banglaDate($c['snapshot']) }} এর সংরক্ষিত হিসাব; হার ({{ FraudReport::pct($c['rate']) }}%) বর্তমান।">{{ $snapLabel }}</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                        <tr class="fc-total-row">
                                            <td>মোট</td>
                                            <td data-label="মোট">{{ $counts['total'] }}</td>
                                            <td data-label="সফল">{{ $counts['delivered'] }}</td>
                                            <td data-label="বাতিল">{{ $counts['cancelled'] }}</td>
                                            <td data-label="সফলতার হার" class="fc-rate">
                                                <div class="fc-tone-{{ $tone($overall['rate']) }}">
                                                    <div class="fc-rate-text" data-overall>{{ $overall['rate'] === null ? 'তথ্য নেই' : FraudReport::pct($overall['rate']) . '%' }}</div>
                                                    @if($overall['rate'] !== null)<div class="fc-bar"><span style="width: {{ max(0, min(100, $overall['rate'])) }}%"></span></div>@endif
                                                </div>
                                            </td>
                                            <td data-label="পদ্ধতি"><span class="fc-src">{{ $overall['method'] === 'weighted' ? 'ভারিত গড়' : 'হারের গড়' }}</span></td>
                                        </tr>
                                    </tbody>
                                </table>
                                </div>
                                @if($counts['ranges'])
                                    <div class="small text-muted mt-2">মোট যোগফলে {{ implode(', ', $counts['ranges']) }} ধরা হয়নি (নির্দিষ্ট সংখ্যা নয়)।</div>
                                @endif
                            @else
                                <div class="fc-nodata">কোনো কুরিয়ারে এই নম্বরের আগের পার্সেলের তথ্য নেই।</div>
                            @endif

                            @if($report['no_data'])
                                <div class="fc-nodata" data-no-data>কোনো তথ্য নেই: {{ implode(', ', $report['no_data']) }}</div>
                            @endif
                        </div>
                    </div>

                    {{-- How this is calculated --}}
                    <details class="fc-card fc-how">
                        <summary class="fc-card-body">ⓘ এই হিসাব কীভাবে করা হয়েছে</summary>
                        <div class="fc-card-body pt-0">
                            <ol>
                                @foreach($report['explanation'] as $line)
                                    <li>{{ $line }}</li>
                                @endforeach
                            </ol>
                        </div>
                    </details>

                    {{-- This shop's own orders --}}
                    <div class="fc-card">
                        <div class="fc-card-body">
                            <h6 class="fc-card-title">আপনার স্টোরে এই নম্বর</h6>
                            @if($store)
                                <div class="fc-store-stats">
                                    <div><b>{{ $store['total'] }}</b><span>মোট অর্ডার</span></div>
                                    <div><b class="text-success">{{ $store['delivered'] }}</b><span>ডেলিভারড</span></div>
                                    <div><b class="text-danger">{{ $store['cancelled'] }}</b><span>বাতিল</span></div>
                                    <div><b>{{ $store['open'] }}</b><span>চলমান</span></div>
                                </div>
                                <ul class="fc-store-list">
                                    @foreach($store['last'] as $o)
                                        <li><span>#{{ $o['invoice'] }} · {{ $o['date'] }}</span><span>৳{{ number_format($o['amount']) }} · {{ $o['status'] }}</span></li>
                                    @endforeach
                                </ul>
                                <a href="{{ $store['url'] }}" class="small d-inline-block mt-2">সব অর্ডার দেখুন →</a>
                            @else
                                <p class="small text-muted mb-0">এই নম্বরে আপনার স্টোরে এখনও কোনো অর্ডার নেই।</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Fraud / merchant reports --}}
            @if($report['reports'])
                <div class="fc-card" id="fc-reports">
                    <div class="fc-card-body">
                        <h6 class="fc-card-title text-danger">ফ্রড / মার্চেন্ট রিপোর্ট ({{ count($report['reports']) }})</h6>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead><tr><th>কুরিয়ার</th><th>নাম</th><th>বিবরণ</th><th>তারিখ</th></tr></thead>
                                <tbody>
                                    @foreach($report['reports'] as $r)
                                        <tr>
                                            <td class="text-nowrap">{{ is_scalar($r['courierName'] ?? null) ? $r['courierName'] : '—' }}</td>
                                            <td>{{ is_scalar($r['name'] ?? null) ? $r['name'] : '—' }}</td>
                                            <td class="small">{{ is_scalar($r['details'] ?? null) ? $r['details'] : '—' }}</td>
                                            <td class="small text-muted text-nowrap">{{ is_scalar($r['created_at'] ?? null) ? rescue(fn () => \Carbon\Carbon::parse($r['created_at'])->timezone(config('app.timezone'))->format('d M Y'), $r['created_at'], false) : '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif
        @endif
    </div>
    @endisset

    {{-- Settings --}}
    @can('fraud-setting-edit')
        <details class="fc-card fc-settings" @if($errors->any()) open @endif>
            <summary class="fc-card-body">⚙ হিসাবের সেটিংস</summary>
            <div class="fc-card-body pt-0">
                @unless($settingsReady)
                    <div class="fc-alert fc-alert-warn">courier_settings টেবিল এখনও তৈরি হয়নি — সার্ভারে <code>php artisan migrate</code> চালান। ততক্ষণ ডিফল্ট সেটিংস চলবে।</div>
                @endunless
                @if($errors->any())
                    <div class="fc-alert fc-alert-error"><ul class="mb-0 ps-3">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
                @endif
                <form method="POST" action="{{ route('manualFraud.settings') }}">
                    @csrf
                    <fieldset @disabled(!$settingsReady)>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="d-block mb-1">সামগ্রিক হার কীভাবে হিসাব হবে</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="fraud_rate_method" id="fc_m_avg" value="average" @checked(old('fraud_rate_method', $settings['fraud_rate_method']) !== 'weighted')>
                                    <label class="form-check-label fw-normal" for="fc_m_avg">কুরিয়ারগুলোর হারের গড় (ডিফল্ট) — যেমন 100, 98, 100 → 99.3%</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="fraud_rate_method" id="fc_m_w" value="weighted" @checked(old('fraud_rate_method', $settings['fraud_rate_method']) === 'weighted')>
                                    <label class="form-check-label fw-normal" for="fc_m_w">পার্সেল সংখ্যা অনুযায়ী ভারিত গড় — বেশি পার্সেলের কুরিয়ারের গুরুত্ব বেশি</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check form-switch mt-md-4">
                                    <input type="hidden" name="fraud_mask_phone" value="0">
                                    <input class="form-check-input" type="checkbox" name="fraud_mask_phone" id="fc_mask" value="1" @checked(old('fraud_mask_phone', $settings['fraud_mask_phone']) === '1')>
                                    <label class="form-check-label" for="fc_mask">ফলাফলে নম্বর লুকিয়ে দেখান (019****0165)</label>
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <label for="fc_cache" class="mb-1">সংরক্ষিত ফল কত মিনিট চলবে</label>
                                <input type="number" class="form-control" id="fc_cache" name="fraud_cache_minutes" min="0" max="10080" value="{{ old('fraud_cache_minutes', $settings['fraud_cache_minutes']) }}">
                                <div class="form-text">এর মধ্যে আবার চেক করলে নতুন সার্চ খরচ হবে না। ০ = প্রতিবার নতুন সার্চ।</div>
                            </div>
                            <div class="col-sm-4">
                                <label for="fc_safe" class="mb-1">"নিরাপদ" — সর্বনিম্ন হার (%)</label>
                                <input type="number" step="0.1" class="form-control" id="fc_safe" name="fraud_risk_safe_min" min="1" max="100" value="{{ old('fraud_risk_safe_min', $settings['fraud_risk_safe_min']) }}">
                            </div>
                            <div class="col-sm-4">
                                <label for="fc_medium" class="mb-1">"মাঝারি" — সর্বনিম্ন হার (%)</label>
                                <input type="number" step="0.1" class="form-control" id="fc_medium" name="fraud_risk_medium_min" min="0" max="100" value="{{ old('fraud_risk_medium_min', $settings['fraud_risk_medium_min']) }}">
                                <div class="form-text">এর নিচে "উচ্চ ঝুঁকি"। ফ্রড রিপোর্ট থাকলে কমপক্ষে "মাঝারি"।</div>
                            </div>
                        </div>
                        <button type="submit" class="btn fc-btn mt-3">সংরক্ষণ করুন</button>
                    </fieldset>
                </form>
            </div>
        </details>
    @endcan
</div>
@endsection

@section('script')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var page = document.getElementById('fc-page');

    // Loading skeleton while a check is on its way.
    document.querySelectorAll('form[data-fc-check]').forEach(function (form) {
        form.addEventListener('submit', function () {
            page.classList.add('fc-loading');
            form.querySelectorAll('button[type=submit]').forEach(function (b) { b.disabled = true; });
        });
    });
    // Back/forward cache: don't come back to a page stuck on the skeleton.
    window.addEventListener('pageshow', function () {
        page.classList.remove('fc-loading');
        page.querySelectorAll('form[data-fc-check] button[type=submit]').forEach(function (b) { b.disabled = false; });
    });

    if (window.bootstrap && bootstrap.Tooltip) {
        page.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
            bootstrap.Tooltip.getOrCreateInstance(el, { trigger: 'hover focus' });
        });
    }

    var reports = document.getElementById('fc-reports');
    var tile = page.querySelector('a[data-reports]');
    if (tile && reports) {
        tile.addEventListener('click', function (e) {
            e.preventDefault();
            reports.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    }
});
</script>
@endsection
