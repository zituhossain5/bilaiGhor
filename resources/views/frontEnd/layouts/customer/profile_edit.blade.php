@php
use Illuminate\Support\Facades\Auth;
use App\Models\Order;

$customer           = Auth::guard('customer')->user();
$customerId         = $customer->id;
$pendingOrdersCount = Order::where('customer_id', $customerId)->whereNotIn('order_status', ['6', '11'])->count();
$totalOrderAmount   = Order::where('customer_id', $customerId)->sum('amount');
$profileImage       = $profile_edit->image ? asset($profile_edit->image) : null;
$customerInitial    = strtoupper(substr($profile_edit->name ?? 'U', 0, 1));

$selectedDistrict = old('district_id', $profile_edit->district_id);
$selectedZone     = old('zone_id', $profile_edit->zone_id);
@endphp

@extends('frontEnd.layouts.master')
@section('title', 'Profile | ' . ($profile_edit->name ?? 'Account'))

@push('css')
<style>
/* BilaiGhor Customer Profile Edit Start */
:root {
    --bilai-prof-primary:      var(--bilai-primary, #F28C00);
    --bilai-prof-primary-dark: var(--bilai-primary-dark, #c96f00);
    --bilai-prof-brown:        var(--bilai-brown, #3A1F0F);
    --bilai-prof-cream:        var(--bilai-cream, #FFF8EC);
    --bilai-prof-card:         #FFFDF8;
    --bilai-prof-border:       var(--bilai-border, #E8CDA5);
    --bilai-prof-text:         var(--bilai-text, #2B1A10);
    --bilai-prof-muted:        var(--bilai-muted, #77706A);
    --bilai-prof-radius:       14px;
}

.bilai-prof-page { background: #f5f5f0; min-height: 72vh; padding: 20px 0 52px; }

/* Breadcrumb */
.bilai-prof-bc { display: flex; align-items: center; gap: 5px; font-size: 12.5px; margin-bottom: 18px; flex-wrap: wrap; }
.bilai-prof-bc a { color: var(--bilai-prof-muted); text-decoration: none; }
.bilai-prof-bc a:hover { color: var(--bilai-prof-primary); }
.bilai-prof-bc-sep    { color: #c0b0a0; font-size: 11px; }
.bilai-prof-bc-active { color: var(--bilai-prof-primary); font-weight: 600; }

/* Layout */
.bilai-prof-layout { display: grid; grid-template-columns: 248px 1fr; gap: 20px; align-items: start; }

/* ── Shared sidebar (same look as other account pages) ── */
.bilai-prof-sidebar { position: sticky; top: 90px; display: flex; flex-direction: column; gap: 14px; }
.bilai-card { background: #fff; border: 1px solid var(--bilai-prof-border); border-radius: var(--bilai-prof-radius); overflow: hidden; }
.bilai-dash-profile-box { padding: 18px 16px 16px; }
.bilai-dash-profile-row { display: flex; align-items: center; gap: 12px; margin-bottom: 14px; }
.bilai-dash-avatar { width: 56px; height: 56px; border-radius: 50%; object-fit: cover; border: 2px solid var(--bilai-prof-border); flex-shrink: 0; }
.bilai-dash-avatar-placeholder { width: 56px; height: 56px; border-radius: 50%; background: var(--bilai-prof-primary); color: #fff; font-size: 22px; font-weight: 700; display: flex; align-items: center; justify-content: center; flex-shrink: 0; text-transform: uppercase; }
.bilai-dash-profile-info { flex: 1; min-width: 0; }
.bilai-dash-profile-name { font-size: 14px; font-weight: 700; color: var(--bilai-prof-text); margin: 0 0 2px; line-height: 1.3; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; text-transform: capitalize; }
.bilai-dash-profile-sub { font-size: 12px; color: var(--bilai-prof-muted); margin: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.bilai-dash-profile-divider { border: none; border-top: 1px solid var(--bilai-prof-border); margin: 0 0 12px; }
.bilai-dash-rp-row { display: flex; align-items: center; justify-content: center; gap: 10px; }
.bilai-dash-rp-item { display: flex; align-items: center; gap: 5px; font-size: 12px; font-weight: 600; color: var(--bilai-prof-text); }
.bilai-dash-rp-icon { color: var(--bilai-prof-primary); font-size: 13px; }
.bilai-dash-rp-sep-icon { width: 24px; height: 24px; border-radius: 50%; background: var(--bilai-prof-cream); border: 1px solid var(--bilai-prof-border); display: flex; align-items: center; justify-content: center; color: var(--bilai-prof-muted); font-size: 10px; flex-shrink: 0; }
.bilai-dash-nav { padding: 6px 0 8px; }
.bilai-dash-nav-title { font-size: 11px; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; color: var(--bilai-prof-muted); padding: 12px 16px 8px; margin: 0; }
.bilai-dash-nav-item { display: flex; align-items: center; gap: 10px; padding: 9px 16px; color: var(--bilai-prof-text); font-size: 13.5px; font-weight: 500; text-decoration: none; border-left: 3px solid transparent; transition: background 0.12s, color 0.12s; line-height: 1.3; }
.bilai-dash-nav-item:hover { background: var(--bilai-prof-cream); color: var(--bilai-prof-primary); text-decoration: none; }
.bilai-dash-nav-item.active { background: var(--bilai-prof-cream); color: var(--bilai-prof-primary); border-left-color: var(--bilai-prof-primary); font-weight: 600; }
.bilai-dash-nav-icon { width: 16px; text-align: center; flex-shrink: 0; font-size: 13px; opacity: 0.7; }
.bilai-dash-nav-item.active .bilai-dash-nav-icon, .bilai-dash-nav-item:hover .bilai-dash-nav-icon { opacity: 1; }
.bilai-dash-nav-badge { margin-left: auto; background: #e53935; color: #fff; font-size: 10px; font-weight: 700; padding: 1px 6px; border-radius: 100px; line-height: 1.5; }
.bilai-dash-nav-sep { border: none; border-top: 1px solid var(--bilai-prof-border); margin: 4px 0; }
.bilai-dash-nav-item--logout { color: #c0392b; }
.bilai-dash-nav-item--logout .bilai-dash-nav-icon { opacity: 0.8; }
.bilai-dash-nav-item--logout:hover { background: #fff5f5; color: #a93226; }

/* ── Profile card ── */
.bilai-prof-card { background: var(--bilai-prof-card); border: 1px solid var(--bilai-prof-border); border-radius: var(--bilai-prof-radius); padding: 22px 30px 34px; }
.bilai-prof-card-title { font-size: 16px; font-weight: 700; color: var(--bilai-prof-text); text-align: center; margin: 0 0 16px; }
.bilai-prof-card-divider { border: none; border-top: 1px solid var(--bilai-prof-border); margin: 0 0 26px; }

/* Avatar uploader */
.bilai-prof-avatar-wrap { display: flex; flex-direction: column; align-items: center; margin-bottom: 26px; }
.bilai-prof-avatar-box { position: relative; width: 104px; height: 104px; margin-bottom: 12px; }
.bilai-prof-avatar-img,
.bilai-prof-avatar-fallback {
    width: 104px; height: 104px; border-radius: 50%;
    object-fit: cover; background: var(--bilai-prof-cream);
    border: 1px solid var(--bilai-prof-border);
}
.bilai-prof-avatar-fallback {
    display: flex; align-items: center; justify-content: center;
    font-size: 34px; font-weight: 700; text-transform: uppercase;
    color: var(--bilai-prof-muted);
}
.bilai-prof-avatar-btn {
    position: absolute; right: 2px; bottom: 2px;
    width: 30px; height: 30px; border-radius: 50%;
    background: var(--bilai-prof-primary); color: #fff;
    border: 2px solid var(--bilai-prof-card);
    display: flex; align-items: center; justify-content: center;
    font-size: 12px; cursor: pointer; transition: 0.15s;
}
.bilai-prof-avatar-btn:hover { background: var(--bilai-prof-primary-dark); }
#bilai-prof-image-input { display: none; }
.bilai-prof-avatar-label { font-size: 14px; font-weight: 600; color: var(--bilai-prof-text); margin: 0 0 4px; }
.bilai-prof-avatar-hint  { font-size: 11.5px; color: var(--bilai-prof-primary); margin: 0; }
.bilai-prof-avatar-file  { font-size: 11.5px; color: var(--bilai-prof-muted); margin: 4px 0 0; }

/* Fields */
.bilai-prof-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px 30px; }
.bilai-prof-field { margin-bottom: 12px; }
.bilai-prof-field label { display: block; font-size: 13px; font-weight: 600; color: var(--bilai-prof-text); margin-bottom: 6px; }
.bilai-prof-field label .req { color: #e04b4b; }
.bilai-prof-field input,
.bilai-prof-field select,
.bilai-prof-field textarea {
    width: 100%; height: 44px;
    border: 1px solid var(--bilai-prof-border); border-radius: 8px;
    padding: 10px 14px; font-size: 13.5px; color: var(--bilai-prof-text); background: #fff;
}
.bilai-prof-field input::placeholder { color: #b6ab9c; }
.bilai-prof-field input:focus,
.bilai-prof-field select:focus,
.bilai-prof-field textarea:focus { outline: none; border-color: var(--bilai-prof-primary); box-shadow: 0 0 0 3px rgba(242,140,0,0.10); }
.bilai-prof-field input.is-invalid, .bilai-prof-field select.is-invalid { border-color: #e04b4b; }
.bilai-prof-err { font-size: 11.5px; color: #e04b4b; margin: 5px 0 0; }

/* Submit */
.bilai-prof-submit-wrap { text-align: center; margin-top: 20px; }
.bilai-prof-submit {
    min-width: 280px; padding: 13px 32px;
    background: var(--bilai-prof-primary); color: #fff;
    border: none; border-radius: 10px;
    font-size: 14.5px; font-weight: 700; cursor: pointer; transition: 0.2s;
}
.bilai-prof-submit:hover { background: var(--bilai-prof-primary-dark); }

/* Select2 skin — match the input height/colors above */
.bilai-prof-card .select2-container .select2-selection--single { height: 44px; border: 1px solid var(--bilai-prof-border); border-radius: 8px; background: #fff; }
.bilai-prof-card .select2-container .select2-selection--single .select2-selection__rendered { line-height: 42px; padding-left: 14px; font-size: 13.5px; color: var(--bilai-prof-text); }
.bilai-prof-card .select2-container .select2-selection--single .select2-selection__placeholder { color: #b6ab9c; }
.bilai-prof-card .select2-container .select2-selection--single .select2-selection__arrow { height: 42px; }
.bilai-prof-card .select2-container--default.select2-container--disabled .select2-selection--single { background: #f7f2e9; }

/* Responsive */
@media (max-width: 1199px) { .bilai-prof-layout { grid-template-columns: 228px 1fr; gap: 16px; } }
@media (max-width: 991px)  { .bilai-prof-layout { grid-template-columns: 1fr; } .bilai-prof-sidebar { position: static; } }
@media (max-width: 767px)  {
    .bilai-prof-grid { grid-template-columns: 1fr; gap: 0; }
    .bilai-prof-card { padding: 20px 16px 26px; }
    .bilai-prof-submit { min-width: 100%; }
}
/* BilaiGhor Customer Profile Edit End */
</style>
@endpush

@section('content')
<div class="bilai-prof-page">
    <div class="container">

        {{-- Breadcrumb --}}
        <nav class="bilai-prof-bc" aria-label="breadcrumb">
            <a href="{{ route('home') }}">Home</a>
            <span class="bilai-prof-bc-sep">›</span>
            <a href="{{ route('customer.account') }}">My Account</a>
            <span class="bilai-prof-bc-sep">›</span>
            <span class="bilai-prof-bc-active">Profile</span>
        </nav>

        <div class="bilai-prof-layout">

            {{-- ═══════════ LEFT SIDEBAR ═══════════ --}}
            <aside class="bilai-prof-sidebar">
                <div class="bilai-card">
                    <div class="bilai-dash-profile-box">
                        <div class="bilai-dash-profile-row">
                            @if($profileImage)
                                <img src="{{ $profileImage }}" onerror="this.style.display='none';this.nextElementSibling.style.display='flex';" class="bilai-dash-avatar" alt="{{ $profile_edit->name }}">
                                <div class="bilai-dash-avatar-placeholder" style="display:none;">{{ $customerInitial }}</div>
                            @else
                                <div class="bilai-dash-avatar-placeholder">{{ $customerInitial }}</div>
                            @endif
                            <div class="bilai-dash-profile-info">
                                <p class="bilai-dash-profile-name">{{ $profile_edit->name ?? 'Customer' }}</p>
                                <p class="bilai-dash-profile-sub">{{ $profile_edit->phone ?? $profile_edit->email ?? '' }}</p>
                            </div>
                        </div>
                        <hr class="bilai-dash-profile-divider">
                        <div class="bilai-dash-rp-row">
                            <span class="bilai-dash-rp-item">
                                {{-- Replace SVG icon later --}}
                                <span class="bilai-dash-rp-icon"><i class="fa fa-star"></i></span><span>{{ $customer->rewardBalance() }} RP</span>
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
                        <a href="{{ route('customer.profile_edit') }}" class="bilai-dash-nav-item active">
                            {{-- Replace sidebar SVG icon later --}}
                            <span class="bilai-dash-nav-icon"><i class="fa fa-user"></i></span> Profile
                        </a>
                        <a href="#" class="bilai-dash-nav-item">
                            {{-- Replace sidebar SVG icon later --}}
                            <span class="bilai-dash-nav-icon"><i class="fa fa-heart-o"></i></span> Wishlist
                        </a>
                        <a href="{{ route('customer.addresses') }}" class="bilai-dash-nav-item">
                            {{-- Replace sidebar SVG icon later --}}
                            <span class="bilai-dash-nav-icon"><i class="fa fa-map-marker"></i></span> Addresses
                        </a>
                        <a href="#" class="bilai-dash-nav-item">
                            {{-- Replace sidebar SVG icon later --}}
                            <span class="bilai-dash-nav-icon"><i class="fa fa-ticket"></i></span> Coupon
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
                           onclick="event.preventDefault(); document.getElementById('bilai-prof-logout-form').submit();"
                           class="bilai-dash-nav-item bilai-dash-nav-item--logout">
                            {{-- Replace sidebar SVG icon later --}}
                            <span class="bilai-dash-nav-icon"><i class="fa fa-sign-out"></i></span> Logout
                        </a>
                        <form id="bilai-prof-logout-form" action="{{ route('customer.logout') }}" method="POST" style="display:none;">@csrf</form>
                    </nav>
                </div>
            </aside>

            {{-- ═══════════ RIGHT CONTENT ═══════════ --}}
            <main>
                <div class="bilai-prof-card">
                    <h2 class="bilai-prof-card-title">Profile Info</h2>
                    <hr class="bilai-prof-card-divider">

                    <form action="{{ route('customer.profile_update') }}" method="POST" enctype="multipart/form-data" id="bilai-prof-form">
                        @csrf

                        {{-- Profile picture --}}
                        <div class="bilai-prof-avatar-wrap">
                            <div class="bilai-prof-avatar-box">
                                @if($profileImage)
                                    <img id="bilai-prof-avatar-img" src="{{ $profileImage }}" class="bilai-prof-avatar-img" alt="Profile picture"
                                         onerror="this.style.display='none';document.getElementById('bilai-prof-avatar-fallback').style.display='flex';">
                                    <div id="bilai-prof-avatar-fallback" class="bilai-prof-avatar-fallback" style="display:none;">{{ $customerInitial }}</div>
                                @else
                                    <img id="bilai-prof-avatar-img" src="" class="bilai-prof-avatar-img" alt="Profile picture" style="display:none;">
                                    <div id="bilai-prof-avatar-fallback" class="bilai-prof-avatar-fallback">{{ $customerInitial }}</div>
                                @endif
                                <label for="bilai-prof-image-input" class="bilai-prof-avatar-btn" title="Change picture">
                                    {{-- Replace camera SVG icon later --}}
                                    <i class="fa fa-camera"></i>
                                </label>
                                <input type="file" name="image" id="bilai-prof-image-input" accept="image/jpeg,image/jpg,image/png,image/webp">
                            </div>
                            <p class="bilai-prof-avatar-label">Profile Picture</p>
                            <p class="bilai-prof-avatar-hint">Supported format PNG, JPG or WEBP (Max 2MB)</p>
                            <p class="bilai-prof-avatar-file" id="bilai-prof-image-name" style="display:none;"></p>
                            @error('image')<p class="bilai-prof-err">{{ $message }}</p>@enderror
                        </div>

                        <div class="bilai-prof-grid">
                            {{-- Full Name --}}
                            <div class="bilai-prof-field">
                                <label for="bilai-prof-name">Full Name <span class="req">*</span></label>
                                <input type="text" name="name" id="bilai-prof-name" maxlength="255" required
                                       placeholder="Write your full name"
                                       class="@error('name') is-invalid @enderror"
                                       value="{{ old('name', $profile_edit->name) }}">
                                @error('name')<p class="bilai-prof-err">{{ $message }}</p>@enderror
                            </div>

                            {{-- Mobile --}}
                            <div class="bilai-prof-field">
                                <label for="bilai-prof-phone">Mobile <span class="req">*</span></label>
                                <input type="text" name="phone" id="bilai-prof-phone" maxlength="20" required
                                       placeholder="01xxxxxxxxx"
                                       class="@error('phone') is-invalid @enderror"
                                       value="{{ old('phone', $profile_edit->phone) }}">
                                @error('phone')<p class="bilai-prof-err">{{ $message }}</p>@enderror
                            </div>

                            {{-- Email --}}
                            <div class="bilai-prof-field">
                                <label for="bilai-prof-email">Email</label>
                                <input type="email" name="email" id="bilai-prof-email" maxlength="255"
                                       placeholder="Write your email"
                                       class="@error('email') is-invalid @enderror"
                                       value="{{ old('email', $profile_edit->email) }}">
                                @error('email')<p class="bilai-prof-err">{{ $message }}</p>@enderror
                            </div>

                            {{-- Address --}}
                            <div class="bilai-prof-field">
                                <label for="bilai-prof-address">Address <span class="req">*</span></label>
                                <input type="text" name="address" id="bilai-prof-address" maxlength="500" required
                                       placeholder="House no, Road no, Area"
                                       class="@error('address') is-invalid @enderror"
                                       value="{{ old('address', $profile_edit->address) }}">
                                @error('address')<p class="bilai-prof-err">{{ $message }}</p>@enderror
                            </div>

                            {{-- District (same source as the Add/Edit Address flow) --}}
                            <div class="bilai-prof-field">
                                <label for="bilai-prof-district">District <span class="req">*</span></label>
                                <select name="district_id" id="bilai-prof-district" required
                                        class="@error('district_id') is-invalid @enderror">
                                    <option value="">Select District</option>
                                    @foreach($districts as $district)
                                        <option value="{{ $district->id }}" @selected((string) $selectedDistrict === (string) $district->id)>{{ $district->name }}</option>
                                    @endforeach
                                </select>
                                @error('district_id')<p class="bilai-prof-err">{{ $message }}</p>@enderror
                            </div>

                            {{-- Zone (loaded from delivery_zones for the selected district) --}}
                            <div class="bilai-prof-field">
                                <label for="bilai-prof-zone">Zone <span class="req">*</span></label>
                                <select name="zone_id" id="bilai-prof-zone" required disabled
                                        class="@error('zone_id') is-invalid @enderror">
                                    <option value="">Select Zone</option>
                                </select>
                                @error('zone_id')<p class="bilai-prof-err">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <div class="bilai-prof-submit-wrap">
                            <button type="submit" class="bilai-prof-submit">Update</button>
                        </div>
                    </form>
                </div>
            </main>

        </div>
    </div>
</div>
@endsection

{{-- Shared District→Zone helper (also loads select2 once). --}}
@include('frontEnd.layouts.customer.partials.district-zone-js')

@push('script')
<script>
/* BilaiGhor Customer Profile Edit — avatar preview + District→Zone */
(function () {
    // ── Avatar preview (2MB / image types, same limits as the backend rule) ──
    var input    = document.getElementById('bilai-prof-image-input');
    var img      = document.getElementById('bilai-prof-avatar-img');
    var fallback = document.getElementById('bilai-prof-avatar-fallback');
    var nameEl   = document.getElementById('bilai-prof-image-name');
    var allowed  = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];

    if (input) {
        input.addEventListener('change', function () {
            var file = this.files && this.files[0];
            if (!file) { return; }
            if (allowed.indexOf(file.type) === -1) {
                alert('Only JPG, PNG or WEBP images are allowed.');
                this.value = '';
                return;
            }
            if (file.size > 2 * 1024 * 1024) {
                alert('Image must be 2MB or smaller.');
                this.value = '';
                return;
            }
            var reader = new FileReader();
            reader.onload = function (e) {
                img.src = e.target.result;
                img.style.display = 'block';
                fallback.style.display = 'none';
                nameEl.textContent = file.name;
                nameEl.style.display = 'block';
            };
            reader.readAsDataURL(file);
        });
    }

    // ── District → Zone (shared helper; same endpoint as the address modal) ──
    // District is rendered pre-selected; the helper loads its zones and then selects
    // the saved zone once the AJAX response arrives (no setTimeout).
    $(function () {
        window.BilaiDistrictZone.initFields({
            district:     '#bilai-prof-district',
            zone:         '#bilai-prof-zone',
            selectedZone: @json($selectedZone),
            fresh:        true
        });
    });
}());
</script>
@endpush
