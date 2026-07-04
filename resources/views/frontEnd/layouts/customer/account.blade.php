@php
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Models\Order;
use App\Models\Product;

$customer      = Auth::guard('customer')->user();
$customerId    = $customer->id;

// Order statistics — mapped to Figma cards
$pendingOrders    = Order::where('customer_id', $customerId)->whereIn('order_status', ['1', '2'])->count();
$processingOrders = Order::where('customer_id', $customerId)->whereIn('order_status', ['3', '4', '5'])->count();
$deliveredOrders  = Order::where('customer_id', $customerId)->where('order_status', '6')->count();

// Badge count (all non-terminal orders)
$pendingOrdersCount = Order::where('customer_id', $customerId)
    ->whereNotIn('order_status', ['6', '11'])->count();

// Total spent
$totalOrderAmount = Order::where('customer_id', $customerId)->sum('amount');

// Recent orders (last 5)
$recentOrders = Order::where('customer_id', $customerId)
    ->with(['payment', 'orderdetails.product'])
    ->latest()->limit(5)->get();

// Recommended products
$recommendedProducts = Product::where('status', 1)
    ->where('approval_status', 'approved')
    ->where('stock', '>', 0)
    ->with('image')
    ->inRandomOrder()->limit(4)->get();

// Profile image / initials fallback
$profileImage    = $customer->image ? asset($customer->image) : null;
$customerInitial = strtoupper(substr($customer->name ?? 'U', 0, 1));
@endphp

@extends('frontEnd.layouts.master')

@section('title', 'Dashboard | ' . ($customer->name ?? 'Account'))

@push('css')
<style>
/* BilaiGhor Customer Dashboard Start */

/* ── Variables (fallback if root vars missing) ───────────────────── */
:root {
    --bilai-dash-primary:  var(--bilai-primary,  #e8861a);
    --bilai-dash-primary-dark: var(--bilai-primary-dark, #c96f00);
    --bilai-dash-brown:    var(--bilai-brown,    #3a1f0f);
    --bilai-dash-cream:    var(--bilai-cream,    #fdfcf8);
    --bilai-dash-border:   var(--bilai-border,   #dccab2);
    --bilai-dash-text:     var(--bilai-text,     #4f4f4f);
    --bilai-dash-muted:    var(--bilai-muted,    #7a6a5e);
    --bilai-dash-radius:   var(--bilai-radius-md, 14px);
    --bilai-dash-radius-sm: var(--bilai-radius-sm, 8px);
}

/* ── Page shell ──────────────────────────────────────────────────── */
.bilai-dash-breadcrumb-bar {
    background: #fff;
    border-bottom: 1px solid var(--bilai-dash-border);
    padding: 11px 0;
}
.bilai-dash-bc {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 13px;
    flex-wrap: wrap;
    margin: 0;
    padding: 0;
    list-style: none;
}
.bilai-dash-bc a {
    color: var(--bilai-dash-text);
    text-decoration: none;
}
.bilai-dash-bc a:hover { color: var(--bilai-dash-primary); }
.bilai-dash-bc-sep    { color: var(--bilai-dash-muted); }
.bilai-dash-bc-active { color: var(--bilai-dash-primary); font-weight: 600; }

.bilai-dash-page {
    background: var(--bilai-dash-cream);
    min-height: 70vh;
    padding: 28px 0 56px;
}

/* ── Two-column layout ───────────────────────────────────────────── */
.bilai-dash-layout {
    display: grid;
    grid-template-columns: 268px 1fr;
    gap: 24px;
    align-items: start;
}

/* ── Sidebar card ────────────────────────────────────────────────── */
.bilai-dash-sidebar-card {
    background: #fff;
    border: 1px solid var(--bilai-dash-border);
    border-radius: var(--bilai-dash-radius);
    overflow: hidden;
    position: sticky;
    top: 90px;
}

/* Profile box */
.bilai-dash-profile-box {
    padding: 28px 20px 20px;
    text-align: center;
    border-bottom: 1px solid var(--bilai-dash-border);
}
.bilai-dash-avatar {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid var(--bilai-dash-border);
    margin: 0 auto 12px;
    display: block;
}
.bilai-dash-avatar-placeholder {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: var(--bilai-dash-primary);
    color: #fff;
    font-size: 30px;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 12px;
}
.bilai-dash-profile-name {
    font-size: 16px;
    font-weight: 700;
    color: var(--bilai-dash-brown);
    margin: 0 0 4px;
    line-height: 1.3;
}
.bilai-dash-profile-sub {
    font-size: 13px;
    color: var(--bilai-dash-muted);
    margin: 0 0 16px;
}
/* RP / TK summary row */
.bilai-dash-rp-row {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 14px;
}
.bilai-dash-rp-item {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 12px;
    font-weight: 600;
    color: var(--bilai-dash-text);
}
.bilai-dash-rp-icon { color: var(--bilai-dash-primary); }
.bilai-dash-rp-divider {
    width: 1px;
    height: 18px;
    background: var(--bilai-dash-border);
}

/* Sidebar nav */
.bilai-dash-nav { padding: 8px 0; }
.bilai-dash-nav-item {
    display: flex;
    align-items: center;
    gap: 11px;
    padding: 12px 20px;
    color: var(--bilai-dash-text);
    font-size: 14px;
    font-weight: 500;
    text-decoration: none;
    border-left: 3px solid transparent;
    transition: background 0.15s, color 0.15s;
}
.bilai-dash-nav-item:hover {
    background: var(--bilai-dash-cream);
    color: var(--bilai-dash-primary);
    text-decoration: none;
}
.bilai-dash-nav-item.active {
    background: var(--bilai-dash-cream);
    color: var(--bilai-dash-primary);
    border-left-color: var(--bilai-dash-primary);
    font-weight: 600;
}
.bilai-dash-nav-icon {
    width: 18px;
    text-align: center;
    flex-shrink: 0;
    font-size: 14px;
}
.bilai-dash-nav-badge {
    margin-left: auto;
    background: #e53935;
    color: #fff;
    font-size: 10px;
    font-weight: 700;
    padding: 2px 7px;
    border-radius: 100px;
    line-height: 1.5;
}
.bilai-dash-nav-logout {
    border-top: 1px solid var(--bilai-dash-border);
    margin-top: 6px;
    padding-top: 6px;
}
.bilai-dash-nav-item--logout { color: #dc3545; }
.bilai-dash-nav-item--logout:hover {
    background: #fff5f5;
    color: #c62828;
}

/* ── Right content cards ─────────────────────────────────────────── */
.bilai-dash-main-card {
    background: #fff;
    border: 1px solid var(--bilai-dash-border);
    border-radius: var(--bilai-dash-radius);
    padding: 24px 24px 28px;
    margin-bottom: 20px;
}
.bilai-dash-welcome-title {
    font-size: 19px;
    font-weight: 700;
    color: var(--bilai-dash-brown);
    margin: 0 0 6px;
}
.bilai-dash-welcome-text {
    font-size: 13.5px;
    color: var(--bilai-dash-muted);
    margin: 0 0 22px;
    line-height: 1.6;
}

/* Stats grid — 3 columns wrapping */
.bilai-dash-stats-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
}
.bilai-dash-stat-card {
    background: var(--bilai-dash-cream);
    border: 1px solid var(--bilai-dash-border);
    border-radius: var(--bilai-dash-radius-sm);
    padding: 16px 14px;
    display: flex;
    align-items: center;
    gap: 12px;
}
.bilai-dash-stat-card--pending {
    background: #fff8ee;
    border-color: #f5c97a;
}
.bilai-dash-stat-icon-wrap {
    width: 40px;
    height: 40px;
    border-radius: var(--bilai-dash-radius-sm);
    background: #fff;
    border: 1px solid var(--bilai-dash-border);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    color: var(--bilai-dash-primary);
    font-size: 15px;
}
.bilai-dash-stat-label {
    font-size: 12px;
    color: var(--bilai-dash-muted);
    margin: 0 0 3px;
    white-space: nowrap;
}
.bilai-dash-stat-value {
    font-size: 20px;
    font-weight: 700;
    color: var(--bilai-dash-brown);
    margin: 0;
    line-height: 1.2;
}

/* Section header */
.bilai-dash-section-hdr {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 14px;
}
.bilai-dash-section-title {
    font-size: 16px;
    font-weight: 700;
    color: var(--bilai-dash-brown);
    margin: 0;
}
.bilai-dash-view-all {
    font-size: 13px;
    color: var(--bilai-dash-primary);
    font-weight: 600;
    text-decoration: none;
}
.bilai-dash-view-all:hover {
    color: var(--bilai-dash-primary-dark);
    text-decoration: underline;
}

/* Orders table */
.bilai-dash-table { width: 100%; border-collapse: collapse; }
.bilai-dash-table th {
    font-size: 11.5px;
    font-weight: 600;
    color: var(--bilai-dash-muted);
    text-transform: uppercase;
    letter-spacing: 0.04em;
    padding: 10px 12px;
    border-bottom: 1px solid var(--bilai-dash-border);
    background: var(--bilai-dash-cream);
    white-space: nowrap;
}
.bilai-dash-table td {
    padding: 13px 12px;
    font-size: 13.5px;
    color: var(--bilai-dash-text);
    border-bottom: 1px solid var(--bilai-dash-border);
    vertical-align: middle;
}
.bilai-dash-table tbody tr:last-child td { border-bottom: none; }
.bilai-dash-table tbody tr:hover { background: var(--bilai-dash-cream); }
.bilai-dash-order-id { color: var(--bilai-dash-primary); font-weight: 700; }
.bilai-dash-td-muted { color: var(--bilai-dash-muted); }
.bilai-dash-empty { color: var(--bilai-dash-muted); padding: 32px !important; font-size: 14px; }
.bilai-dash-icon-link { color: var(--bilai-dash-muted); font-size: 15px; text-decoration: none; }
.bilai-dash-icon-link:hover { color: var(--bilai-dash-primary); }

/* Badges */
.bd-badge {
    display: inline-block;
    padding: 3px 9px;
    border-radius: 100px;
    font-size: 11px;
    font-weight: 700;
    line-height: 1.5;
    white-space: nowrap;
}
.bd-badge-green  { background: #e6f4ea; color: #2e7d32; }
.bd-badge-red    { background: #fdecea; color: #c62828; }
.bd-badge-orange { background: #fff3e0; color: #e65100; }
.bd-badge-blue   { background: #e3f2fd; color: #1565c0; }

/* Product cards */
.bilai-dash-products-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 14px;
}
.bilai-dash-product-card {
    border: 1px solid var(--bilai-dash-border);
    border-radius: var(--bilai-dash-radius-sm);
    overflow: hidden;
    background: #fff;
    transition: box-shadow 0.2s;
}
.bilai-dash-product-card:hover { box-shadow: 0 4px 16px rgba(58,31,15,0.1); }
.bilai-dash-product-img-wrap {
    position: relative;
    aspect-ratio: 1 / 1;
    overflow: hidden;
    background: var(--bilai-dash-cream);
}
.bilai-dash-product-img-wrap img {
    width: 100%; height: 100%;
    object-fit: cover;
    transition: transform 0.3s;
}
.bilai-dash-product-card:hover .bilai-dash-product-img-wrap img { transform: scale(1.05); }
.bilai-dash-product-badge {
    position: absolute;
    top: 8px; left: 8px;
    background: var(--bilai-dash-primary);
    color: #fff;
    font-size: 10px;
    font-weight: 700;
    padding: 2px 8px;
    border-radius: 4px;
}
.bilai-dash-product-body { padding: 11px 12px 14px; }
.bilai-dash-product-name {
    font-size: 12.5px;
    font-weight: 600;
    color: var(--bilai-dash-text);
    margin: 0 0 7px;
    min-height: 2.5em;
    overflow: hidden;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
}
.bilai-dash-product-name a { color: inherit; text-decoration: none; }
.bilai-dash-product-name a:hover { color: var(--bilai-dash-primary); }
.bilai-dash-product-price {
    display: flex;
    align-items: baseline;
    gap: 6px;
    margin-bottom: 10px;
    flex-wrap: wrap;
}
.bilai-dash-old-price { font-size: 11px; color: var(--bilai-dash-muted); text-decoration: line-through; }
.bilai-dash-new-price { font-size: 15px; font-weight: 700; color: var(--bilai-dash-primary); }
.bilai-dash-order-btn {
    display: block;
    width: 100%;
    padding: 8px 6px;
    background: var(--bilai-dash-primary);
    color: #fff;
    text-align: center;
    border-radius: var(--bilai-dash-radius-sm);
    font-size: 12.5px;
    font-weight: 600;
    text-decoration: none;
    transition: background 0.15s;
}
.bilai-dash-order-btn:hover { background: var(--bilai-dash-primary-dark); color: #fff; text-decoration: none; }

/* ── Responsive ──────────────────────────────────────────────────── */
@media (max-width: 1199px) {
    .bilai-dash-layout { grid-template-columns: 240px 1fr; gap: 18px; }
}
@media (max-width: 991px) {
    .bilai-dash-layout { grid-template-columns: 1fr; }
    .bilai-dash-sidebar-card { position: static; }
    .bilai-dash-stats-grid  { grid-template-columns: repeat(2, 1fr); }
    .bilai-dash-products-grid { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 575px) {
    .bilai-dash-main-card   { padding: 16px 14px 20px; }
    .bilai-dash-stats-grid  { grid-template-columns: 1fr 1fr; gap: 10px; }
    .bilai-dash-stat-value  { font-size: 18px; }
    .bilai-dash-products-grid { grid-template-columns: 1fr 1fr; gap: 10px; }
    .bilai-dash-table th, .bilai-dash-table td { padding: 10px 8px; font-size: 12px; }
}

/* BilaiGhor Customer Dashboard End */
</style>
@endpush

@section('content')

{{-- Breadcrumb bar --}}
<div class="bilai-dash-breadcrumb-bar">
    <div class="container">
        <nav class="bilai-dash-bc" aria-label="breadcrumb">
            <a href="{{ route('home') }}">Home</a>
            <span class="bilai-dash-bc-sep">›</span>
            <span>Profile</span>
            <span class="bilai-dash-bc-sep">›</span>
            <span class="bilai-dash-bc-active">Dashboard</span>
        </nav>
    </div>
</div>

{{-- Dashboard page --}}
<div class="bilai-dash-page">
    <div class="container">
        <div class="bilai-dash-layout">

            {{-- ───────────────── LEFT SIDEBAR ─────────────────── --}}
            <aside class="bilai-dash-sidebar">
                <div class="bilai-dash-sidebar-card">

                    {{-- Profile box --}}
                    <div class="bilai-dash-profile-box">
                        @if($profileImage)
                            <img src="{{ $profileImage }}"
                                 onerror="this.style.display='none'; document.getElementById('bilai-avatar-fallback').style.display='flex';"
                                 class="bilai-dash-avatar" alt="{{ $customer->name }}">
                            <div class="bilai-dash-avatar-placeholder" id="bilai-avatar-fallback" style="display:none;">
                                {{ $customerInitial }}
                            </div>
                        @else
                            <div class="bilai-dash-avatar-placeholder">{{ $customerInitial }}</div>
                        @endif

                        <p class="bilai-dash-profile-name">{{ $customer->name ?? 'Customer' }}</p>
                        <p class="bilai-dash-profile-sub">{{ $customer->phone ?? $customer->email ?? '' }}</p>

                        {{-- RP / TK summary --}}
                        <div class="bilai-dash-rp-row">
                            <span class="bilai-dash-rp-item">
                                {{-- Replace SVG icon later --}}
                                <span class="bilai-dash-rp-icon"><i class="fa fa-star"></i></span>
                                <span>0 RP</span>
                            </span>
                            <span class="bilai-dash-rp-divider"></span>
                            <span class="bilai-dash-rp-item">
                                {{-- Replace SVG icon later --}}
                                <span class="bilai-dash-rp-icon"><i class="fa fa-money"></i></span>
                                <span>৳{{ number_format($totalOrderAmount, 0) }}</span>
                            </span>
                        </div>
                    </div>

                    {{-- Navigation --}}
                    <nav class="bilai-dash-nav">
                        <a href="{{ route('customer.account') }}"
                           class="bilai-dash-nav-item {{ request()->is('customer/account') ? 'active' : '' }}">
                            {{-- Replace SVG icon later --}}
                            <span class="bilai-dash-nav-icon"><i class="fa fa-home"></i></span>
                            Dashboard
                        </a>

                        <a href="{{ route('customer.orders') }}"
                           class="bilai-dash-nav-item {{ request()->is('customer/orders') ? 'active' : '' }}">
                            {{-- Replace SVG icon later --}}
                            <span class="bilai-dash-nav-icon"><i class="fa fa-shopping-bag"></i></span>
                            Orders
                            @if($pendingOrdersCount > 0)
                                <span class="bilai-dash-nav-badge">{{ $pendingOrdersCount }}</span>
                            @endif
                        </a>

                        <a href="{{ route('customer.profile_edit') }}"
                           class="bilai-dash-nav-item {{ request()->is('customer/profile-edit') ? 'active' : '' }}">
                            {{-- Replace SVG icon later --}}
                            <span class="bilai-dash-nav-icon"><i class="fa fa-user"></i></span>
                            Profile
                        </a>

                        {{-- Wishlist — no route yet, add when implemented --}}
                        <a href="#" class="bilai-dash-nav-item">
                            {{-- Replace SVG icon later --}}
                            <span class="bilai-dash-nav-icon"><i class="fa fa-heart"></i></span>
                            Wishlist
                            {{-- Replace with route when wishlist is implemented --}}
                        </a>

                        {{-- Addresses — no route yet --}}
                        <a href="#" class="bilai-dash-nav-item">
                            {{-- Replace SVG icon later --}}
                            <span class="bilai-dash-nav-icon"><i class="fa fa-map-marker"></i></span>
                            Addresses
                            {{-- Replace with route when implemented --}}
                        </a>

                        {{-- Coupon — no route yet --}}
                        <a href="#" class="bilai-dash-nav-item">
                            {{-- Replace SVG icon later --}}
                            <span class="bilai-dash-nav-icon"><i class="fa fa-tag"></i></span>
                            Coupon
                            {{-- Replace with route when implemented --}}
                        </a>

                        {{-- Gift Cards — no route yet --}}
                        <a href="#" class="bilai-dash-nav-item">
                            {{-- Replace SVG icon later --}}
                            <span class="bilai-dash-nav-icon"><i class="fa fa-gift"></i></span>
                            Gift Cards
                            {{-- Replace with route when implemented --}}
                        </a>

                        {{-- Reward Points — no route yet --}}
                        <a href="#" class="bilai-dash-nav-item">
                            {{-- Replace SVG icon later --}}
                            <span class="bilai-dash-nav-icon"><i class="fa fa-star-o"></i></span>
                            Reward Points
                            {{-- Replace with route when implemented --}}
                        </a>

                        <a href="{{ route('customer.order_track') }}"
                           class="bilai-dash-nav-item {{ request()->is('customer/order-track*') ? 'active' : '' }}">
                            {{-- Replace SVG icon later --}}
                            <span class="bilai-dash-nav-icon"><i class="fa fa-truck"></i></span>
                            Track Order
                        </a>

                        <a href="{{ route('customer.refunds') }}"
                           class="bilai-dash-nav-item {{ request()->is('customer/refunds*') ? 'active' : '' }}">
                            {{-- Replace SVG icon later --}}
                            <span class="bilai-dash-nav-icon"><i class="fa fa-undo"></i></span>
                            Return Request
                        </a>

                        <a href="{{ route('complaint') }}"
                           class="bilai-dash-nav-item {{ request()->is('complaint') ? 'active' : '' }}">
                            {{-- Replace SVG icon later --}}
                            <span class="bilai-dash-nav-icon"><i class="fa fa-headphones"></i></span>
                            Support Ticket
                        </a>

                        {{-- Logout --}}
                        <div class="bilai-dash-nav-logout">
                            <a href="{{ route('customer.logout') }}"
                               onclick="event.preventDefault(); document.getElementById('bilai-logout-form').submit();"
                               class="bilai-dash-nav-item bilai-dash-nav-item--logout">
                                {{-- Replace SVG icon later --}}
                                <span class="bilai-dash-nav-icon"><i class="fa fa-sign-out"></i></span>
                                Logout
                            </a>
                            <form id="bilai-logout-form" action="{{ route('customer.logout') }}" method="POST" style="display:none;">
                                @csrf
                            </form>
                        </div>
                    </nav>

                </div>
            </aside>

            {{-- ───────────────── RIGHT CONTENT ────────────────── --}}
            <main class="bilai-dash-content">

                {{-- Welcome + Stats card --}}
                <div class="bilai-dash-main-card">
                    <h2 class="bilai-dash-welcome-title">Welcome back, {{ $customer->name ?? 'Customer' }}!</h2>
                    <p class="bilai-dash-welcome-text">From your account dashboard, you can easily view your orders, track reward points, manage your wishlist, and check your coupons.</p>

                    <div class="bilai-dash-stats-grid">

                        {{-- Total RP --}}
                        <div class="bilai-dash-stat-card">
                            <div class="bilai-dash-stat-icon-wrap">
                                {{-- Replace SVG icon later --}}
                                <i class="fa fa-cog"></i>
                            </div>
                            <div>
                                <p class="bilai-dash-stat-label">Total RP</p>
                                <p class="bilai-dash-stat-value">00</p>
                                {{-- Replace with real reward points when implemented --}}
                            </div>
                        </div>

                        {{-- Total Taka --}}
                        <div class="bilai-dash-stat-card">
                            <div class="bilai-dash-stat-icon-wrap">
                                {{-- Replace SVG icon later --}}
                                <i class="fa fa-search"></i>
                            </div>
                            <div>
                                <p class="bilai-dash-stat-label">Total Taka</p>
                                <p class="bilai-dash-stat-value">৳{{ number_format($totalOrderAmount, 0) }}</p>
                            </div>
                        </div>

                        {{-- Total Wishlist --}}
                        <div class="bilai-dash-stat-card">
                            <div class="bilai-dash-stat-icon-wrap">
                                {{-- Replace SVG icon later --}}
                                <i class="fa fa-heart"></i>
                            </div>
                            <div>
                                <p class="bilai-dash-stat-label">Total Wishlist</p>
                                <p class="bilai-dash-stat-value">00</p>
                                {{-- Replace with real wishlist count when implemented --}}
                            </div>
                        </div>

                        {{-- Delivered Order --}}
                        <div class="bilai-dash-stat-card">
                            <div class="bilai-dash-stat-icon-wrap">
                                {{-- Replace SVG icon later --}}
                                <i class="fa fa-check-circle"></i>
                            </div>
                            <div>
                                <p class="bilai-dash-stat-label">Delivered Order</p>
                                <p class="bilai-dash-stat-value">{{ $deliveredOrders }}</p>
                            </div>
                        </div>

                        {{-- Pending Order (highlighted) --}}
                        <div class="bilai-dash-stat-card bilai-dash-stat-card--pending">
                            <div class="bilai-dash-stat-icon-wrap">
                                {{-- Replace SVG icon later --}}
                                <i class="fa fa-spinner"></i>
                            </div>
                            <div>
                                <p class="bilai-dash-stat-label">Pending Order</p>
                                <p class="bilai-dash-stat-value">{{ $pendingOrders }}</p>
                            </div>
                        </div>

                        {{-- Processing Order --}}
                        <div class="bilai-dash-stat-card">
                            <div class="bilai-dash-stat-icon-wrap">
                                {{-- Replace SVG icon later --}}
                                <i class="fa fa-exchange"></i>
                            </div>
                            <div>
                                <p class="bilai-dash-stat-label">Processing Order</p>
                                <p class="bilai-dash-stat-value">{{ $processingOrders }}</p>
                            </div>
                        </div>

                        {{-- Active Coupon --}}
                        <div class="bilai-dash-stat-card">
                            <div class="bilai-dash-stat-icon-wrap">
                                {{-- Replace SVG icon later --}}
                                <i class="fa fa-ticket"></i>
                            </div>
                            <div>
                                <p class="bilai-dash-stat-label">Active Coupon</p>
                                <p class="bilai-dash-stat-value">00</p>
                                {{-- Replace with real coupon count when implemented --}}
                            </div>
                        </div>

                    </div>
                </div>

                {{-- Recent Orders --}}
                <div class="bilai-dash-main-card">
                    <div class="bilai-dash-section-hdr">
                        <h3 class="bilai-dash-section-title">Recent Orders</h3>
                        <a href="{{ route('customer.orders') }}" class="bilai-dash-view-all">View All</a>
                    </div>
                    <div class="table-responsive">
                        <table class="bilai-dash-table">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Date</th>
                                    <th>Product</th>
                                    <th>Total</th>
                                    <th>Payment</th>
                                    <th>Status</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentOrders as $order)
                                    @php
                                        $firstProduct = $order->orderdetails->first();
                                        $productName  = $firstProduct && $firstProduct->product
                                            ? Str::limit($firstProduct->product->name, 30) : 'N/A';
                                        $payment  = $order->payment;
                                        $isPaid   = $payment && in_array(
                                            strtolower($payment->payment_status ?? ''), ['paid', 'success']
                                        );
                                        if ($order->order_status == '6') {
                                            $sLabel = 'Delivered'; $sCls = 'bd-badge-green';
                                        } elseif ($order->order_status == '11') {
                                            $sLabel = 'Cancelled'; $sCls = 'bd-badge-red';
                                        } elseif (in_array($order->order_status, ['3','4','5'])) {
                                            $sLabel = 'Shipped'; $sCls = 'bd-badge-orange';
                                        } else {
                                            $sLabel = 'Processing'; $sCls = 'bd-badge-blue';
                                        }
                                    @endphp
                                    <tr>
                                        <td><span class="bilai-dash-order-id">#{{ $order->invoice_id ?? $order->id }}</span></td>
                                        <td class="bilai-dash-td-muted">{{ $order->created_at->format('d M, Y') }}</td>
                                        <td>{{ $productName }}</td>
                                        <td><strong>৳{{ number_format($order->amount, 0) }}</strong></td>
                                        <td>
                                            <span class="bd-badge {{ $isPaid ? 'bd-badge-green' : 'bd-badge-red' }}">
                                                {{ $isPaid ? 'Paid' : 'Unpaid' }}
                                            </span>
                                        </td>
                                        <td><span class="bd-badge {{ $sCls }}">{{ $sLabel }}</span></td>
                                        <td>
                                            <a href="{{ route('customer.invoice', ['id' => $order->id]) }}"
                                               class="bilai-dash-icon-link" title="View Invoice">
                                                <i class="fa fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center bilai-dash-empty">No orders found</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Recommended Products --}}
                @if($recommendedProducts->count() > 0)
                <div class="bilai-dash-main-card">
                    <div class="bilai-dash-section-hdr">
                        <h3 class="bilai-dash-section-title">Recommended For You</h3>
                    </div>
                    <div class="bilai-dash-products-grid">
                        @foreach($recommendedProducts as $product)
                            @php
                                $discount = 0;
                                if ($product->old_price && $product->new_price && $product->old_price > $product->new_price) {
                                    $discount = round((($product->old_price - $product->new_price) / $product->old_price) * 100);
                                }
                            @endphp
                            <div class="bilai-dash-product-card">
                                <div class="bilai-dash-product-img-wrap">
                                    <a href="{{ route('product', $product->slug ?? $product->id) }}">
                                        <img src="{{ asset($product->image->image ?? 'public/uploads/default/no-image.png') }}"
                                             onerror="this.src='{{ asset('public/uploads/default/no-image.png') }}'"
                                             alt="{{ $product->name }}">
                                    </a>
                                    @if($discount > 0)
                                        <span class="bilai-dash-product-badge">{{ $discount }}% OFF</span>
                                    @endif
                                </div>
                                <div class="bilai-dash-product-body">
                                    <h4 class="bilai-dash-product-name">
                                        <a href="{{ route('product', $product->slug ?? $product->id) }}">{{ $product->name }}</a>
                                    </h4>
                                    <div class="bilai-dash-product-price">
                                        @if($product->old_price && $product->old_price > $product->new_price)
                                            <span class="bilai-dash-old-price">৳{{ number_format($product->old_price, 0) }}</span>
                                        @endif
                                        <span class="bilai-dash-new-price">৳{{ number_format($product->new_price ?? 0, 0) }}</span>
                                    </div>
                                    <a href="{{ route('product', $product->slug ?? $product->id) }}"
                                       class="bilai-dash-order-btn">Order Now</a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                @endif

            </main>
        </div>
    </div>
</div>

@endsection
