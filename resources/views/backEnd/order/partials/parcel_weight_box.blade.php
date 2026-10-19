{{-- Parcel weight (kg), typed by an admin. Saved on its own (AJAX); never filled in from products.
     The input is tied to no form (form="pw-none"), so Enter can't submit a surrounding order form. --}}
@php
    $pwWeight = $order->parcel_weight_kg !== null ? \App\Services\CourierDispatchService::formatWeight((float) $order->parcel_weight_kg) : '';
    $pwMax = \App\Support\CourierSettings::weightMax();
    $pwSent = filled($order->courier_tracking_id) || $order->courierParcel;
@endphp
<div class="pw-box" data-url="{{ route('admin.order.parcel_weight', $order) }}" data-max="{{ $pwMax }}">
    <label class="pw-label" for="pw-input-{{ $order->id }}"><i class="fas fa-weight-hanging"></i> পার্সেলের ওজন (kg)</label>
    <div class="pw-row">
        <input type="text" inputmode="decimal" id="pw-input-{{ $order->id }}" form="pw-none" class="form-control form-control-sm pw-input"
               value="{{ $pwWeight }}" placeholder="যেমন 1.5" autocomplete="off">
        <button type="button" class="btn btn-sm btn-primary pw-save">সেভ</button>
    </div>
    <div class="pw-picks">
        @foreach(['0.5', '1', '1.5', '2'] as $w)
            <button type="button" data-w="{{ $w }}">{{ $w }}</button>
        @endforeach
    </div>
    <div class="pw-msg" aria-live="polite"></div>
    <small class="pw-help">
        @if($pwSent) পাঠানোর পর ওজন বদলালে শুধু এখানে বদলায় — Steadfast এ পাঠানো পার্সেল বদলায় না।
        @else সর্বোচ্চ {{ \App\Services\CourierDispatchService::formatWeight($pwMax) }} kg, ২ দশমিক ঘর পর্যন্ত।
        @endif
    </small>
</div>

@once
<style>
    .pw-box { margin-top: 4px; }
    .pw-label { font-size: 12.5px; font-weight: 600; color: #334155; margin-bottom: 4px; display: block; }
    .pw-row { display: flex; gap: 6px; max-width: 260px; }
    .pw-input { border: 1px solid #94a3b8; }
    .pw-input:focus { border-color: #2563eb; box-shadow: 0 0 0 2px rgba(37, 99, 235, .25); }
    .pw-input.is-bad { border-color: #dc2626; background: #fef2f2; }
    .pw-picks { display: flex; gap: 4px; margin-top: 6px; flex-wrap: wrap; }
    .pw-picks button { border: 1px solid #cbd5e1; background: #f8fafc; border-radius: 6px; font-size: 12px; padding: 2px 9px; color: #334155; }
    .pw-picks button:hover { background: #e2e8f0; }
    .pw-msg { font-size: 12px; min-height: 16px; margin-top: 4px; }
    .pw-msg.ok { color: #047857; }
    .pw-msg.bad { color: #b91c1c; }
    .pw-help { color: #64748b; font-size: 11.5px; }
</style>
@endonce
<script>
(function pwInit() {
    // On a full page this runs before jQuery is loaded (layout scripts come last); in the quick view it is ready.
    if (!window.jQuery) { document.addEventListener('DOMContentLoaded', pwInit); return; }
    if (window.pwBoxReady) return;   // the quick view loads this partial again for every order
    window.pwBoxReady = true;
    var TOKEN = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    function save($box) {
        var $in = $box.find('.pw-input'), $msg = $box.find('.pw-msg'), $btn = $box.find('.pw-save');
        $btn.prop('disabled', true);
        $.post($box.data('url'), { _token: TOKEN, weight: $.trim($in.val()) })
            .done(function (res) {
                $in.val(res.weight || '').removeClass('is-bad');
                $msg.removeClass('bad').addClass('ok').text(res.message);
            })
            .fail(function (xhr) {
                $in.addClass('is-bad');
                $msg.removeClass('ok').addClass('bad').text((xhr.responseJSON && xhr.responseJSON.message) || 'সেভ করা যায়নি');
            })
            .always(function () { $btn.prop('disabled', false); });
    }

    $(document).on('click', '.pw-save', function () { save($(this).closest('.pw-box')); });
    $(document).on('click', '.pw-picks button', function () {
        var $box = $(this).closest('.pw-box');
        $box.find('.pw-input').val($(this).data('w'));
        save($box);
    });
    $(document).on('keydown', '.pw-input', function (e) {
        if (e.key === 'Enter') { e.preventDefault(); save($(this).closest('.pw-box')); }
    });
    $(document).on('input', '.pw-input', function () {
        $(this).removeClass('is-bad').closest('.pw-box').find('.pw-msg').removeClass('ok bad').text('');
    });
})();
</script>
