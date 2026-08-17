<?php

namespace App\Http\Middleware;

use App\Services\LicenseVerificationService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AppSessionHandler
{
    public function __construct(private readonly LicenseVerificationService $license)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $redirect = $this->license->redirectIfMissingLicense($request);

        if ($redirect !== null) {
            return $redirect;
        }

        $this->license->bootstrapValidatedCache($request);

        return $next($request);
    }
}
