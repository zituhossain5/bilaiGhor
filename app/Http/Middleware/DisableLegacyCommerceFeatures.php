<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DisableLegacyCommerceFeatures
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('business.vendor_enabled') && $this->isVendorRequest($request)) {
            abort(404);
        }

        if (! config('business.reseller_enabled') && $this->isResellerRequest($request)) {
            abort(404);
        }

        return $next($request);
    }

    private function isVendorRequest(Request $request): bool
    {
        $routeName = (string) optional($request->route())->getName();

        return $request->is('vendor', 'vendor/*', 'admin/vendor*', 'admin/vendors*', 'shop/*', 'sellers')
            || str_starts_with($routeName, 'vendor.')
            || str_starts_with($routeName, 'admin.vendor')
            || $routeName === 'sellers';
    }

    private function isResellerRequest(Request $request): bool
    {
        $routeName = (string) optional($request->route())->getName();

        return $request->is('reseller', 'reseller/*', 'r/*', 'admin/reseller*')
            || str_starts_with($routeName, 'reseller.')
            || str_starts_with($routeName, 'admin.reseller');
    }
}
