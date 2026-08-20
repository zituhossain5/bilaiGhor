
<?php if (! $__env->hasRenderedOnce('6fc30dc4-d83e-4beb-a3af-75cf8cfff617')): $__env->markAsRenderedOnce('6fc30dc4-d83e-4beb-a3af-75cf8cfff617'); ?>
<?php $__env->startPush('script'); ?>
<script src="<?php echo e(asset('public/frontEnd/js/select2.min.js')); ?>"></script>
<script>
window.BilaiDistrictZone = (function () {
    var zonesUrl = '<?php echo e(route('customer.delivery_zones')); ?>';

    function hasSelect2() { return !!(window.jQuery && $.fn && $.fn.select2); }

    // Set a value and keep a Select2 widget (if any) in sync.
    function setVal($el, v) {
        $el.val(v || '');
        if ($el.hasClass('select2-hidden-accessible')) { $el.trigger('change.select2'); }
    }

    // Build/rebuild Select2 on a select. fresh=true destroys any live instance first
    // so widgets are always bound to the current plugin registration; if destroy fails,
    // the select2 DOM is stripped so the NATIVE select is never left hidden behind it.
    function select2On($el, placeholder, dropdownParent, fresh) {
        if (!hasSelect2()) {
            console.error('BilaiDistrictZone: Select2 is not loaded — using native selects');
            return;
        }
        if (fresh && $el.hasClass('select2-hidden-accessible')) {
            try { $el.select2('destroy'); } catch (e) {}
            if ($el.hasClass('select2-hidden-accessible')) {
                $el.removeClass('select2-hidden-accessible')
                   .removeAttr('data-select2-id aria-hidden tabindex')
                   .removeData('select2')
                   .show();
            }
        }
        if (fresh) { $el.nextAll('.select2-container').remove(); }
        if (!$el.hasClass('select2-hidden-accessible')) {
            var opts = { width: '100%', placeholder: placeholder, allowClear: false };
            if (dropdownParent) { opts.dropdownParent = dropdownParent; }
            try { $el.select2(opts); }
            catch (e) { console.error('BilaiDistrictZone: Select2 init failed — using native select', e); }
        }
    }

    // Load active zones of a district, then (optionally) preselect one.
    // Preselection happens in the AJAX callback — never on a timer.
    function loadZones($zone, districtId, selectedZoneId) {
        $zone.prop('disabled', true).html('<option value="">Select Zone</option>');
        setVal($zone, '');
        if (!districtId) { return; }

        return $.get(zonesUrl, { district_id: districtId }, function (res) {
            var opts = '<option value="">Select Zone</option>';
            (res.data || []).forEach(function (z) {
                var label = z.name + (z.name_bn ? ' — ' + z.name_bn : '');
                opts += '<option value="' + z.id + '">' + label + '</option>';
            });
            $zone.html(opts).prop('disabled', false);
            if (selectedZoneId) { setVal($zone, selectedZoneId); }
        });
    }

    // One-call setup for a plain (non-modal) page such as Profile Edit.
    function initFields(cfg) {
        var $district = $(cfg.district);
        var $zone     = $(cfg.zone);
        if (!$district.length || !$zone.length) { return; }

        select2On($district, 'Select District', cfg.dropdownParent, cfg.fresh);
        select2On($zone, 'Select Zone', cfg.dropdownParent, cfg.fresh);

        // Namespaced + delegated: survives Select2 rebuilds, never double-binds.
        var ns = 'change.bilaiDZ_' + cfg.zone.replace(/[^a-z0-9]/gi, '');
        $(document).off(ns, cfg.district).on(ns, cfg.district, function () {
            loadZones($zone, this.value, null); // district changed -> old zone cleared
        });

        // Edit prefill: district is already selected server-side; load its zones,
        // then select the saved zone once the response arrives.
        var districtId = $district.val();
        if (districtId) { loadZones($zone, districtId, cfg.selectedZone || null); }
    }

    return { loadZones: loadZones, initFields: initFields, select2On: select2On, setVal: setVal };
}());
</script>
<?php $__env->stopPush(); ?>
<?php endif; ?>
<?php /**PATH C:\laragon\www\bilaiGhor\resources\views/frontEnd/layouts/customer/partials/district-zone-js.blade.php ENDPATH**/ ?>