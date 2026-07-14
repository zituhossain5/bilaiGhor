@php
use Illuminate\Support\Facades\Auth;
use App\Models\Order;

$customer            = Auth::guard('customer')->user();
$customerId          = $customer->id;
$pendingOrdersCount  = Order::where('customer_id', $customerId)->whereNotIn('order_status', ['6', '11'])->count();
$totalOrderAmount    = Order::where('customer_id', $customerId)->sum('amount');
$profileImage        = $customer->image ? asset($customer->image) : null;
$customerInitial     = strtoupper(substr($customer->name ?? 'U', 0, 1));

// Active tab from controller (all | confirmed | processing | cancelled)
$activeTab = $activeTab ?? 'all';
@endphp

@extends('frontEnd.layouts.master')
@section('title', 'My Orders | ' . ($customer->name ?? 'Account'))

@push('css')
<style>
/* BilaiGhor Customer Orders Fix Start */

:root {
    --bilai-dash-primary:      var(--bilai-primary,      #e8861a);
    --bilai-dash-primary-dark: var(--bilai-primary-dark, #c96f00);
    --bilai-dash-cream:        #FFF8EC;
    --bilai-dash-border:       #E8CDA5;
    --bilai-dash-text:         #2B1A10;
    --bilai-dash-muted:        #77706A;
    --bilai-dash-radius:       12px;
    --bilai-dash-radius-sm:    8px;
}

/* ── Page ── */
.bilai-dash-page { background: #f5f5f0; min-height: 72vh; padding: 20px 0 52px; }

/* ── Breadcrumb ── */
.bilai-dash-bc { display: flex; align-items: center; gap: 5px; font-size: 12.5px; margin-bottom: 18px; flex-wrap: wrap; }
.bilai-dash-bc a { color: var(--bilai-dash-muted); text-decoration: none; }
.bilai-dash-bc a:hover { color: var(--bilai-dash-primary); }
.bilai-dash-bc-sep    { color: #c0b0a0; font-size: 11px; }
.bilai-dash-bc-active { color: var(--bilai-dash-primary); font-weight: 600; }

/* ── Two-column grid ── */
.bilai-dash-layout { display: grid; grid-template-columns: 248px 1fr; gap: 20px; align-items: start; }

/* ── Sidebar column: two separate stacked cards ── */
.bilai-dash-sidebar { position: sticky; top: 90px; display: flex; flex-direction: column; gap: 14px; }
.bilai-card {
    background: #fff;
    border: 1px solid var(--bilai-dash-border);
    border-radius: var(--bilai-dash-radius);
    overflow: hidden;
}

/* ── Profile card ── */
.bilai-dash-profile-box { padding: 18px 16px 16px; }
.bilai-dash-profile-row { display: flex; align-items: center; gap: 12px; }
.bilai-dash-avatar {
    width: 56px; height: 56px; border-radius: 50%; object-fit: cover;
    border: 2px solid var(--bilai-dash-border); flex-shrink: 0;
}
.bilai-dash-avatar-placeholder {
    width: 56px; height: 56px; border-radius: 50%;
    background: var(--bilai-dash-primary); color: #fff;
    font-size: 22px; font-weight: 700;
    display: flex; align-items: center; justify-content: center; flex-shrink: 0;
    text-transform: uppercase;
}
.bilai-dash-profile-info { flex: 1; min-width: 0; }
.bilai-dash-profile-name {
    font-size: 14px; font-weight: 700; color: var(--bilai-dash-text);
    margin: 0 0 2px; line-height: 1.3; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
    text-transform: capitalize;
}
.bilai-dash-profile-sub {
    font-size: 12px; color: var(--bilai-dash-muted); margin: 0;
    overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
}
.bilai-dash-profile-divider { border: none; border-top: 1px solid var(--bilai-dash-border); margin: 0 0 12px; }

/* RP / TK row */
.bilai-dash-rp-row { display: flex; align-items: center; justify-content: center; gap: 10px; }
.bilai-dash-rp-item { display: flex; align-items: center; gap: 5px; font-size: 12px; font-weight: 600; color: var(--bilai-dash-text); }
.bilai-dash-rp-icon { color: var(--bilai-dash-primary); font-size: 13px; }
.bilai-dash-rp-sep-icon {
    width: 24px; height: 24px; border-radius: 50%;
    background: var(--bilai-dash-cream); border: 1px solid var(--bilai-dash-border);
    display: flex; align-items: center; justify-content: center;
    color: var(--bilai-dash-muted); font-size: 10px; flex-shrink: 0;
}

/* ── Menu card ── */
.bilai-dash-nav { padding: 6px 0 8px; }
.bilai-dash-nav-title {
    font-size: 11px; font-weight: 700; letter-spacing: 0.06em;
    text-transform: uppercase; color: var(--bilai-dash-muted);
    padding: 12px 16px 8px; margin: 0;
}
.bilai-dash-nav-item {
    display: flex; align-items: center; gap: 10px;
    padding: 9px 16px; color: var(--bilai-dash-text);
    font-size: 13.5px; font-weight: 500; text-decoration: none;
    border-left: 3px solid transparent;
    transition: background 0.12s, color 0.12s; line-height: 1.3;
}
.bilai-dash-nav-item:hover { background: var(--bilai-dash-cream); color: var(--bilai-dash-primary); text-decoration: none; }
.bilai-dash-nav-item.active {
    background: var(--bilai-dash-cream); color: var(--bilai-dash-primary);
    border-left-color: var(--bilai-dash-primary); font-weight: 600;
}
.bilai-dash-nav-icon { width: 16px; text-align: center; flex-shrink: 0; font-size: 13px; opacity: 0.7; }
.bilai-dash-nav-item.active .bilai-dash-nav-icon,
.bilai-dash-nav-item:hover .bilai-dash-nav-icon { opacity: 1; }
.bilai-dash-nav-badge {
    margin-left: auto; background: #e53935; color: #fff;
    font-size: 10px; font-weight: 700; padding: 1px 6px;
    border-radius: 100px; line-height: 1.5;
}
.bilai-dash-nav-sep { border: none; border-top: 1px solid var(--bilai-dash-border); margin: 4px 0; }
.bilai-dash-nav-item--logout { color: #c0392b; }
.bilai-dash-nav-item--logout .bilai-dash-nav-icon { opacity: 0.8; }
.bilai-dash-nav-item--logout:hover { background: #fff5f5; color: #a93226; }

/* ── Orders content card ── */
.bilai-ord-card { padding: 22px 22px 24px; }
.bilai-ord-card-title {
    font-size: 17px; font-weight: 700; color: var(--bilai-dash-text);
    margin: 0 0 16px;
}

/* ── Tabs (GET links) ── */
.bilai-ord-tabs { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 18px; }
.bilai-ord-tab {
    padding: 6px 16px; font-size: 13px; font-weight: 500;
    border: 1px solid var(--bilai-dash-border); border-radius: 100px;
    background: #fff; color: var(--bilai-dash-muted);
    cursor: pointer; transition: all 0.13s; user-select: none;
    text-decoration: none; display: inline-block;
}
.bilai-ord-tab:hover { border-color: var(--bilai-dash-primary); color: var(--bilai-dash-primary); text-decoration: none; }
.bilai-ord-tab.active {
    background: var(--bilai-dash-primary); border-color: var(--bilai-dash-primary);
    color: #fff; font-weight: 600;
}

/* ── Table ── */
.bilai-ord-table-wrap { overflow-x: auto; }
.bilai-ord-table { width: 100%; border-collapse: collapse; }
.bilai-ord-table thead tr { border-bottom: 1px solid var(--bilai-dash-border); }
.bilai-ord-table th {
    padding: 10px 12px; font-size: 12px; font-weight: 600;
    color: var(--bilai-dash-muted); text-align: left;
    white-space: nowrap; background: var(--bilai-dash-cream);
}
.bilai-ord-table th:first-child { border-radius: var(--bilai-dash-radius-sm) 0 0 var(--bilai-dash-radius-sm); }
.bilai-ord-table th:last-child  { border-radius: 0 var(--bilai-dash-radius-sm) var(--bilai-dash-radius-sm) 0; }
.bilai-ord-table td {
    padding: 13px 12px; font-size: 13.5px;
    color: var(--bilai-dash-text); vertical-align: middle;
    border-bottom: 1px solid #f0e8d8;
}
.bilai-ord-table tbody tr:last-child td { border-bottom: none; }
.bilai-ord-table tbody tr:hover { background: #fffdf8; }

/* Status badges */
.bilai-ord-badge {
    display: inline-block; padding: 3px 10px; border-radius: 100px;
    font-size: 11.5px; font-weight: 600; white-space: nowrap;
}
.bilai-ord-badge--confirmed  { background: #e8f5e9; color: #2e7d32; }
.bilai-ord-badge--pending    { background: #e3f2fd; color: #1565c0; }
.bilai-ord-badge--processing { background: #fff3e0; color: #e65100; }
.bilai-ord-badge--cancelled  { background: #fce4ec; color: #b71c1c; }
.bilai-ord-badge--default    { background: #f5f5f5; color: #616161; }

/* Action buttons */
.bilai-ord-btn-view {
    display: inline-block; padding: 6px 14px;
    background: var(--bilai-dash-primary); color: #fff;
    border-radius: 100px; font-size: 12px; font-weight: 600;
    text-decoration: none; transition: background 0.13s; white-space: nowrap;
}
.bilai-ord-btn-view:hover { background: var(--bilai-dash-primary-dark, #c96f00); color: #fff; text-decoration: none; }
.bilai-ord-btn-sm {
    display: inline-flex; align-items: center; justify-content: center;
    width: 28px; height: 28px; border-radius: 6px;
    font-size: 11px; text-decoration: none; transition: opacity 0.13s; border: none;
}
.bilai-ord-btn-sm:hover { opacity: 0.85; }
.bilai-ord-actions { display: flex; align-items: center; gap: 5px; flex-wrap: nowrap; }

/* Product name */
.bilai-ord-product-name { max-width: 180px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; display: block; }

/* Empty state — compact, aligned inside card */
.bilai-ord-empty { text-align: center; padding: 32px 16px; }
.bilai-ord-empty-icon {
    width: 52px; height: 52px; border-radius: 50%;
    background: var(--bilai-dash-cream); border: 1px solid var(--bilai-dash-border);
    display: inline-flex; align-items: center; justify-content: center;
    font-size: 20px; color: var(--bilai-dash-muted); margin-bottom: 10px;
}
.bilai-ord-empty-title { font-size: 14px; font-weight: 600; color: var(--bilai-dash-text); margin: 0 0 3px; }
.bilai-ord-empty-text  { font-size: 12.5px; color: var(--bilai-dash-muted); margin: 0; }

/* Pagination */
.bilai-ord-pagination { display: flex; align-items: center; justify-content: center; gap: 6px; flex-wrap: wrap; margin-top: 20px; }
.bilai-ord-page-btn {
    padding: 6px 12px; border: 1px solid var(--bilai-dash-border);
    border-radius: var(--bilai-dash-radius-sm); font-size: 13px;
    color: var(--bilai-dash-text); background: #fff; text-decoration: none;
    transition: all 0.12s;
}
.bilai-ord-page-btn:hover { border-color: var(--bilai-dash-primary); color: var(--bilai-dash-primary); text-decoration: none; }
.bilai-ord-page-btn.active { background: var(--bilai-dash-primary); border-color: var(--bilai-dash-primary); color: #fff; font-weight: 600; }
.bilai-ord-page-btn.disabled { background: #f5f5f0; color: #ccc; pointer-events: none; }

/* ── Responsive ── */
@media (max-width: 1199px) { .bilai-dash-layout { grid-template-columns: 228px 1fr; gap: 16px; } }
@media (max-width: 991px)  { .bilai-dash-layout { grid-template-columns: 1fr; } .bilai-dash-sidebar { position: static; } }
@media (max-width: 767px)  { .bilai-ord-card { padding: 16px 14px 18px; } }

/* BilaiGhor Customer Orders Fix End */
</style>
@endpush

@section('content')
<div class="bilai-dash-page">
    <div class="container">

        {{-- Breadcrumb --}}
        <nav class="bilai-dash-bc" aria-label="breadcrumb">
            <a href="{{ route('home') }}">Home</a>
            <span class="bilai-dash-bc-sep">›</span>
            <span>Profile</span>
            <span class="bilai-dash-bc-sep">›</span>
            <span class="bilai-dash-bc-active">Orders</span>
        </nav>

        <div class="bilai-dash-layout">

            {{-- ────────────── LEFT SIDEBAR — two separate cards ────────────── --}}
            <aside class="bilai-dash-sidebar">

                {{-- Card 1: Profile --}}
                <div class="bilai-card">
                    <div class="bilai-dash-profile-box">
                        <div class="bilai-dash-profile-row">
                            @if($profileImage)
                                <img src="{{ $profileImage }}"
                                     onerror="this.style.display='none';this.nextElementSibling.style.display='flex';"
                                     class="bilai-dash-avatar" alt="{{ $customer->name }}">
                                <div class="bilai-dash-avatar-placeholder" style="display:none;">{{ $customerInitial }}</div>
                            @else
                                <div class="bilai-dash-avatar-placeholder">{{ $customerInitial }}</div>
                            @endif
                            <div class="bilai-dash-profile-info">
                                <p class="bilai-dash-profile-name">{{ $customer->name ?? 'Customer' }}</p>
                                <p class="bilai-dash-profile-sub">{{ $customer->phone ?? $customer->email ?? '' }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Card 2: My Account menu --}}
                <div class="bilai-card">
                    <nav class="bilai-dash-nav">
                        <p class="bilai-dash-nav-title">My Account</p>

                        <a href="{{ route('customer.account') }}"
                           class="bilai-dash-nav-item {{ request()->is('customer/account') ? 'active' : '' }}">
                            {{-- Replace sidebar SVG icon later --}}
                            <span class="bilai-dash-nav-icon"><i class="fa fa-home"></i></span>
                            Dashboard
                        </a>

                        <a href="{{ route('customer.orders') }}"
                           class="bilai-dash-nav-item {{ request()->is('customer/orders') ? 'active' : '' }}">
                            {{-- Replace sidebar SVG icon later --}}
                            <span class="bilai-dash-nav-icon"><i class="fa fa-shopping-bag"></i></span>
                            Orders
                            @if($pendingOrdersCount > 0)
                                <span class="bilai-dash-nav-badge">{{ $pendingOrdersCount }}</span>
                            @endif
                        </a>

                        <a href="{{ route('customer.profile_edit') }}"
                           class="bilai-dash-nav-item {{ request()->is('customer/profile-edit') ? 'active' : '' }}">
                            {{-- Replace sidebar SVG icon later --}}
                            <span class="bilai-dash-nav-icon"><i class="fa fa-user"></i></span>
                            Profile
                        </a>

                        <a href="{{ route('customer.wishlist') }}" class="bilai-dash-nav-item">
                            {{-- Replace sidebar SVG icon later --}}
                            <span class="bilai-dash-nav-icon"><i class="fa fa-heart-o"></i></span>
                            Wishlist
                        </a>

                        <a href="{{ route('customer.addresses') }}"
                           class="bilai-dash-nav-item {{ request()->is('customer/addresses') ? 'active' : '' }}">
                            {{-- Replace sidebar SVG icon later --}}
                            <span class="bilai-dash-nav-icon"><i class="fa fa-map-marker"></i></span>
                            Addresses
                        </a>

                        <a href="#" class="bilai-dash-nav-item">
                            {{-- Replace sidebar SVG icon later --}}
                            <span class="bilai-dash-nav-icon"><i class="fa fa-ticket"></i></span>
                            Coupon
                        </a>

                        {{-- Gift Cards removed from sidebar UI per design (backend untouched) --}}

                        <a href="{{ route('customer.rewards') }}"
                           class="bilai-dash-nav-item {{ request()->is('customer/rewards') ? 'active' : '' }}">
                            {{-- Replace sidebar SVG icon later --}}
                            <span class="bilai-dash-nav-icon"><i class="fa fa-star-o"></i></span>
                            Reward Points
                        </a>

                        <a href="{{ route('customer.order_track') }}"
                           class="bilai-dash-nav-item {{ request()->is('customer/order-track*') ? 'active' : '' }}">
                            {{-- Replace sidebar SVG icon later --}}
                            <span class="bilai-dash-nav-icon"><i class="fa fa-truck"></i></span>
                            Track Order
                        </a>

                        <a href="{{ route('customer.refunds') }}"
                           class="bilai-dash-nav-item {{ request()->is('customer/refunds*') ? 'active' : '' }}">
                            {{-- Replace sidebar SVG icon later --}}
                            <span class="bilai-dash-nav-icon"><i class="fa fa-undo"></i></span>
                            Return Request
                        </a>

                        <a href="{{ route('complaint') }}"
                           class="bilai-dash-nav-item {{ request()->is('complaint') ? 'active' : '' }}">
                            {{-- Replace sidebar SVG icon later --}}
                            <span class="bilai-dash-nav-icon"><i class="fa fa-headphones"></i></span>
                            Support Ticket
                        </a>

                        <hr class="bilai-dash-nav-sep">

                        <a href="{{ route('customer.logout') }}"
                           onclick="event.preventDefault(); document.getElementById('bilai-ord-logout-form').submit();"
                           class="bilai-dash-nav-item bilai-dash-nav-item--logout">
                            {{-- Replace sidebar SVG icon later --}}
                            <span class="bilai-dash-nav-icon"><i class="fa fa-sign-out"></i></span>
                            Logout
                        </a>
                        <form id="bilai-ord-logout-form" action="{{ route('customer.logout') }}" method="POST" style="display:none;">
                            @csrf
                        </form>
                    </nav>
                </div>
            </aside>

            {{-- ────────────── RIGHT CONTENT ───────────────── --}}
            <main>
                <div class="bilai-card bilai-ord-card">
                    <h2 class="bilai-ord-card-title">My Orders</h2>

                    {{-- Status Tabs — server-side GET filter --}}
                    <div class="bilai-ord-tabs">
                        <a href="{{ route('customer.orders') }}"
                           class="bilai-ord-tab {{ $activeTab === 'all' ? 'active' : '' }}">All</a>
                        <a href="{{ route('customer.orders', ['status' => 'confirmed']) }}"
                           class="bilai-ord-tab {{ $activeTab === 'confirmed' ? 'active' : '' }}">Confirmed</a>
                        <a href="{{ route('customer.orders', ['status' => 'processing']) }}"
                           class="bilai-ord-tab {{ $activeTab === 'processing' ? 'active' : '' }}">Processing</a>
                        <a href="{{ route('customer.orders', ['status' => 'cancelled']) }}"
                           class="bilai-ord-tab {{ $activeTab === 'cancelled' ? 'active' : '' }}">Cancelled</a>
                    </div>

                    @if($orders->count() > 0)
                        <div class="bilai-ord-table-wrap">
                            <table class="bilai-ord-table">
                                <thead>
                                    <tr>
                                        {{-- Replace SVG icons later --}}
                                        <th><i class="fa fa-list-alt" style="margin-right:5px;opacity:.6;"></i> Order</th>
                                        <th><i class="fa fa-calendar-o" style="margin-right:5px;opacity:.6;"></i> Date</th>
                                        <th><i class="fa fa-cube" style="margin-right:5px;opacity:.6;"></i> Product</th>
                                        <th><i class="fa fa-info-circle" style="margin-right:5px;opacity:.6;"></i> Status</th>
                                        <th><i class="fa fa-money" style="margin-right:5px;opacity:.6;"></i> Total</th>
                                        <th><i class="fa fa-pencil" style="margin-right:5px;opacity:.6;"></i> Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                @foreach($orders as $value)
                                    @php
                                        // ── Payment logic (preserved from original) ──
                                        $payment_record = \App\Models\Payment::where('order_id', $value->id)->orderBy('id', 'desc')->first();
                                        $gateway_status = $payment_record ? strtolower(trim($payment_record->payment_status)) : '';
                                        $payment_method = $payment_record ? strtolower(trim($payment_record->payment_method)) : '';
                                        $admin_status   = strtolower(trim($value->payment_status ?? ''));
                                        $grand_total    = $value->amount;
                                        $paid_amount    = 0;

                                        if ($payment_record && !in_array($gateway_status, ['failed', 'cancel', 'cancelled', 'rejected'])) {
                                            $paid_amount = $payment_record->amount;
                                        }

                                        $is_cod             = in_array($payment_method, ['cod', 'cash', 'cash_on_delivery']);
                                        $order_status_slug  = strtolower(trim($value->status->slug ?? $value->status->name ?? ''));
                                        $is_order_completed = in_array($order_status_slug, ['completed', 'delivered']) || in_array($admin_status, ['completed', 'delivered']);

                                        if ($is_cod && !$is_order_completed && $paid_amount >= $grand_total) {
                                            $paid_amount = 0;
                                        }
                                        if ($is_order_completed) {
                                            $paid_amount = $grand_total;
                                        } elseif (($paid_amount == 0 || !$payment_record) && in_array($admin_status, ['paid', 'success', 'approved'])) {
                                            $paid_amount = $grand_total;
                                        }

                                        $due_amount    = max(0, $grand_total - $paid_amount);
                                        $is_failed     = ($paid_amount == 0 && in_array($gateway_status, ['failed', 'cancel', 'cancelled']));

                                        $hasPendingRefund = method_exists($value, 'hasPendingRefund') ? $value->hasPendingRefund() : false;
                                        $canRefund        = ($value->order_status != 11 && $paid_amount > 0 && !$hasPendingRefund);
                                        $existingRefund   = \App\Models\Refund::where('order_id', $value->id)
                                                                ->whereIn('status', ['pending', 'approved'])->first();

                                        // ── Status badge — color by real order_status id, text from real name ──
                                        $os        = (string) $value->order_status;
                                        $badgeText = $value->status->name ?? 'Pending';
                                        if ($os === '11') {
                                            $badgeClass = 'bilai-ord-badge--cancelled';
                                        } elseif ($os === '6') {
                                            $badgeClass = 'bilai-ord-badge--confirmed';
                                        } elseif (in_array($os, ['2', '3', '5'])) {
                                            $badgeClass = 'bilai-ord-badge--processing';
                                        } elseif (in_array($os, ['1', '8'])) {
                                            $badgeClass = 'bilai-ord-badge--pending';
                                        } else {
                                            $badgeClass = 'bilai-ord-badge--default';
                                        }

                                        // ── First product name ──
                                        $firstDetail      = $value->orderdetails->first();
                                        $firstProductName = $firstDetail->product_name ?? ($firstDetail->product->name ?? 'N/A');
                                        $moreItems        = max(0, $value->orderdetails->count() - 1);
                                    @endphp

                                    <tr>
                                        <td style="font-weight:600;">#{{ $value->invoice_id ?? $value->id }}</td>
                                        <td style="color:var(--bilai-dash-muted); white-space:nowrap;">
                                            {{ $value->created_at->format('M j, Y') }}
                                        </td>
                                        <td>
                                            <span class="bilai-ord-product-name" title="{{ $firstProductName }}">
                                                {{ $firstProductName }}
                                            </span>
                                            @if($moreItems > 0)
                                                <span style="font-size:11px; color:var(--bilai-dash-muted);">+{{ $moreItems }} more</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="bilai-ord-badge {{ $badgeClass }}">{{ $badgeText }}</span>
                                        </td>
                                        <td style="font-weight:600; white-space:nowrap;">
                                            ৳{{ number_format($grand_total, 0) }}
                                        </td>
                                        <td>
                                            <div class="bilai-ord-actions">
                                                <a href="{{ route('customer.order_details', $value->id) }}"
                                                   class="bilai-ord-btn-view">View Details</a>

                                                @if($value->admin_note)
                                                    <a href="{{ route('customer.order_note', ['id' => $value->id]) }}"
                                                       class="bilai-ord-btn-sm" style="background:#eff6ff; color:#1d4ed8;" title="Admin Note">
                                                        {{-- Replace SVG icon later --}}
                                                        <i class="fa fa-sticky-note-o"></i>
                                                    </a>
                                                @endif

                                                @if($canRefund)
                                                    <a href="{{ route('customer.refunds.create', $value->id) }}"
                                                       class="bilai-ord-btn-sm" style="background:#fefce8; color:#a16207;" title="Request Refund">
                                                        {{-- Replace SVG icon later --}}
                                                        <i class="fa fa-undo"></i>
                                                    </a>
                                                @elseif($existingRefund)
                                                    <a href="{{ route('customer.refunds.show', $existingRefund->id) }}"
                                                       class="bilai-ord-btn-sm" style="background:#faf5ff; color:#7e22ce;" title="View Refund">
                                                        {{-- Replace SVG icon later --}}
                                                        <i class="fa fa-eye"></i>
                                                    </a>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>

                        {{-- Pagination (status filter preserved via withQueryString) --}}
                        @if(method_exists($orders, 'hasPages') && $orders->hasPages())
                            <div class="bilai-ord-pagination">
                                @if($orders->onFirstPage())
                                    <span class="bilai-ord-page-btn disabled"><i class="fa fa-chevron-left"></i></span>
                                @else
                                    <a href="{{ $orders->previousPageUrl() }}" class="bilai-ord-page-btn"><i class="fa fa-chevron-left"></i></a>
                                @endif

                                @php
                                    $cp = $orders->currentPage();
                                    $lp = $orders->lastPage();
                                    $sp = max(1, $cp - 2);
                                    $ep = min($lp, $cp + 2);
                                @endphp

                                @if($sp > 1)
                                    <a href="{{ $orders->url(1) }}" class="bilai-ord-page-btn">1</a>
                                    @if($sp > 2)<span style="color:var(--bilai-dash-muted);padding:0 2px;">…</span>@endif
                                @endif

                                @for($pg = $sp; $pg <= $ep; $pg++)
                                    @if($pg == $cp)
                                        <span class="bilai-ord-page-btn active">{{ $pg }}</span>
                                    @else
                                        <a href="{{ $orders->url($pg) }}" class="bilai-ord-page-btn">{{ $pg }}</a>
                                    @endif
                                @endfor

                                @if($ep < $lp)
                                    @if($ep < $lp - 1)<span style="color:var(--bilai-dash-muted);padding:0 2px;">…</span>@endif
                                    <a href="{{ $orders->url($lp) }}" class="bilai-ord-page-btn">{{ $lp }}</a>
                                @endif

                                @if($orders->hasMorePages())
                                    <a href="{{ $orders->nextPageUrl() }}" class="bilai-ord-page-btn"><i class="fa fa-chevron-right"></i></a>
                                @else
                                    <span class="bilai-ord-page-btn disabled"><i class="fa fa-chevron-right"></i></span>
                                @endif
                            </div>
                        @endif

                    @else
                        {{-- Empty state — compact --}}
                        <div class="bilai-ord-empty">
                            <div class="bilai-ord-empty-icon">
                                {{-- Replace SVG icon later --}}
                                <i class="fa fa-inbox"></i>
                            </div>
                            <p class="bilai-ord-empty-title">No orders found</p>
                            <p class="bilai-ord-empty-text">
                                @if($activeTab === 'all')
                                    You haven't placed any orders yet.
                                @else
                                    No orders match the selected filter.
                                @endif
                            </p>
                        </div>
                    @endif
                </div>
            </main>
        </div>
    </div>
</div>
@endsection
