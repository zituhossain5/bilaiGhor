<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    private array $allowedProviders = ['google', 'facebook'];

    public function redirect(Request $request, string $provider)
    {
        $this->abortIfUnsupportedProvider($provider);
        $this->rememberIntendedUrl($request);

        return Socialite::driver($provider)->redirect();
    }

    public function callback(Request $request, string $provider)
    {
        $this->abortIfUnsupportedProvider($provider);

        if ($request->has('error')) {
            Toastr::error('Social login was cancelled. Please try again.', 'Login failed');
            return redirect()->route('customer.login');
        }

        try {
            $socialUser = Socialite::driver($provider)->user();
        } catch (\Throwable $e) {
            Log::warning('Customer social login callback failed', [
                'provider' => $provider,
                'message' => $e->getMessage(),
            ]);

            Toastr::error('Social login failed. Please try again.', 'Login failed');
            return redirect()->route('customer.login')
                ->with('error', 'Social login failed. Please try again.');
        }

        $providerId = (string) $socialUser->getId();
        $providerColumn = $provider . '_id';
        $email = $socialUser->getEmail();
        $name = trim((string) $socialUser->getName()) ?: trim((string) $socialUser->getNickname()) ?: ucfirst($provider) . ' Customer';
        $avatar = $socialUser->getAvatar();

        try {
            $customer = DB::transaction(function () use ($providerColumn, $providerId, $email, $name) {
                $customer = Customer::where($providerColumn, $providerId)->lockForUpdate()->first();

                if (!$customer && $email) {
                    $customer = Customer::where('email', $email)->lockForUpdate()->first();
                }

                if ($customer) {
                    if (empty($customer->{$providerColumn})) {
                        $customer->{$providerColumn} = $providerId;
                    } elseif ((string) $customer->{$providerColumn} !== $providerId) {
                        throw new \RuntimeException('This social account is already linked to another customer.');
                    }

                    if (!$customer->email && $email) {
                        $customer->email = $email;
                    }

                    if (!$customer->name && $name) {
                        $customer->name = $name;
                    }

                    $customer->verify = 1;
                    $customer->status = $customer->status ?: 'active';
                    $customer->save();

                    return $customer;
                }

                return Customer::create([
                    'name' => $name,
                    'slug' => $this->uniqueCustomerSlug($name),
                    'phone' => null,
                    'email' => $email,
                    $providerColumn => $providerId,
                    'password' => Hash::make(Str::random(48)),
                    'verify' => 1,
                    'status' => 'active',
                ]);
            });

            $this->assignCustomerRole($customer);
        } catch (\Throwable $e) {
            Log::warning('Customer social login linking failed', [
                'provider' => $provider,
                'provider_id' => $providerId,
                'email' => $email,
                'avatar_received' => (bool) $avatar,
                'message' => $e->getMessage(),
            ]);

            Toastr::error('We could not complete social login. Please try again.', 'Login failed');
            return redirect()->route('customer.login')
                ->with('error', 'We could not complete social login. Please try again.');
        }

        Auth::guard('customer')->login($customer, true);
        $request->session()->regenerate();

        Toastr::success('You are login successfully', 'success!');
        return redirect()->intended(route('customer.account'));
    }

    private function abortIfUnsupportedProvider(string $provider): void
    {
        abort_unless(in_array($provider, $this->allowedProviders, true), 404);
    }

    private function rememberIntendedUrl(Request $request): void
    {
        if ($request->session()->has('url.intended')) {
            return;
        }

        $previous = url()->previous();
        $current = $request->fullUrl();

        if (!$previous || $previous === $current || !Str::startsWith($previous, url('/'))) {
            return;
        }

        if (Str::contains($previous, [
            route('customer.login', [], false),
            route('customer.register', [], false),
            '/customer/auth/',
        ])) {
            return;
        }

        $request->session()->put('url.intended', $previous);
    }

    private function uniqueCustomerSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'customer';
        $slug = $base;
        $counter = 1;

        while (Customer::where('slug', $slug)->exists()) {
            $slug = $base . '-' . Str::lower(Str::random(6)) . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    private function assignCustomerRole(Customer $customer): void
    {
        try {
            $role = \Spatie\Permission\Models\Role::firstOrCreate(
                ['name' => 'customer', 'guard_name' => 'customer'],
                ['name' => 'customer', 'guard_name' => 'customer']
            );

            if (!$customer->hasRole($role)) {
                $customer->assignRole($role);
            }
        } catch (\Throwable $e) {
            Log::warning('Customer social login role assignment failed', [
                'customer_id' => $customer->id,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
