@php
use Illuminate\Support\Facades\Auth;
use App\Models\Order;

$customer            = Auth::guard('customer')->user();
$customerId          = $customer->id;
$pendingOrdersCount  = Order::where('customer_id', $customerId)->whereNotIn('order_status', ['6', '11'])->count();
$totalOrderAmount    = Order::where('customer_id', $customerId)->sum('amount');
$profileImage        = $customer->image ? asset($customer->image) : null;
$customerInitial     = strtoupper(substr($customer->name ?? 'U', 0, 1));

$defaultAddressId    = optional($addresses->firstWhere('is_default', true))->id;
@endphp

@extends('frontEnd.layouts.master')
@section('title', 'My Addresses | ' . ($customer->name ?? 'Account'))

@push('css')
<style>
/* BilaiGhor Customer Addresses Start */
:root {
    --bilai-adr-primary:      var(--bilai-primary, #F28C00);
    --bilai-adr-primary-dark: var(--bilai-primary-dark, #c96f00);
    --bilai-adr-brown:        var(--bilai-brown, #3A1F0F);
    --bilai-adr-cream:        var(--bilai-cream, #FFF8EC);
    --bilai-adr-card:         #FFFDF8;
    --bilai-adr-border:       var(--bilai-border, #E8CDA5);
    --bilai-adr-text:         var(--bilai-text, #2B1A10);
    --bilai-adr-muted:        var(--bilai-muted, #77706A);
    --bilai-adr-radius:       14px;
}

.bilai-adr-page { background: #f5f5f0; min-height: 72vh; padding: 20px 0 52px; }

/* Breadcrumb */
.bilai-adr-bc { display: flex; align-items: center; gap: 5px; font-size: 12.5px; margin-bottom: 18px; flex-wrap: wrap; }
.bilai-adr-bc a { color: var(--bilai-adr-muted); text-decoration: none; }
.bilai-adr-bc a:hover { color: var(--bilai-adr-primary); }
.bilai-adr-bc-sep    { color: #c0b0a0; font-size: 11px; }
.bilai-adr-bc-active { color: var(--bilai-adr-primary); font-weight: 600; }

/* Layout */
.bilai-adr-layout { display: grid; grid-template-columns: 248px 1fr; gap: 20px; align-items: start; }

/* â”€â”€ Shared sidebar (same look as other account pages) â”€â”€ */
.bilai-adr-sidebar { position: sticky; top: 90px; display: flex; flex-direction: column; gap: 14px; }
.bilai-card { background: #fff; border: 1px solid var(--bilai-adr-border); border-radius: var(--bilai-adr-radius); overflow: hidden; }
.bilai-dash-profile-box { padding: 18px 16px 16px; }
.bilai-dash-profile-row { display: flex; align-items: center; gap: 12px; }
.bilai-dash-avatar { width: 56px; height: 56px; border-radius: 50%; object-fit: cover; border: 2px solid var(--bilai-adr-border); flex-shrink: 0; }
.bilai-dash-avatar-placeholder { width: 56px; height: 56px; border-radius: 50%; background: var(--bilai-adr-primary); color: #fff; font-size: 22px; font-weight: 700; display: flex; align-items: center; justify-content: center; flex-shrink: 0; text-transform: uppercase; }
.bilai-dash-profile-info { flex: 1; min-width: 0; }
.bilai-dash-profile-name { font-size: 14px; font-weight: 700; color: var(--bilai-adr-text); margin: 0 0 2px; line-height: 1.3; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; text-transform: capitalize; }
.bilai-dash-profile-sub { font-size: 12px; color: var(--bilai-adr-muted); margin: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.bilai-dash-profile-divider { border: none; border-top: 1px solid var(--bilai-adr-border); margin: 0 0 12px; }
.bilai-dash-rp-row { display: flex; align-items: center; justify-content: center; gap: 10px; }
.bilai-dash-rp-item { display: flex; align-items: center; gap: 5px; font-size: 12px; font-weight: 600; color: var(--bilai-adr-text); }
.bilai-dash-rp-icon { color: var(--bilai-adr-primary); font-size: 13px; }
.bilai-dash-rp-sep-icon { width: 24px; height: 24px; border-radius: 50%; background: var(--bilai-adr-cream); border: 1px solid var(--bilai-adr-border); display: flex; align-items: center; justify-content: center; color: var(--bilai-adr-muted); font-size: 10px; flex-shrink: 0; }
.bilai-dash-nav { padding: 6px 0 8px; }
.bilai-dash-nav-title { font-size: 11px; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; color: var(--bilai-adr-muted); padding: 12px 16px 8px; margin: 0; }
.bilai-dash-nav-item { display: flex; align-items: center; gap: 10px; padding: 9px 16px; color: var(--bilai-adr-text); font-size: 13.5px; font-weight: 500; text-decoration: none; border-left: 3px solid transparent; transition: background 0.12s, color 0.12s; line-height: 1.3; }
.bilai-dash-nav-item:hover { background: var(--bilai-adr-cream); color: var(--bilai-adr-primary); text-decoration: none; }
.bilai-dash-nav-item.active { background: var(--bilai-adr-cream); color: var(--bilai-adr-primary); border-left-color: var(--bilai-adr-primary); font-weight: 600; }
.bilai-dash-nav-icon { width: 16px; text-align: center; flex-shrink: 0; font-size: 13px; opacity: 0.7; }
.bilai-dash-nav-item.active .bilai-dash-nav-icon, .bilai-dash-nav-item:hover .bilai-dash-nav-icon { opacity: 1; }
.bilai-dash-nav-badge { margin-left: auto; background: #e53935; color: #fff; font-size: 10px; font-weight: 700; padding: 1px 6px; border-radius: 100px; line-height: 1.5; }
.bilai-dash-nav-sep { border: none; border-top: 1px solid var(--bilai-adr-border); margin: 4px 0; }
.bilai-dash-nav-item--logout { color: #c0392b; }
.bilai-dash-nav-item--logout .bilai-dash-nav-icon { opacity: 0.8; }
.bilai-dash-nav-item--logout:hover { background: #fff5f5; color: #a93226; }

/* â”€â”€ Content header â”€â”€ */
.bilai-adr-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 18px; flex-wrap: wrap; }
.bilai-adr-title { font-size: 19px; font-weight: 800; color: var(--bilai-adr-text); margin: 0; }
.bilai-adr-mkdefault { background: none; border: none; padding: 0; font-size: 13.5px; font-weight: 600; color: var(--bilai-adr-primary); cursor: pointer; }
.bilai-adr-mkdefault:hover { color: var(--bilai-adr-primary-dark); text-decoration: underline; }

/* â”€â”€ Cards grid â”€â”€ */
.bilai-adr-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 22px; }
.bilai-adr-card { background: var(--bilai-adr-card); border: 1px solid var(--bilai-adr-border); border-radius: 10px; overflow: hidden; display: flex; flex-direction: column; }
.bilai-adr-card-head { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 13px 18px; background: var(--bilai-adr-cream); border-bottom: 1px solid var(--bilai-adr-border); }
.bilai-adr-card-title { font-size: 14px; font-weight: 700; color: var(--bilai-adr-text); margin: 0; }
.bilai-adr-actions { display: flex; gap: 8px; }
.bilai-adr-btn-delete, .bilai-adr-btn-edit {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 6px 14px; border: none; border-radius: 8px;
    font-size: 12px; font-weight: 600; cursor: pointer; transition: 0.15s;
    text-decoration: none;
}
.bilai-adr-btn-delete { background: var(--bilai-adr-primary); color: #fff; }
.bilai-adr-btn-delete:hover { background: var(--bilai-adr-primary-dark); }
.bilai-adr-btn-edit { background: #241307; color: #fff; }
.bilai-adr-btn-edit:hover { background: var(--bilai-adr-brown); }
.bilai-adr-card-body { padding: 16px 18px 18px; flex: 1; display: flex; flex-direction: column; }
.bilai-adr-line { font-size: 13px; color: var(--bilai-adr-text); margin: 0 0 9px; line-height: 1.55; }
.bilai-adr-line.name { font-weight: 600; }
.bilai-adr-line.muted { color: var(--bilai-adr-muted); }
.bilai-adr-tags { display: flex; gap: 8px; flex-wrap: wrap; margin-top: auto; padding-top: 10px; }
.bilai-adr-tag-type {
    display: inline-flex; align-items: center; gap: 6px;
    background: var(--bilai-adr-primary); color: #fff;
    font-size: 11.5px; font-weight: 600; padding: 5px 13px; border-radius: 100px;
}
.bilai-adr-tag-default {
    display: inline-flex; align-items: center;
    background: var(--bilai-adr-cream); color: var(--bilai-adr-muted);
    border: 1px solid var(--bilai-adr-border);
    font-size: 11.5px; font-weight: 600; padding: 5px 13px; border-radius: 100px;
}

/* Add new address */
.bilai-adr-foot { display: flex; justify-content: flex-end; margin-top: 22px; }
.bilai-adr-add-btn {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 13px 28px; background: var(--bilai-adr-primary); color: #fff;
    border: none; border-radius: 10px; font-size: 14px; font-weight: 700;
    cursor: pointer; transition: 0.2s; text-decoration: none;
}
.bilai-adr-add-btn:hover { background: var(--bilai-adr-primary-dark); }

/* Empty state */
.bilai-adr-empty { background: var(--bilai-adr-card); border: 1px solid var(--bilai-adr-border); border-radius: 10px; text-align: center; padding: 44px 20px; }
.bilai-adr-empty-ic { width: 56px; height: 56px; border-radius: 50%; background: var(--bilai-adr-cream); border: 1px solid var(--bilai-adr-border); display: inline-flex; align-items: center; justify-content: center; font-size: 22px; color: var(--bilai-adr-muted); margin-bottom: 12px; }
.bilai-adr-empty h4 { font-size: 15px; font-weight: 700; color: var(--bilai-adr-text); margin: 0 0 5px; }
.bilai-adr-empty p { font-size: 13px; color: var(--bilai-adr-muted); margin: 0 0 18px; }

/* â”€â”€ Add/Edit modal â”€â”€ */
.bilai-adr-overlay { display: none; position: fixed; inset: 0; z-index: 1600; background: rgba(30,18,8,0.5); align-items: flex-start; justify-content: center; padding: 40px 16px; overflow-y: auto; }
.bilai-adr-overlay.open { display: flex; }
.bilai-adr-dialog { background: var(--bilai-adr-cream); width: 100%; max-width: 560px; border-radius: 14px; overflow: hidden; }
.bilai-adr-dialog-head { background: var(--bilai-adr-primary); color: #fff; display: flex; align-items: center; justify-content: space-between; padding: 15px 22px; }
.bilai-adr-dialog-head h5 { margin: 0; font-size: 15.5px; font-weight: 700; color: #fff; }
.bilai-adr-dialog-close { background: transparent; border: none; color: #fff; font-size: 20px; cursor: pointer; line-height: 1; padding: 4px; }
.bilai-adr-dialog-body { padding: 22px; }
.bilai-adr-field { margin-bottom: 14px; }
.bilai-adr-field label { display: block; font-size: 13px; font-weight: 600; color: var(--bilai-adr-text); margin-bottom: 6px; }
.bilai-adr-field label .req { color: #e04b4b; }
.bilai-adr-field input, .bilai-adr-field select, .bilai-adr-field textarea {
    width: 100%; border: 1px solid var(--bilai-adr-border); border-radius: 8px;
    padding: 10px 13px; font-size: 13.5px; color: var(--bilai-adr-text); background: #fff;
}
.bilai-adr-field input:focus, .bilai-adr-field select:focus, .bilai-adr-field textarea:focus { outline: none; border-color: var(--bilai-adr-primary); box-shadow: 0 0 0 3px rgba(242,140,0,0.10); }
.bilai-adr-field-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.bilai-adr-dialog-foot { display: flex; justify-content: flex-end; gap: 10px; padding-top: 6px; }

/* Responsive */
@media (max-width: 1199px) { .bilai-adr-layout { grid-template-columns: 228px 1fr; gap: 16px; } }
@media (max-width: 991px)  { .bilai-adr-layout { grid-template-columns: 1fr; } .bilai-adr-sidebar { position: static; } }
@media (max-width: 767px)  { .bilai-adr-grid { grid-template-columns: 1fr; gap: 14px; } .bilai-adr-field-row { grid-template-columns: 1fr; } }
/* BilaiGhor Customer Addresses End */

/* BilaiGhor Default Address Mode Start */
.bilai-adr-radio { display: none; width: 20px; height: 20px; accent-color: var(--bilai-adr-primary); cursor: pointer; flex-shrink: 0; }
.bilai-adr-savebar { display: none; justify-content: flex-end; gap: 12px; margin-top: 22px; }
.bilai-adr-cancel-btn {
    padding: 13px 30px; background: #f3ead9; color: var(--bilai-adr-text);
    border: 1px solid var(--bilai-adr-border); border-radius: 10px;
    font-size: 14px; font-weight: 600; cursor: pointer; transition: 0.15s;
}
.bilai-adr-cancel-btn:hover { border-color: var(--bilai-adr-primary); color: var(--bilai-adr-primary); }
.bilai-adr-save-btn {
    padding: 13px 36px; background: var(--bilai-adr-primary); color: #fff;
    border: none; border-radius: 10px; font-size: 14px; font-weight: 700;
    cursor: pointer; transition: 0.15s;
}
.bilai-adr-save-btn:hover { background: var(--bilai-adr-primary-dark); }

/* Selection mode toggles */
.bilai-adr-app.select-mode .bilai-adr-actions   { display: none; }
.bilai-adr-app.select-mode .bilai-adr-radio     { display: inline-block; }
.bilai-adr-app.select-mode .bilai-adr-mkdefault { visibility: hidden; }
.bilai-adr-app.select-mode .bilai-adr-foot      { display: none; }
.bilai-adr-app.select-mode .bilai-adr-savebar   { display: flex; }
.bilai-adr-app.select-mode .bilai-adr-tag-default { display: none; }
/* BilaiGhor Default Address Mode End */
</style>
@endpush

@section('content')
<div class="bilai-adr-page">
    <div class="container">

        {{-- Breadcrumb --}}
        <nav class="bilai-adr-bc" aria-label="breadcrumb">
            <a href="{{ route('home') }}">Home</a>
            <span class="bilai-adr-bc-sep">â€º</span>
            <a href="{{ route('customer.account') }}">Account</a>
            <span class="bilai-adr-bc-sep">â€º</span>
            <span class="bilai-adr-bc-active">Addresses</span>
        </nav>

        <div class="bilai-adr-layout">

            {{-- â•â•â•â•â•â•â•â•â•â•â• LEFT SIDEBAR â•â•â•â•â•â•â•â•â•â•â• --}}
            <aside class="bilai-adr-sidebar">
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
                        <a href="{{ route('customer.wishlist') }}" class="bilai-dash-nav-item">
                            {{-- Replace sidebar SVG icon later --}}
                            <span class="bilai-dash-nav-icon"><i class="fa fa-heart-o"></i></span> Wishlist
                        </a>
                        <a href="{{ route('customer.addresses') }}" class="bilai-dash-nav-item active">
                            {{-- Replace sidebar SVG icon later --}}
                            <span class="bilai-dash-nav-icon"><i class="fa fa-map-marker"></i></span> Addresses
                        </a>
                        <a href="{{ route('customer.rewards') }}" class="bilai-dash-nav-item">
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
                           onclick="event.preventDefault(); document.getElementById('bilai-adr-logout-form').submit();"
                           class="bilai-dash-nav-item bilai-dash-nav-item--logout">
                            {{-- Replace sidebar SVG icon later --}}
                            <span class="bilai-dash-nav-icon"><i class="fa fa-sign-out"></i></span> Logout
                        </a>
                        <form id="bilai-adr-logout-form" action="{{ route('customer.logout') }}" method="POST" style="display:none;">@csrf</form>
                    </nav>
                </div>
            </aside>

            {{-- â•â•â•â•â•â•â•â•â•â•â• RIGHT CONTENT â•â•â•â•â•â•â•â•â•â•â• --}}
            <main class="bilai-adr-app" id="bilai-adr-app">

                <div class="bilai-adr-head">
                    <h2 class="bilai-adr-title">My Addresses</h2>
                    @if($addresses->count() > 0)
                        <button type="button" class="bilai-adr-mkdefault" id="bilai-adr-mkdefault">Make Default Address</button>
                    @endif
                </div>

                @if($addresses->count() > 0)
                    <div class="bilai-adr-grid">
                        @foreach($addresses as $i => $addr)
                            <div class="bilai-adr-card">
                                <div class="bilai-adr-card-head">
                                    <p class="bilai-adr-card-title">Address {{ $i + 1 }}</p>

                                    {{-- Normal mode: Delete + Edit --}}
                                    <div class="bilai-adr-actions">
                                        <form action="{{ route('customer.addresses.delete', $addr->id) }}" method="POST"
                                              onsubmit="return confirm('Delete this address?');" style="display:inline;">
                                            @csrf
                                            <button type="submit" class="bilai-adr-btn-delete">
                                                {{-- Replace delete SVG icon later --}}
                                                <i class="fa fa-trash-o"></i> Delete
                                            </button>
                                        </form>
                                        <button type="button" class="bilai-adr-btn-edit bilai-afm-edit-open"
                                                data-id="{{ $addr->id }}"
                                                data-name="{{ $addr->name }}"
                                                data-mobile="{{ $addr->phone }}"
                                                data-email="{{ $addr->email }}"
                                                data-postcode="{{ $addr->post_code }}"
                                                data-district="{{ $addr->district_id }}"
                                                data-zone="{{ $addr->zone_id }}"
                                                data-address="{{ $addr->address }}">
                                            {{-- Replace edit SVG icon later --}}
                                            <i class="fa fa-pencil-square-o"></i> Edit
                                        </button>
                                    </div>

                                    {{-- Selection mode: radio --}}
                                    <input type="radio" class="bilai-adr-radio" name="default_address_radio"
                                           value="{{ $addr->id }}"
                                           data-default="{{ $addr->is_default ? '1' : '0' }}"
                                           @checked($addr->is_default)
                                           aria-label="Select as default address">
                                </div>
                                <div class="bilai-adr-card-body">
                                    <p class="bilai-adr-line name">{{ $addr->name }}</p>
                                    <p class="bilai-adr-line muted">{{ $addr->address }}</p>
                                    {{-- Zone, District â€” built from live relationships; each part shown only when present --}}
                                    @php
                                        $addrLocation = array_filter([
                                            optional($addr->zone)->name,
                                            optional($addr->district)->name,
                                        ]);
                                    @endphp
                                    @if(!empty($addrLocation))
                                        <p class="bilai-adr-line muted">{{ implode(', ', $addrLocation) }}</p>
                                    @endif
                                    @if($addr->post_code)
                                        <p class="bilai-adr-line muted">Post Code: {{ $addr->post_code }}</p>
                                    @endif
                                    <p class="bilai-adr-line muted">{{ $addr->phone }}</p>
                                    @if($addr->email)
                                        <p class="bilai-adr-line muted">{{ $addr->email }}</p>
                                    @endif
                                    <div class="bilai-adr-tags">
                                        @if($addr->label)
                                            <span class="bilai-adr-tag-type">
                                                {{-- Replace type SVG icon later --}}
                                                <i class="fa {{ strtolower($addr->label) === 'office' ? 'fa-briefcase' : 'fa-home' }}"></i>
                                                {{ $addr->label }}
                                            </span>
                                        @endif
                                        @if($addr->is_default)
                                            <span class="bilai-adr-tag-default">Default Address</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Normal mode footer --}}
                    <div class="bilai-adr-foot">
                        <button type="button" class="bilai-adr-add-btn bilai-afm-add-open">Add New Address</button>
                    </div>

                    {{-- Selection mode footer --}}
                    <div class="bilai-adr-savebar">
                        <button type="button" class="bilai-adr-cancel-btn" id="bilai-adr-cancel">Cancel</button>
                        <button type="button" class="bilai-adr-save-btn" id="bilai-adr-save">Save</button>
                    </div>

                    {{-- Hidden default-save form --}}
                    <form id="bilai-adr-default-form" action="{{ route('customer.addresses.default') }}" method="POST" style="display:none;">
                        @csrf
                        <input type="hidden" name="address_id" id="bilai-adr-default-id" value="">
                    </form>
                @else
                    {{-- Empty state --}}
                    <div class="bilai-adr-empty">
                        {{-- Replace empty-state SVG icon later --}}
                        <div class="bilai-adr-empty-ic"><i class="fa fa-map-marker"></i></div>
                        <h4>No saved addresses yet</h4>
                        <p>Add an address to speed up your checkout.</p>
                        <button type="button" class="bilai-adr-add-btn bilai-afm-add-open">Add New Address</button>
                    </div>
                @endif

            </main>
        </div>
    </div>
</div>

{{-- Reusable Add/Edit Address popup (shared with checkout) --}}
@include('frontEnd.layouts.customer.partials.address-form-modal')

@push('script')
<script>
/* Addresses page: default-selection mode (add/edit modal lives in the shared partial) */
(function () {
    var app = document.getElementById('bilai-adr-app');
    if (!app) return;

    var mkDefaultBtn    = document.getElementById('bilai-adr-mkdefault');
    var cancelBtn       = document.getElementById('bilai-adr-cancel');
    var saveBtn         = document.getElementById('bilai-adr-save');
    var defaultForm     = document.getElementById('bilai-adr-default-form');
    var defaultIdInput  = document.getElementById('bilai-adr-default-id');
    var radios          = Array.prototype.slice.call(document.querySelectorAll('.bilai-adr-radio'));

    // â”€â”€ Default-address selection mode â”€â”€
    function resetRadios() {
        radios.forEach(function (r) { r.checked = (r.getAttribute('data-default') === '1'); });
    }
    if (mkDefaultBtn) {
        mkDefaultBtn.addEventListener('click', function () {
            resetRadios();
            app.classList.add('select-mode');
        });
    }
    if (cancelBtn) {
        cancelBtn.addEventListener('click', function () {
            app.classList.remove('select-mode');
            resetRadios();
        });
    }
    if (saveBtn) {
        saveBtn.addEventListener('click', function () {
            var chosen = radios.filter(function (r) { return r.checked; })[0];
            if (!chosen) return;
            defaultIdInput.value = chosen.value;
            defaultForm.submit();
        });
    }
}());
</script>
@endpush

@endsection

