<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * 301-redirects retired URLs listed in config/seo_redirects.php before routing runs.
 */
class SeoRedirects
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('GET') || $request->isMethod('HEAD')) {
            $target = $this->targetFor($request->getPathInfo());

            if ($target !== null) {
                $query = $request->getQueryString();

                return redirect()->to(url($target) . ($query ? '?' . $query : ''), 301);
            }
        }

        return $next($request);
    }

    private function targetFor(string $path): ?string
    {
        $map = config('seo_redirects', []);
        if (!$map) {
            return null;
        }

        // "/category/cat-care-%26-health" and "/category/cat-care-&amp;-health" (old sitemap) both match "&".
        $key = mb_strtolower(rtrim(html_entity_decode(rawurldecode($path), ENT_QUOTES | ENT_HTML5, 'UTF-8'), '/'));

        foreach ($map as $old => $new) {
            if (mb_strtolower(rtrim($old, '/')) === $key) {
                return $new;
            }
        }

        return null;
    }
}
