<?php

namespace App\Observers;

use App\Services\SitemapService;

/**
 * Rebuilds sitemap.xml after a product, category, subcategory, blog or page is created, updated
 * or deleted. "saved" (not "updated") so view-count increments on blog posts don't trigger it.
 */
class SitemapObserver
{
    public function saved(): void
    {
        SitemapService::regenerateAfterResponse();
    }

    public function deleted(): void
    {
        SitemapService::regenerateAfterResponse();
    }
}
