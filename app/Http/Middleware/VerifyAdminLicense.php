<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class VerifyAdminLicense
{
    public function handle(Request $request, Closure $next): Response
    {
        $domain = strtolower(str_replace(['www.', 'http://', 'https://'], '', $request->getHost()));

        if ($domain === 'bmitltd.com') {
            return $next($request);
        }

        $path = ltrim($request->path(), '/');
        if (in_array($path, ['admin/license-locked', 'admin/license-check', 'super/7575/clear'], true)) {
            return $next($request);
        }

        if (Cache::get('admin_panel_server_invalid_' . md5($domain))) {
            return redirect()->away(
                'https://www.bmitltd.com/license?domain=' . urlencode($domain)
            );
        }

        return $next($request);
    }
}
