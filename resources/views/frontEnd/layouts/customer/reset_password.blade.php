@extends('frontEnd.layouts.master')
@section('title','Set New Password')

@push('css')
<style>
/* BilaiGhor Reset Password Start */
:root {
    --bilai-rst-primary:      var(--bilai-primary, #F28C00);
    --bilai-rst-primary-dark: var(--bilai-primary-dark, #c96f00);
    --bilai-rst-card-bg:      #FFFDF8;
    --bilai-rst-border:       var(--bilai-border, #E8CDA5);
    --bilai-rst-text:         var(--bilai-text, #2B1A10);
    --bilai-rst-muted:        var(--bilai-muted, #77706A);
}

.bilai-rst-page {
    background: #fdfaf3;
    min-height: 62vh;
    display: flex; align-items: center; justify-content: center;
    padding: 60px 16px 80px;
}
.bilai-rst-card {
    background: var(--bilai-rst-card-bg);
    border: 1px solid var(--bilai-rst-border);
    border-radius: 14px;
    width: 100%; max-width: 470px;
    padding: 40px 38px 36px;
}
.bilai-rst-icon { text-align: center; margin-bottom: 14px; }
.bilai-rst-icon i { font-size: 30px; color: var(--bilai-rst-text); }
.bilai-rst-title {
    text-align: center; font-size: 21px; font-weight: 700;
    color: var(--bilai-rst-text); margin: 0 0 26px;
}
.bilai-rst-field { margin-bottom: 18px; }
.bilai-rst-field label {
    display: block; font-size: 13px; font-weight: 600;
    color: var(--bilai-rst-text); margin-bottom: 7px;
}
.bilai-rst-field label .req { color: #e04b4b; }
.bilai-rst-input-wrap { position: relative; }
.bilai-rst-field input {
    width: 100%; height: 46px;
    border: 1px solid var(--bilai-rst-border); border-radius: 8px;
    padding: 10px 44px 10px 15px; font-size: 14px; background: #fff;
    color: var(--bilai-rst-text);
}
.bilai-rst-field input::placeholder { color: #b6ab9c; }
.bilai-rst-field input:focus {
    outline: none; border-color: var(--bilai-rst-primary);
    box-shadow: 0 0 0 3px rgba(242,140,0,0.10);
}
.bilai-rst-eye {
    position: absolute; top: 50%; right: 12px; transform: translateY(-50%);
    background: none; border: none; padding: 4px;
    color: var(--bilai-rst-muted); font-size: 15px; cursor: pointer; line-height: 1;
}
.bilai-rst-eye:hover { color: var(--bilai-rst-text); }
.bilai-rst-err { font-size: 12px; color: #e04b4b; margin: 6px 0 0; }
.bilai-rst-hint { font-size: 11.5px; color: var(--bilai-rst-muted); margin: 6px 0 0; }
.bilai-rst-btn {
    display: block; width: 100%; max-width: 320px; height: 48px; margin: 24px auto 0;
    background: var(--bilai-rst-primary); color: #fff;
    border: none; border-radius: 8px;
    font-size: 14.5px; font-weight: 700; cursor: pointer; transition: 0.2s;
}
.bilai-rst-btn:hover { background: var(--bilai-rst-primary-dark); }

@media (max-width: 480px) { .bilai-rst-card { padding: 30px 20px 26px; } }
/* BilaiGhor Reset Password End */
</style>
@endpush

@section('content')
<div class="bilai-rst-page">
    <div class="bilai-rst-card">

        {{-- Replace lock SVG icon later --}}
        <div class="bilai-rst-icon"><i class="fa fa-lock"></i></div>
        <h1 class="bilai-rst-title">Set Your New Password</h1>

        <form action="{{ route('customer.password.update') }}" method="POST">
            @csrf
            {{-- Email flow only: the broker token proves authorization. The OTP flow
                 carries its authorization in the session, so no token is rendered. --}}
            @if(!empty($token))
                <input type="hidden" name="token" value="{{ $token }}">
                <input type="hidden" name="email" value="{{ $email }}">
            @endif

            <div class="bilai-rst-field">
                <label for="bilai-rst-password">New Password <span class="req">*</span></label>
                <div class="bilai-rst-input-wrap">
                    <input type="password" name="password" id="bilai-rst-password"
                           minlength="8" required autocomplete="new-password" placeholder="Minimum 8 characters">
                    <button type="button" class="bilai-rst-eye" data-target="bilai-rst-password" aria-label="Show password">
                        {{-- Replace eye SVG icon later --}}
                        <i class="fa fa-eye"></i>
                    </button>
                </div>
                @error('password')<p class="bilai-rst-err">{{ $message }}</p>@enderror
                <p class="bilai-rst-hint">Use at least 8 characters.</p>
            </div>

            <div class="bilai-rst-field">
                <label for="bilai-rst-password-confirm">Confirm New Password <span class="req">*</span></label>
                <div class="bilai-rst-input-wrap">
                    <input type="password" name="password_confirmation" id="bilai-rst-password-confirm"
                           minlength="8" required autocomplete="new-password" placeholder="Re-type your new password">
                    <button type="button" class="bilai-rst-eye" data-target="bilai-rst-password-confirm" aria-label="Show password">
                        {{-- Replace eye SVG icon later --}}
                        <i class="fa fa-eye"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="bilai-rst-btn">Update Password</button>
        </form>
    </div>
</div>
@endsection

@push('script')
<script>
/* BilaiGhor Reset Password — visibility toggles */
document.querySelectorAll('.bilai-rst-eye').forEach(function (btn) {
    btn.addEventListener('click', function () {
        var input = document.getElementById(this.dataset.target);
        var icon  = this.querySelector('i');
        var show  = input.type === 'password';
        input.type = show ? 'text' : 'password';
        icon.classList.toggle('fa-eye', !show);
        icon.classList.toggle('fa-eye-slash', show);
    });
});
</script>
@endpush
