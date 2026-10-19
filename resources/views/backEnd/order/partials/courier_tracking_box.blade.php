{{--
    Courier tracking box (order quick view). $order required; $parcel = the order's current courier parcel or null.
    Light version: status, last update, link to the courier, refresh, and linking a parcel made in a courier portal.
--}}
@php
    $parcel = $parcel ?? $order->courierParcel;
    $courierLabel = fn ($c) => \App\Services\CourierDispatchService::label($c);
@endphp
<div class="cpt-box" id="cptBox{{ $order->id }}" data-order-id="{{ $order->id }}">
@if($parcel)
    @php
        $info = $parcel->statusInfo();
        $tone = $info['tone'] ?? 'secondary';
        $seen = collect([$parcel->last_event_at, $parcel->last_synced_at])->filter()->max();
        $trackUrl = $parcel->publicTrackingUrl();
        $portalUrl = config('courier_tracking.portal_url.' . $parcel->courier);
        $lastMessage = $parcel->events()->whereNotNull('message')->where('source', '!=', 'link')->first();
        $due = max(0, (float) $order->amount - (float) \App\Services\OrderPaymentService::state($order)['paid']);
        $codMismatch = $parcel->courier_cod !== null && abs($due - (float) $parcel->courier_cod) >= 1;
    @endphp
    <div class="cpt-head">
        <span class="cpt-courier"><i class="fas fa-truck"></i> {{ $courierLabel($parcel->courier) }}</span>
        <span class="badge bg-{{ $tone }} {{ $tone === 'warning' ? 'text-dark' : '' }}">{{ $info['bn'] }} · {{ $info['en'] }}</span>
        @if($parcel->isStuck())
            <span class="badge bg-warning text-dark" title="{{ config('courier_tracking.stuck_days') }} দিনের বেশি কোনো আপডেট নেই">আটকে আছে?</span>
        @endif
    </div>

    <div class="cpt-row">
        <span class="text-muted">Consignment:</span>
        <code class="cpt-code">{{ $parcel->consignment_id }}</code>
        <button type="button" class="btn btn-link btn-sm p-0 cpt-copy" data-copy="{{ $parcel->consignment_id }}" title="কপি"><i class="fas fa-copy"></i></button>
        @if($parcel->tracking_code && $parcel->tracking_code !== $parcel->consignment_id)
            <span class="text-muted ms-2">Tracking:</span> <code class="cpt-code">{{ $parcel->tracking_code }}</code>
            <button type="button" class="btn btn-link btn-sm p-0 cpt-copy" data-copy="{{ $parcel->tracking_code }}" title="কপি"><i class="fas fa-copy"></i></button>
        @endif
    </div>

    <div class="cpt-row text-muted">
        @if($seen)সর্বশেষ আপডেট: {{ $seen->diffForHumans() }} ({{ $seen->timezone(config('app.timezone'))->format('d M, h:i A') }})@else এখনো কোনো আপডেট আসেনি @endif
        @if($parcel->raw_status) · <span title="Courier status">{{ $parcel->raw_status }}</span>@endif
    </div>

    @if($lastMessage)
        <div class="cpt-msg">“{{ $lastMessage->message }}” <small class="text-muted">{{ $lastMessage->event_time?->timezone(config('app.timezone'))->format('d M, h:i A') }}</small></div>
    @endif

    @if($parcel->courier_cod !== null || $parcel->delivery_charge !== null)
        <div class="cpt-row">
            @if($parcel->courier_cod !== null)<span>কুরিয়ার COD: <strong>৳{{ number_format((float) $parcel->courier_cod, 2) }}</strong></span>@endif
            @if($parcel->delivery_charge !== null)<span class="ms-2">ডেলিভারি চার্জ: ৳{{ number_format((float) $parcel->delivery_charge, 2) }}</span>@endif
        </div>
        @if($codMismatch)
            <div class="cpt-warn">⚠ COD মেলেনি: অর্ডারের বাকি ৳{{ number_format($due, 2) }} (অর্ডারের টাকা বদলানো হয়নি)</div>
        @endif
    @endif

    @php
        // What this panel sent (dispatch event) vs what the courier reports. Order data is never changed from here.
        $sentInfo = $parcel->events()->where('source', 'dispatch')->first()?->raw_payload ?? [];
        $sentWeight = $sentInfo['weight_kg'] ?? null;
        $courierWeight = filled($parcel->weight) ? (float) preg_replace('/[^0-9.]/', '', (string) $parcel->weight) : null;
    @endphp
    @if($sentWeight !== null || $parcel->items_in_lot !== null || $courierWeight)
        <div class="cpt-row">
            @if($sentWeight !== null)<span>পাঠানো ওজন: <strong>{{ \App\Services\CourierDispatchService::formatWeight((float) $sentWeight) }} kg</strong></span>@endif
            @if($courierWeight)<span class="ms-2">{{ $courierLabel($parcel->courier) }} এর ওজন: <strong>{{ $parcel->weight }}</strong></span>@endif
            @if($parcel->items_in_lot !== null)<span class="ms-2">total_lot: {{ $parcel->items_in_lot }}</span>@endif
        </div>
        @if($sentWeight !== null && $courierWeight && abs((float) $sentWeight - $courierWeight) >= 0.01)
            <div class="cpt-warn">⚠ ওজন মেলেনি: আপনার {{ \App\Services\CourierDispatchService::formatWeight((float) $sentWeight) }} kg, {{ $courierLabel($parcel->courier) }} {{ $parcel->weight }} (অর্ডার বদলানো হয়নি)</div>
        @endif
    @endif

    @if($parcel->last_sync_error)
        <div class="cpt-warn">⚠ {{ $courierLabel($parcel->courier) }} থেকে শেষবার আপডেট আনা যায়নি ({{ $parcel->last_sync_error_at?->diffForHumans() }}) — আগের তথ্য দেখানো হচ্ছে।</div>
    @endif

    <div class="cpt-actions">
        <button type="button" class="btn btn-sm btn-outline-primary cpt-refresh" data-url="{{ route('admin.courier_parcels.refresh', $parcel) }}">
            <i class="fas fa-sync-alt"></i> রিফ্রেশ
        </button>
        @if($trackUrl)
            <a class="btn btn-sm btn-outline-secondary" href="{{ $trackUrl }}" target="_blank" rel="noopener"><i class="fas fa-external-link-alt"></i> ট্র্যাকিং পেজ</a>
        @endif
        @if($portalUrl)
            <a class="btn btn-sm btn-outline-secondary" href="{{ $portalUrl }}" target="_blank" rel="noopener"><i class="fas fa-external-link-alt"></i> {{ $courierLabel($parcel->courier) }} পোর্টাল</a>
        @endif
    </div>
    @if($parcel->courier === 'steadfast')
        <small class="text-muted d-block mt-1">পুরো টাইমলাইন Steadfast পোর্টালে: Consignment নম্বর কপি করে সার্চ করুন।</small>
    @endif
@else
    @if(filled($order->courier_tracking_id))
        <div class="cpt-row"><i class="fas fa-truck"></i> {{ $courierLabel($order->courier_type ?: 'steadfast') }} · {{ $order->courier_tracking_id }}
            <small class="text-muted d-block">ট্র্যাকিং তথ্য পরের সিঙ্কে (১০ মিনিটের মধ্যে) দেখা যাবে।</small></div>
    @else
        <div class="cpt-head"><span class="cpt-courier"><i class="fas fa-truck"></i> কুরিয়ার</span>
            <span class="text-muted small">এই অর্ডারে কোনো কুরিয়ার পার্সেল নেই</span></div>
        <button type="button" class="btn btn-sm btn-outline-primary cpt-link-toggle"><i class="fas fa-link"></i> কুরিয়ার পার্সেল লিংক করুন</button>
        <div class="cpt-link-form mt-2" style="display:none;">
            <small class="text-muted d-block mb-2">কুরিয়ার পোর্টালে নিজে বানানো পার্সেল এই অর্ডারের সাথে যুক্ত করুন — এরপর এর স্ট্যাটাস নিজে থেকেই আপডেট হবে।</small>
            <div class="d-flex flex-wrap gap-2">
                <select class="form-select form-select-sm cpt-link-courier" style="max-width:130px;">
                    <option value="steadfast">Steadfast</option>
                    <option value="pathao">Pathao</option>
                    <option value="redx">RedX</option>
                </select>
                <input type="text" class="form-control form-control-sm cpt-link-code" style="max-width:200px;" placeholder="Consignment ID (যেমন 308914966)" maxlength="64">
                <button type="button" class="btn btn-sm btn-secondary cpt-link-check" data-url="{{ route('admin.courier_parcels.preview') }}">যাচাই করুন</button>
            </div>
            <div class="cpt-link-result mt-2"></div>
            <button type="button" class="btn btn-sm btn-primary cpt-link-confirm mt-1" style="display:none;" data-url="{{ route('admin.courier_parcels.link') }}">
                <i class="fas fa-check"></i> নিশ্চিত — লিংক করুন
            </button>
        </div>
    @endif
@endif
</div>
