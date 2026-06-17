<?php

namespace App\Http\Controllers\Concerns;

use App\Helpers\SmsHelper;
use App\Models\GeneralSetting;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

trait HandlesCheckoutOtp
{
    protected function checkoutOtpIsEnabled(): bool
    {
        $s = GeneralSetting::where('status', 1)->first();

        return $s && (int) ($s->checkout_otp_enabled ?? 0) === 1;
    }

    protected function checkoutOtpSessionKeys(string $channel): array
    {
        return [
            'pending' => "chkotp_{$channel}_pending",
            'code' => "chkotp_{$channel}_code",
            'phone' => "chkotp_{$channel}_phone",
            'expires' => "chkotp_{$channel}_expires",
            'sent_at' => "chkotp_{$channel}_sent_at",
        ];
    }

    protected function checkoutOtpForget(string $channel): void
    {
        foreach ($this->checkoutOtpSessionKeys($channel) as $sessionKey) {
            Session::forget($sessionKey);
        }
    }

    protected function normalizeBdCheckoutPhone(?string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', (string) $phone);
        if (strlen($phone) === 11 && str_starts_with($phone, '0')) {
            $phone = '88'.$phone;
        } elseif (strlen($phone) === 10) {
            $phone = '880'.$phone;
        }

        return $phone;
    }

    /**
     * @return \Illuminate\Http\RedirectResponse|null null = চালিয়ে অর্ডার প্রসেস করুন
     */
    protected function checkoutOtpGate(Request $request, string $channel): ?\Illuminate\Http\RedirectResponse
    {
        if (! $this->checkoutOtpIsEnabled()) {
            return null;
        }

        $keys = $this->checkoutOtpSessionKeys($channel);

        if (Session::get($keys['pending'])) {
            $exp = (int) Session::get($keys['expires'], 0);
            if ($exp > 0 && now()->timestamp > $exp) {
                $this->checkoutOtpForget($channel);
            }
        }

        $normalized = $this->normalizeBdCheckoutPhone($request->input('phone'));

        if (Session::get($keys['pending'])) {
            if ($normalized !== Session::get($keys['phone'])) {
                $this->checkoutOtpForget($channel);
                Toastr::warning('মোবাইল নম্বর পরিবর্তিত হয়েছে। নতুন OTP পাঠানো হচ্ছে।');

                return $this->checkoutOtpIssueFresh($request, $channel, $normalized);
            }

            $request->validate([
                'checkout_otp' => 'required|digits:6',
            ], [
                'checkout_otp.required' => 'এসএমএসে পাঠানো OTP কোডটি লিখুন।',
                'checkout_otp.digits' => 'OTP ৬ ডিজিটের হতে হবে।',
            ]);

            $exp = (int) Session::get($keys['expires'], 0);
            if ($exp === 0 || now()->timestamp > $exp) {
                $this->checkoutOtpForget($channel);
                Toastr::error('OTP এর মেয়াদ শেষ। আবার চেষ্টা করুন।');

                return redirect()->back()->withInput();
            }

            if ((string) Session::get($keys['code']) !== trim((string) $request->input('checkout_otp'))) {
                Toastr::error('OTP ভুল। আবার চেষ্টা করুন।');

                return redirect()->back()->withInput();
            }

            $this->checkoutOtpForget($channel);

            return null;
        }

        return $this->checkoutOtpIssueFresh($request, $channel, $normalized);
    }

    protected function checkoutOtpIssueFresh(Request $request, string $channel, string $normalizedPhone): \Illuminate\Http\RedirectResponse
    {
        if ($normalizedPhone === '' || strlen($normalizedPhone) < 12) {
            Toastr::error('সঠিক মোবাইল নম্বর দিন।');

            return redirect()->back()->withInput();
        }

        $keys = $this->checkoutOtpSessionKeys($channel);
        $otp = (string) random_int(100000, 999999);
        $setting = GeneralSetting::where('status', 1)->first();
        $siteName = $setting->name ?? config('app.name');
        $msg = "আপনার অর্ডার ভেরিফিকেশন কোড: {$otp}। এটি কারও সাথে শেয়ার করবেন না। — {$siteName}";

        $sent = SmsHelper::send($normalizedPhone, $msg, ['order' => 1]);
        if (! $sent) {
            Toastr::error('এসএমএস পাঠানো যায়নি। SMS গেটওয়ে (অর্ডার টাইপ) সক্রিয় ও API চেক করুন।');

            return redirect()->back()->withInput();
        }

        Session::put($keys['pending'], true);
        Session::put($keys['code'], $otp);
        Session::put($keys['phone'], $normalizedPhone);
        Session::put($keys['expires'], now()->addMinutes(10)->timestamp);
        Session::put($keys['sent_at'], now()->timestamp);

        Toastr::success('আপনার মোবাইলে OTP পাঠানো হয়েছে। কোডটি বসিয়ে আবার অর্ডার নিশ্চিত করুন।', 'OTP পাঠানো হয়েছে');

        return redirect()->back()->withInput();
    }

    protected function checkoutOtpResendForChannel(Request $request, string $channel): \Illuminate\Http\RedirectResponse
    {
        if (! $this->checkoutOtpIsEnabled()) {
            Toastr::error('এই ফিচার বন্ধ আছে।');

            return redirect()->back()->withInput();
        }

        $keys = $this->checkoutOtpSessionKeys($channel);
        if (! Session::get($keys['pending'])) {
            Toastr::warning('প্রথমে অর্ডার ফর্ম জমা দিন।');

            return redirect()->back()->withInput();
        }

        $last = (int) Session::get($keys['sent_at'], 0);
        if ($last && (now()->timestamp - $last) < 55) {
            Toastr::info('এক মিনিট অপেক্ষা করে আবার OTP চাইতে পারেন।');

            return redirect()->back()->withInput();
        }

        $normalized = $this->normalizeBdCheckoutPhone($request->input('phone'));
        if ($normalized !== Session::get($keys['phone'])) {
            Toastr::error('OTP যে নম্বরে গেছে সেই নম্বরটি পরিবর্তন করবেন না।');

            return redirect()->back()->withInput();
        }

        return $this->checkoutOtpIssueFresh($request, $channel, $normalized);
    }
}
