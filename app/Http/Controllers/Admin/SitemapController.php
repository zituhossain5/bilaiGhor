<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SitemapService;

class SitemapController extends Controller
{
    public function index()
    {
        return view('backEnd.sitemap.index');
    }

    public function generate(SitemapService $sitemapService)
    {
        $result = $sitemapService->generate(base_path('sitemap.xml'));

        return redirect()->back()->with(
            'success',
            "Sitemap generated successfully with {$result['urls']} canonical URLs."
        );
    }
}
