<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     *
     * Note: In Laravel 12, route registration is handled in bootstrap/app.php.
     * This method now only configures rate limiting.
     *
     * @return void
     */
    public function boot()
    {
        $this->configureRateLimiting();

        // Route registration is now handled in bootstrap/app.php (Laravel 12)
        // No need to register routes here anymore
    }

    /**
     * Configure the rate limiters for the application.
     *
     * @return void
     */
    protected function configureRateLimiting()
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // Guest tracking uses a bearer secret. Limit guesses independently of other API routes.
        RateLimiter::for('mobile-order-track', fn (Request $request) => [
            Limit::perMinute(5)->by('mobile-order-track:minute:' . $request->ip()),
            Limit::perHour(20)->by('mobile-order-track:hour:' . $request->ip()),
        ]);

        // Public blog comment form: at most 3 per minute and 10 per hour from one IP.
        RateLimiter::for('blog-comments', function (Request $request) {
            return [
                Limit::perMinute(3)->by('blog-comments:m:' . $request->ip()),
                Limit::perHour(10)->by('blog-comments:h:' . $request->ip()),
            ];
        });

        // Customer auth/OTP forms. Each limiter keeps its own counter (plain "throttle:N,M"
        // shares one per-IP counter across every throttled route).
        RateLimiter::for('customer-signin', function (Request $request) {
            return [
                Limit::perMinute(10)->by('signin:ip:' . $request->ip()),
                Limit::perMinutes(15, 20)->by('signin:login:' . strtolower((string) $request->input('login'))),
            ];
        });
        RateLimiter::for('customer-register', fn (Request $request) => Limit::perMinutes(10, 10)->by('register:' . $request->ip()));
        RateLimiter::for('account-verify', fn (Request $request) => Limit::perMinute(10)->by('verify:' . $request->ip()));
        RateLimiter::for('otp-resend', fn (Request $request) => Limit::perMinutes(10, 3)->by('otp-resend:' . $request->ip()));
        // Admin customer search (typing in the invoice customer picker / mobile field).
        RateLimiter::for('admin-customer-search', fn (Request $request) => Limit::perMinute(120)->by('customer-search:' . ($request->user('admin')?->id ?: $request->ip())));
        RateLimiter::for('checkout-otp-resend', fn (Request $request) => Limit::perMinutes(10, 5)->by('checkout-otp:' . $request->ip()));
    }
}
