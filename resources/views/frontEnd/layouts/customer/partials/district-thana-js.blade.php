@once
@push('script')
<script src="{{ asset('public/frontEnd/js/select2.min.js') }}"></script>
<script>
(function (window, $) {
    'use strict';

    if (!$) {
        return;
    }

    var thanasUrl = @json(route('customer.delivery_thanas'));

    function setValue($field, value) {
        $field.val(value == null ? '' : String(value)).trigger('change.select2');
    }

    function select2On($field, options) {
        if (!$.fn.select2 || $field.hasClass('select2-hidden-accessible')) return;
        $field.select2($.extend({width: '100%'}, options || {}));
    }

    function loadThanas($thana, districtId, selectedThanaId) {
        $thana.prop('disabled', true).html('<option value="">Loading Thanas...</option>');
        if (!districtId) {
            $thana.html('<option value="">Select District First</option>');
            return $.Deferred().resolve().promise();
        }

        return $.get(thanasUrl, {district_id: districtId}).done(function (response) {
            var options = '<option value="">Select Thana</option>';
            $.each(response.data || [], function (_, thana) {
                options += '<option value="' + thana.id + '" data-charge="' + (thana.delivery_charge || 0) + '" data-post-code="' + (thana.post_code || '') + '">' + (thana.name_bn || thana.name) + '</option>';
            });
            $thana.html(options).prop('disabled', false);
            if (selectedThanaId) setValue($thana, selectedThanaId);
            $thana.trigger('thanas:loaded');
        }).fail(function () {
            $thana.html('<option value="">Unable to load Thanas</option>');
        });
    }

    function initFields(config) {
        var $district = $(config.district);
        var $thana = $(config.thana);
        if (!$district.length || !$thana.length) return;

        select2On($district, config.select2);
        select2On($thana, config.select2);
        $district.off('change.bilaiThana').on('change.bilaiThana', function () {
            loadThanas($thana, this.value, null);
        });

        if ($district.val()) loadThanas($thana, $district.val(), config.selectedThana || null);
        else loadThanas($thana, null, null);
    }

    window.BilaiDistrictThana = {
        initFields: initFields,
        loadThanas: loadThanas,
        select2On: select2On,
        setValue: setValue
    };
})(window, window.jQuery);
</script>
@endpush
@endonce
