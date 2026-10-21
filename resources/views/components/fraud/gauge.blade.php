{{-- Semicircle gauge for a 0–100 rate. Null rate = grey "no data". --}}
@props(['rate' => null, 'tone' => 'none', 'label' => null])
@php $fill = $rate === null ? 0 : max(0, min(100, (float) $rate)); @endphp
<div {{ $attributes->class(['fc-gauge', 'fc-tone-' . $tone]) }}>
    <div class="fc-gauge-dial">
        <svg viewBox="0 0 120 66" role="img" aria-label="{{ $label }}: {{ $rate === null ? 'তথ্য নেই' : \App\Services\FraudCheck\FraudReport::pct($rate) . '%' }}">
            <path class="fc-gauge-track" d="M10 60 A50 50 0 0 1 110 60" pathLength="100"></path>
            @if($fill > 0)
                <path class="fc-gauge-fill" d="M10 60 A50 50 0 0 1 110 60" pathLength="100" stroke-dasharray="{{ $fill }} 100"></path>
            @endif
        </svg>
        <div class="fc-gauge-value">{{ $rate === null ? 'তথ্য নেই' : \App\Services\FraudCheck\FraudReport::pct($rate) . '%' }}</div>
    </div>
    @if($label)
        <div class="fc-gauge-label">{{ $label }}</div>
    @endif
</div>
