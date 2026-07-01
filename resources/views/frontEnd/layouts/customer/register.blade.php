@extends('frontEnd.layouts.master')
@section('title', 'Customer Register')
@php
    $generalsetting = \App\Models\GeneralSetting::first();
@endphp
@section('content')

<style>
/* BilaiGhor Customer Auth Figma Fix Start */
:root {
    --bilai-auth-primary: #F28C00;
    --bilai-auth-brown:   #3A1F0F;
    --bilai-auth-cream:   #FFF8EC;
    --bilai-auth-tab:     #F5E3AD;
    --bilai-auth-border:  #E8CDA5;
    --bilai-auth-text:    #2B1A10;
    --bilai-auth-muted:   #77706A;
}
.ba-section {
    min-height: 80vh;
    background: var(--bilai-auth-cream);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 48px 16px;
}
.ba-card {
    background: var(--bilai-auth-cream);
    border: 1.5px solid var(--bilai-auth-border);
    border-radius: 16px;
    width: 100%;
    max-width: 420px;
    padding: 32px 32px 36px;
}
.ba-tabs {
    display: flex;
    background: #EDD98A;
    border-radius: 10px;
    padding: 4px;
    gap: 4px;
    margin-bottom: 28px;
}
.ba-tab {
    flex: 1;
    text-align: center;
    padding: 9px 0;
    border-radius: 7px;
    font-size: 14px;
    font-weight: 600;
    color: var(--bilai-auth-text);
    text-decoration: none !important;
    transition: background 0.15s;
    font-family: inherit;
}
.ba-tab:hover { color: var(--bilai-auth-brown); text-decoration: none !important; }
.ba-tab.active {
    background: var(--bilai-auth-tab);
    color: var(--bilai-auth-brown);
    box-shadow: 0 1px 4px rgba(58,31,15,0.12);
}
.ba-flash { padding: 10px 14px; margin-bottom: 16px; border-radius: 8px; font-size: 13px; }
.ba-flash-info  { background: #fff8f0; border: 1px solid var(--bilai-auth-primary); color: #5a3a10; }
.ba-flash-error { background: #fff5f5; border: 1px solid #dc3545; color: #842029; }
.ba-label {
    display: block;
    font-size: 13px;
    font-weight: 600;
    color: var(--bilai-auth-text);
    margin-bottom: 6px;
}
.ba-label .req { color: var(--bilai-auth-primary); margin-left: 2px; }
.ba-field { margin-bottom: 16px; }
.ba-input {
    display: block;
    width: 100%;
    height: 46px;
    padding: 0 44px 0 14px;
    border: 1.5px solid var(--bilai-auth-border);
    border-radius: 8px;
    font-size: 14px;
    color: var(--bilai-auth-text);
    background: #fff;
    outline: none;
    box-shadow: none;
    transition: border-color 0.15s;
    font-family: inherit;
}
.ba-input.no-icon  { padding-right: 14px; }
.ba-input:focus    { border-color: var(--bilai-auth-primary); }
.ba-input.is-invalid { border-color: #dc3545; }
.ba-eye {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: var(--bilai-auth-muted);
    cursor: pointer;
    padding: 2px;
    font-size: 15px;
    line-height: 1;
}
.ba-eye:focus { outline: none; }
.ba-pw-wrap { position: relative; }
.ba-row {
    display: flex;
    align-items: center;
    margin-bottom: 18px;
    font-size: 13px;
}
.ba-check-label {
    display: flex;
    align-items: center;
    gap: 7px;
    cursor: pointer;
    color: var(--bilai-auth-text);
    font-weight: 500;
}
.ba-check-label input[type="checkbox"] {
    width: 15px; height: 15px;
    accent-color: var(--bilai-auth-primary);
    cursor: pointer; flex-shrink: 0;
}
.ba-btn {
    display: block;
    width: 100%;
    height: 48px;
    background: var(--bilai-auth-primary);
    color: #fff;
    border: none;
    border-radius: 10px;
    font-size: 15px;
    font-weight: 700;
    cursor: pointer;
    transition: opacity 0.15s;
    font-family: inherit;
    letter-spacing: 0.3px;
}
.ba-btn:hover { opacity: 0.88; }
.ba-divider {
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 20px 0;
    font-size: 13px;
    color: var(--bilai-auth-muted);
}
.ba-divider::before,
.ba-divider::after { content: ''; flex: 1; height: 1px; background: var(--bilai-auth-border); }
.ba-social {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    width: 100%;
    height: 44px;
    background: #fff;
    border: 1.5px solid var(--bilai-auth-border);
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    color: var(--bilai-auth-text);
    text-decoration: none !important;
    margin-bottom: 10px;
    transition: border-color 0.15s;
    font-family: inherit;
}
.ba-social:last-child { margin-bottom: 0; }
.ba-social:hover { border-color: var(--bilai-auth-primary); color: var(--bilai-auth-text); text-decoration: none !important; }
.ba-social .fab.fa-google     { color: #EA4335; font-size: 16px; }
.ba-social .fab.fa-facebook-f { color: #1877F2; font-size: 16px; }
.ba-err { font-size: 12px; color: #dc3545; display: block; margin-top: 4px; }
@media (max-width: 480px) {
    .ba-card { padding: 24px 18px 28px; }
}
/* BilaiGhor Customer Auth Figma Fix End */
</style>

<section class="ba-section">
    <div class="ba-card">

        <div class="ba-tabs">
            <a href="{{ route('customer.login') }}" class="ba-tab">Login</a>
            <a href="{{ route('customer.register') }}" class="ba-tab active">Register</a>
        </div>

        @if(session('info'))
            <div class="ba-flash ba-flash-info">{{ session('info') }}</div>
        @endif
        @if(session('error'))
            <div class="ba-flash ba-flash-error">{{ session('error') }}</div>
        @endif

        <form action="{{ route('customer.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="ba-field">
                <label class="ba-label" for="name">Your Name <span class="req">*</span></label>
                <input type="text" id="name" name="name"
                       class="ba-input no-icon @error('name') is-invalid @enderror"
                       value="{{ old('name', session('social_name', '')) }}"
                       placeholder="Full name" required>
                @error('name')<span class="ba-err">{{ $message }}</span>@enderror
            </div>

            <div class="ba-field">
                <label class="ba-label" for="phone">Email or Mobile <span class="req">*</span></label>
                <input type="text" id="phone" name="phone"
                       class="ba-input no-icon @error('phone') is-invalid @enderror"
                       value="{{ old('phone') }}"
                       placeholder="017xxxxxxxx or email@example.com" required>
                @error('phone')<span class="ba-err">{{ $message }}</span>@enderror
            </div>

            {{-- Hidden email field — carried over from social login pre-fill if available --}}
            <input type="hidden" name="email" value="{{ old('email', session('social_email', '')) }}">

            <div class="ba-field">
                <label class="ba-label" for="password">Password <span class="req">*</span></label>
                <div class="ba-pw-wrap">
                    <input type="password" id="password" name="password"
                           class="ba-input @error('password') is-invalid @enderror"
                           placeholder="••••••••" required>
                    <button type="button" class="ba-eye" onclick="baToggle('password',this)" aria-label="Toggle password">
                        <i class="fa fa-eye"></i>
                    </button>
                </div>
                @error('password')<span class="ba-err">{{ $message }}</span>@enderror
            </div>

            <div class="ba-field">
                <label class="ba-label" for="password_confirmation">Password Again</label>
                <div class="ba-pw-wrap">
                    <input type="password" id="password_confirmation" name="password_confirmation"
                           class="ba-input @error('password_confirmation') is-invalid @enderror"
                           placeholder="••••••••">
                    <button type="button" class="ba-eye" onclick="baToggle('password_confirmation',this)" aria-label="Toggle password">
                        <i class="fa fa-eye"></i>
                    </button>
                </div>
                @error('password_confirmation')<span class="ba-err">{{ $message }}</span>@enderror
            </div>

            <div class="ba-row">
                <label class="ba-check-label">
                    <input type="checkbox" name="remember_register">
                    Remember me
                </label>
            </div>

            <button type="submit" class="ba-btn">Register</button>
        </form>

        <div class="ba-divider">Or</div>

        @if(Route::has('customer.social.redirect'))
        <a href="{{ route('customer.social.redirect', 'google') }}" class="ba-social">
            <i class="fab fa-google"></i> Continue with Google
        </a>
        <a href="{{ route('customer.social.redirect', 'facebook') }}" class="ba-social">
            <i class="fab fa-facebook-f"></i> Continue with Facebook
        </a>
        @else
        <a href="#" class="ba-social" onclick="return false;">
            <i class="fab fa-google"></i> Continue with Google
        </a>
        <a href="#" class="ba-social" onclick="return false;">
            <i class="fab fa-facebook-f"></i> Continue with Facebook
        </a>
        @endif

    </div>
</section>

<script>
function baToggle(id, btn) {
    var f = document.getElementById(id);
    var i = btn.querySelector('i');
    if (f.type === 'password') { f.type = 'text'; i.classList.replace('fa-eye','fa-eye-slash'); }
    else { f.type = 'password'; i.classList.replace('fa-eye-slash','fa-eye'); }
}
</script>

@endsection

@push('script')
<script src="{{ asset('public/frontEnd/js/parsley.min.js') }}"></script>
<script src="{{ asset('public/frontEnd/js/form-validation.init.js') }}"></script>
@endpush
