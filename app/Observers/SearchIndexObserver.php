<?php

namespace App\Observers;

use App\Services\ProductSearchService;

/**
 * Drops the cached search index whenever searchable catalog text changes
 * (products, categories, brands, kitten packs). Query-builder updates skip model
 * events, so the index also expires on its own after a few minutes.
 */
class SearchIndexObserver
{
    public function saved(): void
    {
        ProductSearchService::flush();
    }

    public function deleted(): void
    {
        ProductSearchService::flush();
    }
}
