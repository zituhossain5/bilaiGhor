<?php

use App\Models\ResellerLandingPage;

if (!function_exists('landing_url')) {
    /**
     * Generate URL for reseller landing - uses custom domain when applicable.
     */
    function landing_url(string $slug, string $path = ''): string
    {
        $landing = ResellerLandingPage::where('slug', $slug)->first();
        $host = strtolower(request()->getHost());

        if ($landing && $landing->custom_domain && strtolower($landing->custom_domain) === $host) {
            return $path === '' ? url('/') : url($path);
        }

        $base = '/r/' . $slug;
        return $path === '' ? url($base) : url(rtrim($base . '/' . ltrim($path, '/'), '/'));
    }
}

if (!function_exists('asset_v')) {
    /**
     * asset() plus "?v=<file modification time>": the URL changes whenever the file changes
     * (including after a deploy), so browsers may cache it for a year (see .htaccess).
     */
    function asset_v(string $path): string
    {
        static $versions = [];
        if (!array_key_exists($path, $versions)) {
            $file = \App\Support\ImageSize::resolve($path);
            $versions[$path] = $file ? @filemtime($file) : false;
        }

        return asset($path) . ($versions[$path] ? '?v=' . $versions[$path] : '');
    }
}
