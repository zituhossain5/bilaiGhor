{{-- ═══════════════════════════════════════════════════════════════
     Reusable Add/Edit Address popup (District → Zone)
     Open with:  class="bilai-afm-add-open"  (add mode)
             or  class="bilai-afm-edit-open" + data-id/name/mobile/email/
                 postcode/district/zone/address  (edit mode)
     Used on: customer Addresses page + checkout Select Address modal.
════════════════════════════════════════════════════════════════ --}}
@php
    $__afmDistricts = \App\Models\DeliveryDistrict::active()->ordered()->get(['id', 'name']);

    // Reopen the modal with old input after a failed validation redirect.
    $__afmReopen = old('adr_mode') ? [
        'mode'        => old('adr_mode'),
        'id'          => old('adr_id'),
        'name'        => old('adr_name'),
        'mobile'      => old('adr_phone'),
        'email'       => old('adr_email'),
        'post_code'   => old('adr_post_code'),
        'district_id' => old('adr_district_id'),
        'zone_id'     => old('adr_zone_id'),
        'address'     => old('adr_address'),
    ] : null;
@endphp

@once
@push('css')
<link rel="stylesheet" href="{{ asset('public/frontEnd/css/select2.min.css') }}" />
<style>
/* BilaiGhor Address Form Modal Start */
:root {
    --bilai-afm-primary:      var(--bilai-primary, #F28C00);
    --bilai-afm-primary-dark: var(--bilai-primary-dark, #c96f00);
    --bilai-afm-cream:        var(--bilai-cream, #FFF8EC);
    --bilai-afm-border:       var(--bilai-border, #E8CDA5);
    --bilai-afm-text:         var(--bilai-text, #2B1A10);
    --bilai-afm-muted:        var(--bilai-muted, #77706A);
}
.bilai-afm-overlay {
    display: none; position: fixed; inset: 0; z-index: 1700;
    background: rgba(30,18,8,0.5);
    align-items: flex-start; justify-content: center;
    padding: 40px 16px; overflow-y: auto;
}
.bilai-afm-overlay.open { display: flex; }
.bilai-afm-dialog {
    position: relative; background: var(--bilai-afm-cream);
    width: 100%; max-width: 520px; border-radius: 14px;
    padding: 0 0 26px;
}
.bilai-afm-head {
    display: flex; align-items: center; justify-content: space-between; gap: 10px;
    padding: 18px 24px 14px; border-bottom: 1px solid var(--bilai-afm-border);
    margin-bottom: 18px;
}
.bilai-afm-title { font-size: 16px; font-weight: 700; color: var(--bilai-afm-text); margin: 0; }
.bilai-afm-close { background: transparent; border: none; color: var(--bilai-afm-muted); font-size: 20px; cursor: pointer; line-height: 1; padding: 4px; }
.bilai-afm-close:hover { color: var(--bilai-afm-text); }
.bilai-afm-body { padding: 0 24px; }
.bilai-afm-error { background: #fdecea; border: 1px solid #f2b8b5; color: #b3261e; border-radius: 8px; padding: 9px 13px; font-size: 12.5px; margin-bottom: 14px; }
.bilai-afm-field { margin-bottom: 14px; }
.bilai-afm-field label { display: block; font-size: 13px; font-weight: 600; color: var(--bilai-afm-text); margin-bottom: 6px; }
.bilai-afm-field label .req { color: #e04b4b; }
.bilai-afm-field input, .bilai-afm-field select, .bilai-afm-field textarea {
    width: 100%; border: 1px solid var(--bilai-afm-border); border-radius: 8px;
    padding: 10px 13px; font-size: 13.5px; color: var(--bilai-afm-text); background: #fff;
}
.bilai-afm-field input::placeholder, .bilai-afm-field textarea::placeholder { color: #b6ab9c; }
.bilai-afm-field input:focus, .bilai-afm-field select:focus, .bilai-afm-field textarea:focus {
    outline: none; border-color: var(--bilai-afm-primary); box-shadow: 0 0 0 3px rgba(242,140,0,0.10);
}
.bilai-afm-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
.bilai-afm-half { width: calc(50% - 7px); }
.bilai-afm-submit-wrap { text-align: center; padding: 8px 24px 0; }
.bilai-afm-submit {
    min-width: 220px; padding: 12px 30px;
    background: var(--bilai-afm-primary); color: #fff;
    border: none; border-radius: 10px; font-size: 14.5px; font-weight: 700;
    cursor: pointer; transition: 0.2s;
}
.bilai-afm-submit:hover { background: var(--bilai-afm-primary-dark); }
/* Select2 skin to match the cream theme */
.bilai-afm-dialog .select2-container .select2-selection--single {
    height: 41px; border: 1px solid var(--bilai-afm-border); border-radius: 8px; background: #fff;
}
.bilai-afm-dialog .select2-container .select2-selection--single .select2-selection__rendered { line-height: 39px; padding-left: 13px; font-size: 13.5px; color: var(--bilai-afm-text); }
.bilai-afm-dialog .select2-container .select2-selection--single .select2-selection__arrow { height: 39px; }
@media (max-width: 575px) {
    .bilai-afm-row { grid-template-columns: 1fr; gap: 0; }
    .bilai-afm-half { width: 100%; }
}
/* BilaiGhor Address Form Modal End */
</style>
@endpush
@endonce

<div id="bilai-afm-modal" class="bilai-afm-overlay" aria-hidden="true">
    <div class="bilai-afm-dialog" role="dialog" aria-label="Address form">
        <div class="bilai-afm-head">
            <h5 class="bilai-afm-title" id="bilai-afm-title">Add New Address</h5>
            <button type="button" class="bilai-afm-close" aria-label="Close">&times;</button>
        </div>
        <div class="bilai-afm-body">
            @if($__afmReopen && $errors->any())
                <div class="bilai-afm-error">{{ $errors->first() }}</div>
            @endif
            <form id="bilai-afm-form" action="{{ route('customer.addresses.store') }}" method="POST">
                @csrf
                <input type="hidden" name="adr_mode" id="bilai-afm-mode" value="add">
                <input type="hidden" name="adr_id" id="bilai-afm-id" value="">

                <div class="bilai-afm-field">
                    <label>Your Name <span class="req">*</span></label>
                    <input type="text" name="adr_name" id="bilai-afm-name" maxlength="155" placeholder="Write your full name" required>
                </div>

                <div class="bilai-afm-row">
                    <div class="bilai-afm-field">
                        <label>Mobile <span class="req">*</span></label>
                        <input type="text" name="adr_phone" id="bilai-afm-phone" maxlength="55" placeholder="01xxxxxxxxx" required>
                    </div>
                    <div class="bilai-afm-field">
                        <label>Email</label>
                        <input type="email" name="adr_email" id="bilai-afm-email" maxlength="155" placeholder="Write your email">
                    </div>
                </div>

                <div class="bilai-afm-row">
                    <div class="bilai-afm-field">
                        <label>Post Code</label>
                        <input type="text" name="adr_post_code" id="bilai-afm-postcode" maxlength="20" placeholder="1xxxx">
                    </div>
                    <div class="bilai-afm-field">
                        <label>District <span class="req">*</span></label>
                        <select name="adr_district_id" id="bilai-afm-district" required>
                            <option value="">Select District</option>
                            @foreach($__afmDistricts as $d)
                                <option value="{{ $d->id }}">{{ $d->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="bilai-afm-field bilai-afm-half">
                    <label>Zone <span class="req">*</span></label>
                    <select name="adr_zone_id" id="bilai-afm-zone" required disabled>
                        <option value="">Select Zone</option>
                    </select>
                </div>

                <div class="bilai-afm-field">
                    <label>Address Details <span class="req">*</span></label>
                    <textarea name="adr_address" id="bilai-afm-address" rows="4" maxlength="1000" placeholder="House no, Road no, Area" required></textarea>
                </div>

                <div class="bilai-afm-submit-wrap">
                    <button type="submit" class="bilai-afm-submit" id="bilai-afm-submit">Submit</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Shared District→Zone logic + the single select2 load for the page. --}}
@include('frontEnd.layouts.customer.partials.district-zone-js')

@once
@push('script')
<script>
/* BilaiGhor Address Form Modal — reusable add/edit behavior */
(function () {
    if (window.__bilaiAFMInit) return;
    window.__bilaiAFMInit = true;

    var modal    = document.getElementById('bilai-afm-modal');
    if (!modal) return;

    var titleEl  = document.getElementById('bilai-afm-title');
    var form     = document.getElementById('bilai-afm-form');
    var submitEl = document.getElementById('bilai-afm-submit');
    var storeUrl  = '{{ route('customer.addresses.store') }}';
    var updateUrlBase = '{{ url('/customer/addresses') }}'; // + /{id}/update
    var zonesUrl  = '{{ route('customer.delivery_zones') }}';

    var $district = $('#bilai-afm-district');
    var $zone     = $('#bilai-afm-zone');

    var DZ = window.BilaiDistrictZone;

    // Select2 init via the shared helper. fresh=true destroys and rebuilds the widgets —
    // done on every modal open so they are always bound to the live plugin registration
    // (dropdownParent must be the dialog, or the dropdown renders behind the overlay).
    function ensureSelect2(fresh) {
        if (!DZ) { console.error('BilaiAFM: BilaiDistrictZone helper missing'); return false; }
        var $parent = $('#bilai-afm-modal .bilai-afm-dialog');
        DZ.select2On($district, 'Select District', $parent, fresh);
        DZ.select2On($zone, 'Select Zone', $parent, fresh);
        return true;
    }
    ensureSelect2(false);

    function setVal($el, v) { DZ.setVal($el, v); }

    // Load active zones for a district; then optionally preselect one (edit mode).
    function loadZones(districtId, selectedZoneId) {
        DZ.loadZones($zone, districtId, selectedZoneId);
    }

    // District change clears/reloads zones — delegated + namespaced so it survives
    // select2 destroy/rebuild cycles and never double-binds.
    $(document)
        .off('change.bilaiAfmDistrict', '#bilai-afm-district')
        .on('change.bilaiAfmDistrict', '#bilai-afm-district', function () {
            loadZones(this.value, null);
        });

    // Modal stacking (checkout): while this form is open, hide the underlying
    // "Select Address" modal and restore it on close — no stacked active overlays.
    var stackedModal = null;

    function openModal() {
        var underlay = document.querySelector('.bilai-addr-overlay.open');
        if (underlay) { underlay.classList.remove('open'); stackedModal = underlay; }
        modal.classList.add('open');
        document.body.style.overflow = 'hidden';
    }
    function closeModal() {
        modal.classList.remove('open');
        if (stackedModal) {
            stackedModal.classList.add('open'); // back to Select Address modal
            stackedModal = null;
        } else {
            document.body.style.overflow = '';
        }
    }

    function fillForm(d) {
        document.getElementById('bilai-afm-name').value     = d.name || '';
        document.getElementById('bilai-afm-phone').value    = d.mobile || '';
        document.getElementById('bilai-afm-email').value    = d.email || '';
        document.getElementById('bilai-afm-postcode').value = d.post_code || '';
        document.getElementById('bilai-afm-address').value  = d.address || '';
        setVal($district, d.district_id || '');
        // Zone depends on district: load zones first, THEN preselect the saved zone.
        if (d.district_id) {
            loadZones(d.district_id, d.zone_id || null);
        } else {
            loadZones(null, null);
        }
    }

    function openAdd() {
        ensureSelect2(true); // fresh rebuild on every open
        titleEl.textContent = 'Add New Address';
        submitEl.textContent = 'Submit';
        form.action = storeUrl;
        document.getElementById('bilai-afm-mode').value = 'add';
        document.getElementById('bilai-afm-id').value = '';
        form.reset();
        setVal($district, ''); loadZones(null, null);
        openModal();
    }

    function openEdit(d) {
        ensureSelect2(true); // fresh rebuild on every open
        titleEl.textContent = 'Edit Address';
        submitEl.textContent = 'Update';
        form.action = updateUrlBase + '/' + d.id + '/update';
        document.getElementById('bilai-afm-mode').value = 'edit';
        document.getElementById('bilai-afm-id').value = d.id;
        fillForm(d);
        openModal();
    }

    // Delegated triggers — works for buttons on any page using this partial.
    document.addEventListener('click', function (e) {
        var addBtn = e.target.closest('.bilai-afm-add-open');
        if (addBtn) { e.preventDefault(); openAdd(); return; }

        var editBtn = e.target.closest('.bilai-afm-edit-open');
        if (editBtn) {
            e.preventDefault();
            var ds = editBtn.dataset;
            openEdit({
                id: ds.id, name: ds.name, mobile: ds.mobile, email: ds.email,
                post_code: ds.postcode, district_id: ds.district, zone_id: ds.zone,
                address: ds.address
            });
        }
    });

    modal.querySelectorAll('.bilai-afm-close').forEach(function (b) { b.addEventListener('click', closeModal); });
    modal.addEventListener('click', function (e) { if (e.target === modal) closeModal(); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && modal.classList.contains('open')) closeModal(); });

    // Reopen with old input after a failed validation redirect.
    var REOPEN = @json($__afmReopen);
    if (REOPEN && REOPEN.mode) {
        if (REOPEN.mode === 'edit' && REOPEN.id) { openEdit(REOPEN); }
        else { openAdd(); fillForm(REOPEN); }
    }
}());
</script>
@endpush
@endonce
