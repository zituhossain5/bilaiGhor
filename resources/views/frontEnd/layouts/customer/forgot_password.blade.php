@extends('frontEnd.layouts.master')
@section('title','Forgot Password')

@push('css')
<style>
/* BilaiGhor Forgot Password Start */
:root {
    --bilai-auth-primary:      var(--bilai-primary, #F28C00);
    --bilai-auth-primary-dark: var(--bilai-primary-dark, #c96f00);
    --bilai-auth-card-bg:      #FFFDF8;
    --bilai-auth-border:       var(--bilai-border, #E8CDA5);
    --bilai-auth-text:         var(--bilai-text, #2B1A10);
    --bilai-auth-muted:        var(--bilai-muted, #77706A);
}

.bilai-auth-page {
    background: #fdfaf3;
    min-height: 62vh;
    display: flex; align-items: center; justify-content: center;
    padding: 60px 16px 80px;
}
.bilai-auth-card {
    background: var(--bilai-auth-card-bg);
    border: 1px solid var(--bilai-auth-border);
    border-radius: 14px;
    width: 100%; max-width: 440px;
    padding: 40px 38px 36px;
}
.bilai-auth-icon { text-align: center; margin-bottom: 14px; }
.bilai-auth-icon i { font-size: 30px; color: var(--bilai-auth-text); }
.bilai-auth-title {
    text-align: center; font-size: 21px; font-weight: 700;
    color: var(--bilai-auth-text); margin: 0 0 26px;
}
.bilai-auth-field { margin-bottom: 20px; }
.bilai-auth-field label {
    display: block; font-size: 13px; font-weight: 600;
    color: var(--bilai-auth-text); margin-bottom: 7px;
}
.bilai-auth-field label .req { color: #e04b4b; }
.bilai-auth-field input {
    width: 100%; height: 46px;
    border: 1px solid var(--bilai-auth-border); border-radius: 8px;
    padding: 10px 15px; font-size: 14px; background: #fff;
    color: var(--bilai-auth-text);
}
.bilai-auth-field input::placeholder { color: #b6ab9c; }
.bilai-auth-field input:focus {
    outline: none; border-color: var(--bilai-auth-primary);
    box-shadow: 0 0 0 3px rgba(242,140,0,0.10);
}
.bilai-auth-err { font-size: 12px; color: #e04b4b; margin: 6px 0 0; }
.bilai-auth-btn {
    width: 100%; height: 48px;
    background: var(--bilai-auth-primary); color: #fff;
    border: none; border-radius: 8px;
    font-size: 14.5px; font-weight: 700; cursor: pointer; transition: 0.2s;
}
.bilai-auth-btn:hover { background: var(--bilai-auth-primary-dark); }
.bilai-auth-btn:disabled { opacity: 0.55; cursor: not-allowed; }
.bilai-auth-back {
    display: flex; align-items: center; justify-content: center; gap: 7px;
    margin-top: 20px; font-size: 13.5px; color: #2f6fdb; text-decoration: none;
}
.bilai-auth-back:hover { color: #1c53ad; text-decoration: none; }
.bilai-auth-hint { font-size: 12px; color: var(--bilai-auth-muted); margin: 10px 0 0; text-align: center; line-height: 1.6; }

@media (max-width: 480px) { .bilai-auth-card { padding: 30px 20px 26px; } }
/* BilaiGhor Forgot Password End */
</style>
@endpush

@section('content')
<div class="bilai-auth-page">
    <div class="bilai-auth-card">

        {{-- Replace lock SVG icon later --}}
        <div class="bilai-auth-icon"><i class="fa fa-lock"></i></div>
        <h1 class="bilai-auth-title">Reset Your Password</h1>

        <form action="{{ route('customer.forgot.submit') }}" method="POST">
            @csrf
            <div class="bilai-auth-field">
                <label for="bilai-auth-identifier">Mobile / Email <span class="req">*</span></label>
                <input type="text" name="identifier" id="bilai-auth-identifier"
                       value="{{ old('identifier') }}" maxlength="100" autofocus
                       placeholder="01XXXXXXXXX or you@example.com">
                @error('identifier')<p class="bilai-auth-err">{{ $message }}</p>@enderror
            </div>

            <button type="submit" class="bilai-auth-btn">Next</button>
        </form>

        <a href="{{ route('customer.login') }}" class="bilai-auth-back">
            {{-- Replace arrow SVG icon later --}}
            <i class="fa fa-long-arrow-left"></i> Back to Login Page
        </a>
        <p class="bilai-auth-hint">Enter your email to get a reset link, or your mobile number to get an OTP.</p>
    </div>
</div>
@endsection
