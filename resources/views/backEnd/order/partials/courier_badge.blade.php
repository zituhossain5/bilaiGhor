{{-- Compact courier status under the order status (order lists). Needs $order with courierParcel loaded. --}}
@php
    $parcel = $order->courierParcel;
@endphp
@if($parcel)
    @php
        $info = $parcel->statusInfo();
        $tone = $info['tone'] ?? 'secondary';
        $seen = collect([$parcel->last_event_at, $parcel->last_synced_at])->filter()->max();
        $trackUrl = $parcel->publicTrackingUrl();
    @endphp
    <div class="oi-courier mt-1" title="{{ \App\Services\CourierDispatchService::label($parcel->courier) }} · {{ $parcel->raw_status ?: $info['en'] }}">
        <span class="badge bg-{{ $tone }} {{ $tone === 'warning' ? 'text-dark' : '' }}">{{ $info['bn'] }}</span>
        <small class="d-block text-muted" style="font-size:.68rem;line-height:1.3;">
            {{ \App\Services\CourierDispatchService::label($parcel->courier) }}
            @if($trackUrl)
                · <a href="{{ $trackUrl }}" target="_blank" rel="noopener">{{ $parcel->consignment_id }}</a>
            @else
                · {{ $parcel->consignment_id }}
            @endif
            @if($seen)<br>{{ $seen->diffForHumans() }}@endif
        </small>
    </div>
@elseif(filled($order->courier_tracking_id))
    <small class="d-block text-muted mt-1" style="font-size:.68rem;">
        {{ \App\Services\CourierDispatchService::label($order->courier_type ?: 'steadfast') }} · {{ $order->courier_tracking_id }}
    </small>
@endif
