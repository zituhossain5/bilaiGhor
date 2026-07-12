<?php

namespace App\Http\Controllers\Frontend;

use App\Helpers\SmsHelper;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\GeneralSetting;
use App\Models\PasswordResetOtp;
use App\Models\SmsGateway;
use App\Models\User;
use App\Models\Vendor;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Session;

/**
 * Customer "forgot password" — two channels:
 *   • email  → Laravel password broker ("customers") reset link
 *   • mobile → hashed, expiring OTP sent through the existing BulkSMSBD gateway
 *
 * The reset form is never reachable without either a valid broker token or a
 * verified-OTP session authorization.
 */
class CustomerPasswordResetController extends Controller
{
    /** OTP policy. */
    private const OTP_LENGTH       = 6;
    private const OTP_TTL_MINUTES  = 5;
    private const RESEND_COOLDOWN  = 60;   // seconds between two OTPs
    private const MAX_REQUESTS     = 3;    // per mobile and per IP …
    private const REQUEST_WINDOW   = 600;  // … within 10 minutes
    private const AUTH_TTL_MINUTES = 15;   // post-OTP reset authorization

    /** Session keys. */
    private const S_OTP_ID  = 'pwreset_otp_id';
    private const S_MOBILE  = 'pwreset_mobile';
    private const S_MASKED  = 'pwreset_masked';
    private const S_AUTH    = 'pwreset_authorized';

    /* ─────────────────────────── Step 1: identifier ─────────────────────────── */

    public function showForgotForm()
    {
        return view('frontEnd.layouts.customer.forgot_password');
    }

    public function submit(Request $request)
    {
        $request->validate(['identifier' => 'required|string|max:100'], [
            'identifier.required' => 'Please enter your mobile number or email.',
        ]);

        $identifier = trim($request->input('identifier'));

        if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            return $this->sendResetLink($identifier);
        }

        $mobile = $this->normalizeMobile($identifier);
        if ($mobile) {
            return $this->startOtpFlow($request, $mobile);
        }

        return back()->withInput()->withErrors([
            'identifier' => 'Enter a valid email address or Bangladeshi mobile number.',
        ]);
    }

    /* ─────────────────────────── Channel A: email ─────────────────────────── */

    private function sendResetLink(string $email)
    {
        // Broker generates a single-use token, stores it hashed, and mails the link.
        // Its own throttle (config/auth.php) rate-limits repeat requests.
        Password::broker('customers')->sendResetLink(['email' => $email]);

        // Always the same response — never reveal whether the account exists.
        Toastr::success('If an account exists for the provided email, password reset instructions have been sent.', 'Check your inbox');

        return redirect()->route('customer.forgot.password');
    }

    /* ─────────────────────────── Channel B: mobile OTP ─────────────────────────── */

    private function startOtpFlow(Request $request, string $mobile)
    {
        if (!$this->smsResetEnabled()) {
            Toastr::error('Mobile password reset is currently unavailable. Please use email reset or contact support.', 'Unavailable');
            return back()->withInput();
        }

        if ($this->tooManyRequests($request, $mobile)) {
            Toastr::error('Too many OTP requests. Please try again later.', 'Slow down');
            return back()->withInput();
        }

        // Send only when the number belongs to an account — but redirect to the OTP
        // page either way, so the response cannot be used to enumerate accounts.
        $account = $this->findAccountByMobile($mobile);
        if ($account) {
            $this->issueOtp($request, $mobile, $account);
        }

        $this->hitRateLimiters($request, $mobile);

        Session::put(self::S_MOBILE, $mobile);
        Session::put(self::S_MASKED, $this->maskMobile($mobile));

        return redirect()->route('customer.forgot.otp');
    }

    public function showOtpForm()
    {
        if (!Session::get(self::S_MOBILE)) {
            return redirect()->route('customer.forgot.password');
        }

        return view('frontEnd.layouts.customer.forgot_otp', [
            'masked'         => Session::get(self::S_MASKED),
            'resendCooldown' => self::RESEND_COOLDOWN,
            'otpLength'      => self::OTP_LENGTH,
        ]);
    }

    public function verifyOtp(Request $request)
    {
        $mobile = Session::get(self::S_MOBILE);
        if (!$mobile) {
            return redirect()->route('customer.forgot.password');
        }

        $request->validate([
            'otp' => 'required|digits:'.self::OTP_LENGTH,
        ], [
            'otp.required' => 'Enter the OTP sent to your mobile.',
            'otp.digits'   => 'The OTP must be '.self::OTP_LENGTH.' digits.',
        ]);

        $otp = PasswordResetOtp::find(Session::get(self::S_OTP_ID));

        // One generic failure message: never say whether the number has an account,
        // whether the code expired, or how many attempts are left.
        if (!$otp || $otp->mobile !== $mobile || !$otp->isUsable()) {
            return back()->withErrors(['otp' => 'This OTP is invalid or has expired. Please request a new one.']);
        }

        $otp->increment('attempts');

        if (!$otp->matches($request->input('otp'))) {
            if ($otp->fresh()->attempts >= PasswordResetOtp::MAX_ATTEMPTS) {
                $otp->update(['used_at' => now()]); // burn it
                return back()->withErrors(['otp' => 'Too many incorrect attempts. Please request a new OTP.']);
            }
            return back()->withErrors(['otp' => 'The OTP you entered is incorrect.']);
        }

        // Correct: consume immediately so the same code can never be replayed.
        $otp->update(['verified_at' => now(), 'used_at' => now()]);

        $request->session()->regenerate();
        Session::put(self::S_AUTH, [
            'user_type'  => $otp->user_type,
            'user_id'    => $otp->user_id,
            'expires_at' => now()->addMinutes(self::AUTH_TTL_MINUTES)->timestamp,
        ]);
        Session::forget([self::S_OTP_ID, self::S_MOBILE, self::S_MASKED]);

        return redirect()->route('customer.password.reset');
    }

    public function resendOtp(Request $request)
    {
        $mobile = Session::get(self::S_MOBILE);
        if (!$mobile) {
            return redirect()->route('customer.forgot.password');
        }

        if (!$this->smsResetEnabled()) {
            Toastr::error('Mobile password reset is currently unavailable. Please use email reset or contact support.', 'Unavailable');
            return back();
        }

        $last = PasswordResetOtp::where('mobile', $mobile)->latest('id')->first();
        if ($last && $last->created_at->diffInSeconds(now()) < self::RESEND_COOLDOWN) {
            Toastr::error('Please wait before requesting another OTP.', 'Too soon');
            return back();
        }

        if ($this->tooManyRequests($request, $mobile)) {
            Toastr::error('Too many OTP requests. Please try again later.', 'Slow down');
            return back();
        }

        $account = $this->findAccountByMobile($mobile);
        if ($account) {
            $this->issueOtp($request, $mobile, $account);
        }
        $this->hitRateLimiters($request, $mobile);

        Toastr::success('A new OTP has been sent to your mobile.', 'OTP sent');
        return back();
    }

    /** Create a fresh OTP (invalidating older ones) and text it out. */
    private function issueOtp(Request $request, string $mobile, array $account): void
    {
        PasswordResetOtp::invalidateFor($mobile);

        $code = (string) random_int(100000, 999999); // cryptographically secure

        $row = PasswordResetOtp::create([
            'user_type'  => $account['type'],
            'user_id'    => $account['id'],
            'mobile'     => $mobile,
            'otp_hash'   => Hash::make($code),   // never stored in plain text
            'expires_at' => now()->addMinutes(self::OTP_TTL_MINUTES),
            'request_ip' => $request->ip(),
        ]);

        $site = GeneralSetting::where('status', 1)->first();
        $name = $account['name'] ?: 'Customer';

        // ['forget_pass' => 1] makes SmsHelper pick the gateway only when the
        // gateway AND the Forgot-Password OTP toggle are both ON. Credentials stay
        // in the DB/gateway layer; nothing is echoed back to the browser.
        SmsHelper::send(
            $mobile,
            "Dear {$name}!\r\nYour password reset OTP is {$code}\r\nIt expires in ".self::OTP_TTL_MINUTES." minutes.\r\nThank you for using ".($site->name ?? config('app.name')),
            ['forget_pass' => 1]
        );

        Session::put(self::S_OTP_ID, $row->id);
        // $code is deliberately never logged.
    }

    /* ─────────────────────────── Step 3: new password ─────────────────────────── */

    public function showResetForm(Request $request, ?string $token = null)
    {
        // A) Email link — token must exist and still be valid for that customer.
        if ($token) {
            $email    = (string) $request->query('email', '');
            $customer = $email ? Customer::where('email', $email)->first() : null;

            if (!$customer || !Password::broker('customers')->getRepository()->exists($customer, $token)) {
                Toastr::error('This password reset link is invalid or has expired.', 'Link expired');
                return redirect()->route('customer.forgot.password');
            }

            return view('frontEnd.layouts.customer.reset_password', [
                'token' => $token,
                'email' => $email,
            ]);
        }

        // B) Mobile OTP — session authorization must be present and unexpired.
        if (!$this->authorizedReset()) {
            Toastr::error('Please verify your identity before resetting your password.', 'Not authorized');
            return redirect()->route('customer.forgot.password');
        }

        return view('frontEnd.layouts.customer.reset_password', [
            'token' => null,
            'email' => null,
        ]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ], [
            'password.confirmed' => 'The password confirmation does not match.',
            'password.min'       => 'Password must be at least 8 characters.',
        ]);

        // A) Email token path — the broker validates + deletes the token for us.
        if ($request->filled('token')) {
            $request->validate(['email' => 'required|email']);

            $status = Password::broker('customers')->reset(
                $request->only('email', 'password', 'password_confirmation', 'token'),
                function (Customer $customer, string $password) {
                    $customer->password = Hash::make($password);
                    $customer->setRememberToken(\Illuminate\Support\Str::random(60));
                    $customer->save();
                }
            );

            if ($status !== Password::PASSWORD_RESET) {
                Toastr::error('This password reset link is invalid or has expired.', 'Link expired');
                return redirect()->route('customer.forgot.password');
            }

            $request->session()->regenerate();
            Toastr::success('Password updated. Please log in with your new password.', 'Success');
            return redirect()->route('customer.login');
        }

        // B) OTP path — only a verified, unexpired authorization may proceed.
        $auth = $this->authorizedReset();
        if (!$auth) {
            Toastr::error('Please verify your identity before resetting your password.', 'Not authorized');
            return redirect()->route('customer.forgot.password');
        }

        if (!$this->updatePasswordFor($auth['user_type'], (int) $auth['user_id'], $request->input('password'))) {
            Session::forget(self::S_AUTH);
            Toastr::error('We could not update the password. Please start again.', 'Failed');
            return redirect()->route('customer.forgot.password');
        }

        // Authorization is single-use.
        Session::forget([self::S_AUTH, self::S_OTP_ID, self::S_MOBILE, self::S_MASKED]);
        $request->session()->regenerate();

        Toastr::success('Password updated. Please log in with your new password.', 'Success');
        return redirect()->route('customer.login');
    }

    /* ─────────────────────────── helpers ─────────────────────────── */

    /** Gateway ON *and* Forgot-Password OTP toggle ON (admin SMS settings). */
    private function smsResetEnabled(): bool
    {
        return SmsGateway::where('status', 1)->where('forget_pass', 1)->exists();
    }

    private function tooManyRequests(Request $request, string $mobile): bool
    {
        return RateLimiter::tooManyAttempts('pwotp:m:'.$mobile, self::MAX_REQUESTS)
            || RateLimiter::tooManyAttempts('pwotp:ip:'.$request->ip(), self::MAX_REQUESTS);
    }

    private function hitRateLimiters(Request $request, string $mobile): void
    {
        RateLimiter::hit('pwotp:m:'.$mobile, self::REQUEST_WINDOW);
        RateLimiter::hit('pwotp:ip:'.$request->ip(), self::REQUEST_WINDOW);
    }

    /** Valid, unexpired post-OTP authorization, or null. */
    private function authorizedReset(): ?array
    {
        $auth = Session::get(self::S_AUTH);
        if (!is_array($auth) || empty($auth['user_id'])) {
            return null;
        }
        if (($auth['expires_at'] ?? 0) < now()->timestamp) {
            Session::forget(self::S_AUTH);
            return null;
        }
        return $auth;
    }

    /**
     * "01671518316" | "+8801671518316" | "8801671518316" | "1671518316" → "01671518316".
     * Returns null when it is not a valid BD mobile number.
     */
    private function normalizeMobile(string $input): ?string
    {
        $digits = preg_replace('/\D/', '', $input);

        if (strlen($digits) === 13 && str_starts_with($digits, '880')) {
            $digits = '0'.substr($digits, 3);
        } elseif (strlen($digits) === 10 && str_starts_with($digits, '1')) {
            $digits = '0'.$digits;
        }

        return preg_match('/^01[3-9]\d{8}$/', $digits) ? $digits : null;
    }

    private function maskMobile(string $mobile): string
    {
        return substr($mobile, 0, 3).str_repeat('*', 5).substr($mobile, -3);
    }

    /**
     * Match a stored phone in any format the project may hold it in.
     * Keeps the existing customer / vendor / reseller phone-reset behaviour.
     */
    private function findAccountByMobile(string $mobile): ?array
    {
        $variants = [$mobile, '88'.$mobile, '+88'.$mobile, substr($mobile, 1)];

        $customer = Customer::whereIn('phone', $variants)->first();
        if ($customer) {
            // A customer whose email also owns a reseller User account resets both.
            if ($customer->email) {
                $reseller = User::where('email', $customer->email)
                    ->where(function ($q) {
                        $q->where('role', 'reseller')
                          ->orWhereHas('roles', fn ($r) => $r->where('name', 'reseller'));
                    })
                    ->first();

                if ($reseller) {
                    return ['type' => 'reseller', 'id' => $reseller->id, 'name' => $reseller->name ?: $customer->name];
                }
            }

            return ['type' => 'customer', 'id' => $customer->id, 'name' => $customer->name];
        }

        $vendor = Vendor::whereIn('phone', $variants)->first();
        if ($vendor) {
            return ['type' => 'vendor', 'id' => $vendor->id, 'name' => $vendor->owner_name];
        }

        return null;
    }

    /** Write the new password to the model that owns the account. */
    private function updatePasswordFor(string $type, int $id, string $password): bool
    {
        $hash = Hash::make($password);

        if ($type === 'customer') {
            $customer = Customer::find($id);
            if (!$customer) {
                return false;
            }
            $customer->password = $hash;
            $customer->save();
            return true;
        }

        if ($type === 'reseller') {
            $user = User::find($id);
            if (!$user) {
                return false;
            }
            $user->password = $hash;
            $user->save();

            // Reseller logs into both panels with the same credentials.
            if ($user->email) {
                Customer::where('email', $user->email)->update(['password' => $hash]);
            }
            return true;
        }

        if ($type === 'vendor') {
            $vendor = Vendor::find($id);
            $user   = $vendor && $vendor->email ? User::where('email', $vendor->email)->first() : null;
            if (!$user) {
                return false;
            }
            $user->password = $hash;
            $user->save();
            return true;
        }

        return false;
    }
}
