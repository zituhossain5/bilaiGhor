@extends('frontEnd.layouts.master')
@section('title', 'Customer Checkout')
@php
    $generalsetting = \App\Models\GeneralSetting::first();
@endphp
@push('css')
{{-- select2 assets are loaded once by the shared address-form-modal partial (double-loading breaks Select2) --}}
<style>
/* BilaiGhor Checkout Figma Start */
:root {
    --co-primary: var(--bilai-primary, #F28C00);
    --co-primary-dark: var(--bilai-primary-dark, #c96f00);
    --co-brown:   var(--bilai-brown,  #3A1F0F);
    --co-cream:   var(--bilai-cream,  #FFF8EC);
    --co-card:    #FFFDF8;
    --co-border:  var(--bilai-border, #E8CDA5);
    --co-text:    var(--bilai-text,   #2B1A10);
    --co-muted:   var(--bilai-muted,  #77706A);
    --co-radius:  var(--bilai-radius-lg, 16px);
    --co-radius-sm: var(--bilai-radius-md, 10px);
}

.checkout-section {
    background: #f5f5f0;
    padding: 22px 0 56px;
    font-family: inherit;
    color: var(--co-text);
}

/* ── Breadcrumb ── */
.bilai-co-bc { display: flex; align-items: center; gap: 6px; font-size: 12.5px; margin-bottom: 20px; flex-wrap: wrap; }
.bilai-co-bc a { color: var(--co-muted); text-decoration: none; }
.bilai-co-bc a:hover { color: var(--co-primary); }
.bilai-co-bc-sep { color: #c0b0a0; font-size: 11px; }
.bilai-co-bc-active { color: var(--co-primary); font-weight: 600; }

/* ── Cards ── */
.checkout-card {
    background: var(--co-cream);
    border: 1px solid var(--co-border);
    border-radius: var(--co-radius);
    margin-bottom: 22px;
    overflow: hidden;
}
.checkout-header {
    padding: 18px 22px 14px;
    border-bottom: 1px solid var(--co-border);
    display: flex;
    align-items: center;
    gap: 10px;
}
.checkout-header i { color: var(--co-primary); font-size: 18px; }
.checkout-header h6 {
    margin: 0;
    font-size: 16px;
    font-weight: 700;
    color: var(--co-text);
    letter-spacing: 0;
    text-transform: none;
}
.card-body-custom { padding: 20px 22px 22px; }

/* ── Form ── */
.form-group { margin-bottom: 16px; }
.form-label-custom {
    font-size: 13px;
    font-weight: 600;
    color: var(--co-text);
    margin-bottom: 7px;
    display: block;
}
.form-label-custom .req { color: #e04b4b; }
.form-control-custom {
    width: 100%;
    height: 46px;
    border: 1px solid var(--co-border);
    border-radius: var(--co-radius-sm);
    padding: 0 14px;
    font-size: 14px;
    color: var(--co-text);
    transition: all 0.18s;
    background-color: #fff;
}
.form-control-custom::placeholder { color: #b6ab9c; }
.form-control-custom:focus {
    border-color: var(--co-primary);
    box-shadow: 0 0 0 3px rgba(242,140,0,0.10);
    outline: none;
}
select.form-control-custom { cursor: pointer; appearance: none; -webkit-appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2377706A' stroke-width='2'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");
    background-repeat: no-repeat; background-position: right 14px center; padding-right: 34px; }
textarea.form-control-custom { height: auto; padding: 12px 14px; line-height: 1.5; }

/* ── Payment options ── */
.payment-option-label {
    display: flex;
    align-items: center;
    justify-content: space-between;
    border: 1px solid var(--co-border);
    border-radius: var(--co-radius-sm);
    padding: 14px 16px;
    cursor: pointer;
    transition: all 0.18s ease;
    margin-bottom: 12px;
    background: #fff;
    position: relative;
}
.payment-option-label:hover { border-color: var(--co-primary); }
.payment-option-label input { position: absolute; opacity: 0; cursor: pointer; }
.payment-option-label:has(input:checked) {
    border-color: var(--co-primary);
    background-color: #fdefd6;
    box-shadow: 0 0 0 1px var(--co-primary);
}
.payment-content { display: flex; align-items: center; gap: 13px; width: 100%; }
.pay-logo { width: 36px; height: 36px; object-fit: contain; flex-shrink: 0; }
.pay-info strong { display: block; font-size: 14.5px; font-weight: 700; color: var(--co-text); }
.pay-info small { font-size: 12px; color: var(--co-muted); }
.check-circle {
    width: 20px; height: 20px;
    border: 2px solid #cbb99c;
    border-radius: 50%;
    position: relative; flex-shrink: 0;
}
.payment-option-label input:checked ~ .check-circle { border-color: var(--co-primary); }
.payment-option-label input:checked ~ .check-circle::after {
    content: ''; position: absolute; top: 50%; left: 50%;
    transform: translate(-50%, -50%);
    width: 10px; height: 10px; background: var(--co-primary); border-radius: 50%;
}

/* ── Sticky sidebar ── */
.sticky-sidebar { position: sticky; top: 90px; }

/* ── Order items ── */
.cart-items-scroll { max-height: 340px; overflow-y: auto; }
.cart-items-scroll::-webkit-scrollbar { width: 5px; }
.cart-items-scroll::-webkit-scrollbar-thumb { background: var(--co-border); border-radius: 5px; }
.checkout-item {
    display: flex; gap: 13px; padding: 14px 0;
    border-bottom: 1px solid #f0e8d8; position: relative; align-items: flex-start;
}
.checkout-item:last-child { border-bottom: none; }
.checkout-pro-img {
    width: 58px; height: 58px; border-radius: 8px;
    border: 1px solid var(--co-border); object-fit: cover; background: #fff;
}
.checkout-pro-info h6 { font-size: 13.5px; font-weight: 600; color: var(--co-text); margin: 0 0 4px; line-height: 1.4; }
.checkout-pro-info .meta { font-size: 11.5px; color: var(--co-muted); }
.co-price-line { font-size: 12px; color: var(--co-muted); }
.co-price-old { text-decoration: line-through; color: #b6ab9c; margin-left: 6px; }
.co-line-total { font-size: 14px; font-weight: 700; color: var(--co-primary); white-space: nowrap; }
.remove-item-btn { color: #d9534f; cursor: pointer; font-size: 14px; flex-shrink: 0; transition: 0.2s; }
.remove-item-btn:hover { color: #b52b27; }

/* Qty box */
.qty-box { display: flex; align-items: center; background: #fff; border: 1px solid var(--co-border); border-radius: 6px; padding: 2px; margin-top: 8px; width: fit-content; }
.qty-btn { width: 26px; height: 26px; border: none; background: transparent; border-radius: 4px; color: var(--co-primary); font-weight: bold; cursor: pointer; display: flex; align-items: center; justify-content: center; }
.qty-btn:hover { background: var(--co-cream); }
.qty-val { width: 30px; text-align: center; font-size: 13px; font-weight: 600; }

/* ── Edit cart link ── */
.bilai-co-edit { margin-left: auto; font-size: 12.5px; font-weight: 600; color: var(--co-primary); text-decoration: none; }
.bilai-co-edit:hover { color: var(--co-primary-dark); text-decoration: underline; }

/* ── Coupon ── */
.coupon-wrapper { padding: 16px 22px; border-top: 1px solid var(--co-border); }
.coupon-label { font-size: 13px; font-weight: 600; color: var(--co-text); margin-bottom: 8px; display: block; }
.coupon-group-modern {
    display: flex; width: 100%; height: 46px;
    border: 1px solid var(--co-border); border-radius: var(--co-radius-sm);
    overflow: hidden; background: #fff;
}
.coupon-group-modern:focus-within { border-color: var(--co-primary); box-shadow: 0 0 0 3px rgba(242,140,0,0.08); }
.coupon-input-modern { flex-grow: 1; border: none; padding: 0 14px; font-size: 14px; color: var(--co-text); outline: none; background: transparent; }
.coupon-input-modern::placeholder { color: #b6ab9c; }
.coupon-btn-modern {
    background: var(--co-brown); color: #fff; border: none;
    padding: 0 24px; font-weight: 600; font-size: 13px; cursor: pointer; transition: 0.2s;
}
.coupon-btn-modern:hover { background: #24140a; }

/* ── Reward earn info box ── */
.bilai-co-earn {
    display: flex; align-items: flex-start; gap: 12px;
    margin: 16px 22px 20px;
    background: #fff; border: 1px solid var(--co-border);
    border-radius: var(--co-radius-sm); padding: 14px;
}
.bilai-co-earn-ic {
    width: 34px; height: 34px; border-radius: 50%;
    background: var(--co-cream); color: var(--co-primary);
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0; font-size: 15px;
}
.bilai-co-earn p { margin: 0; font-size: 12.5px; color: var(--co-text); line-height: 1.55; }
.bilai-co-earn a { color: var(--co-primary); font-weight: 600; text-decoration: none; font-size: 12px; }
.bilai-co-earn a:hover { text-decoration: underline; }

/* ── Reward point card ── */
.bilai-co-reward-row { display: flex; align-items: center; justify-content: space-between; gap: 14px; }
.bilai-co-reward-pill {
    display: inline-flex; align-items: center; gap: 8px;
    background: var(--co-brown); color: #fff;
    padding: 8px 16px; border-radius: 100px; font-size: 13px; font-weight: 600;
}
.bilai-co-reward-pill .dot { width: 8px; height: 8px; border-radius: 50%; background: var(--co-primary); }
.bilai-co-policy { display: inline-block; margin-top: 12px; font-size: 12px; font-weight: 600; color: var(--co-primary); text-decoration: none; }
.bilai-co-policy:hover { text-decoration: underline; }
/* toggle (visual placeholder, disabled) */
.bilai-co-toggle { position: relative; width: 46px; height: 26px; flex-shrink: 0; }
.bilai-co-toggle input { opacity: 0; width: 0; height: 0; }
.bilai-co-toggle .track { position: absolute; inset: 0; background: #e3d8c4; border-radius: 100px; transition: 0.2s; cursor: not-allowed; }
.bilai-co-toggle .track::before { content: ''; position: absolute; top: 3px; left: 3px; width: 20px; height: 20px; background: #fff; border-radius: 50%; transition: 0.2s; box-shadow: 0 1px 3px rgba(0,0,0,0.2); }
.bilai-co-toggle input:checked + .track { background: var(--co-primary); }
.bilai-co-toggle input:checked + .track::before { transform: translateX(20px); }

/* ── Totals ── */
.summary-totals { padding: 6px 22px 4px; }
.total-row { display: flex; justify-content: space-between; margin-bottom: 12px; font-size: 13.5px; color: var(--co-text); }
.total-row span:first-child { color: var(--co-muted); }
.total-row.final {
    border-top: 1px solid var(--co-border);
    margin-top: 6px; padding-top: 14px;
    font-size: 17px; font-weight: 800; color: var(--co-primary);
}
.total-row.final span:first-child { color: var(--co-text); }

/* ── Place order button ── */
.btn-place-order {
    background: var(--co-primary); color: #fff; width: 100%;
    border: none; padding: 15px; border-radius: 100px;
    font-size: 15px; font-weight: 700; letter-spacing: 0.3px;
    transition: 0.2s; cursor: pointer;
    display: flex; justify-content: center; align-items: center; gap: 10px;
}
.btn-place-order:hover { background: var(--co-primary-dark); }

/* ── Manual payment fields ── */
#manual-payment-fields { background: #fff; border: 1px solid var(--co-border) !important; border-radius: var(--co-radius-sm); }

/* ── Responsive ── */
@media (max-width: 991px) {
    .cus-order-2 { order: 1; }
    .cust-order-1 { order: 2; margin-bottom: 26px; }
    .sticky-sidebar { position: static; }
    .mobile-submit-btn { display: block !important; margin-top: 22px; }
    .desktop-submit-btn { display: none !important; }
}
@media (min-width: 992px) {
    .mobile-submit-btn { display: none !important; }
    .desktop-submit-btn { display: block !important; }
}
/* BilaiGhor Checkout Figma End */

/* BilaiGhor Checkout Payment Fix Start */
/* Only Figma methods (COD + manual gateways) are rendered; online gateways hidden via Blade flag. */
.payment-options-list .payment-option-label { margin-bottom: 12px; }
.payment-options-list .payment-option-label:last-child { margin-bottom: 0; }
/* BilaiGhor Checkout Payment Fix End */

/* BilaiGhor Checkout Order Items Fix Start */
/* Qty +/- and trash removed on checkout — line total sits centered on the right. */
.checkout-item { align-items: center; }
.checkout-item .co-line-total { margin-top: 0; }
.checkout-item .text-end { display: flex; align-items: center; }
/* BilaiGhor Checkout Order Items Fix End */

/* BilaiGhor Checkout Summary Fix Start */
/* BilaiGhor Checkout Summary Fix End */

/* BilaiGhor Checkout Figma Refresh Start */
.checkout-section {
    --co-page: #FDFCF8;
    --co-card: #FBF5E6;
    --co-card-soft: #FDFCF8;
    --co-selected: #EDD9A6;
    --co-border: #DCCAB2;
    --co-text: #4F4F4F;
    --co-heading: #503311;
    --co-strong: #3C2A1E;
    --co-primary: #E8861A;
    --co-brown: #2A1505;
    --co-disabled: #DCCAB2;
    background: var(--co-page);
    padding: 24px 0 72px;
    color: var(--co-text);
    font-family: "DM Sans", sans-serif;
}

/* The global floating actions obscure checkout controls and are not part of this screen. */
.floating-cart-widget,
.chat-widget {
    display: none !important;
}

.checkout-container {
    width: 100%;
    max-width: 1200px;
    margin-right: auto;
    margin-left: auto;
    padding-left: 20px;
    padding-right: 20px;
    box-sizing: border-box;
}

.bilai-co-bc {
    margin-bottom: 26px;
    color: var(--co-text);
    font-size: 13px;
    line-height: 20px;
}

.bilai-co-bc a,
.bilai-co-bc span:not(.bilai-co-bc-active) {
    color: var(--co-text);
}

.bilai-co-bc-active {
    color: var(--co-primary);
    font-weight: 500;
}

.checkout-layout-grid {
    --bs-gutter-x: 0;
    --bs-gutter-y: 0;
    display: grid !important;
    grid-template-columns: minmax(0, 7fr) minmax(360px, 5fr);
    gap: 40px;
    align-items: flex-start;
    margin-right: 0;
    margin-left: 0;
}

.checkout-layout-grid > * {
    min-width: 0;
    width: 100%;
    max-width: none;
    padding-right: 0;
    padding-left: 0;
}

.checkout-card {
    background: var(--co-card);
    border: 1px solid var(--co-border);
    border-radius: 30px;
    box-shadow: none;
    overflow: hidden;
    margin-bottom: 60px;
    padding: 30px;
}

.checkout-header {
    min-height: 40px;
    padding: 0 0 30px;
    gap: 10px;
    border-bottom: 1px solid var(--co-border);
}

.checkout-card-icon,
.pay-logo-wrap {
    width: 40px;
    height: 40px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex: 0 0 40px;
    border-radius: 0;
    background: transparent;
}

.checkout-card-icon img {
    width: 40px;
    height: 40px;
    object-fit: contain;
}

.checkout-header h6 {
    color: var(--co-heading);
    font-size: 24px;
    font-weight: 600;
    line-height: 36px;
}

.card-body-custom {
    padding: 30px 0 0;
}

.cus-order-2 > .checkout-card:first-child .card-body-custom > .row {
    --bs-gutter-x: 30px;
}

.checkout-card:first-child .card-body-custom > .row > .col-md-6 {
    order: 1;
}

.checkout-address-field {
    order: 2;
}

.checkout-location-row {
    order: 3;
}

.checkout-location-fields .form-group {
    margin-bottom: 30px !important;
}

.checkout-card:first-child .card-body-custom > .row > .col-12:not(.checkout-address-field):not(.checkout-location-row) {
    order: 4;
}

.form-group {
    margin-bottom: 30px;
}

.form-label-custom {
    color: var(--co-text);
    font-size: 16px;
    font-weight: 600;
    line-height: 24px;
    margin-bottom: 10px;
}

.form-control-custom,
select.form-control-custom {
    height: 64px;
    border: 1px solid var(--co-border);
    border-radius: 10px;
    background-color: var(--co-page);
    color: var(--co-text);
    font-size: 16px;
    line-height: 24px;
    padding-left: 20px;
    padding-right: 44px;
}

select.form-control-custom {
    background-image: url("{{ asset('public/uploads/checkout-arrow-figma.svg') }}");
    background-repeat: no-repeat;
    background-position: right 20px center;
    background-size: 24px 24px;
}

.form-control-custom::placeholder {
    color: var(--co-disabled);
}

textarea.form-control-custom {
    min-height: 160px;
    padding-top: 20px;
    resize: vertical;
}

.bilai-addr-btn {
    height: 44px;
    margin-top: 20px;
    padding: 0 20px;
    border-radius: 30px;
    background: var(--co-brown);
    color: #fff;
    font-size: 16px;
}

.bilai-addr-btn-ic {
    width: 20px;
    height: 20px;
    object-fit: contain;
    filter: brightness(0) invert(1);
}

.payment-options-list {
    display: grid;
    gap: 30px;
}

.payment-option-label {
    min-height: 70px;
    margin: 0;
    padding: 20px;
    border-radius: 10px;
    background: var(--co-page);
}

.payment-option-label:has(input:checked) {
    background: var(--co-selected);
    box-shadow: none;
}

.payment-content {
    gap: 10px;
}

.pay-logo-wrap {
    width: 30px;
    height: 30px;
    flex-basis: 30px;
}

.pay-logo {
    width: 30px;
    height: 30px;
}

.pay-info strong {
    color: var(--co-strong);
    font-size: 18px;
    font-weight: 500;
    line-height: 30px;
}

.pay-info small {
    display: none;
}

#manual-payment-fields {
    background: #FFFDF8 !important;
    border-color: var(--co-border) !important;
}

.sticky-sidebar {
    top: 98px;
}

.cart-items-scroll {
    max-height: 380px;
}

.checkout-item {
    gap: 20px;
    padding: 30px 0 0;
    border-bottom: 0;
}

.checkout-pro-img {
    width: 48px;
    height: 48px;
    border-radius: 10px;
    object-fit: contain;
    background: #FFFDF8;
}

.checkout-pro-info h6 {
    color: var(--co-text);
    font-size: 16px;
    font-weight: 600;
    line-height: 24px;
}

.checkout-pro-info .meta,
.co-price-line {
    color: var(--co-text);
    font-size: 16px;
    line-height: 24px;
}

.co-line-total {
    color: var(--co-primary);
    font-size: 16px;
    font-weight: 600;
}

.bilai-co-edit {
    margin-left: auto;
    color: var(--co-primary);
    font-size: 16px;
    line-height: 24px;
}

.coupon-wrapper {
    padding: 30px 0 0;
    border-top: 0;
}

.coupon-group-modern {
    height: 64px;
    border-radius: 10px;
    background: var(--co-page);
}

.coupon-btn-modern {
    background: var(--co-brown);
    border-radius: 0 9px 9px 0;
    min-width: 110px;
    color: #EDF4F0;
    font-size: 18px;
    font-weight: 400;
}

.bilai-co-earn {
    margin: 30px 0 0;
    padding: 20px;
    gap: 20px;
    background: var(--co-page);
    border-radius: 20px;
}

.bilai-co-earn-ic {
    width: 56px;
    height: 56px;
    flex: 0 0 56px;
    background: transparent;
}

.bilai-co-earn-ic img {
    width: 56px;
    height: 56px;
    object-fit: contain;
}

.bilai-co-earn p {
    color: var(--co-strong);
    font-size: 18px;
    line-height: 27px;
}

.bilai-co-earn a,
.bilai-co-policy {
    color: var(--co-primary);
    font-size: 16px;
    line-height: 24px;
    font-weight: 400;
    text-decoration: underline;
}

.bilai-co-reward-row {
    align-items: flex-start;
}

.bilai-co-reward-pill {
    min-width: 225px;
    background: var(--co-brown);
    border: 0;
    color: #F0E6D8;
    border-radius: 30px;
    padding: 10px 20px;
    font-size: 20px;
    line-height: 30px;
    font-weight: 400;
}

.bilai-co-reward-pill .dot {
    background: var(--co-primary);
}

.bilai-co-toggle .track {
    cursor: pointer;
    background: transparent url("{{ asset('public/uploads/checkout-toggle-off-figma.svg') }}") center / 100% 100% no-repeat;
}

.bilai-co-toggle {
    width: 78px;
    height: 40px;
}

.bilai-co-toggle .track::before {
    display: none;
}

.bilai-co-toggle input:checked + .track {
    background: transparent url("{{ asset('public/uploads/checkout-toggle-on-figma.svg') }}") center / 100% 100% no-repeat;
}

.summary-totals {
    padding: 30px 0 0 !important;
}

.total-row {
    margin-bottom: 20px;
    color: var(--co-text);
    font-size: 18px;
    line-height: 27px;
}

.total-row span:first-child {
    color: var(--co-text);
}

.total-row.final {
    margin-top: 0;
    padding-top: 20px;
    border-top: 1px solid var(--co-border);
    color: var(--co-heading);
    font-size: 24px;
    line-height: 36px;
}

.total-row.final span:last-child {
    color: var(--co-primary);
}

.btn-place-order {
    min-height: 67px;
    border-radius: 16px;
    background: var(--co-primary);
    font-size: 18px;
    font-weight: 400;
}

.desktop-submit-btn {
    padding: 10px 0 0 !important;
}

.desktop-submit-btn .text-center,
.mobile-submit-btn .text-center {
    display: none;
}

.check-circle {
    width: 24px;
    height: 24px;
    border: 0;
    background: url("{{ asset('public/uploads/checkout-radio-off-figma.svg') }}") center / 24px 24px no-repeat;
}

.payment-option-label input:checked ~ .check-circle {
    border: 0;
    background-image: url("{{ asset('public/uploads/checkout-radio-on-figma.svg') }}");
}

.payment-option-label input:checked ~ .check-circle::after {
    display: none;
}

@media (max-width: 991px) {
    .checkout-section {
        padding-bottom: 96px;
    }

    .checkout-container {
        width: 100%;
        max-width: 100%;
        padding-left: 20px;
        padding-right: 20px;
    }

    .checkout-layout-grid {
        --bs-gutter-x: 0;
        grid-template-columns: minmax(0, 1fr);
        gap: 24px;
        width: 100%;
        max-width: 100%;
        margin-right: 0;
        margin-left: 0;
    }

    .checkout-layout-grid > * {
        width: 100%;
        max-width: 100%;
        padding-right: 0;
        padding-left: 0;
    }

    .checkout-card,
    .sticky-sidebar,
    .coupon-group-modern {
        width: 100%;
        max-width: 100%;
    }

    .coupon-input-modern {
        min-width: 0;
    }
}

@media (max-width: 575px) {
    .checkout-container {
        padding-left: 16px;
        padding-right: 16px;
    }

    .checkout-card {
        padding: 20px;
        border-radius: 20px;
        margin-bottom: 24px;
    }

    .checkout-header {
        padding-right: 0;
        padding-left: 0;
    }

    .card-body-custom,
    .coupon-wrapper,
    .summary-totals {
        padding-right: 0 !important;
        padding-left: 0 !important;
    }

    .checkout-header h6 {
        font-size: 17px;
        line-height: 25px;
    }

    .checkout-card-icon,
    .pay-logo-wrap {
        width: 32px;
        height: 32px;
        flex-basis: 32px;
    }

    .checkout-card-icon img {
        width: 32px;
        height: 32px;
    }

    .payment-option-label {
        padding: 14px;
    }

    .checkout-pro-img {
        width: 62px;
        height: 62px;
    }

    .coupon-btn-modern {
        min-width: 86px;
        padding: 0 14px;
    }
}

@media (max-width: 360px) {
    .checkout-container {
        padding-left: 12px;
        padding-right: 12px;
    }
}
/* BilaiGhor Checkout Figma Refresh End */

/* BilaiGhor Checkout Address Modal Start */
.bilai-addr-btn {
    display: inline-flex; align-items: center; gap: 8px;
    height: 44px;
    margin-top: 20px; padding: 0 20px;
    background: var(--co-brown); color: #fff;
    border: none; border-radius: 30px;
    font-size: 16px; font-weight: 600;
    line-height: 1; cursor: pointer; transition: 0.15s;
}
.bilai-addr-btn:hover { background: var(--co-brown); }
.bilai-addr-btn-ic { width: 20px; height: 20px; flex-shrink: 0; display: block; object-fit: contain; }

/* Overlay */
.bilai-addr-overlay {
    display: none; position: fixed; inset: 0; z-index: 1600;
    background: rgba(30, 18, 8, 0.5);
    align-items: flex-start; justify-content: center;
    padding: 40px 16px; overflow-y: auto;
}
.bilai-addr-overlay.open { display: flex; }

/* Logged-in dialog */
.bilai-addr-dialog {
    background: var(--co-cream); width: 100%; max-width: 960px;
    border-radius: 14px; overflow: hidden;
}
.bilai-addr-head {
    background: var(--co-primary); color: #fff;
    display: flex; align-items: center; justify-content: space-between;
    padding: 16px 24px;
}
.bilai-addr-head h5 { margin: 0; font-size: 16px; font-weight: 700; color: #fff; }
.bilai-addr-close {
    background: transparent; border: none; color: #fff;
    font-size: 20px; cursor: pointer; line-height: 1; padding: 4px;
}
.bilai-addr-body { padding: 28px; }
.bilai-addr-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }

/* Address card */
.bilai-addr-card { background: #FFFDF8; border: 1px solid var(--co-border); border-radius: 10px; overflow: hidden; }
.bilai-addr-card-head {
    display: flex; align-items: center; justify-content: space-between; gap: 10px;
    padding: 14px 18px; border-bottom: 1px solid var(--co-border);
}
.bilai-addr-card-title { font-size: 14px; font-weight: 700; color: var(--co-text); margin: 0; }
.bilai-addr-card-actions { display: flex; gap: 8px; }
.bilai-addr-select {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 6px 14px; border: none; border-radius: 8px;
    background: var(--co-primary); color: #fff;
    font-size: 12px; font-weight: 600; cursor: pointer; transition: 0.15s;
}
.bilai-addr-select:hover { background: var(--co-primary-dark); }
.bilai-addr-select.is-selected { background: #2e9e4f; }
.bilai-addr-edit {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 6px 14px; border: none; border-radius: 8px;
    background: var(--co-brown); color: #fff !important;
    font-size: 12px; font-weight: 600; cursor: pointer; text-decoration: none; transition: 0.15s;
}
.bilai-addr-edit:hover { background: #24140a; text-decoration: none; }
.bilai-addr-card-body { padding: 16px 18px; }
.bilai-addr-row { display: flex; gap: 8px; font-size: 13px; margin-bottom: 9px; line-height: 1.5; }
.bilai-addr-row:last-child { margin-bottom: 0; }
.bilai-addr-row .lbl { width: 70px; flex-shrink: 0; color: var(--co-muted); }
.bilai-addr-row .sep { color: var(--co-muted); flex-shrink: 0; }
.bilai-addr-row .val { color: var(--co-text); word-break: break-word; }

/* Add more card */
.bilai-addr-add {
    margin-top: 24px; display: flex; align-items: center; justify-content: center; gap: 8px;
    width: 100%; padding: 44px 16px;
    background: #FFFDF8; border: 1px solid var(--co-border); border-radius: 10px;
    color: var(--co-text); font-size: 14.5px; font-weight: 600;
    text-decoration: none; cursor: pointer; transition: 0.15s;
}
.bilai-addr-add:hover { border-color: var(--co-primary); color: var(--co-primary); text-decoration: none; }
.bilai-addr-empty { text-align: center; font-size: 13.5px; color: var(--co-muted); padding: 8px 0 0; }

/* Guest dialog */
.bilai-addr-guest {
    position: relative; background: #FFFDF8;
    width: 100%; max-width: 420px; margin-top: 60px;
    border-radius: 12px; padding: 38px 28px 34px; text-align: center;
}
.bilai-addr-guest .bilai-addr-close { position: absolute; top: 12px; right: 14px; color: var(--co-muted); }
.bilai-addr-guest-icon { font-size: 22px; color: var(--co-muted); margin-bottom: 12px; }
.bilai-addr-guest p { margin: 0; font-size: 14.5px; font-weight: 600; color: var(--co-text); line-height: 1.6; }
.bilai-addr-guest a { color: var(--co-primary); text-decoration: none; }
.bilai-addr-guest a:hover { text-decoration: underline; }

@media (max-width: 767px) {
    .bilai-addr-grid { grid-template-columns: 1fr; gap: 14px; }
    .bilai-addr-body { padding: 16px; }
    .bilai-addr-add { margin-top: 14px; padding: 28px 16px; }
}
/* BilaiGhor Checkout Address Modal End */
</style>
@endpush

@section('content')
<section class="checkout-section">
    @php
        // ==============================================================
        //  PHP LOGIC: CART, SHIPPING, DISCOUNT (UNCHANGED)
        // ==============================================================
        $subtotal = Cart::instance('shopping')->subtotal();
        $subtotal = str_replace(',', '', $subtotal);
        $subtotal = str_replace('.00', '', $subtotal);
        $subtotal = (float) $subtotal;

        // ✅ শিপিং লজিক চেক
        $requires_shipping = false;
        foreach (Cart::instance('shopping')->content() as $item) {
            $product = \App\Models\Product::find($item->id);
            if ($product && $product->is_digital != 1) {
                $requires_shipping = true;
                break;
            }
        }

        // ✅ শিপিং চার্জ সেট
        // ⭐ Free Delivery Check - যদি সব প্রোডাক্ট free delivery eligible হয়, shipping charge 0
        $hasAllFreeDelivery = \App\Http\Controllers\Frontend\ShoppingController::hasAllFreeDeliveryProducts();

        if ($requires_shipping && !$hasAllFreeDelivery) {
            $shipping = Session::get('shipping') ? Session::get('shipping') : 0;
        } else {
            $shipping = 0;
            Session::put('shipping', 0);
        }

        $discount = Session::get('discount', 0);
        // ⭐ Grand Total Calculation - Free delivery হলে shipping charge 0
        $grand_total = $subtotal + $shipping - $discount;

        // ✅ JS ডেটা অ্যারে
        $cartItemsForJs = [];
        $hasDigital = false;
        foreach (Cart::instance('shopping')->content() as $item) {
            $p = \App\Models\Product::find($item->id);
            if ($p && $p->is_digital == 1) { $hasDigital = true; }
            $cartItemsForJs[] = [
                'id'                => $item->id,
                'name'              => $item->name,
                'qty'               => $item->qty,
                'price'             => (float) $item->price,
                'image'             => asset($item->options->image ?? ''),
                'link'              => isset($item->options->slug) ? url('/product/'.$item->options->slug) : '#',
                'is_digital'        => (int) ($p->is_digital ?? 0),
                'free_delivery'     => (int) ($p->free_delivery ?? 0),
                'color_id'          => $item->options->color_id ?? null,
                'size_id'           => $item->options->size_id ?? null,
                'variant_price_id'  => $item->options->variant_price_id ?? null,
            ];
        }

        // Advance Payment has been fully retired from this checkout flow (order_save() always
        // charges the real grand total now). Variables kept at zero/false so nothing downstream
        // that still references these names breaks.
        $advance_amount = 0.0;
        $hasAdvance     = false;
        $payable_now    = $grand_total;
        $due_amount     = 0;

        // Online payment gateways hidden from the frontend for now (kept in backend/admin for later use).
        $__showOnlineGateways = false;

        // ── Prefill resolution: old() input (after validation error) wins, else controller prefill ──
        $checkoutPrefill = $checkoutPrefill ?? [];
        $selDistrict = old('district_id', $checkoutPrefill['district_id'] ?? '');
        $selThana    = old('thana_id',    $checkoutPrefill['thana_id']    ?? '');
        $selPostCode = old('post_code',   $checkoutPrefill['post_code']   ?? '');
        $selDivision = old('division_id', '');
        if (! $selDivision && $selDistrict) {
            $selDivision = optional(($checkoutDistricts ?? collect())->firstWhere('id', (int) $selDistrict))->division_id ?? '';
        }

        $__gsCheckoutOtp = \App\Models\GeneralSetting::where('status', 1)->first();
        $__custCheckoutOtpPending = session('chkotp_customer_pending');
        $__showCheckoutOtpModal = $__gsCheckoutOtp && ($__gsCheckoutOtp->checkout_otp_enabled ?? 0) == 1 && $__custCheckoutOtpPending;

        // --- Breadcrumb context (display only; no logic change) ---
        $__firstItem   = Cart::instance('shopping')->content()->first();
        $__bcProduct   = $__firstItem ? \App\Models\Product::find($__firstItem->id) : null;
        $__bcCategory  = $__bcProduct ? optional($__bcProduct->category)->name : null;
        $__bcSub       = $__bcProduct ? optional($__bcProduct->subcategory)->subcategoryName : null;
        $__bcName      = $__bcProduct ? $__bcProduct->name : null;
    @endphp

    <div class="checkout-container">

        {{-- Breadcrumb --}}
        <nav class="bilai-co-bc" aria-label="breadcrumb">
            <a href="{{ route('home') }}">Home</a>
            @if($__bcName)
                @if($__bcCategory)
                    <span class="bilai-co-bc-sep">›</span><span>{{ $__bcCategory }}</span>
                @endif
                @if($__bcSub)
                    <span class="bilai-co-bc-sep">›</span><span>{{ $__bcSub }}</span>
                @endif
                <span class="bilai-co-bc-sep">›</span><span class="bilai-co-bc-active">{{ Str::limit($__bcName, 40) }}</span>
            @else
                <span class="bilai-co-bc-sep">›</span><span class="bilai-co-bc-active">Checkout</span>
            @endif
        </nav>

        {{-- মেইন ফর্ম --}}
        <form id="checkout-form" action="{{ route('customer.ordersave') }}" method="POST" data-parsley-validate="">
            @csrf
            <input type="hidden" name="checkout_otp" id="checkout_otp_hidden" value="{{ old('checkout_otp') }}">
            <input type="hidden" name="post_code" id="checkout_post_code" value="{{ $selPostCode }}">
            {{-- Traffic: সার্ভার সেশন (referrer/fbclid) + ব্রাউজার sessionStorage --}}
            <input type="hidden" name="traffic_source" id="inp_ts" value="{{ old('traffic_source', session('order_traffic_source', 'direct')) }}">
            <input type="hidden" name="traffic_referrer" id="inp_tsr" value="{{ old('traffic_referrer', session('order_traffic_referrer', '')) }}">
            <script>
            try {
                var elTs = document.getElementById('inp_ts');
                var elTsr = document.getElementById('inp_tsr');
                var ts = sessionStorage.getItem('_ts');
                var tsr = sessionStorage.getItem('_tsr');
                if (ts !== null && ts !== '') {
                    elTs.value = ts;
                } else if (elTs.value && elTs.value !== 'direct') {
                    sessionStorage.setItem('_ts', elTs.value);
                }
                if (tsr !== null && tsr !== '') {
                    elTsr.value = tsr;
                } else if (elTsr.value) {
                    sessionStorage.setItem('_tsr', elTsr.value);
                }
            } catch (e) {}
            </script>

            <div class="checkout-layout-grid">

                {{-- LEFT COLUMN: Shipping & Payment --}}
                <div class="checkout-main-column cus-order-2">

                    {{-- 1. SHIPPING INFO CARD --}}
                    <div class="checkout-card">
                        <div class="checkout-header">
                            <span class="checkout-card-icon"><img src="{{ asset('public/uploads/checkout-delivery-figma.svg') }}" alt="" aria-hidden="true"></span>
                            <h6>Shipping Information</h6>
                        </div>
                        <div class="card-body-custom">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label-custom">Your Name <span class="req">*</span></label>
                                        <input type="text" name="name" class="form-control-custom"
                                            value="{{ old('name', $checkoutPrefill['name'] ?? '') }}" placeholder="Write your full name" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label-custom">Mobile <span class="req">*</span></label>
                                        <input type="text" name="phone" class="form-control-custom" minlength="11" maxlength="11" pattern="0[0-9]+"
                                            value="{{ old('phone', $checkoutPrefill['mobile'] ?? '') }}" placeholder="01xxxxxxxxx" required>
                                    </div>
                                </div>

                                @if($requires_shipping)
                                {{-- Division + District + Thana (same source as the Add/Edit Address popup) --}}
                                <div class="col-12 checkout-location-row">
                                    <div class="row g-2 g-md-3 align-items-end checkout-location-fields">
                                        <div class="col-12 col-md-4">
                                            <div class="form-group mb-0 mb-md-2">
                                                <label class="form-label-custom">Division <span class="req">*</span></label>
                                                <select name="division_id" id="checkout_division" class="form-control-custom" required>
                                                    <option value="">Select Division</option>
                                                    @foreach(($divisions ?? collect()) as $division)
                                                        <option value="{{ $division->id }}" @selected((string) $selDivision === (string) $division->id)>{{ $division->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-4">
                                            <div class="form-group mb-0 mb-md-2">
                                                <label class="form-label-custom">District <span class="req">*</span></label>
                                                <select name="district_id" id="checkout_district" class="form-control-custom" required>
                                                    <option value="">Select District</option>
                                                    @foreach(($checkoutDistricts ?? collect()) as $d)
                                                        <option value="{{ $d->id }}"
                                                            data-division="{{ $d->division_id ?? '' }}"
                                                            @selected((string) $selDistrict === (string) $d->id)>{{ $d->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-4">
                                            <div class="form-group mb-0 mb-md-2">
                                                <label class="form-label-custom">Thana/Upazila <span class="req">*</span></label>
                                                <select name="thana_id" id="checkout_thana" class="form-control-custom" required disabled>
                                                    <option value="">Select Thana</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @else
                                <div class="col-12">
                                    <div class="form-group">
                                        <label class="form-label-custom">Delivery Location</label>
                                        <input type="text" class="form-control-custom" value="Free shipping — no location required" readonly disabled style="background:#f3f4f6;">
                                        <input type="hidden" name="district_id" value="">
                                        <input type="hidden" name="thana_id" value="">
                                    </div>
                                </div>
                                @endif

                                {{-- Full Address is shown before the location selectors by the checkout layout CSS. --}}
                                <div class="col-12 checkout-address-field">
                                    <div class="form-group">
                                        <label class="form-label-custom">Full Address <span class="req">*</span></label>
                                        <input type="text" name="address" class="form-control-custom"
                                            value="{{ old('address', $checkoutPrefill['address'] ?? '') }}" placeholder="100 Rasulpur Rd" required>
                                        <button type="button" id="bilai-addr-open" class="bilai-addr-btn">
                                            <img class="bilai-addr-btn-ic" src="{{ asset('public/uploads/checkout-address-figma.svg') }}" alt="" aria-hidden="true">
                                            <span>Select Address</span>
                                        </button>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-group" style="margin-bottom:0;">
                                        <label class="form-label-custom">Order Note (Optional)</label>
                                        <textarea name="order_note" id="order_note" class="form-control-custom" rows="3" style="height:auto; resize:none;"
                                            placeholder="Write your note about the product">{{ $order_note ?? '' }}</textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- 2. PAYMENT METHOD CARD --}}
                    <div class="checkout-card">
                        <div class="checkout-header">
                            <span class="checkout-card-icon"><img src="{{ asset('public/uploads/checkout-wallet-figma.svg') }}" alt="" aria-hidden="true"></span>
                            <h6>Select Payment Method</h6>
                        </div>
                        <div class="card-body-custom">

                            {{-- Payment Options List --}}
                            <div class="payment-options-list">

                                {{-- COD Option --}}
                                @if(!$hasDigital)
                                    <label class="payment-option-label">
                                        <input type="radio" name="payment_method" value="cod" checked required>
                                        <div class="payment-content">
                                            <span class="pay-logo-wrap"><img src="{{ asset('public/uploads/checkout-cod-figma.svg') }}" class="pay-logo" alt="" aria-hidden="true"></span>
                                            <div class="pay-info">
                                                <strong>Cash on Delivery</strong>
                                                <small>Pay when you receive the product</small>
                                            </div>
                                        </div>
                                        <div class="check-circle"></div>
                                    </label>
                                @endif

                                {{-- Bkash (hidden from frontend; toggle $__showOnlineGateways to re-enable) --}}
                                @if($__showOnlineGateways && $bkash_gateway)
                                    <label class="payment-option-label">
                                        <input type="radio" name="payment_method" value="bkash" required>
                                        <div class="payment-content">
                                            <span class="pay-logo-wrap"><img src="{{ asset('public/uploads/checkout-bkash-figma.svg') }}" class="pay-logo" alt="bKash"></span>
                                            <div class="pay-info">
                                                <strong>bKash Payment</strong>
                                                <small>Pay via bKash app or gateway</small>
                                            </div>
                                        </div>
                                        <div class="check-circle"></div>
                                    </label>
                                @endif

                                {{-- ShurjoPay (hidden from frontend; toggle $__showOnlineGateways to re-enable) --}}
                                <!-- @if($__showOnlineGateways && $shurjopay_gateway)
                                    <label class="payment-option-label">
                                        <input type="radio" name="payment_method" value="shurjopay" required>
                                        <div class="payment-content">
                                            <img src="{{ asset('public/frontEnd/images/shurjoPay.png') }}" class="pay-logo" alt="ShurjoPay">
                                            <div class="pay-info">
                                                <strong>Online Payment</strong>
                                                <small>ShurjoPay (Card/Mobile Banking)</small>
                                            </div>
                                        </div>
                                        <div class="check-circle"></div>
                                    </label>
                                @endif -->

                                {{-- UddoktaPay (hidden from frontend; toggle $__showOnlineGateways to re-enable) --}}
                                @if($__showOnlineGateways && $uddoktapay_gateway)
                                    <label class="payment-option-label">
                                        <input type="radio" name="payment_method" value="uddoktapay" required>
                                        <div class="payment-content">
                                            <img src="{{ asset('public/frontEnd/images/uddokta.png') }}" class="pay-logo" alt="UddoktaPay">
                                            <div class="pay-info">
                                                <strong>UddoktaPay</strong>
                                                <small>Mobile banking payment gateway</small>
                                            </div>
                                        </div>
                                        <div class="check-circle"></div>
                                    </label>
                                @endif

                                {{-- aamarPay (hidden from frontend; toggle $__showOnlineGateways to re-enable) --}}
                                @if($__showOnlineGateways && $aamarpay_gateway)
                                    <label class="payment-option-label">
                                        <input type="radio" name="payment_method" value="aamarpay" required>
                                        <div class="payment-content">
                                            <img src="{{ asset('public/frontEnd/images/aamarpay.png') }}" class="pay-logo" alt="aamarPay" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                            <div class="pay-info" style="display: none;">
                                                <i class="fa fa-credit-card" style="color:var(--co-primary); font-size:20px;"></i>
                                            </div>
                                            <div class="pay-info">
                                                <strong>aamarPay</strong>
                                                <small>Card &amp; mobile banking payment</small>
                                            </div>
                                        </div>
                                        <div class="check-circle"></div>
                                    </label>
                                @endif

                                @foreach($manual_gateways ?? [] as $mg)
                                    @php
                                        $__gatewayTitle = strtolower((string) $mg->title);
                                        $__gatewayIcon = str_contains($__gatewayTitle, 'bkash')
                                            ? asset('public/uploads/checkout-bkash-figma.svg')
                                            : (str_contains($__gatewayTitle, 'nagad')
                                                ? asset('public/uploads/checkout-nagad-figma.svg')
                                                : (str_contains($__gatewayTitle, 'rocket')
                                                    ? asset('public/frontEnd/images/rocket.png')
                                                    : ($mg->logo_asset_url ?: asset('public/uploads/checkout-wallet-figma.svg'))));
                                    @endphp
                                    <label class="payment-option-label">
                                        <input type="radio" name="payment_method" value="manual_{{ $mg->id }}" required>
                                        <div class="payment-content">
                                            <span class="pay-logo-wrap"><img src="{{ $__gatewayIcon }}" class="pay-logo" alt="{{ $mg->title }}"></span>
                                            <div class="pay-info">
                                                <strong>{{ $mg->title }}</strong>
                                                <small>Manual payment — send money and enter the transaction ID</small>
                                            </div>
                                        </div>
                                        <div class="check-circle"></div>
                                    </label>
                                @endforeach

                            </div>
                            <div id="manual-payment-fields" class="mt-3 p-3" style="display:none;">
                                <h6 class="fw-bold mb-2" style="color:var(--co-text);"><i class="fa fa-info-circle" style="color:var(--co-primary);"></i> Manual Payment Instructions</h6>
                                <div id="manual-instructions-body" class="small mb-3" style="white-space:pre-wrap; color:var(--co-muted);"></div>
                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold">Transaction ID / Reference <span class="req">*</span></label>
                                        <input type="text" name="manual_trx_id" id="manual_trx_id" class="form-control form-control-custom" value="{{ old('manual_trx_id') }}" maxlength="55" placeholder="TrxID">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold">Sender number (optional)</label>
                                        <input type="text" name="manual_sender_number" class="form-control form-control-custom" value="{{ old('manual_sender_number') }}" maxlength="55" placeholder="01xxx">
                                    </div>
                                </div>
                            </div>
                            <script>
                                window.MANUAL_GATEWAYS = @json(($manual_gateways ?? collect())->map(fn ($g) => [
                                    'code' => 'manual_'.$g->id,
                                    'instructions' => (string) ($g->instructions ?? ''),
                                ])->values()->all());
                            </script>
                            {{-- Error message placeholder --}}
                            <div id="payment-error" class="text-danger fw-bold mt-2 text-center" style="display:none;">
                                <i class="fa fa-exclamation-circle"></i> Please select a payment method.
                            </div>
                        </div>
                    </div>

                    {{-- MOBILE SUBMIT BUTTON (Only Visible on Mobile) --}}
                    <div class="mobile-submit-btn">
                        <button type="submit" class="btn-place-order">
                            Place Order
                        </button>
                        <div class="text-center small mt-3" style="color:var(--co-muted);">
                            <i class="fa fa-shield"></i> 100% safe &amp; secure checkout
                        </div>
                    </div>

                </div>

                {{-- RIGHT COLUMN: Order Summary --}}
                <div class="checkout-side-column cust-order-1">
                    <div class="sticky-sidebar">

                        {{-- CARD 1: Order Summary (items + coupon + reward earn) --}}
                        <div class="checkout-card">
                            <div class="checkout-header">
                                <span class="checkout-card-icon"><img src="{{ asset('public/uploads/checkout-order-figma.svg') }}" alt="" aria-hidden="true"></span>
                                <h6>Order Items</h6>
                                <a href="{{ route('cart.index') }}" class="bilai-co-edit">Edit Cart</a>
                            </div>

                            {{-- Products List --}}
                            <div class="card-body-custom" style="padding-top:6px; padding-bottom:6px;">
                                <div class="cart-items-scroll cartlist">
                                    @foreach (Cart::instance('shopping')->content() as $value)
                                        @php
                                            $__cp = \App\Models\Product::find($value->id);
                                            $__old = ($__cp && $__cp->old_price && $__cp->old_price > $value->price) ? $__cp->old_price : null;
                                        @endphp
                                        <div class="checkout-item">
                                            {{-- Image --}}
                                            <a href="{{ route('product', $value->options->slug) }}">
                                                <img src="{{ asset($value->options->image) }}" class="checkout-pro-img" alt="{{ $value->name }}">
                                            </a>

                                            {{-- Info --}}
                                            <div class="checkout-pro-info flex-grow-1">
                                                <a href="{{ route('product', $value->options->slug) }}" style="text-decoration:none;">
                                                    <h6>{{ Str::limit($value->name, 40) }}</h6>
                                                </a>
                                                @if($value->options->product_size || $value->options->product_color)
                                                    <div class="meta mb-1">
                                                        @if($value->options->product_size) Size: {{ $value->options->product_size }} @endif
                                                        @if($value->options->product_color) | Color: {{ $value->options->product_color }} @endif
                                                    </div>
                                                @endif
                                                <div class="co-price-line">
                                                    {{ $value->qty }} x ৳{{ number_format($value->price, 0) }}
                                                    @if($__old)<span class="co-price-old">৳{{ number_format($__old, 0) }}</span>@endif
                                                </div>
                                                {{-- Qty +/- and remove controls intentionally omitted on checkout; use "Edit Cart" --}}
                                            </div>

                                            {{-- Line total --}}
                                            <div class="text-end">
                                                <div class="co-line-total">৳{{ number_format($value->price * $value->qty, 0) }}</div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            {{-- COUPON SECTION --}}
                            <div class="coupon-wrapper">
                                <span class="coupon-label">Coupon Code</span>
                                @if(!Session::has('coupon_code'))
                                    <div class="coupon-group-modern">
                                        {{-- ভিজ্যুয়াল ইনপুট (এটি কোনো ফর্মের অংশ নয়, শুধু ডাটা নেওয়ার জন্য) --}}
                                        <input type="text" id="coupon_input" class="coupon-input-modern" placeholder="Put your coupon code">
                                        <button type="button" class="coupon-btn-modern" onclick="submitCoupon()">Apply</button>
                                    </div>
                                @else
                                    <div class="d-flex justify-content-between align-items-center" style="background:#eef7e9; border:1px solid #cfe6c0; border-radius:10px; padding:10px 14px;">
                                        <span style="color:#2e7d32; font-size:13px;"><i class="fa fa-check-circle"></i> Coupon <b>{{ Session::get('coupon_code') }}</b> applied!</span>
                                        <a href="{{ route('coupon.remove') }}" class="fw-bold text-decoration-none" style="color:#c0392b; font-size:12px;">REMOVE</a>
                                    </div>
                                @endif
                            </div>

                            {{-- Reward earn info (dynamic: floor(eligible/100), credited on delivery) --}}
                            @php $__rwEarnPreview = \App\Services\RewardPointService::earnedPointsFor(max(0, $subtotal - $discount)); @endphp
                            <div class="bilai-co-earn">
                                <div class="bilai-co-earn-ic"><img src="{{ asset('public/uploads/checkout-reward-figma.svg') }}" alt="" aria-hidden="true"></div>
                                <div>
                                    <p>You will earn <strong id="bilai-rw-earn">{{ $__rwEarnPreview }}</strong> reward points for this order — points are credited once your order has been delivered.</p>
                                    <a href="{{ route('customer.rewards') }}">Reward Points Policy</a>
                                </div>
                            </div>
                        </div>

                        {{-- CARD 2: Use Your Reward Point (logged-in customers only) --}}
                        @auth('customer')
                        @php $__rwBalance = Auth::guard('customer')->user()->rewardBalance(); @endphp
                        <div class="checkout-card">
                            <div class="checkout-header">
                                <span class="checkout-card-icon"><img src="{{ asset('public/uploads/checkout-reward-figma.svg') }}" alt="" aria-hidden="true"></span>
                                <h6>Use Your Reward Point</h6>
                            </div>
                            <div class="card-body-custom">
                                <div class="bilai-co-reward-row">
                                    <span class="bilai-co-reward-pill">
                                        <span class="dot"></span> <span id="bilai-rw-avail">{{ $__rwBalance }}</span> Points available
                                    </span>
                                    {{-- State only; points & discount are recomputed server-side on submit --}}
                                    <input type="hidden" name="use_reward_points" id="bilai-rw-input" value="{{ old('use_reward_points') ? 1 : 0 }}">
                                    <label class="bilai-co-toggle" @if($__rwBalance < 1) title="No points available" @endif>
                                        <input type="checkbox" id="bilai-rw-toggle" @checked(old('use_reward_points')) @disabled($__rwBalance < 1)>
                                        <span class="track"></span>
                                    </label>
                                </div>
                                <a href="{{ route('customer.rewards') }}" class="bilai-co-policy">Reward Points Policy</a>
                            </div>
                        </div>
                        @endauth

                        {{-- CARD 3: Order Summary (totals) + Place Order --}}
                        <div class="checkout-card">
                            <div class="checkout-header">
                                <span class="checkout-card-icon"><img src="{{ asset('public/uploads/checkout-reward-figma.svg') }}" alt="" aria-hidden="true"></span>
                                <h6>Order Summary</h6>
                            </div>
                            <div class="summary-totals" style="padding-top:16px;">
                                <div class="total-row"><span>Subtotal</span> <span id="subtotalAmount">৳ {{ number_format($subtotal, 2) }}</span></div>
                                <div class="total-row"><span>Delivery Charge</span> <span id="shippingAmount">৳ {{ number_format($shipping, 2) }}</span></div>
                                <div class="total-row"><span>Discount</span> <span id="discountAmount">- ৳ {{ number_format($discount, 2) }}</span></div>
                                <div class="total-row"><span>Cash from Reward Points</span> <span id="rewardDiscountAmount">- ৳ 0.00</span></div>
                                <div class="total-row final"><span>Total</span> <span id="grandTotalAmount">৳ {{ number_format($grand_total, 2) }}</span></div>
                            </div>

                            {{-- DESKTOP SUBMIT BUTTON (Only Visible on Desktop) --}}
                            <div class="desktop-submit-btn" style="padding:8px 22px 22px;">
                                <button type="submit" class="btn-place-order">
                                    Place Order
                                </button>
                                <div class="text-center small mt-3" style="color:var(--co-muted);">
                                    <i class="fa fa-lock"></i> 100% safe checkout process
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

            </div>
        </form>

        {{-- ═══════ Select Address modal (logged-in: saved addresses / guest: login prompt) ═══════ --}}
        <div id="bilai-addr-modal" class="bilai-addr-overlay" aria-hidden="true">
            @auth('customer')
                <div class="bilai-addr-dialog" role="dialog" aria-label="Your Addresses">
                    <div class="bilai-addr-head">
                        <h5>Your Addresses</h5>
                        <button type="button" class="bilai-addr-close" aria-label="Close">&times;</button>
                    </div>
                    <div class="bilai-addr-body">
                        @if(count($savedAddresses ?? []) > 0)
                            <div class="bilai-addr-grid">
                                @foreach($savedAddresses as $i => $addr)
                                    <div class="bilai-addr-card">
                                        <div class="bilai-addr-card-head">
                                            <p class="bilai-addr-card-title">Address {{ $i + 1 }}</p>
                                            <div class="bilai-addr-card-actions">
                                                <button type="button" class="bilai-addr-select"
                                                        data-name="{{ $addr['name'] }}"
                                                        data-mobile="{{ $addr['mobile'] }}"
                                                        data-address="{{ $addr['address'] }}"
                                                        data-postcode="{{ $addr['post_code'] ?? '' }}"
                                                        data-division="{{ $addr['division_id'] ?? '' }}"
                                                        data-dist="{{ $addr['district_id'] }}"
                                                        data-thana="{{ $addr['thana_id'] ?? '' }}">
                                                    {{-- Replace check SVG icon later --}}
                                                    <i class="fa fa-check"></i> <span>Select</span>
                                                </button>
                                                @if(!empty($addr['id']))
                                                    <button type="button" class="bilai-addr-edit bilai-afm-edit-open"
                                                            data-id="{{ $addr['id'] }}"
                                                            data-name="{{ $addr['name'] }}"
                                                            data-mobile="{{ $addr['mobile'] }}"
                                                            data-email="{{ $addr['email'] }}"
                                                            data-postcode="{{ $addr['post_code'] ?? '' }}"
                                                            data-district="{{ $addr['district_id'] }}"
                                                            data-thana="{{ $addr['thana_id'] ?? '' }}"
                                                            data-address="{{ $addr['address'] }}">
                                                        {{-- Replace edit SVG icon later --}}
                                                        <i class="fa fa-pencil-square-o"></i> Edit
                                                    </button>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="bilai-addr-card-body">
                                            <div class="bilai-addr-row"><span class="lbl">Name</span><span class="sep">:</span><span class="val">{{ $addr['name'] ?: 'N/A' }}</span></div>
                                            <div class="bilai-addr-row"><span class="lbl">Mobile</span><span class="sep">:</span><span class="val">{{ $addr['mobile'] ?: 'N/A' }}</span></div>
                                            @if(!empty($addr['email']))
                                                <div class="bilai-addr-row"><span class="lbl">Email</span><span class="sep">:</span><span class="val">{{ $addr['email'] }}</span></div>
                                            @endif
                                            <div class="bilai-addr-row"><span class="lbl">Address</span><span class="sep">:</span><span class="val">{{ $addr['address'] }}</span></div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="bilai-addr-empty">No saved addresses yet.</p>
                        @endif

                        <button type="button" class="bilai-addr-add bilai-afm-add-open" style="cursor:pointer;">
                            {{-- Replace plus SVG icon later --}}
                            <i class="fa fa-plus"></i> Add More Address
                        </button>
                    </div>
                </div>
            @else
                <div class="bilai-addr-guest" role="dialog" aria-label="Login required">
                    <button type="button" class="bilai-addr-close" aria-label="Close">&times;</button>
                    {{-- Replace guest address SVG icon later --}}
                    <div class="bilai-addr-guest-icon"><i class="fa fa-user-o"></i></div>
                    <p>
                        <a href="{{ route('customer.login') }}">Login</a> or
                        <a href="{{ route('customer.register') }}">Register</a> to Select/Add<br>Your Address
                    </p>
                </div>
            @endauth
        </div>

        {{-- Reusable Add/Edit Address popup (shared with the Addresses page) --}}
        @auth('customer')
            @include('frontEnd.layouts.customer.partials.address-form-modal')
        @endauth

        @if(!empty($__showCheckoutOtpModal))
        <div class="modal fade" id="checkoutOtpModal" tabindex="-1" aria-labelledby="checkoutOtpModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg overflow-hidden">
                    <div class="modal-header text-white" style="background: linear-gradient(135deg,#F28C00,#c96f00);">
                        <h5 class="modal-title d-flex align-items-center gap-2 mb-0" id="checkoutOtpModalLabel">
                            <i class="fa fa-mobile"></i> OTP Verification
                        </h5>
                    </div>
                    <div class="modal-body p-4">
                        <p class="text-muted small mb-3">A <strong>6 digit OTP</strong> has been sent to your mobile number via SMS. Enter the code and confirm below.</p>
                        @error('checkout_otp')
                            <div class="alert alert-danger py-2 small mb-3">{{ $message }}</div>
                        @enderror
                        <label class="form-label fw-semibold">OTP Code</label>
                        <input type="text" id="checkout_otp_modal_field" class="form-control form-control-lg text-center letter-spacing-wide" maxlength="6"
                            inputmode="numeric" autocomplete="one-time-code" placeholder="● ● ● ● ● ●" style="letter-spacing: 0.35em;"
                            value="{{ old('checkout_otp') }}">
                        <div class="d-flex flex-wrap gap-2 mt-4 justify-content-between align-items-center">
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="checkout_otp_resend_btn">Resend OTP</button>
                            <button type="button" class="btn btn-success px-4 fw-bold" id="checkout_otp_confirm_btn">Complete Order</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <form id="checkout_otp_resend_form" action="{{ route('customer.checkout.resend_otp') }}" method="POST" style="display:none;">
            @csrf
            <input type="hidden" name="phone" id="checkout_otp_resend_phone" value="">
        </form>
        @endif

    </div>
</section>
@endsection

@push('script')
{{-- select2.min.js is loaded once by the shared address-form-modal partial; a second load here
     re-registers the plugin over live instances and breaks the District/Thana dropdowns --}}

@if(!empty($__showCheckoutOtpModal))
<script>
document.addEventListener('DOMContentLoaded', function () {
    var modalEl = document.getElementById('checkoutOtpModal');
    if (!modalEl) return;
    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        bootstrap.Modal.getOrCreateInstance(modalEl, { backdrop: 'static', keyboard: false }).show();
    } else if (window.jQuery && jQuery.fn.modal) {
        jQuery(modalEl).modal({ backdrop: 'static', keyboard: false });
        jQuery(modalEl).modal('show');
    }
    var modalInput = document.getElementById('checkout_otp_modal_field');
    if (modalInput) {
        setTimeout(function () { modalInput.focus(); }, 400);
    }
    document.getElementById('checkout_otp_confirm_btn').addEventListener('click', function () {
        var raw = modalInput ? modalInput.value : '';
        document.getElementById('checkout_otp_hidden').value = raw.replace(/\D/g, '').slice(0, 6);
        document.getElementById('checkout-form').submit();
    });
    document.getElementById('checkout_otp_resend_btn').addEventListener('click', function () {
        var phoneIn = document.querySelector('#checkout-form input[name="phone"]');
        document.getElementById('checkout_otp_resend_phone').value = phoneIn ? phoneIn.value : '';
        document.getElementById('checkout_otp_resend_form').submit();
    });
});
</script>
@endif

{{-- ============================================================== --}}
{{--  JAVASCRIPT LOGIC (EXACT COPY - NO FUNCTIONALITY REMOVED)  --}}
{{-- ============================================================== --}}

        {{-- ========================================================= --}}
        {{--  🔴 এই অংশটুকু আপনার কোডে মিসিং ছিল, তাই কাজ করছিল না   --}}
        {{-- ========================================================= --}}

        {{-- হিডেন কুপন ফর্ম (এটি অবশ্যই মেইন ফর্মের বাইরে থাকতে হবে) --}}
        <form id="coupon-form" action="{{ route('coupon.apply') }}" method="POST" style="display:none;">
            @csrf
            <input type="hidden" name="coupon_code" id="hidden_coupon_code">
        </form>

        {{-- কুপন সাবমিট করার জাভাস্ক্রিপ্ট --}}
        <script>
            function submitCoupon() {
                var code = document.getElementById('coupon_input').value;
                if(code) {
                    document.getElementById('hidden_coupon_code').value = code;
                    document.getElementById('coupon-form').submit();
                } else {
                    // টোস্টার থাকলে টোস্টার, নাহলে এলার্ট
                    if(typeof toastr !== 'undefined') {
                        toastr.error('Please enter a coupon code');
                    } else {
                        alert('Please enter a coupon code');
                    }
                }
            }
        </script>
<script>
    // গ্লোবাল ভেরিয়েবল (Global Variables)
    let incompleteOrderTimer;
    let isSubmitting = false; // অর্ডার সাবমিট হচ্ছে কিনা তা চেক করার জন্য

    $(document).ready(function() {
        // Select2 Initialize (guarded: select2 only ships via the address modal partial for logged-in users).
        // Scoped to real <select> elements only: select2 v4's generated container spans also
        // carry the class "select2", and initializing on those spans renders empty duplicate boxes.
        if ($.fn && $.fn.select2) { $("select.select2").not(".select2-hidden-accessible").select2({ width: '100%' }); }

        // ==========================================
        // 1. CART LOGIC (REMOVE, INCREASE, DECREASE)
        // ==========================================

        // Remove Item
        $(document).on('click', '.cart_remove', function(e) {
            e.preventDefault(); e.stopImmediatePropagation();
            var id = $(this).data("id");
            if (id) {
                $("#loading").show();
                $.ajax({
                    type: "GET",
                    url: "{{ route('cart.remove') }}",
                    data: { id: id },
                    success: function() { toastr.success('Success', 'Item removed'); window.location.reload(); },
                    error: function() { window.location.reload(); }
                });
            }
        });

        // Quantity Increment
        $('.checkout-qty .plus').on('click', function() {
            var rowId = $(this).closest('.checkout-qty').data('rowid');
            $("#loading").show();
            $.get("{{ route('cart.increment') }}", { id: rowId }, function() { window.location.reload(); });
        });

        // Quantity Decrement
        $('.checkout-qty .minus').on('click', function() {
            var rowId = $(this).closest('.checkout-qty').data('rowid');
            $("#loading").show();
            $.get("{{ route('cart.decrement') }}", { id: rowId }, function() { window.location.reload(); });
        });

        // ==========================================
        // 2. SHIPPING & TOTAL CALCULATION
        // ==========================================

        const baseSubtotal = parseFloat("{{ $subtotal ?? 0 }}");
        const baseDiscount = parseFloat("{{ $discount ?? 0 }}");
        const requiresShipping = @json($requires_shipping ?? false);
        const cartItems = @json($cartItemsForJs ?? []);
        const hasAllFreeDelivery = @json($hasAllFreeDelivery ?? false);

        // ⭐ Free Delivery Check Function
        function checkFreeDelivery() {
            // Check if all physical products have free_delivery = 1
            let allFreeDelivery = true;
            for (let i = 0; i < cartItems.length; i++) {
                let item = cartItems[i];
                // Skip digital products
                if (item.is_digital == 1) {
                    continue;
                }
                // If any physical product doesn't have free_delivery, return false
                if (item.free_delivery != 1) {
                    allFreeDelivery = false;
                    break;
                }
            }
            return allFreeDelivery;
        }

        function thanaChargeFromSelect() {
            if (!$('#checkout_thana').length || !$('#checkout_thana').val()) {
                return 0;
            }
            return parseFloat($('#checkout_thana option:selected').attr('data-charge')) || 0;
        }

        function syncCheckoutDistrictOptions(clearDistrict) {
            var $division = $('#checkout_division');
            var $district = $('#checkout_district');
            if (!$division.length || !$district.length) {
                return;
            }

            var divisionId = String($division.val() || '');
            var currentDistrictVisible = true;
            $district.find('option').each(function () {
                var $opt = $(this);
                if (!$opt.val()) {
                    $opt.prop('disabled', false).show();
                    return;
                }
                var matches = !divisionId || String($opt.data('division') || '') === divisionId;
                $opt.prop('disabled', !matches).toggle(matches);
                if ($opt.is(':selected') && !matches) {
                    currentDistrictVisible = false;
                }
            });

            if (clearDistrict || !currentDistrictVisible) {
                $district.val('');
                $('#checkout_thana').html('<option value="">Select Thana</option>').prop('disabled', true);
            }
        }

        // Reward discount currently applied (display only — server recomputes on submit).
        window.bilaiRewardDiscount = 0;

        function applyShippingToDomAndSession() {
            var isFreeDelivery = checkFreeDelivery();
            var shippingCharge = isFreeDelivery ? 0 : thanaChargeFromSelect();

            var grandTotal = Math.max(0, baseSubtotal + shippingCharge - baseDiscount - (window.bilaiRewardDiscount || 0));

            $('#shippingAmount').text('৳ ' + shippingCharge.toFixed(2));
            $('#grandTotalAmount').text('৳ ' + grandTotal.toFixed(2));

            if (!requiresShipping) {
                return;
            }

            if (isFreeDelivery) {
                $.get('{{ route("shipping.charge") }}', { id: 'free_delivery' });
            } else {
                var thanaId = $('#checkout_thana').val();
                if (thanaId) {
                    $.get('{{ route("shipping.charge") }}', { id: thanaId });
                }
            }
        }

        // District to Thana options are loaded by the shared helper.
        // (same endpoint as the Add/Edit Address popup); this handler only refreshes the
        // shipping charge, which comes from the selected Thana.
        $('#checkout_division').on('change', function () {
            syncCheckoutDistrictOptions(true);
            applyShippingToDomAndSession();
            saveIncompleteOrder();
        });

        syncCheckoutDistrictOptions(false);

        $('#checkout_district').on('change', function () {
            applyShippingToDomAndSession();
            saveIncompleteOrder();
        });

        // ── Use Your Reward Point toggle (logged-in only; card absent for guests) ──
        // Numbers always come from the backend preview endpoint, never from JS math.
        $('#bilai-rw-toggle').on('change', function () {
            var on = this.checked;
            var $toggle = $(this).prop('disabled', true);
            $.post('{{ route("customer.checkout.reward_preview") }}', {
                _token: '{{ csrf_token() }}',
                use_reward_points: on ? 1 : 0
            }, function (res) {
                $('#bilai-rw-input').val(on ? 1 : 0);
                $('#bilai-rw-avail').text(res.available_points);
                $('#rewardDiscountAmount').text('- ৳ ' + Number(res.reward_discount).toFixed(2));
                $('#bilai-rw-earn').text(res.earn_points);
                window.bilaiRewardDiscount = Number(res.reward_discount) || 0;
                applyShippingToDomAndSession(); // recompute total with the new discount
            }).fail(function () {
                $('#bilai-rw-toggle').prop('checked', false);
                $('#bilai-rw-input').val(0);
            }).always(function () {
                $toggle.prop('disabled', false);
            });
        });

        $('#checkout_thana').on('change', function () {
            applyShippingToDomAndSession();
            saveIncompleteOrder();
        });

        // ⭐ পেজ লোড — ফ্রি ডেলিভারি / শিপিং সার্ফেস
        $(document).ready(function() {
            var isFreeDeliveryOnLoad = hasAllFreeDelivery || checkFreeDelivery();

            if (!requiresShipping) {
                return;
            }

            if (isFreeDeliveryOnLoad) {
                applyShippingToDomAndSession();
            } else {
                var currentShipping = parseFloat($('#shippingAmount').text().replace(/[৳,\s]/g, '').trim()) || 0;
                var grandTotal = Math.max(0, baseSubtotal + currentShipping - baseDiscount - (window.bilaiRewardDiscount || 0));

                $('#grandTotalAmount').text('৳ ' + grandTotal.toFixed(2));

                var thanaId = $('#checkout_thana').val();
                if (thanaId) {
                    $.get('{{ route("shipping.charge") }}', { id: thanaId });
                }
            }
        });

        // ==========================================
        // 3. INCOMPLETE ORDER LOGIC (MAIN REQUEST)
        // ==========================================

        function selectedLocationText($sel) {
            if (!$sel.length || !$sel.val()) return '';
            return ($sel.find('option:selected').text() || '').replace(/\s*\(৳[^)]*\)\s*/g, '').trim();
        }

        function buildCheckoutAddress() {
            var street = ($('input[name="address"]').val() || '').trim();
            var parts = [];
            if (requiresShipping) {
                var thana = selectedLocationText($('#checkout_thana'));
                var dist = selectedLocationText($('#checkout_district'));
                if (thana) parts.push(thana);
                if (dist) parts.push(dist);
            }
            if (street) parts.unshift(street);
            return parts.join(', ');
        }

        function buildCheckoutMeta(shippingCharge) {
            var meta = {
                subtotal: baseSubtotal,
                discount: baseDiscount,
                shipping_charge: shippingCharge,
                order_note: ($('#order_note').val() || '').trim()
            };
            if (requiresShipping) {
                // Incomplete-order meta: district_id is still sent; location_label carries
                // "Thana, District" for accepting an incomplete order later.
                meta.district_id = $('#checkout_district').val() || null;
                meta.thana_id    = $('#checkout_thana').val() || null;
                meta.post_code   = ($('#checkout_post_code').val() || '').trim();
                var loc = [];
                var thana = selectedLocationText($('#checkout_thana'));
                var dist = selectedLocationText($('#checkout_district'));
                if (thana) loc.push(thana);
                if (dist) loc.push(dist);
                meta.location_label = loc.join(', ');
            }
            return meta;
        }

        function saveIncompleteOrder() {
            if (isSubmitting) return;
            if (incompleteOrderTimer) clearTimeout(incompleteOrderTimer);

            incompleteOrderTimer = setTimeout(function() {
                var name = ($('input[name="name"]').val() || '').trim();
                var phone = ($('input[name="phone"]').val() || '').replace(/\D/g, '');
                var address = buildCheckoutAddress();

                if (!name || phone.length < 11) {
                    return;
                }

                if (!cartItems || !cartItems.length) {
                    return;
                }

                var isFreeDelivery = checkFreeDelivery();
                var shippingCharge = isFreeDelivery ? 0 : thanaChargeFromSelect();
                var total = (baseSubtotal + shippingCharge - baseDiscount).toFixed(2);
                var meta = buildCheckoutMeta(shippingCharge);

                $.ajax({
                    url: '{{ route("incomplete.order.store") }}',
                    type: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                        'Accept': 'application/json'
                    },
                    data: {
                        name: name,
                        phone: phone,
                        address: address,
                        items: cartItems,
                        checkout_meta: meta,
                        total_amount: total,
                        product_image: cartItems[0] && cartItems[0].image ? cartItems[0].image : '',
                        product_link: cartItems[0] && cartItems[0].link ? cartItems[0].link : ''
                    }
                });
            }, 2000);
        }

        // ফর্মের যেকোনো ইনপুট চেঞ্জ হলে এই ফাংশন কল হবে
        $('#checkout-form input, #checkout-form select, #checkout-form textarea').on('input change', function() {
             if($(this).attr('name') !== 'payment_method') {
                 saveIncompleteOrder();
             }
        });

        // ==========================================
        // 4. FORM SUBMISSION & VALIDATION
        // ==========================================

        $('#checkout-form').on('submit', function(e) {
            // পেমেন্ট মেথড চেক
            var paymentMethod = $('input[name="payment_method"]:checked').val();

            if (!paymentMethod) {
                e.preventDefault();
                toastr.error('অর্ডার সম্পন্ন করতে পেমেন্ট মেথড নির্বাচন করুন।', 'Error');
                $('#payment-error').show();
                $('html, body').animate({ scrollTop: $(".checkout-card .fa-wallet").offset().top - 150 }, 500);
                $('.btn-place-order').prop('disabled', false);
                return false;
            } else {
                $('#payment-error').hide();

                var pm = $('input[name="payment_method"]:checked').val() || '';
                if (pm.indexOf('manual_') === 0) {
                    var trx = $('input[name="manual_trx_id"]').val();
                    if (!trx || !String(trx).trim()) {
                        e.preventDefault();
                        toastr.error('ম্যানুয়াল পেমেন্টের জন্য ট্রানজেকশন আইডি লিখুন।', 'Error');
                        $('#manual-payment-fields').show();
                        $('html, body').animate({ scrollTop: $('#manual-payment-fields').offset().top - 120 }, 400);
                        $('.btn-place-order').prop('disabled', false);
                        return false;
                    }
                }

                // ৩. অর্ডার সাবমিট হচ্ছে, তাই ইনকমপ্লিট টাইমার বন্ধ করে দেওয়া হলো
                isSubmitting = true;
                if(incompleteOrderTimer) {
                    clearTimeout(incompleteOrderTimer);
                }

                // ফর্ম সাবমিট হতে দিন...
            }
        });

        // পেমেন্ট সিলেক্ট করলে এরর হাইড হবে
        function syncManualPaymentUi() {
            var v = $('input[name="payment_method"]:checked').val() || '';
            if (v.indexOf('manual_') === 0) {
                $('#manual-payment-fields').show();
                var inst = '';
                (window.MANUAL_GATEWAYS || []).forEach(function (g) {
                    if (g.code === v) {
                        inst = g.instructions || '';
                    }
                });
                $('#manual-instructions-body').html($('<div/>').text(inst).html().replace(/\n/g, '<br>'));
                $('#manual_trx_id').prop('required', true);
            } else {
                $('#manual-payment-fields').hide();
                $('#manual_trx_id').prop('required', false);
            }
        }

        $('input[name="payment_method"]').on('change', function() {
            $('#payment-error').hide();
            syncManualPaymentUi();
        });
        if (!$('input[name="payment_method"]:checked').length && $('input[name="payment_method"]').length) {
            $('input[name="payment_method"]:first').prop('checked', true);
        }
        syncManualPaymentUi();

        // চেকআউটে আগে থেকে তথ্য থাকলে একবার ইনকমপ্লিট সেভ ট্রিগার
        setTimeout(function() { saveIncompleteOrder(); }, 2500);
    });
</script>
{{-- GTM + Facebook + TikTok — checkout funnel --}}
<script type="text/javascript">
(function () {
    if (typeof window.EcomTracking === 'undefined') return;

    var items = @json($cartItemsForJs);
    var grandTotal = parseFloat("{{ $grand_total }}") || 0;
    var payableNow = grandTotal;
    var coupon = @json(Session::get('coupon_code', null));

    function checkoutUserFromForm() {
        return {
            name: ($('input[name="name"]').val() || '').trim(),
            phone: ($('input[name="phone"]').val() || '').trim(),
            address: ($('input[name="address"]').val() || '').trim(),
            city: ($('#checkout_district option:selected').text() || '').replace(/\s*\(৳[^)]*\)\s*/g, '').trim()
        };
    }

    if (items.length) {
        EcomTracking.initiateCheckout({
            items: items,
            value: payableNow,
            coupon: coupon
        });
    }

    var identifyTimer;
    $('#checkout-form input[name="name"], #checkout-form input[name="phone"]').on('input blur', function () {
        clearTimeout(identifyTimer);
        identifyTimer = setTimeout(function () {
            var u = checkoutUserFromForm();
            if (u.phone && String(u.phone).replace(/\D/g, '').length >= 11) {
                EcomTracking.identify(u);
            }
        }, 800);
    });

    @auth('customer')
    EcomTracking.identify(@json(\App\Support\EcommerceTrackingUser::fromCustomer(auth('customer')->user())));
    @endauth

    var form = document.getElementById('checkout-form');
    if (form) {
        form.addEventListener('submit', function () {
            var pm = form.querySelector('input[name="payment_method"]:checked');
            EcomTracking.identify(checkoutUserFromForm());
            EcomTracking.addPaymentInfo({
                items: items,
                value: payableNow,
                coupon: coupon,
                payment_method: pm ? pm.value : ''
            });
        });
    }
})();
</script>

@if($requires_shipping)
{{-- District -> Thana: shared helper (same path as Add/Edit Address). --}}
@include('frontEnd.layouts.customer.partials.district-thana-js')
@php
    $__prefillJs = [
        'district_id' => (string) $selDistrict,
        'thana_id'    => (string) $selThana,
        'subtotal'    => (float) ($subtotal ?? 0),
        'discount'    => (float) ($discount ?? 0),
        'free'        => (bool) ($hasAllFreeDelivery ?? false),
    ];
@endphp
<script>
$(function () {
    var PF = @json($__prefillJs);

    // District is rendered pre-selected server-side. initFields() makes both selects
    // searchable, loads the District's Thanas, and selects the saved Thana once AJAX
    // resolves. Changing District clears the Thana automatically.
    window.BilaiDistrictThana.initFields({
        district:     '#checkout_district',
        thana:        '#checkout_thana',
        selectedThana: PF.thana_id || null,
        fresh:        true
    });

    // Apply the selected Thana's authoritative delivery charge.
    $('#checkout_thana').one('thanas:loaded', function () {
        var charge = PF.free ? 0 : (parseFloat($('#checkout_thana option:selected').attr('data-charge')) || 0);
        $('#shippingAmount').text('৳ ' + charge.toFixed(2));
        $('#grandTotalAmount').text('৳ ' + Math.max(0, PF.subtotal + charge - PF.discount - (window.bilaiRewardDiscount || 0)).toFixed(2));
        $.get('{{ route("shipping.charge") }}', { id: PF.free ? 'free_delivery' : PF.thana_id });
    });
});
</script>
@endif

{{-- ═══════ Select Address modal behavior ═══════ --}}
@php
    $__addrLocJs = [
        'subtotal' => (float) ($subtotal ?? 0),
        'discount' => (float) ($discount ?? 0),
        'free'     => (bool) ($hasAllFreeDelivery ?? false),
    ];
@endphp
<script>
(function () {
    var modal   = document.getElementById('bilai-addr-modal');
    var openBtn = document.getElementById('bilai-addr-open');
    if (!modal || !openBtn) return;

    var LOC = @json($__addrLocJs);

    function openModal()  { modal.classList.add('open');  document.body.style.overflow = 'hidden'; }
    function closeModal() { modal.classList.remove('open'); document.body.style.overflow = ''; }

    openBtn.addEventListener('click', openModal);
    modal.querySelectorAll('.bilai-addr-close').forEach(function (b) { b.addEventListener('click', closeModal); });
    modal.addEventListener('click', function (e) { if (e.target === modal) closeModal(); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && modal.classList.contains('open')) closeModal(); });

    // Apply a saved address' District -> Thana. District options are already on the page,
    // so we select it and let the shared helper load its Thanas and charge.
    // helper load that District's Thanas and preselect the saved value after AJAX.
    function applyLocation(distId, thanaId) {
        var $dist = $('#checkout_district'), $thana = $('#checkout_thana');
        if (!$dist.length || !distId) return;

        var divisionId = String($dist.find('option[value="' + distId + '"]').data('division') || '');
        if (divisionId && $('#checkout_division').length) {
            $('#checkout_division').val(divisionId).trigger('change');
        }
        window.BilaiDistrictThana.setValue($dist, distId);
        window.BilaiDistrictThana.loadThanas($thana, distId, thanaId || null).done(function () {
            var charge = LOC.free ? 0 : (parseFloat($thana.find('option:selected').attr('data-charge')) || 0);
            $('#shippingAmount').text('৳ ' + charge.toFixed(2));
            $('#grandTotalAmount').text('৳ ' + Math.max(0, LOC.subtotal + charge - LOC.discount - (window.bilaiRewardDiscount || 0)).toFixed(2));
            $.get('{{ route("shipping.charge") }}', {id: LOC.free ? 'free_delivery' : thanaId});
        });
    }

    // Select an address: fill checkout fields, mark card selected, close.
    modal.querySelectorAll('.bilai-addr-select').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var d = this.dataset;
            $('input[name="name"]').val(d.name || '').trigger('change');
            $('input[name="phone"]').val(d.mobile || '').trigger('change');
            $('input[name="address"]').val(d.address || '').trigger('change');
            $('#checkout_post_code').val(d.postcode || '').trigger('change');
            if (d.dist) { applyLocation(d.dist, d.thana); }

            modal.querySelectorAll('.bilai-addr-select').forEach(function (b) {
                b.classList.remove('is-selected');
                b.querySelector('span').textContent = 'Select';
            });
            this.classList.add('is-selected');
            this.querySelector('span').textContent = 'Selected';
            closeModal();
        });
    });
}());
</script>
@endpush
