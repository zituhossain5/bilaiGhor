<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    public function redirect(string $provider)
    {
        return Socialite::driver($provider)->redirect();
    }

    public function callback(string $provider)
    {
        try {
            $socialUser = Socialite::driver($provider)->user();
        } catch (\Throwable $e) {
            return redirect()->route('customer.login')
                ->with('error', 'সোশ্যাল লগিন ব্যর্থ হয়েছে। আবার চেষ্টা করুন।');
        }

        $email = $socialUser->getEmail();

        // If a customer already has this email, log them in directly
        if ($email) {
            $customer = Customer::where('email', $email)->first();
            if ($customer) {
                Auth::guard('customer')->login($customer, true);
                return redirect()->intended(route('customer.account'));
            }
        }

        // No existing account — redirect to register with social data pre-filled
        return redirect()->route('customer.register')
            ->with('social_name', $socialUser->getName())
            ->with('social_email', $email)
            ->with('social_provider', $provider)
            ->with('info', ucfirst($provider) . ' একাউন্ট যাচাই হয়েছে। মোবাইল নাম্বার দিয়ে রেজিস্ট্রেশন সম্পন্ন করুন।');
    }
}
