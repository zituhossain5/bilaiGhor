<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Auth;
class Customer
{
    public function handle($request, Closure $next)
    {
        if (Auth::guard('customer')->user()){
            $response = $next($request);

            // Authenticated pages must never be served from browser cache,
            // otherwise the back button shows them after logout.
            $response->headers->set('Cache-Control', 'no-cache, no-store, max-age=0, must-revalidate');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', '0');

            return $response;
        }
        return redirect()->route('customer.login');
    }
}
