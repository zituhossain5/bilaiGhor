@php
use Illuminate\Support\Facades\Auth;
use App\Models\Order;

$customer            = Auth::guard('customer')->user();
$customerId          = $customer->id;
$pendingOrdersCount  = Order::where('customer_id', $customerId)->whereNotIn('order_status', ['6', '11'])->count();
$totalOrderAmount    = Order::where('customer_id', $customerId)->sum('amount');
$profileImage        = $customer->image ? asset($customer->image) : null;
$customerInitial     = strtoupper(substr($customer->name ?? 'U', 0, 1));
$firstName           = ucfirst(explode(' ', trim($customer->name ?? 'there'))[0]);

// Reward backend not implemented yet — safe zero placeholders (replace with real values later).
$rewardPoints = 0;
$cashValue    = 0;
@endphp

@extends('frontEnd.layouts.master')
@section('title', 'Reward Points | ' . ($customer->name ?? 'Account'))

@push('css')
<style>
/* BilaiGhor Reward Points Page Start */
:root {
    --bilai-rw-primary:      var(--bilai-primary, #F28C00);
    --bilai-rw-primary-dark: var(--bilai-primary-dark, #c96f00);
    --bilai-rw-brown:        #2B1A10;
    --bilai-rw-hero:         #241307;
    --bilai-rw-cream:        var(--bilai-cream, #FFF8EC);
    --bilai-rw-card:         #FFFDF8;
    --bilai-rw-border:       var(--bilai-border, #E8CDA5);
    --bilai-rw-text:         var(--bilai-text, #2B1A10);
    --bilai-rw-muted:        var(--bilai-muted, #77706A);
    --bilai-rw-radius:       14px;
    --bilai-rw-radius-sm:    8px;
}

.bilai-rw-page { background: #f5f5f0; min-height: 72vh; padding: 20px 0 52px; }

/* Breadcrumb */
.bilai-rw-bc { display: flex; align-items: center; gap: 5px; font-size: 12.5px; margin-bottom: 18px; flex-wrap: wrap; }
.bilai-rw-bc a { color: var(--bilai-rw-muted); text-decoration: none; }
.bilai-rw-bc a:hover { color: var(--bilai-rw-primary); }
.bilai-rw-bc-sep    { color: #c0b0a0; font-size: 11px; }
.bilai-rw-bc-active { color: var(--bilai-rw-primary); font-weight: 600; }

/* Two-column layout */
.bilai-rw-layout { display: grid; grid-template-columns: 248px 1fr; gap: 20px; align-items: start; }
.bilai-rw-content { display: flex; flex-direction: column; gap: 18px; }

/* ── Shared sidebar (same look as dashboard/orders) ── */
.bilai-rw-sidebar { position: sticky; top: 90px; display: flex; flex-direction: column; gap: 14px; }
.bilai-card { background: #fff; border: 1px solid var(--bilai-rw-border); border-radius: var(--bilai-rw-radius); overflow: hidden; }
.bilai-dash-profile-box { padding: 18px 16px 16px; }
.bilai-dash-profile-row { display: flex; align-items: center; gap: 12px; margin-bottom: 14px; }
.bilai-dash-avatar { width: 56px; height: 56px; border-radius: 50%; object-fit: cover; border: 2px solid var(--bilai-rw-border); flex-shrink: 0; }
.bilai-dash-avatar-placeholder { width: 56px; height: 56px; border-radius: 50%; background: var(--bilai-rw-primary); color: #fff; font-size: 22px; font-weight: 700; display: flex; align-items: center; justify-content: center; flex-shrink: 0; text-transform: uppercase; }
.bilai-dash-profile-info { flex: 1; min-width: 0; }
.bilai-dash-profile-name { font-size: 14px; font-weight: 700; color: var(--bilai-rw-text); margin: 0 0 2px; line-height: 1.3; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; text-transform: capitalize; }
.bilai-dash-profile-sub { font-size: 12px; color: var(--bilai-rw-muted); margin: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.bilai-dash-profile-divider { border: none; border-top: 1px solid var(--bilai-rw-border); margin: 0 0 12px; }
.bilai-dash-rp-row { display: flex; align-items: center; justify-content: center; gap: 10px; }
.bilai-dash-rp-item { display: flex; align-items: center; gap: 5px; font-size: 12px; font-weight: 600; color: var(--bilai-rw-text); }
.bilai-dash-rp-icon { color: var(--bilai-rw-primary); font-size: 13px; }
.bilai-dash-rp-sep-icon { width: 24px; height: 24px; border-radius: 50%; background: var(--bilai-rw-cream); border: 1px solid var(--bilai-rw-border); display: flex; align-items: center; justify-content: center; color: var(--bilai-rw-muted); font-size: 10px; flex-shrink: 0; }
.bilai-dash-nav { padding: 6px 0 8px; }
.bilai-dash-nav-title { font-size: 11px; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; color: var(--bilai-rw-muted); padding: 12px 16px 8px; margin: 0; }
.bilai-dash-nav-item { display: flex; align-items: center; gap: 10px; padding: 9px 16px; color: var(--bilai-rw-text); font-size: 13.5px; font-weight: 500; text-decoration: none; border-left: 3px solid transparent; transition: background 0.12s, color 0.12s; line-height: 1.3; }
.bilai-dash-nav-item:hover { background: var(--bilai-rw-cream); color: var(--bilai-rw-primary); text-decoration: none; }
.bilai-dash-nav-item.active { background: var(--bilai-rw-cream); color: var(--bilai-rw-primary); border-left-color: var(--bilai-rw-primary); font-weight: 600; }
.bilai-dash-nav-icon { width: 16px; text-align: center; flex-shrink: 0; font-size: 13px; opacity: 0.7; }
.bilai-dash-nav-item.active .bilai-dash-nav-icon, .bilai-dash-nav-item:hover .bilai-dash-nav-icon { opacity: 1; }
.bilai-dash-nav-badge { margin-left: auto; background: #e53935; color: #fff; font-size: 10px; font-weight: 700; padding: 1px 6px; border-radius: 100px; line-height: 1.5; }
.bilai-dash-nav-sep { border: none; border-top: 1px solid var(--bilai-rw-border); margin: 4px 0; }
.bilai-dash-nav-item--logout { color: #c0392b; }
.bilai-dash-nav-item--logout .bilai-dash-nav-icon { opacity: 0.8; }
.bilai-dash-nav-item--logout:hover { background: #fff5f5; color: #a93226; }

/* ── Hero card ── */
.bilai-rw-hero { background: var(--bilai-rw-hero); border-radius: var(--bilai-rw-radius); overflow: hidden; }
.bilai-rw-hero-top { display: flex; align-items: center; justify-content: space-between; gap: 20px; padding: 26px 28px; }
.bilai-rw-hero-hello { font-size: 13px; color: #d8c6b2; margin: 0 0 6px; }
.bilai-rw-hero-title { font-size: 22px; font-weight: 800; color: #fff; margin: 0 0 8px; }
.bilai-rw-hero-sub { font-size: 13px; color: #c9b6a1; margin: 0; line-height: 1.5; }
.bilai-rw-hero-points {
    background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.12);
    border-radius: 12px; padding: 16px 26px; text-align: center; flex-shrink: 0;
}
.bilai-rw-hero-points .lbl { font-size: 12px; color: #d8c6b2; margin: 0 0 4px; }
.bilai-rw-hero-points .val { font-size: 34px; font-weight: 800; color: #fff; margin: 0 0 2px; line-height: 1.1; }
.bilai-rw-hero-points .sub { font-size: 11.5px; color: #c9b6a1; margin: 0; }

/* Hero bottom summary strip */
.bilai-rw-hero-strip { display: grid; grid-template-columns: repeat(3, 1fr); background: var(--bilai-rw-card); }
.bilai-rw-strip-col { padding: 18px 24px; border-right: 1px solid var(--bilai-rw-border); }
.bilai-rw-strip-col:last-child { border-right: none; }
.bilai-rw-strip-label { display: flex; align-items: center; gap: 6px; font-size: 11px; font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase; color: var(--bilai-rw-muted); margin: 0 0 6px; }
.bilai-rw-strip-dot { width: 7px; height: 7px; border-radius: 50%; background: var(--bilai-rw-brown); flex-shrink: 0; }
.bilai-rw-strip-value { font-size: 19px; font-weight: 800; color: var(--bilai-rw-text); margin: 0 0 3px; }
.bilai-rw-strip-value.orange { color: var(--bilai-rw-primary); }
.bilai-rw-strip-sub { font-size: 12px; color: var(--bilai-rw-muted); margin: 0; }

/* ── Generic card ── */
.bilai-rw-card { background: var(--bilai-rw-card); border: 1px solid var(--bilai-rw-border); border-radius: var(--bilai-rw-radius); padding: 22px 24px; }
.bilai-rw-card-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 20px; flex-wrap: wrap; }
.bilai-rw-card-title { display: flex; align-items: center; gap: 9px; font-size: 15.5px; font-weight: 700; color: var(--bilai-rw-text); margin: 0; }
.bilai-rw-card-title .ic { color: var(--bilai-rw-primary); font-size: 16px; }
.bilai-rw-badge-pill { background: var(--bilai-rw-cream); border: 1px solid var(--bilai-rw-border); border-radius: 100px; padding: 5px 14px; font-size: 12px; font-weight: 600; color: var(--bilai-rw-muted); white-space: nowrap; }

/* ── Membership progress ── */
.bilai-rw-stages { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; margin-bottom: 18px; }
.bilai-rw-stage { text-align: center; }
.bilai-rw-stage-ic {
    width: 34px; height: 34px; border-radius: 50%; margin: 0 auto 8px;
    display: flex; align-items: center; justify-content: center;
    background: #fff; border: 2px solid #ddd2c2; color: #b9ac99; font-size: 14px;
}
.bilai-rw-stage.active .bilai-rw-stage-ic { border-color: var(--bilai-rw-primary); color: var(--bilai-rw-primary); box-shadow: 0 0 0 3px rgba(242,140,0,0.15); }
.bilai-rw-stage-name { font-size: 12.5px; font-weight: 600; color: var(--bilai-rw-muted); margin: 0 0 2px; }
.bilai-rw-stage.active .bilai-rw-stage-name { color: var(--bilai-rw-primary); }
.bilai-rw-stage-here { font-size: 10.5px; color: #c4b8a6; margin: 0; }
.bilai-rw-stage.active .bilai-rw-stage-here { color: var(--bilai-rw-primary); }
.bilai-rw-progress { height: 6px; background: #eee3d0; border-radius: 100px; overflow: hidden; margin-bottom: 10px; }
.bilai-rw-progress-fill { height: 100%; width: 1.5%; background: var(--bilai-rw-primary); border-radius: 100px; }
.bilai-rw-progress-foot { display: flex; align-items: center; justify-content: space-between; gap: 10px; font-size: 12px; flex-wrap: wrap; }
.bilai-rw-progress-foot .pts { color: var(--bilai-rw-muted); font-weight: 600; }
.bilai-rw-progress-foot .hint { color: var(--bilai-rw-primary); font-weight: 600; }

/* ── How it works ── */
.bilai-rw-how-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; }
.bilai-rw-how-card { background: var(--bilai-rw-cream); border: 1px solid var(--bilai-rw-border); border-radius: 10px; padding: 18px; display: flex; flex-direction: column; }
.bilai-rw-how-num {
    width: 34px; height: 34px; border-radius: 8px; margin-bottom: 14px;
    display: flex; align-items: center; justify-content: center;
    font-size: 15px; font-weight: 700; color: var(--bilai-rw-text);
    background: #f3e2c3; border: 1px solid var(--bilai-rw-border);
}
.bilai-rw-how-card:nth-child(2) .bilai-rw-how-num { background: #e8dccb; }
.bilai-rw-how-card:nth-child(3) .bilai-rw-how-num { background: #f5d9d2; }
.bilai-rw-how-title { font-size: 14.5px; font-weight: 700; color: var(--bilai-rw-text); margin: 0 0 8px; }
.bilai-rw-how-text { font-size: 12.5px; color: var(--bilai-rw-muted); line-height: 1.6; margin: 0 0 14px; flex: 1; }
.bilai-rw-how-pill { align-self: flex-start; font-size: 11px; font-weight: 700; padding: 4px 12px; border-radius: 100px; background: #fff3dd; color: var(--bilai-rw-primary-dark); }
.bilai-rw-how-card:nth-child(3) .bilai-rw-how-pill { background: #fde8e4; color: #c0564a; }

/* ── Point history ── */
.bilai-rw-hist-sub { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; border-top: 1px solid var(--bilai-rw-border); padding-top: 16px; margin-bottom: 8px; }
.bilai-rw-hist-sub-title { display: flex; align-items: center; gap: 8px; font-size: 13.5px; font-weight: 600; color: var(--bilai-rw-text); margin: 0; }
.bilai-rw-hist-tabs { display: flex; gap: 6px; }
.bilai-rw-hist-tab {
    padding: 5px 16px; font-size: 12px; font-weight: 600;
    border: 1px solid var(--bilai-rw-border); border-radius: 100px;
    background: #fff; color: var(--bilai-rw-muted); cursor: pointer; transition: 0.13s;
}
.bilai-rw-hist-tab:hover { border-color: var(--bilai-rw-primary); color: var(--bilai-rw-primary); }
.bilai-rw-hist-tab.active { border-color: var(--bilai-rw-primary); color: var(--bilai-rw-primary); background: #fff3dd; }
.bilai-rw-hist-empty { text-align: center; padding: 34px 16px 26px; }
.bilai-rw-hist-empty-ic { font-size: 30px; color: var(--bilai-rw-muted); margin-bottom: 12px; }
.bilai-rw-hist-empty-title { font-size: 14.5px; font-weight: 700; color: var(--bilai-rw-text); margin: 0 0 6px; }
.bilai-rw-hist-empty-text { font-size: 12.5px; color: var(--bilai-rw-muted); line-height: 1.6; margin: 0 auto 18px; max-width: 380px; }
.bilai-rw-browse-btn {
    display: inline-flex; align-items: center; gap: 8px; padding: 11px 22px;
    background: var(--bilai-rw-primary); color: #fff !important; border-radius: 10px;
    font-size: 13.5px; font-weight: 700; text-decoration: none; transition: 0.2s;
}
.bilai-rw-browse-btn:hover { background: var(--bilai-rw-primary-dark); text-decoration: none; }

/* Responsive */
@media (max-width: 1199px) { .bilai-rw-layout { grid-template-columns: 228px 1fr; gap: 16px; } }
@media (max-width: 991px)  {
    .bilai-rw-layout { grid-template-columns: 1fr; }
    .bilai-rw-sidebar { position: static; }
}
@media (max-width: 767px)  {
    .bilai-rw-hero-top { flex-direction: column; align-items: flex-start; }
    .bilai-rw-hero-points { width: 100%; }
    .bilai-rw-hero-strip { grid-template-columns: 1fr; }
    .bilai-rw-strip-col { border-right: none; border-bottom: 1px solid var(--bilai-rw-border); }
    .bilai-rw-strip-col:last-child { border-bottom: none; }
    .bilai-rw-how-grid { grid-template-columns: 1fr; }
    .bilai-rw-stages { grid-template-columns: repeat(2, 1fr); gap: 14px; }
    .bilai-rw-card { padding: 16px; }
}
/* BilaiGhor Reward Points Page End */

/* BilaiGhor Reward Terms Accordion Start */
.bilai-rw-acc { display: flex; flex-direction: column; gap: 10px; }
.bilai-rw-acc-item { border: 1px solid var(--bilai-rw-border); border-radius: 10px; background: #fff; overflow: hidden; }
.bilai-rw-acc-item.open { background: var(--bilai-rw-cream); }
.bilai-rw-acc-head {
    display: flex; align-items: center; gap: 10px; width: 100%;
    padding: 14px 18px; background: transparent; border: none; cursor: pointer;
    font-size: 13.5px; font-weight: 600; color: var(--bilai-rw-text); text-align: left;
}
.bilai-rw-acc-head .star { color: #f2b705; font-size: 14px; flex-shrink: 0; }
.bilai-rw-acc-head .chev { margin-left: auto; color: var(--bilai-rw-muted); font-size: 12px; transition: transform 0.2s; flex-shrink: 0; }
.bilai-rw-acc-item.open .bilai-rw-acc-head .chev { transform: rotate(180deg); }
.bilai-rw-acc-body { display: none; padding: 0 18px 16px 42px; }
.bilai-rw-acc-item.open .bilai-rw-acc-body { display: block; }
.bilai-rw-acc-body ul { margin: 0; padding-left: 16px; }
.bilai-rw-acc-body li { font-size: 12.5px; color: var(--bilai-rw-muted); line-height: 1.7; margin-bottom: 4px; }
/* BilaiGhor Reward Terms Accordion End */
</style>
@endpush

@section('content')
<div class="bilai-rw-page">
    <div class="container">

        {{-- Breadcrumb --}}
        <nav class="bilai-rw-bc" aria-label="breadcrumb">
            <a href="{{ route('home') }}">Home</a>
            <span class="bilai-rw-bc-sep">›</span>
            <span>Profile</span>
            <span class="bilai-rw-bc-sep">›</span>
            <span class="bilai-rw-bc-active">Reward Points</span>
        </nav>

        <div class="bilai-rw-layout">

            {{-- ═══════════ LEFT SIDEBAR (shared style) ═══════════ --}}
            <aside class="bilai-rw-sidebar">
                <div class="bilai-card">
                    <div class="bilai-dash-profile-box">
                        <div class="bilai-dash-profile-row">
                            @if($profileImage)
                                <img src="{{ $profileImage }}" onerror="this.style.display='none';this.nextElementSibling.style.display='flex';" class="bilai-dash-avatar" alt="{{ $customer->name }}">
                                <div class="bilai-dash-avatar-placeholder" style="display:none;">{{ $customerInitial }}</div>
                            @else
                                <div class="bilai-dash-avatar-placeholder">{{ $customerInitial }}</div>
                            @endif
                            <div class="bilai-dash-profile-info">
                                <p class="bilai-dash-profile-name">{{ $customer->name ?? 'Customer' }}</p>
                                <p class="bilai-dash-profile-sub">{{ $customer->phone ?? $customer->email ?? '' }}</p>
                            </div>
                        </div>
                        <hr class="bilai-dash-profile-divider">
                        <div class="bilai-dash-rp-row">
                            <span class="bilai-dash-rp-item">
                                {{-- Replace SVG icon later --}}
                                <span class="bilai-dash-rp-icon"><i class="fa fa-star"></i></span><span>{{ $rewardPoints }} RP</span>
                            </span>
                            {{-- Replace exchange SVG icon later --}}
                            <span class="bilai-dash-rp-sep-icon"><i class="fa fa-exchange"></i></span>
                            <span class="bilai-dash-rp-item">
                                {{-- Replace SVG icon later --}}
                                <span class="bilai-dash-rp-icon"><i class="fa fa-money"></i></span><span>৳{{ number_format($totalOrderAmount, 0) }} TK</span>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="bilai-card">
                    <nav class="bilai-dash-nav">
                        <p class="bilai-dash-nav-title">My Account</p>
                        <a href="{{ route('customer.account') }}" class="bilai-dash-nav-item">
                            {{-- Replace sidebar SVG icon later --}}
                            <span class="bilai-dash-nav-icon"><i class="fa fa-home"></i></span> Dashboard
                        </a>
                        <a href="{{ route('customer.orders') }}" class="bilai-dash-nav-item">
                            {{-- Replace sidebar SVG icon later --}}
                            <span class="bilai-dash-nav-icon"><i class="fa fa-shopping-bag"></i></span> Orders
                            @if($pendingOrdersCount > 0)<span class="bilai-dash-nav-badge">{{ $pendingOrdersCount }}</span>@endif
                        </a>
                        <a href="{{ route('customer.profile_edit') }}" class="bilai-dash-nav-item">
                            {{-- Replace sidebar SVG icon later --}}
                            <span class="bilai-dash-nav-icon"><i class="fa fa-user"></i></span> Profile
                        </a>
                        <a href="#" class="bilai-dash-nav-item">
                            {{-- Replace sidebar SVG icon later --}}
                            <span class="bilai-dash-nav-icon"><i class="fa fa-heart-o"></i></span> Wishlist
                        </a>
                        <a href="{{ route('customer.addresses') }}" class="bilai-dash-nav-item {{ request()->is('customer/addresses') ? 'active' : '' }}">
                            {{-- Replace sidebar SVG icon later --}}
                            <span class="bilai-dash-nav-icon"><i class="fa fa-map-marker"></i></span> Addresses
                        </a>
                        <a href="#" class="bilai-dash-nav-item">
                            {{-- Replace sidebar SVG icon later --}}
                            <span class="bilai-dash-nav-icon"><i class="fa fa-ticket"></i></span> Coupon
                        </a>
                        {{-- Gift Cards removed from sidebar UI per design (backend untouched) --}}
                        <a href="{{ route('customer.rewards') }}" class="bilai-dash-nav-item active">
                            {{-- Replace sidebar SVG icon later --}}
                            <span class="bilai-dash-nav-icon"><i class="fa fa-star-o"></i></span> Reward Points
                        </a>
                        <a href="{{ route('customer.order_track') }}" class="bilai-dash-nav-item">
                            {{-- Replace sidebar SVG icon later --}}
                            <span class="bilai-dash-nav-icon"><i class="fa fa-truck"></i></span> Track Order
                        </a>
                        <a href="{{ route('customer.refunds') }}" class="bilai-dash-nav-item">
                            {{-- Replace sidebar SVG icon later --}}
                            <span class="bilai-dash-nav-icon"><i class="fa fa-undo"></i></span> Return Request
                        </a>
                        <a href="{{ route('complaint') }}" class="bilai-dash-nav-item">
                            {{-- Replace sidebar SVG icon later --}}
                            <span class="bilai-dash-nav-icon"><i class="fa fa-headphones"></i></span> Support Ticket
                        </a>
                        <hr class="bilai-dash-nav-sep">
                        <a href="{{ route('customer.logout') }}"
                           onclick="event.preventDefault(); document.getElementById('bilai-rw-logout-form').submit();"
                           class="bilai-dash-nav-item bilai-dash-nav-item--logout">
                            {{-- Replace sidebar SVG icon later --}}
                            <span class="bilai-dash-nav-icon"><i class="fa fa-sign-out"></i></span> Logout
                        </a>
                        <form id="bilai-rw-logout-form" action="{{ route('customer.logout') }}" method="POST" style="display:none;">@csrf</form>
                    </nav>
                </div>
            </aside>

            {{-- ═══════════ RIGHT CONTENT ═══════════ --}}
            <main class="bilai-rw-content">

                {{-- Hero --}}
                <div class="bilai-rw-hero">
                    <div class="bilai-rw-hero-top">
                        <div>
                            <p class="bilai-rw-hero-hello">Hello, {{ $firstName }}!</p>
                            <h2 class="bilai-rw-hero-title">Your Reward Points</h2>
                            <p class="bilai-rw-hero-sub">Earn points on every purchase and redeem them for discounts</p>
                        </div>
                        <div class="bilai-rw-hero-points">
                            <p class="lbl">Available Points</p>
                            <p class="val">{{ $rewardPoints }}</p>
                            <p class="sub">Reward Points</p>
                        </div>
                    </div>
                    {{-- Hidden for now — change @if(false) to @if(true) to show the summary strip again --}}
                    @if(false)
                    <div class="bilai-rw-hero-strip">
                        <div class="bilai-rw-strip-col">
                            <p class="bilai-rw-strip-label"><span class="bilai-rw-strip-dot"></span> Total Points</p>
                            <p class="bilai-rw-strip-value">{{ $rewardPoints }} RP</p>
                            <p class="bilai-rw-strip-sub">Available to use</p>
                        </div>
                        <div class="bilai-rw-strip-col">
                            <p class="bilai-rw-strip-label"><span class="bilai-rw-strip-dot"></span> Cash Value</p>
                            <p class="bilai-rw-strip-value orange">৳{{ number_format($cashValue, 2) }}</p>
                            <p class="bilai-rw-strip-sub">1 point = ৳1.00</p>
                        </div>
                        <div class="bilai-rw-strip-col">
                            <p class="bilai-rw-strip-label"><span class="bilai-rw-strip-dot"></span> Membership Tier</p>
                            <p class="bilai-rw-strip-value">No Tier Yet</p>
                            <p class="bilai-rw-strip-sub">500 pts needed for silver</p>
                        </div>
                    </div>
                    @endif
                </div>

                {{-- Membership Progress — hidden for now; change @if(false) to @if(true) to show it again --}}
                @if(false)
                <div class="bilai-rw-card">
                    <div class="bilai-rw-card-head">
                        <h3 class="bilai-rw-card-title">
                            {{-- Replace crown SVG icon later --}}
                            <span class="ic"><i class="fa fa-trophy"></i></span> Membership Progress
                        </h3>
                        <span class="bilai-rw-badge-pill">500 pts – Silver</span>
                    </div>

                    <div class="bilai-rw-stages">
                        <div class="bilai-rw-stage active">
                            {{-- Replace membership SVG icon later --}}
                            <div class="bilai-rw-stage-ic"><i class="fa fa-circle-o"></i></div>
                            <p class="bilai-rw-stage-name">New Member</p>
                            <p class="bilai-rw-stage-here">You are here</p>
                        </div>
                        <div class="bilai-rw-stage">
                            {{-- Replace membership SVG icon later --}}
                            <div class="bilai-rw-stage-ic"><i class="fa fa-shield"></i></div>
                            <p class="bilai-rw-stage-name">Silver</p>
                            <p class="bilai-rw-stage-here">500 pts</p>
                        </div>
                        <div class="bilai-rw-stage">
                            {{-- Replace membership SVG icon later --}}
                            <div class="bilai-rw-stage-ic"><i class="fa fa-trophy"></i></div>
                            <p class="bilai-rw-stage-name">Gold</p>
                            <p class="bilai-rw-stage-here">1500 pts</p>
                        </div>
                        <div class="bilai-rw-stage">
                            {{-- Replace membership SVG icon later --}}
                            <div class="bilai-rw-stage-ic"><i class="fa fa-diamond"></i></div>
                            <p class="bilai-rw-stage-name">Platinum</p>
                            <p class="bilai-rw-stage-here">3000 pts</p>
                        </div>
                    </div>

                    <div class="bilai-rw-progress"><div class="bilai-rw-progress-fill"></div></div>
                    <div class="bilai-rw-progress-foot">
                        <span class="pts">{{ $rewardPoints }} pts</span>
                        <span class="hint">Earn faster with special promotions!</span>
                    </div>
                </div>
                @endif

                {{-- How Reward Points Work --}}
                <div class="bilai-rw-card">
                    <div class="bilai-rw-card-head" style="margin-bottom:16px;">
                        <h3 class="bilai-rw-card-title">How Reward Points Work</h3>
                    </div>
                    <div class="bilai-rw-how-grid">
                        <div class="bilai-rw-how-card">
                            <div class="bilai-rw-how-num">1</div>
                            <p class="bilai-rw-how-title">Collect Points</p>
                            <p class="bilai-rw-how-text">Place any order on our website or in-store. Points are credited once your order is successfully shipped.</p>
                            <span class="bilai-rw-how-pill">1 pt per ৳100 spent</span>
                        </div>
                        <div class="bilai-rw-how-card">
                            <div class="bilai-rw-how-num">2</div>
                            <p class="bilai-rw-how-title">Redeem at Checkout</p>
                            <p class="bilai-rw-how-text">Apply your points when placing an order. Each point is worth ৳1. You can redeem up to 50 points per day.</p>
                            <span class="bilai-rw-how-pill">1 point = ৳1 off</span>
                        </div>
                        <div class="bilai-rw-how-card">
                            <div class="bilai-rw-how-num">3</div>
                            <p class="bilai-rw-how-title">Points Expiry</p>
                            <p class="bilai-rw-how-text">Points remain valid for 5 years from the date the order was placed. Keep shopping to keep them active!</p>
                            <span class="bilai-rw-how-pill">Valid for 5 years</span>
                        </div>
                    </div>
                </div>

                {{-- Point History --}}
                <div class="bilai-rw-card">
                    <div class="bilai-rw-card-head" style="margin-bottom:16px;">
                        <h3 class="bilai-rw-card-title">Point History</h3>
                    </div>
                    <div class="bilai-rw-hist-sub">
                        <p class="bilai-rw-hist-sub-title">
                            {{-- Replace clock SVG icon later --}}
                            <i class="fa fa-clock-o" style="color:var(--bilai-rw-muted);"></i> Transaction History
                        </p>
                        {{-- Visual-only tabs: reward transactions backend not implemented yet --}}
                        <div class="bilai-rw-hist-tabs">
                            <button type="button" class="bilai-rw-hist-tab active">All</button>
                            <button type="button" class="bilai-rw-hist-tab">Earned</button>
                            <button type="button" class="bilai-rw-hist-tab">Spent</button>
                        </div>
                    </div>

                    {{-- Empty state (no reward transaction data exists yet) --}}
                    <div class="bilai-rw-hist-empty">
                        {{-- Replace empty-state SVG icon later --}}
                        <div class="bilai-rw-hist-empty-ic"><i class="fa fa-clipboard"></i></div>
                        <p class="bilai-rw-hist-empty-title">No transactions yet</p>
                        <p class="bilai-rw-hist-empty-text">Your full point history — every time you earn or spend points — will appear here after your first order.</p>
                        <a href="{{ route('shop') }}" class="bilai-rw-browse-btn">Browse Products <i class="fa fa-arrow-right"></i></a>
                    </div>
                </div>

                {{-- Terms & Conditions --}}
                <div class="bilai-rw-card">
                    <div class="bilai-rw-card-head" style="margin-bottom:16px;">
                        <h3 class="bilai-rw-card-title">Terms &amp; Conditions</h3>
                    </div>

                    {{-- Static placeholder copy for now — replace with CMS/admin content later --}}
                    <div class="bilai-rw-acc">
                        <div class="bilai-rw-acc-item open">
                            <button type="button" class="bilai-rw-acc-head">
                                {{-- Replace star SVG icon later --}}
                                <span class="star"><i class="fa fa-star"></i></span>
                                Earning Points
                                <span class="chev"><i class="fa fa-chevron-down"></i></span>
                            </button>
                            <div class="bilai-rw-acc-body">
                                <ul>
                                    <li>You earn 1 Reward Point for every ৳100 of your order value. Non-round totals round to the nearest ৳0.5 — e.g. a ৳9.3 order earns 9 pts, a ৳9.9 order earns 10 pts.</li>
                                    <li>Bonus points may be awarded during special offers and promotions; these will be announced on our website and newsletters.</li>
                                    <li>Points are only credited to your account once the order has been successfully shipped.</li>
                                </ul>
                            </div>
                        </div>

                        <div class="bilai-rw-acc-item">
                            <button type="button" class="bilai-rw-acc-head">
                                {{-- Replace star SVG icon later --}}
                                <span class="star"><i class="fa fa-star"></i></span>
                                Adjustments to Points
                                <span class="chev"><i class="fa fa-chevron-down"></i></span>
                            </button>
                            <div class="bilai-rw-acc-body">
                                <ul>
                                    <li>If an order is cancelled, returned, or refunded, the points earned from that order will be deducted from your balance.</li>
                                    <li>We reserve the right to correct point balances in case of technical errors or misuse.</li>
                                </ul>
                            </div>
                        </div>

                        <div class="bilai-rw-acc-item">
                            <button type="button" class="bilai-rw-acc-head">
                                {{-- Replace star SVG icon later --}}
                                <span class="star"><i class="fa fa-star"></i></span>
                                Redeeming Points
                                <span class="chev"><i class="fa fa-chevron-down"></i></span>
                            </button>
                            <div class="bilai-rw-acc-body">
                                <ul>
                                    <li>Each point is worth ৳1 and can be applied as a discount at checkout.</li>
                                    <li>A maximum of 50 points can be redeemed per day.</li>
                                    <li>Points cannot be exchanged for cash.</li>
                                </ul>
                            </div>
                        </div>

                        <div class="bilai-rw-acc-item">
                            <button type="button" class="bilai-rw-acc-head">
                                {{-- Replace star SVG icon later --}}
                                <span class="star"><i class="fa fa-star"></i></span>
                                Points Expiry
                                <span class="chev"><i class="fa fa-chevron-down"></i></span>
                            </button>
                            <div class="bilai-rw-acc-body">
                                <ul>
                                    <li>Points remain valid for 5 years from the date the order was placed.</li>
                                    <li>Expired points are automatically removed from your balance and cannot be restored.</li>
                                </ul>
                            </div>
                        </div>

                        <div class="bilai-rw-acc-item">
                            <button type="button" class="bilai-rw-acc-head">
                                {{-- Replace star SVG icon later --}}
                                <span class="star"><i class="fa fa-star"></i></span>
                                Coupon &amp; Cashback Policy
                                <span class="chev"><i class="fa fa-chevron-down"></i></span>
                            </button>
                            <div class="bilai-rw-acc-body">
                                <ul>
                                    <li>Reward points can be combined with coupons unless a specific promotion states otherwise.</li>
                                    <li>Cashback offers are credited separately and follow their own campaign terms.</li>
                                </ul>
                            </div>
                        </div>

                        <div class="bilai-rw-acc-item">
                            <button type="button" class="bilai-rw-acc-head">
                                {{-- Replace star SVG icon later --}}
                                <span class="star"><i class="fa fa-star"></i></span>
                                General Conditions
                                <span class="chev"><i class="fa fa-chevron-down"></i></span>
                            </button>
                            <div class="bilai-rw-acc-body">
                                <ul>
                                    <li>The reward program is available to registered customers only.</li>
                                    <li>BilaiGhor may modify or discontinue the reward program at any time with prior notice on the website.</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

            </main>
        </div>
    </div>
</div>

@push('script')
<script>
/* Reward T&C accordion + visual history tabs (no backend yet) */
(function () {
    document.querySelectorAll('.bilai-rw-acc-head').forEach(function (head) {
        head.addEventListener('click', function () {
            this.closest('.bilai-rw-acc-item').classList.toggle('open');
        });
    });

    document.querySelectorAll('.bilai-rw-hist-tab').forEach(function (tab) {
        tab.addEventListener('click', function () {
            document.querySelectorAll('.bilai-rw-hist-tab').forEach(function (t) { t.classList.remove('active'); });
            this.classList.add('active');
        });
    });
}());
</script>
@endpush

@endsection
