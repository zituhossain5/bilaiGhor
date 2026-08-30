@extends('frontEnd.layouts.master')
@section('title', $details->name)
@push('seo')
@php
    $metaTitle = $details->meta_title ?? $details->name;
    $metaDescription = $details->meta_description ?? Str::limit(strip_tags($details->description), 160);
    $metaKeywords = $details->meta_keywords ?? $details->name;
    $metaImage = $details->meta_image ? asset($details->meta_image) : asset(optional($details->image)->image);
@endphp
<meta name="app-url" content="{{ route('product', $details->slug) }}" />
<meta name="robots" content="index, follow" />
<meta name="title" content="{{ $metaTitle }}" />
<meta name="description" content="{{ $metaDescription }}" />
<meta name="keywords" content="{{ $metaKeywords }}" />
<meta name="twitter:card" content="summary_large_image" />
<meta name="twitter:title" content="{{ $metaTitle }}" />
<meta name="twitter:description" content="{{ $metaDescription }}" />
<meta name="twitter:image" content="{{ $metaImage }}" />
<meta property="og:title" content="{{ $metaTitle }}" />
<meta property="og:type" content="product" />
<meta property="og:url" content="{{ route('product', $details->slug) }}" />
<meta property="og:image" content="{{ $metaImage }}" />
<meta property="og:description" content="{{ $metaDescription }}" />
<meta property="og:site_name" content="BilaiGhor" />
@endpush

@push('css')
<link rel="stylesheet" href="{{ asset('public/frontEnd/css/zoomsl.css') }}">
<style>
/* BilaiGhor Product Details Start */
:root {
    --bilai-primary:   #F28C00;
    --bilai-brown:     #3A1F0F;
    --bilai-page-bg:   #FDFCF8;
    --bilai-cream:     #FFF8EC;
    --bilai-border:    #E8CDA5;
    --bilai-text:      #4F4F4F;
    --bilai-heading:   #2A1505;
    --bilai-muted:     #4F4F4F;
    --bilai-radius-md: 8px;
    --bilai-radius-lg: 14px;
}

html,
body.gotop {
    background: var(--bilai-page-bg);
    color: var(--bilai-text);
}

/* Breadcrumb */
.bpd-breadcrumb-wrap { background: var(--bilai-page-bg); padding: 10px 0; border-bottom: 1px solid var(--bilai-border); }
.bpd-breadcrumb { display: flex; align-items: center; flex-wrap: wrap; gap: 4px 8px; font-size: 13px; color: var(--bilai-muted); }
.bpd-breadcrumb a { color: var(--bilai-text); text-decoration: none; }
.bpd-breadcrumb a:hover { color: var(--bilai-primary); }
.bpd-breadcrumb .bpd-bc-sep { color: var(--bilai-border); }
.bpd-breadcrumb .bpd-bc-current { color: var(--bilai-primary); font-weight: 500; }

/* Product Section */
.bpd-product-section { background: var(--bilai-page-bg); padding: 28px 0 36px; }
.bpd-product-layout {
    display: grid;
    grid-template-columns: 90px 1fr 1.2fr;
    gap: 20px;
    align-items: start;
}
@media (max-width: 991px) {
    .bpd-product-layout { grid-template-columns: 72px 1fr; grid-template-rows: auto auto; }
    .bpd-info-col { grid-column: 1 / -1; }
}
@media (max-width: 576px) {
    .bpd-product-layout { grid-template-columns: 1fr; }
    .bpd-thumbs-col { display: flex; flex-direction: row; gap: 8px; order: 3; }
    .bpd-main-img-col { order: 2; }
    .bpd-info-col { order: 4; }
}

/* BilaiGhor Product Gallery Start */
.bpd-thumbs-col { display: flex; flex-direction: column; gap: 8px; max-height: 440px; overflow-y: auto; scrollbar-width: thin; }
.bpd-thumbs-col .indicator-item {
    width: 82px; height: 82px; flex-shrink: 0;
    border: 1.5px solid var(--bilai-border);
    border-radius: var(--bilai-radius-md);
    overflow: hidden; cursor: pointer;
    background: var(--bilai-cream);
    transition: border-color 0.15s;
    display: flex; align-items: center; justify-content: center;
}
.bpd-thumbs-col .indicator-item:hover,
.bpd-thumbs-col .indicator-item.bpd-thumb-active { border-color: var(--bilai-primary); }
.bpd-thumbs-col .indicator-item img { width: 100%; height: 100%; object-fit: contain; }

.bpd-main-img-col {
    position: relative;
    background: var(--bilai-cream);
    border: 1.5px solid var(--bilai-border);
    border-radius: var(--bilai-radius-lg);
    overflow: hidden;
    min-height: 340px;
    display: flex; align-items: center; justify-content: center;
}
.bpd-main-img-col .details_slider { width: 100%; }
.bpd-main-img-col .dimage_item { display: flex; align-items: center; justify-content: center; min-height: 320px; }
.bpd-main-img-col .block__pic { max-height: 380px; width: 100%; object-fit: contain; }
.bpd-main-discount-badge {
    position: absolute; top: 12px; right: 12px; z-index: 5;
    background: var(--bilai-primary); color: #fff;
    font-size: 12px; font-weight: 700;
    padding: 4px 12px; border-radius: 20px;
    pointer-events: none;
}
/* BilaiGhor Product Gallery End */

/* Product Info Column */
.bpd-info-col { }
.bpd-title { font-size: 21px; font-weight: 700; color: var(--bilai-heading); line-height: 1.4; margin: 0 0 10px; }

.bpd-meta-line {
    display: flex; flex-wrap: wrap; align-items: center;
    gap: 4px 10px; font-size: 13px; margin-bottom: 12px;
}
.bpd-meta-item { color: var(--bilai-muted); }
.bpd-meta-item a { color: var(--bilai-primary); text-decoration: none; font-weight: 600; }
.bpd-meta-sep { color: var(--bilai-border); }
.bpd-stars-sm { display: inline-flex; align-items: center; gap: 2px; }
.bpd-stars-sm i { font-size: 13px; color: #F8B400; }
.bpd-stars-sm .fa-star-empty-c { color: #ccc; }

.bpd-price-row {
    display: flex; align-items: center; flex-wrap: wrap;
    gap: 10px; margin-bottom: 14px;
}
.bpd-new-price { font-size: 28px; font-weight: 800; color: var(--bilai-primary); line-height: 1; }
.bpd-old-price { font-size: 16px; color: var(--bilai-muted); text-decoration: line-through; }
.bpd-disc-pill {
    background: #FFF0D6; color: var(--bilai-primary);
    font-size: 12px; font-weight: 700;
    padding: 3px 10px; border-radius: 20px;
}
.bpd-wishlist {
    margin-left: auto; display: flex; align-items: center; gap: 6px;
    color: var(--bilai-muted); font-size: 13px; text-decoration: none;
}
.bpd-wishlist:hover { color: var(--bilai-primary); text-decoration: none; }
.bpd-wishlist i { font-size: 16px; }

.bpd-weight-row { display: flex; align-items: center; gap: 10px; margin-bottom: 14px; font-size: 13px; color: var(--bilai-muted); }
.bpd-weight-label { font-weight: 600; color: var(--bilai-text); }
.bpd-weight-pill {
    background: var(--bilai-cream); border: 1.5px solid var(--bilai-border);
    border-radius: 20px; padding: 4px 16px;
    font-size: 13px; font-weight: 600; color: var(--bilai-text);
}

.bpd-qty-row { display: flex; align-items: center; gap: 14px; margin-bottom: 14px; }
.bpd-qty-label { font-size: 14px; font-weight: 600; color: var(--bilai-text); }
.bpd-qty-control { display: flex; align-items: center; border: 1.5px solid var(--bilai-border); border-radius: var(--bilai-radius-md); overflow: hidden; }
.bpd-qty-control .minus,
.bpd-qty-control .plus {
    width: 36px; height: 42px;
    display: flex; align-items: center; justify-content: center;
    background: var(--bilai-cream); color: var(--bilai-text);
    cursor: pointer; font-size: 20px; user-select: none;
    transition: background 0.12s;
}
.bpd-qty-control .minus:hover,
.bpd-qty-control .plus:hover { background: var(--bilai-border); }
.bpd-qty-input {
    width: 52px; height: 42px; border: none;
    border-left: 1.5px solid var(--bilai-border);
    border-right: 1.5px solid var(--bilai-border);
    text-align: center; font-size: 15px; font-weight: 600;
    color: var(--bilai-text); outline: none; background: #fff;
    -moz-appearance: textfield;
}
.bpd-qty-input::-webkit-inner-spin-button,
.bpd-qty-input::-webkit-outer-spin-button { -webkit-appearance: none; }

.bpd-actions { display: flex; gap: 10px; margin-bottom: 10px; }
.bpd-btn {
    height: 46px; border-radius: 10px; font-size: 14px; font-weight: 700;
    cursor: pointer; border: none; padding: 0 20px; font-family: inherit;
    transition: opacity 0.15s; display: inline-flex; align-items: center;
    justify-content: center; gap: 8px; text-decoration: none !important;
}
input.bpd-btn { cursor: pointer; }
.bpd-btn-buy  { background: var(--bilai-brown); color: #fff; flex: 1; }
.bpd-btn-cart {
    background: var(--bilai-cream); color: var(--bilai-brown);
    border: 1.5px solid var(--bilai-border) !important; flex: 1;
}
.bpd-btn-wa {
    display: flex; align-items: center; justify-content: center; gap: 8px;
    width: 100%; height: 46px; background: #25D366; color: #fff !important;
    border-radius: 10px; font-size: 14px; font-weight: 700;
    text-decoration: none !important; margin-bottom: 16px; font-family: inherit;
}
.bpd-btn:hover, .bpd-btn-wa:hover { opacity: 0.85; }

.bpd-info-cards { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 4px; }
.bpd-info-card {
    background: var(--bilai-cream); border: 1.5px solid var(--bilai-border);
    border-radius: 10px; padding: 12px 14px;
    display: flex; align-items: flex-start; gap: 10px;
}
.bpd-card-icon { color: var(--bilai-primary); font-size: 18px; margin-top: 2px; flex-shrink: 0; }
.bpd-card-label { font-size: 11px; font-weight: 700; color: var(--bilai-muted); text-transform: uppercase; letter-spacing: 0.4px; margin-bottom: 2px; }
.bpd-card-value { font-size: 13px; color: var(--bilai-text); font-weight: 500; line-height: 1.4; }

/* BilaiGhor Product Accordions Start */
.bpd-delivery-section { padding: 0 0 24px; background: var(--bilai-page-bg); }
.bpd-delivery-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; align-items: start; }
@media (max-width: 768px) { .bpd-delivery-grid { grid-template-columns: 1fr; } }
.bpd-accordion {
    background: var(--bilai-cream); border: 1.5px solid var(--bilai-border);
    border-radius: var(--bilai-radius-md); overflow: hidden;
}
.bpd-accordion-btn {
    width: 100%; display: flex; align-items: center; justify-content: space-between;
    padding: 14px 18px; background: transparent; border: none; cursor: pointer;
    font-size: 14px; font-weight: 600; color: var(--bilai-text); font-family: inherit;
}
.bpd-accordion-btn:hover { background: rgba(242,140,0,0.06); }
.bpd-accordion-ico { display: flex; align-items: center; gap: 10px; }
.bpd-accordion-ico i.bpd-acc-icon { color: var(--bilai-primary); font-size: 15px; }
.bpd-accordion-ico svg.bpd-acc-icon { color: var(--bilai-primary); flex-shrink: 0; }
.bpd-acc-chevron { font-size: 12px; color: var(--bilai-muted); transition: transform 0.2s; }
.bpd-acc-chevron.collapsed { transform: rotate(180deg); }
.bpd-accordion-body { padding: 6px 18px 18px; font-size: 13px; color: var(--bilai-text); line-height: 1.75; }
.bpd-accordion-body ul, .bpd-accordion-body ol { padding-left: 18px; margin: 6px 0; }
.bpd-accordion-body li { margin-bottom: 4px; }
.bpd-accordion-body strong { color: var(--bilai-brown); }
/* BilaiGhor Product Accordions End */

/* BilaiGhor Product Tabs Figma Start */
.bpd-tabs-section { padding: 24px 0 36px; background: var(--bilai-page-bg); }
.bpd-tab-nav {
    display: flex;
    border-bottom: 2px solid var(--bilai-border);
    margin-bottom: 32px;
}
.bilai-product-tab {
    flex: 1;
    padding: 14px 20px;
    border: none;
    border-bottom: 3px solid transparent;
    background: var(--bilai-page-bg);
    color: var(--bilai-muted);
    font-size: 15px;
    font-weight: 600;
    text-align: center;
    cursor: pointer;
    font-family: inherit;
    transition: color 0.15s, border-color 0.15s, background 0.15s;
    margin-bottom: -2px;
}
.bilai-product-tab:hover { color: var(--bilai-primary); }
.bilai-product-tab.active {
    color: var(--bilai-primary);
    border-bottom-color: var(--bilai-primary);
    background: var(--bilai-cream);
}
.bpd-tab-pane { display: none; }
.bpd-tab-pane.active { display: block; }
/* BilaiGhor Product Tabs Figma End */

.bpd-description-content { font-size: 14px; color: var(--bilai-text); line-height: 1.8; max-width: 820px; }
.bpd-description-content img { max-width: 100%; height: auto; border-radius: 8px; }
.bpd-description-content table { width: 100%; border-collapse: collapse; }
.bpd-description-content td, .bpd-description-content th { border: 1px solid var(--bilai-border); padding: 8px 12px; }
.bpd-video-wrap { margin-top: 28px; max-width: 640px; }
.bpd-video-wrap iframe, .bpd-video-wrap video { border-radius: 10px; }

/* BilaiGhor Product Reviews Start */
.bpd-reviews-layout { display: grid; grid-template-columns: 260px 1fr; gap: 28px; align-items: start; }
@media (max-width: 768px) { .bpd-reviews-layout { grid-template-columns: 1fr; } }

.bpd-review-summary {
    background: var(--bilai-cream); border: 1.5px solid var(--bilai-border);
    border-radius: var(--bilai-radius-lg); padding: 24px 20px; text-align: center; position: sticky; top: 20px;
}
.bpd-review-avg { font-size: 52px; font-weight: 800; color: var(--bilai-text); line-height: 1; }
.bpd-review-stars-lg { margin: 8px 0 4px; }
.bpd-review-stars-lg i { font-size: 20px; color: #F8B400; }
.bpd-review-count { font-size: 13px; color: var(--bilai-muted); margin-bottom: 18px; }

.bpd-rating-bars { text-align: left; margin-bottom: 20px; }
.bpd-bar-row { display: flex; align-items: center; gap: 7px; margin-bottom: 7px; font-size: 12px; }
.bpd-bar-lbl { width: 26px; color: var(--bilai-text); font-weight: 600; text-align: right; flex-shrink: 0; }
.bpd-bar-track { flex: 1; height: 7px; background: #E8CDA5; border-radius: 4px; overflow: hidden; }
.bpd-bar-fill { height: 100%; background: var(--bilai-primary); border-radius: 4px; }
.bpd-bar-pct { width: 30px; color: var(--bilai-muted); font-size: 11px; text-align: right; }

.bpd-write-btn {
    display: block; width: 100%; padding: 11px;
    background: var(--bilai-primary); color: #fff; border: none;
    border-radius: var(--bilai-radius-md); font-size: 14px; font-weight: 700;
    cursor: pointer; font-family: inherit;
}
.bpd-write-btn:hover { opacity: 0.85; }

.bpd-review-list { }
.bpd-rcard {
    background: #fff; border: 1px solid var(--bilai-border);
    border-radius: var(--bilai-radius-md); padding: 16px 18px; margin-bottom: 14px;
}
.bpd-rcard-hdr { display: flex; align-items: flex-start; gap: 12px; margin-bottom: 10px; }
.bpd-rcard-avatar {
    width: 44px; height: 44px; min-width: 44px; border-radius: 50%;
    background: var(--bilai-primary); color: #fff;
    display: flex; align-items: center; justify-content: center;
    font-size: 15px; font-weight: 700;
}
.bpd-rcard-meta { flex: 1; }
.bpd-rcard-name { font-size: 15px; font-weight: 600; color: var(--bilai-text); margin: 0; }
.bpd-rcard-date { font-size: 12px; color: var(--bilai-muted); }
.bpd-rcard-stars { margin-left: auto; }
.bpd-rcard-stars i { color: #F8B400; font-size: 14px; }
.bpd-rcard-body { font-size: 14px; color: var(--bilai-text); line-height: 1.6; }
.bpd-review-empty {
    background: var(--bilai-cream); border: 1px dashed var(--bilai-border);
    border-radius: 10px; padding: 32px; text-align: center; color: var(--bilai-muted);
}
.bpd-review-login { padding: 20px; text-align: center; }
.bpd-review-login a { color: var(--bilai-primary); text-decoration: none; font-weight: 600; }

.bpd-rform {
    background: var(--bilai-cream); border: 1.5px solid var(--bilai-border);
    border-radius: var(--bilai-radius-md); padding: 22px 20px; margin-top: 16px;
}
.bpd-rform-title { font-size: 17px; font-weight: 700; color: var(--bilai-text); margin: 0 0 16px; }
.bpd-rform-field { margin-bottom: 14px; }
.bpd-rform-lbl { display: block; font-size: 13px; font-weight: 600; color: var(--bilai-text); margin-bottom: 6px; }
.bpd-rform-textarea {
    display: block; width: 100%; border: 1.5px solid var(--bilai-border);
    border-radius: var(--bilai-radius-md); padding: 10px 14px;
    font-size: 14px; color: var(--bilai-text); resize: vertical;
    font-family: inherit; outline: none; background: #fff;
}
.bpd-rform-textarea:focus { border-color: var(--bilai-primary); }
.bpd-rform-actions { display: flex; gap: 10px; }
.bpd-rform-submit {
    flex: 1; height: 44px; background: var(--bilai-primary); color: #fff;
    border: none; border-radius: var(--bilai-radius-md);
    font-size: 14px; font-weight: 700; cursor: pointer; font-family: inherit;
}
.bpd-rform-cancel {
    padding: 0 22px; height: 44px; background: #fff; color: var(--bilai-muted);
    border: 1.5px solid var(--bilai-border); border-radius: var(--bilai-radius-md);
    font-size: 14px; cursor: pointer; font-family: inherit;
}
/* BilaiGhor Product Reviews End */

/* BilaiGhor Review Rating Fix Start */
.bilai-star-picker { display: flex; gap: 6px; margin-top: 4px; }
.bilai-rating-star { font-size: 30px; color: #ddd; background: none; border: none; cursor: pointer; padding: 0; line-height: 1; transition: color 0.1s; }
.bilai-rating-star.active { color: #F8B400; }
.bilai-rating-star.hover { color: #F8B400; }
/* BilaiGhor Review Rating Fix End */

/* BilaiGhor Review Photo Upload Start */
.bilai-photo-upload-box {
    border: 2px dashed var(--bilai-border); border-radius: var(--bilai-radius-md);
    background: #fff; padding: 28px 20px; text-align: center; cursor: pointer;
    transition: border-color 0.15s; position: relative;
}
.bilai-photo-upload-box:hover, .bilai-photo-upload-box.drag-over { border-color: var(--bilai-primary); }
.bilai-photo-upload-icon { font-size: 26px; color: var(--bilai-muted); margin-bottom: 8px; }
.bilai-photo-upload-text { font-size: 13px; color: var(--bilai-muted); }
.bilai-photo-upload-text span { color: var(--bilai-primary); font-weight: 600; cursor: pointer; }
.bilai-photo-upload-input { display: none; }
.bilai-photo-preview { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 10px; }
.bilai-photo-preview-item { position: relative; width: 72px; height: 72px; border-radius: 8px; overflow: hidden; border: 1.5px solid var(--bilai-border); }
.bilai-photo-preview-item img { width: 100%; height: 100%; object-fit: cover; }
.bpd-rcard-img-row { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 10px; }
.bpd-rcard-img { width: 72px; height: 72px; border-radius: 8px; object-fit: cover; border: 1px solid var(--bilai-border); cursor: pointer; }
/* BilaiGhor Review Photo Upload End */

/* BilaiGhor Review Scroll Fix Start */
/* (scroll fix is in JS — see bpdShowReviewForm) */
/* BilaiGhor Review Scroll Fix End */

/* BilaiGhor Related Products Figma Start */
.bpd-related-section { padding: 36px 0 52px; background: var(--bilai-cream); }
.bpd-related-hdr { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 24px; gap: 12px; }
.bpd-related-title { font-family: var(--bilai-font-title); font-size: 36px; font-weight: 400; color: var(--bilai-brown); margin: 0; line-height: 48px; }
.bpd-view-all {
    color: var(--bilai-text); font-size: 13px; font-weight: 500;
    text-decoration: none; border: 1.5px solid var(--bilai-border);
    padding: 7px 18px; border-radius: 20px; background: #fff;
    white-space: nowrap; flex-shrink: 0; margin-top: 4px; transition: all 0.15s;
}
.bpd-view-all:hover { border-color: var(--bilai-primary); color: var(--bilai-primary); }
@media (max-width: 991px) { .bpd-related-section .bilai-cat-grid-home { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 480px) { .bpd-related-section .bilai-cat-grid-home { grid-template-columns: 1fr; } }
/* BilaiGhor Related Products Figma End */

/* Wholesale tier styles preserved */
.wholesale-tier-row:hover { background: #f0f8f0 !important; }
.wholesale-tier-row.active-tier { background: #d4edda !important; border-left: 3px solid #28a745 !important; }

/* BilaiGhor Product Top Fix Start */

/* Fix 1: image column narrower than info column */
.bpd-product-layout { grid-template-columns: 90px minmax(0, 340px) 1fr; }

/* Fix 2: divider below meta row, above price */
.bpd-meta-line {
    border-bottom: 1px solid var(--bilai-border);
    padding-bottom: 14px;
    margin-bottom: 16px;
}

/* Fix 4: qty control — override the global .quantity absolute positioning */
.bpd-qty-control.quantity {
    position: static;
    display: flex;
    overflow: visible;
    height: auto;
    width: auto;
    border: 1.5px solid var(--bilai-border);
    border-radius: var(--bilai-radius-md);
    margin-top: 0;
}
.bpd-qty-control.quantity .minus,
.bpd-qty-control.quantity .plus {
    position: static !important;
    width: 36px; height: 42px; line-height: 42px;
    border: none !important;
    font-size: 20px; background: var(--bilai-cream);
    display: flex; align-items: center; justify-content: center;
    cursor: pointer; user-select: none; flex-shrink: 0;
}
.bpd-qty-control.quantity .minus:hover,
.bpd-qty-control.quantity .plus:hover { background: var(--bilai-border); }
.bpd-qty-control.quantity input[name="qty"] {
    position: static !important;
    pointer-events: auto;
    width: 52px; height: 42px;
    border: none !important;
    border-left: 1.5px solid var(--bilai-border) !important;
    border-right: 1.5px solid var(--bilai-border) !important;
    text-align: center; font-size: 15px; font-weight: 600;
    color: var(--bilai-text); outline: none; background: #fff;
    -moz-appearance: textfield; flex-shrink: 0;
}
.bpd-qty-control.quantity input[name="qty"]::-webkit-inner-spin-button,
.bpd-qty-control.quantity input[name="qty"]::-webkit-outer-spin-button { -webkit-appearance: none; }

/* Fix 5: three buttons in one row */
.bpd-btn-row {
    display: flex; gap: 10px; margin-bottom: 16px; flex-wrap: wrap;
}
.bpd-btn-row .bpd-btn,
.bpd-btn-row .bpd-btn-wa {
    flex: 1; min-width: 100px; margin-bottom: 0; width: auto;
}
.bpd-btn-row .bpd-btn-wa {
    display: inline-flex; height: 46px; border-radius: 10px;
    align-items: center; justify-content: center; gap: 6px;
}
@media (max-width: 576px) {
    .bpd-btn-row { flex-wrap: wrap; }
    .bpd-btn-row .bpd-btn,
    .bpd-btn-row .bpd-btn-wa { flex: 1 1 calc(50% - 5px); min-width: 130px; }
}

/* BilaiGhor Product Top Fix End */

/* BilaiGhor Product Details Mobile Fix Start */
@media (max-width: 480px) {
    html,
    body {
        overflow-x: hidden;
    }

    .bpd-breadcrumb-wrap .container,
    .bpd-product-section .container,
    .bpd-delivery-section .container,
    .bpd-tabs-section .container,
    .bpd-related-section .container {
        max-width: 100%;
        padding-left: 12px;
        padding-right: 12px;
    }

    .bpd-breadcrumb-wrap {
        padding: 10px 0 6px;
    }

    .bpd-breadcrumb {
        gap: 5px;
        font-size: 11px;
        line-height: 1.4;
        overflow-x: auto;
        white-space: nowrap;
        scrollbar-width: none;
    }

    .bpd-breadcrumb::-webkit-scrollbar {
        display: none;
    }

    .bpd-product-section {
        padding: 12px 0 20px;
    }

    .bpd-product-layout {
        display: flex;
        flex-direction: column;
        gap: 12px;
        grid-template-columns: none;
        width: 100%;
    }

    .bpd-main-img-col {
        order: 1;
        width: 100%;
        min-height: 0;
        aspect-ratio: 1 / 1;
        border-radius: 16px;
    }

    .bpd-main-img-col .details_slider,
    .bpd-main-img-col .owl-stage-outer,
    .bpd-main-img-col .owl-stage,
    .bpd-main-img-col .owl-item,
    .bpd-main-img-col .dimage_item {
        height: 100%;
        min-height: 0;
    }

    .bpd-main-img-col .dimage_item {
        aspect-ratio: 1 / 1;
        padding: 14px;
    }

    .bpd-main-img-col .block__pic {
        width: 100%;
        height: 100%;
        max-height: 100%;
        object-fit: contain;
    }

    .bpd-main-discount-badge {
        top: 10px;
        right: 10px;
        padding: 4px 10px;
        font-size: 11px;
        z-index: 4;
    }

    .bpd-thumbs-col {
        order: 2;
        flex-direction: row;
        width: 100%;
        max-height: none;
        overflow-x: auto;
        overflow-y: hidden;
        gap: 8px;
        padding-bottom: 2px;
        scrollbar-width: none;
    }

    .bpd-thumbs-col::-webkit-scrollbar {
        display: none;
    }

    #indicator_thumb_wrapper {
        display: flex;
        gap: 8px;
        min-width: 0;
    }

    .bpd-thumbs-col .indicator-item {
        width: 64px;
        height: 64px;
        flex: 0 0 64px;
        border-radius: 10px;
    }

    .bpd-info-col {
        order: 3;
        width: 100%;
        min-width: 0;
        grid-column: auto;
    }

    .bpd-title {
        margin-bottom: 10px;
        font-size: 20px;
        line-height: 1.35;
    }

    .bpd-meta-line {
        gap: 6px 8px;
        padding-bottom: 10px;
        margin-bottom: 12px;
        font-size: 12px;
        line-height: 1.5;
    }

    .bpd-meta-sep {
        display: none;
    }

    .bpd-stars-sm {
        white-space: nowrap;
    }

    .bpd-price-row {
        display: flex;
        flex-wrap: nowrap;
        align-items: center;
        gap: 8px;
        margin-bottom: 12px;
    }

    .bpd-new-price {
        font-size: 24px;
        line-height: 1.2;
    }

    .bpd-old-price {
        font-size: 14px;
    }

    .bpd-disc-pill {
        padding: 4px 9px;
        font-size: 11px;
        line-height: 1.2;
        white-space: nowrap;
        flex: 0 0 auto;
    }

    .bpd-wishlist {
        width: 38px;
        height: 38px;
        min-height: 38px;
        margin-left: auto;
        padding: 0;
        border: 0;
        border-radius: 50%;
        justify-content: center;
        gap: 0;
        flex: 0 0 38px;
    }

    .bpd-wishlist-label {
        display: none;
    }

    .bpd-wishlist-icon {
        width: 38px;
        height: 38px;
        padding: 9px;
    }

    .bpd-weight-row,
    .bpd-qty-row {
        flex-wrap: wrap;
        gap: 8px 10px;
        margin-bottom: 12px;
    }

    .bpd-qty-label {
        width: 100%;
    }

    .bpd-qty-control.quantity .minus,
    .bpd-qty-control.quantity .plus {
        width: 42px;
        height: 42px;
        line-height: 42px;
    }

    .bpd-qty-control.quantity input[name="qty"] {
        width: 56px;
        height: 42px;
    }

    .bpd-btn-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
        margin-bottom: 14px;
    }

    .bpd-btn-row .bpd-btn,
    .bpd-btn-row .bpd-btn-wa {
        width: 100%;
        min-width: 0;
        height: 44px;
        padding: 0 10px;
        font-size: 13px;
    }

    .bpd-btn-row .bpd-btn-buy {
        grid-column: 1 / -1;
    }

    .bpd-info-cards {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 8px;
    }

    .bpd-info-card {
        align-items: flex-start;
        gap: 8px;
        padding: 10px;
    }

    .bpd-card-icon {
        font-size: 18px;
    }

    .bpd-card-label {
        font-size: 10px;
    }

    .bpd-card-value {
        font-size: 12px;
        line-height: 1.35;
    }

    .bpd-delivery-section {
        padding-bottom: 18px;
    }

    .bpd-delivery-grid {
        gap: 10px;
    }

    .bpd-accordion-btn {
        padding: 12px;
        font-size: 13px;
    }

    .bpd-accordion-body {
        padding: 4px 12px 14px;
        font-size: 12.5px;
        line-height: 1.55;
        overflow-wrap: anywhere;
    }

    .bpd-tabs-section {
        padding: 18px 0 24px;
    }

    .bpd-tab-nav {
        gap: 8px;
        margin-bottom: 16px;
        border-bottom: 0;
        overflow-x: auto;
        scrollbar-width: none;
    }

    .bpd-tab-nav::-webkit-scrollbar {
        display: none;
    }

    .bilai-product-tab {
        flex: 0 0 auto;
        min-width: 112px;
        padding: 10px 12px;
        border: 1px solid var(--bilai-border);
        border-radius: 12px;
        margin-bottom: 0;
        font-size: 13px;
    }

    .bpd-description-content {
        max-width: 100%;
        font-size: 13px;
        line-height: 1.65;
        overflow-x: auto;
    }

    .bpd-description-content img {
        max-width: 100%;
        height: auto;
    }

    .bpd-description-content table {
        min-width: 480px;
    }

    .bpd-reviews-layout {
        gap: 14px;
    }

    .bpd-review-summary {
        position: static;
        padding: 18px 14px;
    }

    .bpd-review-avg {
        font-size: 40px;
    }

    .bpd-rcard,
    .bpd-rform {
        padding: 14px;
    }

    .bpd-rcard-hdr {
        flex-wrap: wrap;
    }

    .bpd-rcard-stars {
        width: 100%;
        margin-left: 0;
    }

    .bpd-rform-actions {
        flex-direction: column;
    }

    .bpd-related-section {
        padding: 24px 0 96px;
        overflow: hidden;
    }

    .bpd-related-hdr {
        align-items: center;
        margin-bottom: 16px;
    }

    .bpd-related-title {
        font-size: 22px;
    }

    .bpd-view-all {
        padding: 7px 14px;
        font-size: 12px;
    }

    .bpd-related-section .bilai-cat-grid-home {
        display: grid;
        grid-auto-flow: column;
        grid-auto-columns: 260px;
        grid-template-columns: none;
        gap: 14px;
        overflow-x: auto;
        overflow-y: hidden;
        padding-right: 12px;
        margin-right: -12px;
        scroll-snap-type: x proximity;
        scrollbar-width: none;
    }

    .bpd-related-section .bilai-cat-grid-home::-webkit-scrollbar {
        display: none;
    }

    .bpd-related-section .bilai-product-card {
        width: 260px;
        min-width: 0;
        height: auto;
        min-height: 0;
        scroll-snap-align: start;
    }

    .bpd-related-section .bilai-product-image {
        height: 170px;
        max-width: 100%;
    }

    .bpd-related-section .bilai-product-title {
        font-size: 14px;
        line-height: 20px;
    }

    .bpd-related-section .bilai-product-cat-rating {
        gap: 8px;
    }

    .bpd-related-section .bilai-product-category,
    .bpd-related-section .bilai-product-rating {
        white-space: nowrap;
    }

    .bpd-related-section .bilai-product-price {
        grid-template-columns: 1fr;
        gap: 6px;
    }

    .bpd-related-section .bilai-discount-badge {
        justify-self: start;
    }

    .bpd-related-section .bilai-product-actions {
        gap: 8px;
    }

    .bpd-related-section .bilai-cart-btn {
        flex: 0 0 48px;
        width: 48px;
        height: 40px;
    }

    .bpd-related-section .bilai-buy-btn {
        min-height: 40px;
        padding: 8px 10px;
        font-size: 14px;
    }
}

@media (max-width: 360px) {
    .bpd-info-cards {
        grid-template-columns: 1fr;
    }

    .bpd-related-section .bilai-cat-grid-home {
        grid-auto-columns: 236px;
    }

    .bpd-related-section .bilai-product-card {
        width: 236px;
    }
}
/* BilaiGhor Product Details Mobile Fix End */
/* BilaiGhor Product Details End */
</style>
<link rel="stylesheet" href="{{ asset('public/frontEnd/css/product-details-figma.css') }}?v=6">
@endpush

@section('content')
@php
    $reviewTotal  = $reviews->count();
    $reviewAvg    = $reviewTotal > 0 ? round($reviews->avg('ratting'), 1) : 0;
    $starCounts   = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
    foreach ($reviews as $r) { $k = max(1, min(5, (int)$r->ratting)); $starCounts[$k]++; }
    $discountPct  = ($details->old_price && $details->old_price > $details->new_price)
                    ? (int)round((($details->old_price - $details->new_price) / $details->old_price) * 100)
                    : 0;
    $videoType    = $details->pro_video_type ?? ($details->pro_video ? 'youtube' : null);
    $hasVideo     = ($videoType === 'youtube' && $details->pro_video) ||
                    ($videoType === 'upload' && !empty($details->pro_video_path));
    /* BilaiGhor Product Sold Fix Start */
    $soldCount    = $details->sold ?? 0;
    /* BilaiGhor Product Sold Fix End */
    $rewardPoints = \App\Services\RewardPointService::earnedPointsFor((float) $details->new_price);
    $formatProductPrice = function ($price) {
        return preg_replace('/\.00$/', '', number_format((float) $price, 2, '.', ''));
    };
@endphp

{{-- ─────────────────── BREADCRUMB ─────────────────── --}}
<div class="bpd-breadcrumb-wrap">
    <div class="container">
        <nav class="bpd-breadcrumb">
            <a href="{{ url('/') }}">Home</a>
            @if($details->category)
            <span class="bpd-bc-sep">›</span>
            <a href="{{ url('/category/' . $details->category->slug) }}">{{ $details->category->name }}</a>
            @endif
            @if($details->subcategory)
            <span class="bpd-bc-sep">›</span>
            <a href="{{ route('subcategory', $details->subcategory->slug) }}">{{ $details->subcategory->subcategoryName }}</a>
            @endif
            @if($details->childcategory)
            <span class="bpd-bc-sep">›</span>
            <span>{{ $details->childcategory->childcategoryName }}</span>
            @endif
            <span class="bpd-bc-sep">›</span>
            <span class="bpd-bc-current">{{ Str::limit($details->name, 50) }}</span>
        </nav>
    </div>
</div>

{{-- ─────────────────── PRODUCT TOP SECTION ─────────────────── --}}
<section class="bpd-product-section">
    <div class="container">
        <div class="bpd-product-layout">

            <div class="bpd-gallery">

            {{-- Col 1: Vertical Thumbnails --}}
            <div class="bpd-thumbs-col">
                <div id="indicator_thumb_wrapper">
                    @foreach ($details->images as $key => $image)
                    <div class="indicator-item{{ $key === 0 ? ' bpd-thumb-active' : '' }}"
                         data-id="{{ $key }}"
                         data-color-id="{{ $image->color_id ?? '' }}">
                        <img src="{{ asset($image->image) }}" alt="" />
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Col 2: Main Image --}}
            <div class="bpd-main-img-col">
                @if($discountPct > 0)
                <span class="bpd-main-discount-badge">{{ $discountPct }}% OFF</span>
                @endif
                <div class="details_slider owl-carousel" id="details_slider_main">
                    @foreach ($details->images as $value)
                    <div class="dimage_item" data-color-id="{{ $value->color_id ?? '' }}">
                        <img src="{{ asset($value->image) }}" class="block__pic" />
                    </div>
                    @endforeach
                </div>
            </div>

            </div>{{-- /bpd-gallery --}}

            {{-- Col 3: Product Info --}}
            <div class="bpd-info-col">

                <h1 class="bpd-title">{{ $details->name }}</h1>

                <div class="bpd-meta-line">
                    @if($details->brand)
                    <span class="bpd-meta-item">Brand:
                        <a href="{{ url('/brand/' . $details->brand->slug) }}">{{ $details->brand->name }}</a>
                    </span>
                    @endif
                    @if($details->category)
                    <span class="bpd-meta-sep">|</span>
                    <span class="bpd-meta-item">Category:
                        <a href="{{ url('/category/' . $details->category->slug) }}">{{ $details->category->name }}</a>
                    </span>
                    @endif
                    @if($reviewTotal > 0)
                    <span class="bpd-meta-sep">|</span>
                    <span class="bpd-meta-item">Reviews:
                        <span class="bpd-stars-sm">
                            @php $fs = floor($reviewAvg); $hs = ($reviewAvg - $fs) >= 0.5; @endphp
                            @for($i = 1; $i <= 5; $i++)
                                <img src="{{ asset($i <= $fs || ($hs && $i == $fs + 1) ? 'public/uploads/default/product-star.svg' : 'public/uploads/default/product-star-empty.svg') }}"
                                     alt="" aria-hidden="true">
                                @if($hs && $i == $fs + 1) @php $hs = false; @endphp @endif
                            @endfor
                            ({{ $reviewAvg }})
                        </span>
                    </span>
                    @endif
                </div>

                {{-- Price --}}
                <div class="bpd-price-row">
                    <span class="bpd-new-price" id="newPrice">৳{{ $formatProductPrice($details->new_price) }}</span>
                    @if($details->old_price && $details->old_price > $details->new_price)
                    <span class="bpd-old-price">৳{{ $formatProductPrice($details->old_price) }}</span>
                    <span class="bpd-disc-pill">{{ $discountPct }}% OFF</span>
                    @endif
                    <a href="#" class="bpd-wishlist" data-product-id="{{ $details->id }}" aria-pressed="false">
                        <span class="bpd-wishlist-icon">
                            <img src="{{ asset('public/uploads/default/product-wishlist.svg') }}" alt="" aria-hidden="true">
                        </span>
                        <span class="bpd-wishlist-label">Add to Wishlist</span>
                    </a>
                </div>

                {{-- Weight: from product_weights relation (weight_id) --}}
                @php $weightName = optional($details->weight)->name; @endphp
                @if($weightName)
                <div class="bpd-weight-row">
                    <span class="bpd-weight-label">Weight</span>
                    <span class="bpd-weight-pill">{{ $weightName }}</span>
                </div>
                @endif

                {{-- Main Form: variants + qty + buttons --}}
                <form action="{{ route('cart.store') }}" method="POST" name="formName">
                    @csrf
                    <input type="hidden" name="id" value="{{ $details->id }}" />

                    {{-- Variants: Color --}}
                    @if ($details->variantPrices->count() > 0)
                        @php
                            $productcolors = $details->variantPrices->pluck('color')->unique('id')->filter();
                            $productsizes  = $details->variantPrices->pluck('size')->unique('id')->filter();
                        @endphp
                        @if ($productcolors->count() > 0)
                        <div class="pro-color" style="width:100%;">
                            <div class="color_inner">
                                <p>Color -</p>
                                <div class="size-container">
                                    <div class="selector">
                                        @foreach ($productcolors as $procolor)
                                        <div class="selector-item">
                                            <input type="radio"
                                                id="fc-option{{ $procolor->id }}"
                                                value="{{ $procolor->id }}"
                                                name="product_color"
                                                class="selector-item_radio emptyalert"
                                                required />
                                            <label for="fc-option{{ $procolor->id }}"
                                                style="background-color:{{ $procolor->color ?? '#ccc' }}"
                                                class="selector-item_label">
                                                <span><img src="{{ asset('public/frontEnd/images/check-icon.svg') }}" alt="" /></span>
                                            </label>
                                        </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif
                        @if ($productsizes->count() > 0)
                        <div class="pro-size" style="width:100%;">
                            <div class="size_inner">
                                <p>Size & Variant - <span class="attibute-name"></span></p>
                                <div class="size-container">
                                    <div class="selector">
                                        @foreach ($productsizes as $prosize)
                                        <div class="selector-item">
                                            <input type="radio"
                                                id="f-option{{ $prosize->id }}"
                                                value="{{ $prosize->id }}"
                                                name="product_size"
                                                class="selector-item_radio emptyalert"
                                                required />
                                            <label for="f-option{{ $prosize->id }}" class="selector-item_label">
                                                {{ $prosize->sizeName ?? $prosize->name }}
                                            </label>
                                        </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif
                    @endif

                    {{-- Wholesale Pricing --}}
                    @if($details->is_wholesale && $details->wholesalePrices && $details->wholesalePrices->count() > 0)
                    <div class="wholesale-pricing-section" style="margin:14px 0;">
                        <h5 style="margin-bottom:12px;font-size:15px;font-weight:600;color:#333;">
                            <i class="fa fa-tag me-2"></i> Wholesale Pricing
                        </h5>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover mb-0" style="background:#fff;">
                                <thead style="background:#f8f9fa;">
                                    <tr>
                                        <th style="padding:10px;font-size:13px;">Quantity</th>
                                        <th style="padding:10px;font-size:13px;">Price</th>
                                        <th style="padding:10px;font-size:13px;">Stock</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($details->wholesalePrices->sortBy('min_quantity') as $tier)
                                    <tr class="wholesale-tier-row"
                                        data-min-qty="{{ $tier->min_quantity }}"
                                        data-max-qty="{{ $tier->max_quantity ?? 999999 }}"
                                        data-price="{{ $tier->wholesale_price }}"
                                        style="cursor:pointer;transition:background 0.2s;">
                                        <td style="padding:10px;font-size:13px;">{{ $tier->min_quantity }}{{ $tier->max_quantity ? ' - '.$tier->max_quantity : '+' }} pcs</td>
                                        <td style="padding:10px;font-size:13px;font-weight:600;color:#28a745;">৳{{ number_format($tier->wholesale_price, 2) }}</td>
                                        <td style="padding:10px;font-size:13px;color:{{ ($tier->stock ?? 0) > 0 ? '#28a745' : '#dc3545' }};">{{ $tier->stock ?? 0 }} pcs</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <p class="text-muted mt-2 mb-0" style="font-size:12px;">
                            <i class="fa fa-info-circle me-1"></i> Select quantity to apply wholesale price automatically.
                        </p>
                    </div>
                    @endif

                    {{-- Unit hidden --}}
                    @if($details->pro_unit)
                    <input type="hidden" name="pro_unit" value="{{ $details->pro_unit }}" />
                    @endif

                    {{-- Quantity --}}
                    <div class="bpd-qty-row">
                        <span class="bpd-qty-label">Quantity</span>
                        <div class="quantity bpd-qty-control">
                            <span class="minus" role="button" tabindex="0" aria-label="Decrease quantity">
                                <img src="{{ asset('public/uploads/default/product-minus.svg') }}" alt="" aria-hidden="true">
                            </span>
                            @php
                                $defaultQty = 1;
                                if ($details->is_wholesale && $details->wholesalePrices && $details->wholesalePrices->count() > 0) {
                                    $defaultQty = max(1, (int) $details->wholesalePrices->sortBy('min_quantity')->first()->min_quantity);
                                }
                            @endphp
                            <input type="number" name="qty"
                                   class="product-qty-input bpd-qty-input"
                                   value="{{ $defaultQty }}" min="1" step="1" />
                            <span class="plus" role="button" tabindex="0" aria-label="Increase quantity">
                                <img src="{{ asset('public/uploads/default/product-plus.svg') }}" alt="" aria-hidden="true">
                            </span>
                        </div>
                    </div>

                    {{-- Action Buttons: Buy Now + Add to Cart + WhatsApp in one row --}}
                    <div class="bpd-btn-row">
                        <button type="submit"
                                class="bpd-btn bpd-btn-buy order_now_btn order_now_btn_m"
                                onclick="return sendSuccess();"
                                name="order_now"
                                value="Buy Now">Buy Now</button>
                        <button type="submit"
                                class="bpd-btn bpd-btn-cart add_cart_btn cart_store"
                                data-id="{{ $details->id }}"
                                onclick="return sendSuccess();"
                                name="add_cart"
                                value="Add to Cart">
                            <img class="bpd-btn-icon" src="{{ asset('public/uploads/default/product-cart.svg') }}" alt="" aria-hidden="true">
                            Add to Cart
                        </button>
                        <a href="https://api.whatsapp.com/send?phone={{ optional($contact)->whatsapp }}&text={{ urlencode($details->name . ' - ' . Request::url()) }}"
                           target="_blank"
                           class="bpd-btn bpd-btn-wa">
                            <img class="bpd-btn-icon" src="{{ asset('public/uploads/default/product-whatsapp.svg') }}" alt="" aria-hidden="true">
                            Order on WhatsApp
                        </a>
                    </div>
                </form>

                {{-- Reward + Sold Cards --}}
                <div class="bpd-info-cards">
                    <div class="bpd-info-card">
                        <div class="bpd-info-card-heading">
                            <img class="bpd-card-icon" src="{{ asset('public/uploads/default/rewardIcon.svg') }}" alt="" aria-hidden="true">
                            <div class="bpd-card-label">Reward Point</div>
                        </div>
                        <div class="bpd-card-value">Earn <strong>{{ $rewardPoints }} Reward Points</strong> on this item</div>
                    </div>
                    <div class="bpd-info-card">
                        <div class="bpd-info-card-heading">
                            <img class="bpd-card-icon" src="{{ asset('public/uploads/default/soldIcon.svg') }}" alt="" aria-hidden="true">
                            <div class="bpd-card-label">Sold</div>
                        </div>
                        <div class="bpd-card-value"><strong>{{ $soldCount > 0 ? $soldCount . '+' : '0' }}</strong> sold in last 7 days</div>
                    </div>
                </div>

            </div>{{-- /bpd-info-col --}}
        </div>{{-- /bpd-product-layout --}}
    </div>
</section>

{{-- ─────────────────── DELIVERY ACCORDIONS ─────────────────── --}}
<section class="bpd-delivery-section">
    <div class="container">
        <div class="bpd-delivery-grid">

            {{-- Delivery Details --}}
            {{-- BilaiGhor Delivery Accordion Fix Start --}}
            <div class="bpd-accordion">
                <button class="bpd-accordion-btn" onclick="bpdAccordion(this)" type="button">
                    <span class="bpd-accordion-ico">
                        <span class="bpd-accordion-icon-box">
                            <img src="{{ asset('public/uploads/default/carIcon.svg') }}" alt="" aria-hidden="true">
                        </span>
                        Delivery Details
                    </span>
                    <img class="bpd-acc-chevron collapsed" src="{{ asset('public/uploads/default/product-chevron.svg') }}" alt="" aria-hidden="true">
                </button>
                <div class="bpd-accordion-body" style="display:none;">
                    <p><strong>Estimated Delivery Time</strong></p>
                    <ul>
                        <li>Inside Dhaka City: Up to 3 working days. (Mostly within 48 hours.)</li>
                        <li>Outside Dhaka City: Up to 3 working days.</li>
                    </ul>
                    <p style="margin-top:10px;"><strong>Note:</strong></p>
                    <ol>
                        <li>Order placed after 11 am will be processed the next business day. Delivery may be delayed due to issues with the delivery services.</li>
                        <li>Inside the following Dhaka's sub area there will be same day delivery if you order before 5 pm. The areas are: Postogola, Gandaria, Wari, Jurain, Donia, Soni Akhra, K.B Road, Sutrapur, Luxmibazar, Sadarghat, Gulistan.</li>
                    </ol>
                </div>
            </div>

            {{-- Delivery Charges --}}
            {{-- BilaiGhor Delivery Accordion Fix End --}}
            <div class="bpd-accordion">
                <button class="bpd-accordion-btn" onclick="bpdAccordion(this)" type="button">
                    <span class="bpd-accordion-ico">
                        <span class="bpd-accordion-icon-box">
                            <img src="{{ asset('public/uploads/default/delivery-charge.svg') }}" alt="" aria-hidden="true">
                        </span>
                        Delivery Charges
                    </span>
                    <img class="bpd-acc-chevron collapsed" src="{{ asset('public/uploads/default/product-chevron.svg') }}" alt="" aria-hidden="true">
                </button>
                <div class="bpd-accordion-body" style="display:none;">
                    <ul>
                        <li>Inside Dhaka Metro: BDT 70</li>
                        <li>Outside Dhaka Metro: BDT 100</li>
                        <li>Outside Dhaka: BDT 150</li>
                    </ul>
                    <p style="margin-top:10px;"><strong>Note:</strong></p>
                    <ul>
                        <li>In our specific selected area there will be BDT 00 delivery charge.</li>
                        <li>To know our specific selected area check this link or contact us.</li>
                    </ul>
                </div>
            </div>

        </div>
    </div>
</section>

{{-- ─────────────────── DESCRIPTION / REVIEWS TABS ─────────────────── --}}
<section class="bpd-tabs-section">
    <div class="container">

        <div class="bpd-tab-nav">
            <button class="bilai-product-tab active" onclick="bpdTab(this,'tab-description')" type="button">Descriptions</button>
            <button class="bilai-product-tab" onclick="bpdTab(this,'tab-reviews')" type="button">Reviews ({{ $reviewTotal }})</button>
        </div>

        {{-- Description Tab --}}
        <div class="bpd-tab-pane active" id="tab-description">
            <div class="bpd-description-content">
                {!! $details->description !!}
            </div>
            {{-- Video removed from frontend display; backend upload/fields remain intact --}}
        </div>

        {{-- Reviews Tab --}}
        <div class="bpd-tab-pane" id="tab-reviews">
            @php
                $rf = floor($reviewAvg); $rh = ($reviewAvg - $rf) >= 0.5;
                $re = 5 - $rf - ($rh ? 1 : 0);
            @endphp
            <div class="bpd-reviews-layout">

                {{-- Left: Summary --}}
                <div class="bpd-review-summary">
                    <div class="bpd-review-avg">{{ number_format($reviewAvg, 1) }}</div>
                    <div class="bpd-review-stars-lg">
                        @for($i = 0; $i < $rf; $i++)<i class="fas fa-star"></i>@endfor
                        @if($rh)<i class="fas fa-star-half-alt"></i>@endif
                        @for($i = 0; $i < $re; $i++)<i class="far fa-star" style="color:#ccc;"></i>@endfor
                    </div>
                    <div class="bpd-review-count">Based on {{ $reviewTotal }} reviews</div>
                    <div class="bpd-rating-bars">
                        @foreach([5, 4, 3, 2, 1] as $star)
                        @php $pct = $reviewTotal > 0 ? round($starCounts[$star] / $reviewTotal * 100) : 0; @endphp
                        <div class="bpd-bar-row">
                            <span class="bpd-bar-lbl">{{ $star }}★</span>
                            <div class="bpd-bar-track"><div class="bpd-bar-fill" style="width:{{ $pct }}%"></div></div>
                            <span class="bpd-bar-pct">{{ $pct }}%</span>
                        </div>
                        @endforeach
                    </div>
                    <button class="bpd-write-btn" id="bpd-open-form" onclick="bpdShowReviewForm()" type="button">
                        Write Your Review
                    </button>
                </div>

                {{-- Right: Review list + Form --}}
                <div class="bpd-review-list">
                    @if($reviews->count() > 0)
                        @foreach($reviews as $review)
                        <div class="bpd-rcard">
                            <div class="bpd-rcard-hdr">
                                <div class="bpd-rcard-avatar">{{ strtoupper(substr($review->name, 0, 2)) }}</div>
                                <div class="bpd-rcard-meta">
                                    <div class="bpd-rcard-name">{{ $review->name }}</div>
                                    <div class="bpd-rcard-date">{{ $review->created_at->format('d M Y') }}</div>
                                </div>
                                <div class="bpd-rcard-stars">
                                    @for($i = 1; $i <= 5; $i++)
                                        @if($i <= $review->ratting)<i class="fas fa-star"></i>
                                        @else<i class="far fa-star" style="color:#ccc;"></i>
                                        @endif
                                    @endfor
                                </div>
                            </div>
                            <div class="bpd-rcard-body">{{ $review->review }}</div>
                            @if($review->image)
                            {{-- BilaiGhor Review Photo Display Fix Start --}}
                            @php
                                $rImgSrc = Str::startsWith($review->image, 'public/')
                                    ? asset($review->image)
                                    : asset('public/review_images/' . $review->image);
                            @endphp
                            <div class="bpd-rcard-img-row">
                                <img src="{{ $rImgSrc }}" alt="Review photo" class="bpd-rcard-img"
                                     onerror="this.closest('.bpd-rcard-img-row').style.display='none'" />
                            </div>
                            {{-- BilaiGhor Review Photo Display Fix End --}}
                            @endif
                        </div>
                        @endforeach
                    @else
                    <div class="bpd-review-empty">
                        <i class="far fa-comment-dots" style="font-size:32px;margin-bottom:12px;display:block;"></i>
                        <p>No reviews yet. Be the first to write one!</p>
                    </div>
                    @endif

                    {{-- Inline Review Form --}}
                    <div id="bpd-review-form" style="display:none;">
                        @if(Auth::guard('customer')->user())
                        <form action="{{ route('customer.review') }}" method="POST" class="bpd-rform" enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" name="product_id" value="{{ $details->id }}" />
                            <h4 class="bpd-rform-title">Write Your Review</h4>
                            <div class="bpd-rform-field">
                                <label class="bpd-rform-lbl">Give Rating</label>
                                <div class="bilai-star-picker" id="bilaiStarPicker">
                                    @for($s = 1; $s <= 5; $s++)
                                    <button type="button" class="bilai-rating-star" data-val="{{ $s }}" aria-label="{{ $s }} star">★</button>
                                    @endfor
                                </div>
                                <input type="hidden" name="ratting" id="bilaiRatingVal" value="" />
                            </div>
                            <div class="bpd-rform-field">
                                <label class="bpd-rform-lbl" for="bpd-review-text">Your Review</label>
                                <textarea name="review" id="bpd-review-text"
                                          class="bpd-rform-textarea" rows="4"
                                          placeholder="What did you like or dislike?" required></textarea>
                            </div>
                            <div class="bpd-rform-field">
                                <label class="bpd-rform-lbl">Add Photos (Optional)</label>
                                <div class="bilai-photo-upload-box" id="bilaiUploadBox">
                                    <div class="bilai-photo-upload-icon"><i class="fa-solid fa-arrow-up-from-bracket"></i></div>
                                    <div class="bilai-photo-upload-text">Drag photo here or <span id="bilaiUploadBrowse">browse</span></div>
                                    <input type="file" name="image" id="bilaiPhotoInput" class="bilai-photo-upload-input" accept="image/jpg,image/jpeg,image/png,image/webp" />
                                </div>
                                <div class="bilai-photo-preview" id="bilaiPhotoPreview"></div>
                            </div>
                            <div class="bpd-rform-actions">
                                <button type="submit" class="bpd-rform-submit">Submit Your Review</button>
                                <button type="button" class="bpd-rform-cancel" onclick="bpdHideReviewForm()">Cancel</button>
                            </div>
                        </form>
                        @else
                        <div class="bpd-review-login bpd-rform">
                            <a href="{{ route('customer.login') }}">Login to write a review</a>
                        </div>
                        @endif
                    </div>

                </div>{{-- /bpd-review-list --}}
            </div>{{-- /bpd-reviews-layout --}}
        </div>{{-- /tab-reviews --}}

    </div>
</section>

{{-- ─────────────────── RELATED PRODUCTS ─────────────────── --}}
<section class="bpd-related-section">
    <div class="container">
        <div class="bpd-related-hdr">
            <h2 class="bpd-related-title">Related Products</h2>
            <a href="{{ url('/category/' . $details->category->slug) }}" class="bpd-view-all">View All Deals</a>
        </div>
        <div class="bilai-cat-grid-home">
            @foreach ($products as $value)
            @php
                $avgRating   = $value->reviews->avg('ratting');
                $filledStars = floor($avgRating);
                $hasHalf     = $avgRating - $filledStars >= 0.5;
                $emptyStars  = 5 - $filledStars - ($hasHalf ? 1 : 0);
                $discount    = ($value->old_price && $value->old_price > $value->new_price)
                               ? round((($value->old_price - $value->new_price) * 100) / $value->old_price)
                               : 0;
            @endphp
            <div class="bilai-product-card">
                <div class="bilai-product-top">
                    @if(!empty($value->product_badge))
                    <span class="bilai-card-badge">{{ $value->product_badge }}</span>
                    @else
                    <span></span>
                    @endif
                    <button class="bilai-wishlist-btn" type="button" aria-label="Add to wishlist">
                        <i class="far fa-heart"></i>
                    </button>
                </div>
                <div class="bilai-product-image">
                    <a href="{{ route('product', $value->slug) }}">
                        <img src="{{ asset($value->image ? $value->image->image : '') }}"
                             alt="{{ $value->name }}"
                             loading="{{ $loop->first ? 'eager' : 'lazy' }}" />
                    </a>
                    @if($value->sold && $value->sold > 0)
                    <span class="bilai-cat-sold-pill">{{ $value->sold }} Sold</span>
                    @endif
                </div>
                <div class="bilai-product-meta">
                    <h3 class="bilai-product-title">
                        <a href="{{ route('product', $value->slug) }}">{{ Str::limit($value->name, 55) }}</a>
                    </h3>
                    <div class="bilai-product-cat-rating">
                        @if($value->category)
                        <p class="bilai-product-category">{{ $value->category->name }}</p>
                        @endif
                        <div class="bilai-product-rating">
                            @for($i = 0; $i < $filledStars; $i++)<i class="fas fa-star"></i>@endfor
                            @if($hasHalf)<i class="fas fa-star-half-alt"></i>@endif
                            @for($i = 0; $i < $emptyStars; $i++)<i class="far fa-star"></i>@endfor
                        </div>
                    </div>
                    <div class="bilai-product-price">
                        <div class="bilai-price-row">
                            <span class="bilai-price-new">&#2547; {{ $value->new_price }}</span>
                            @if($value->old_price)
                            <del class="bilai-price-old">&#2547; {{ $value->old_price }}</del>
                            @endif
                        </div>
                        @if($discount > 0)
                        <span class="bilai-discount-badge">{{ $discount }}% OFF</span>
                        @endif
                    </div>
                </div>
                <div class="bilai-product-actions">
                    @if(!$value->prosizes->isEmpty() || !$value->procolors->isEmpty())
                        <a href="{{ route('product', $value->slug) }}" class="bilai-cart-btn">
                            <i class="fa-solid fa-cart-shopping"></i>
                        </a>
                        <a href="{{ route('product', $value->slug) }}" class="bilai-buy-btn">Buy Now</a>
                    @else
                        <form action="{{ route('cart.store') }}" method="POST">
                            @csrf
                            <input type="hidden" name="id" value="{{ $value->id }}" />
                            <input type="hidden" name="qty" value="1" />
                            <button type="submit" class="bilai-cart-btn cart_store" data-id="{{ $value->id }}">
                                <i class="fa-solid fa-cart-shopping"></i>
                            </button>
                        </form>
                        <form action="{{ route('cart.store') }}" method="POST">
                            @csrf
                            <input type="hidden" name="id" value="{{ $value->id }}" />
                            <input type="hidden" name="qty" value="1" />
                            <input type="hidden" name="order_now" value="1">
                            <button type="submit" class="bilai-buy-btn">Buy Now</button>
                        </form>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

@endsection

@push('script')
<script src="{{ asset('public/frontEnd/js/owl.carousel.min.js') }}"></script>
<script src="{{ asset('public/frontEnd/js/zoomsl.min.js') }}"></script>

<script>
    {{-- Variant price data + wholesale tiers --}}
    const variants = @json($details->variantPrices);

    @if($details->is_wholesale && $details->wholesalePrices && $details->wholesalePrices->count() > 0)
    var wholesaleTiers = [
        @foreach($details->wholesalePrices->sortBy('min_quantity') as $tier)
        { min_quantity: {{ $tier->min_quantity }}, max_quantity: {{ $tier->max_quantity ?? 999999 }}, price: {{ $tier->wholesale_price }} }@if(!$loop->last),@endif
        @endforeach
    ];
    var regularPrice = {{ $details->new_price }};

    function getWholesalePrice(qty) {
        var matched = null;
        for (var i = 0; i < wholesaleTiers.length; i++) {
            var t = wholesaleTiers[i];
            if (qty >= t.min_quantity && qty <= t.max_quantity) matched = t.price;
        }
        return matched;
    }
    function highlightWholesaleTier(qty) {
        $('.wholesale-tier-row').removeClass('active-tier');
        $('.wholesale-tier-row').each(function() {
            var min = parseInt($(this).data('min-qty'), 10);
            var max = parseInt($(this).data('max-qty'), 10);
            if (qty >= min && qty <= max) $(this).addClass('active-tier');
        });
    }
    @endif

    function getProductQty() {
        var $qty = $('form[name="formName"] input[name="qty"]');
        if (!$qty.length) $qty = $('.product-qty-input').first();
        return parseInt($qty.val(), 10) || 1;
    }

    function updateDisplayedPrice() {
        let color = $('form[name="formName"] input[name="product_color"]:checked').val() || null;
        let size  = $('form[name="formName"] input[name="product_size"]:checked').val() || null;
        let match = null;
        if (color && size) {
            match = variants.find(v => String(v.color_id ?? v.color) == String(color) && String(v.size_id ?? v.size) == String(size));
        }
        if (!match && color && !size) {
            match = variants.find(v => String(v.color_id ?? v.color) == String(color) && (v.size_id ?? v.size) == null);
        }
        if (!match && size && !color) {
            match = variants.find(v => String(v.size_id ?? v.size) == String(size) && (v.color_id ?? v.color) == null);
        }
        let basePrice = parseFloat({{ $details->new_price }});
        if (match && match.price != null) basePrice = parseFloat(match.price);
        @if($details->is_wholesale && $details->wholesalePrices && $details->wholesalePrices->count() > 0)
        let qty = getProductQty();
        let wp = getWholesalePrice(qty);
        if (wp !== null) basePrice = parseFloat(wp);
        highlightWholesaleTier(qty);
        @endif
        $('#newPrice').text('৳' + formatProductPrice(basePrice));
    }

    function formatProductPrice(price) {
        let numericPrice = parseFloat(price);
        if (Number.isNaN(numericPrice)) return price;
        return numericPrice.toFixed(2).replace(/\.00$/, '');
    }

    {{-- Product images for color filter --}}
    var productImages = @json($details->images->map(function($img) {
        return ['src' => asset($img->image), 'color_id' => $img->color_id];
    }));

    {{-- Update gallery + thumbnails by selected color (vertical thumb version) --}}
    function updateImagesByColor(colorId) {
        var colorIdStr = colorId ? String(colorId) : null;
        var filtered = [];
        if (colorIdStr) {
            var specific = productImages.filter(function(img) { return img.color_id && String(img.color_id) === colorIdStr; });
            var defaults = productImages.filter(function(img) { return !img.color_id; });
            filtered = specific.length > 0 ? specific : defaults;
        } else {
            filtered = productImages.filter(function(img) { return !img.color_id; });
            if (!filtered.length) filtered = productImages;
        }
        if (!filtered.length) filtered = productImages;

        // Rebuild main slider
        var $slider = $('.details_slider');
        var owl = $slider.data('owl.carousel');
        if (owl) owl.destroy();
        $slider.html(filtered.map(function(img) {
            return '<div class="dimage_item"><img src="' + img.src + '" class="block__pic" /></div>';
        }).join(''));
        $slider.owlCarousel({ margin: 0, items: 1, loop: filtered.length > 1, dots: false, autoplay: true, autoplayTimeout: 6000, autoplayHoverPause: true });

        // Rebuild vertical thumbnails
        var $tw = $('#indicator_thumb_wrapper');
        $tw.html(filtered.map(function(img, i) {
            return '<div class="indicator-item' + (i === 0 ? ' bpd-thumb-active' : '') + '" data-id="' + i + '"><img src="' + img.src + '" /></div>';
        }).join(''));
        $tw.find('.indicator-item').on('click', function() {
            var idx = parseInt($(this).data('id'), 10);
            $tw.find('.indicator-item').removeClass('bpd-thumb-active');
            $(this).addClass('bpd-thumb-active');
            $slider.trigger('to.owl.carousel', idx);
        });

        // Re-apply imagezoomsl
        setTimeout(function() {
            if ($('.block__pic').length && typeof $('.block__pic').imagezoomsl === 'function') {
                $('.block__pic').imagezoomsl({ zoomrange: [3, 3] });
            }
        }, 300);
    }

    $(document).ready(function () {
        // Init main image carousel
        $('.details_slider').owlCarousel({
            margin: 0, items: 1, loop: true, dots: false,
            autoplay: true, autoplayTimeout: 6000, autoplayHoverPause: true,
        });

        // Thumbnail click → change main image
        $(document).on('click', '#indicator_thumb_wrapper .indicator-item', function () {
            var idx = parseInt($(this).data('id'), 10);
            $('#indicator_thumb_wrapper .indicator-item').removeClass('bpd-thumb-active');
            $(this).addClass('bpd-thumb-active');
            $('.details_slider').trigger('to.owl.carousel', idx);
        });

        // Color change → filter images
        $(document).on('change', 'input[name="product_color"]', function () {
            updateImagesByColor($(this).val() || null);
        });

        // Price update on variant/qty change
        updateDisplayedPrice();
        $(document).on('change', 'form[name="formName"] input[name="product_color"], form[name="formName"] input[name="product_size"], form[name="formName"] input[name="qty"]', updateDisplayedPrice);
        $('form[name="formName"] input[name="qty"]').on('keyup input', updateDisplayedPrice);

        // Qty +/- buttons
        $('form[name="formName"] .minus').on('click', function () {
            var $inp = $(this).closest('.quantity').find('input[name="qty"]');
            var v = parseInt($inp.val(), 10) - 1;
            $inp.val(v < 1 ? 1 : v).trigger('change');
            return false;
        });
        $('form[name="formName"] .plus').on('click', function () {
            var $inp = $(this).closest('.quantity').find('input[name="qty"]');
            $inp.val(parseInt($inp.val(), 10) + 1).trigger('change');
            return false;
        });

        @if($details->is_wholesale && $details->wholesalePrices && $details->wholesalePrices->count() > 0)
        $('.wholesale-tier-row').on('click', function () {
            var min = parseInt($(this).data('min-qty'), 10);
            $('form[name="formName"] input[name="qty"]').val(min).trigger('change');
        });
        @endif

        // Related products now use CSS grid — no carousel init needed
    });

    // sendSuccess: validate required variant selections
    function sendSuccess() {
        var form = document.forms['formName'];
        if (!form) return true;
        if (form.querySelector('input[name="product_size"]') && !form.querySelector('input[name="product_size"]:checked')) {
            if (typeof toastr !== 'undefined') toastr.warning('Please select a size.');
            return false;
        }
        if (form.querySelector('input[name="product_color"]') && !form.querySelector('input[name="product_color"]:checked')) {
            if (typeof toastr !== 'undefined') toastr.error('Please select a color.');
            return false;
        }
        var qtyInput = form.querySelector('input[name="qty"]');
        if (qtyInput) { var q = parseInt(qtyInput.value, 10); qtyInput.value = (isNaN(q) || q < 1) ? 1 : q; }
        return true;
    }

    // Accordion toggle
    function bpdAccordion(btn) {
        var body    = btn.nextElementSibling;
        var chevron = btn.querySelector('.bpd-acc-chevron');
        var open    = body.style.display !== 'none';
        body.style.display = open ? 'none' : 'block';
        if (open) chevron.classList.add('collapsed'); else chevron.classList.remove('collapsed');
    }

    // Tab switching
    function bpdTab(btn, id) {
        document.querySelectorAll('.bilai-product-tab').forEach(function(b) { b.classList.remove('active'); });
        document.querySelectorAll('.bpd-tab-pane').forEach(function(p) { p.classList.remove('active'); });
        btn.classList.add('active');
        document.getElementById(id).classList.add('active');
    }

    // Review form toggle — scroll fix accounts for sticky header height
    function bpdShowReviewForm() {
        var target = document.getElementById('bpd-review-form');
        target.style.display = 'block';
        document.getElementById('bpd-open-form').style.display = 'none';
        setTimeout(function () {
            var headerEl = document.getElementById('navbar_top');
            var headerOffset = (headerEl ? headerEl.offsetHeight : 70) + 20;
            var top = target.getBoundingClientRect().top + window.pageYOffset - headerOffset;
            window.scrollTo({ top: top, behavior: 'smooth' });
            var firstInput = target.querySelector('textarea, input:not([type="hidden"]), a');
            if (firstInput) firstInput.focus({ preventScroll: true });
        }, 50);
    }
    function bpdHideReviewForm() {
        document.getElementById('bpd-review-form').style.display = 'none';
        document.getElementById('bpd-open-form').style.display   = 'block';
    }

    // Star rating picker
    (function () {
        var picker   = document.getElementById('bilaiStarPicker');
        var valInput = document.getElementById('bilaiRatingVal');
        if (!picker) return;
        var stars = picker.querySelectorAll('.bilai-rating-star');

        function setActive(val) {
            stars.forEach(function (s) {
                s.classList.toggle('active', parseInt(s.dataset.val) <= val);
            });
            valInput.value = val;
        }

        stars.forEach(function (star) {
            star.addEventListener('click', function () { setActive(parseInt(this.dataset.val)); });
            star.addEventListener('mouseenter', function () {
                var v = parseInt(this.dataset.val);
                stars.forEach(function (s) { s.classList.toggle('hover', parseInt(s.dataset.val) <= v); });
            });
        });
        picker.addEventListener('mouseleave', function () {
            stars.forEach(function (s) { s.classList.remove('hover'); });
        });
    })();

    // Photo upload preview
    (function () {
        var box     = document.getElementById('bilaiUploadBox');
        var input   = document.getElementById('bilaiPhotoInput');
        var preview = document.getElementById('bilaiPhotoPreview');
        if (!box || !input) return;

        box.addEventListener('click', function () { input.click(); });
        document.getElementById('bilaiUploadBrowse').addEventListener('click', function (e) {
            e.stopPropagation();
            input.click();
        });

        box.addEventListener('dragover', function (e) { e.preventDefault(); box.classList.add('drag-over'); });
        box.addEventListener('dragleave', function () { box.classList.remove('drag-over'); });
        box.addEventListener('drop', function (e) {
            e.preventDefault();
            box.classList.remove('drag-over');
            if (e.dataTransfer.files.length) {
                // Assign first dropped file to input
                var dt = new DataTransfer();
                dt.items.add(e.dataTransfer.files[0]);
                input.files = dt.files;
                showPreview(e.dataTransfer.files[0]);
            }
        });

        input.addEventListener('change', function () {
            if (this.files.length) showPreview(this.files[0]);
        });

        function showPreview(file) {
            preview.innerHTML = '';
            var reader = new FileReader();
            reader.onload = function (e) {
                var item = document.createElement('div');
                item.className = 'bilai-photo-preview-item';
                var img = document.createElement('img');
                img.src = e.target.result;
                item.appendChild(img);
                preview.appendChild(item);
            };
            reader.readAsDataURL(file);
        }
    })();
</script>

{{-- imagezoomsl init --}}
<script>
    $(document).ready(function () {
        if ($('.block__pic').length && typeof $('.block__pic').imagezoomsl === 'function') {
            $('.block__pic').imagezoomsl({ zoomrange: [3, 3] });
        }
    });
</script>

{{-- ─── GA4 / Facebook Pixel tracking (unchanged) ─── --}}
<script type="text/javascript">
    window.dataLayer = window.dataLayer || [];
    dataLayer.push({ ecommerce: null });
    dataLayer.push({
        event: "view_item",
        ecommerce: {
            items: [{
                item_name: "{{ $details->name }}",
                item_id: "{{ $details->id }}",
                price: "{{ $details->new_price }}",
                item_brand: "{{ $details->brand ? $details->brand->name : '' }}",
                item_category: "{{ $details->category->name }}",
                item_variant: "{{ $details->pro_unit }}",
                currency: "BDT",
                quantity: {{ $details->stock ?? 0 }}
            }],
            impression: [
                @foreach ($products as $value)
                {
                    item_name: "{{ $value->name }}",
                    item_id: "{{ $value->id }}",
                    price: "{{ $value->new_price }}",
                    item_brand: "{{ $details->brand ? $details->brand->name : '' }}",
                    item_category: "{{ $value->category ? $value->category->name : '' }}",
                    item_variant: "{{ $value->pro_unit }}",
                    currency: "BDT",
                    quantity: {{ $value->stock ?? 0 }}
                },
                @endforeach
            ]
        }
    });
</script>
<script type="text/javascript">
    (function () {
        var productItem = {
            item_id: "{{ $details->id }}",
            item_name: @json($details->name),
            price: {{ (float) $details->new_price }},
            item_brand: @json(optional($details->brand)->name),
            item_category: @json(optional($details->category)->name),
            item_variant: @json($details->pro_unit),
            currency: "BDT",
            quantity: {{ $details->stock ?? 0 }}
        };

        var relatedItems = [
            @foreach ($products as $value)
            {
                item_id: "{{ $value->id }}",
                item_name: @json($value->name),
                price: {{ (float) $value->new_price }},
                item_brand: @json(optional($value->brand)->name),
                item_category: @json(optional($value->category)->name),
                item_variant: @json($value->pro_unit),
                currency: "BDT",
                quantity: {{ $value->stock ?? 0 }}
            }@if(!$loop->last),@endif
            @endforeach
        ];

        if (relatedItems.length) {
            window.dataLayer.push({ event: "view_item_list", ecommerce: { item_list_name: "Related Products", currency: "BDT", items: relatedItems } });
        }

        if (typeof window.EcomTracking !== "undefined") {
            EcomTracking.viewContent({ items: [{ id: productItem.item_id, name: productItem.item_name, price: productItem.price, qty: 1 }], value: productItem.price });
        } else {
            if (typeof fbq === "function") {
                fbq("track", "ViewContent", { content_ids: [productItem.item_id], content_name: productItem.item_name, content_category: productItem.item_category, value: productItem.price, currency: "BDT" });
            }
            if (typeof ttq !== "undefined") {
                ttq.track("ViewContent", { content_type: "product", content_id: String(productItem.item_id), value: productItem.price, currency: "BDT" });
            }
        }

        function buildCurrentItem() {
            var qtyInput = document.querySelector("input[name='qty']");
            var qty = parseInt(qtyInput ? qtyInput.value : "1", 10);
            if (isNaN(qty) || qty < 1) qty = 1;
            return { item_id: productItem.item_id, item_name: productItem.item_name, price: productItem.price, item_brand: productItem.item_brand, item_category: productItem.item_category, item_variant: productItem.item_variant, currency: "BDT", quantity: qty };
        }

        $(document).on("click", ".add_cart_btn", function () {
            var item = buildCurrentItem(); var value = item.price * item.quantity;
            if (typeof window.EcomTracking !== "undefined") {
                EcomTracking.addToCart({ items: [{ id: item.item_id, name: item.item_name, price: item.price, qty: item.quantity }], value: value });
            } else {
                window.dataLayer.push({ ecommerce: null });
                window.dataLayer.push({ event: "add_to_cart", ecommerce: { currency: "BDT", value: value, items: [item] } });
                if (typeof fbq === "function") {
                    fbq("track", "AddToCart", { content_ids: [item.item_id], content_name: item.item_name, value: value, currency: "BDT", contents: [{ id: item.item_id, quantity: item.quantity }] });
                    if (typeof ttq !== "undefined") ttq.track("AddToCart", { content_type: "product", value: value, currency: "BDT", contents: [{ content_id: String(item.item_id), content_name: item.item_name, quantity: item.quantity, price: item.price }] });
                }
            }
        });

        $(document).on("click", ".order_now_btn", function () {
            var item = buildCurrentItem(); var value = item.price * item.quantity;
            window.dataLayer.push({ ecommerce: null });
            window.dataLayer.push({ event: "add_to_cart", ecommerce: { currency: "BDT", value: value, items: [item] } });
            window.dataLayer.push({ event: "begin_checkout", ecommerce: { currency: "BDT", value: value, items: [item] } });
            if (typeof fbq === "function") {
                fbq("track", "AddToCart", { content_ids: [item.item_id], content_name: item.item_name, value: value, currency: "BDT", contents: [{ id: item.item_id, quantity: item.quantity }] });
                fbq("track", "InitiateCheckout", { value: value, currency: "BDT", num_items: item.quantity });
            }
        });
    })();
</script>
@endpush
