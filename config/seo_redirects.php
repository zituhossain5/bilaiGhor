<?php

/*
|--------------------------------------------------------------------------
| Permanent (301) redirects for retired URLs
|--------------------------------------------------------------------------
| Old path => new path, both decoded and without the domain. Applied before routing by
| App\Http\Middleware\SeoRedirects; matching ignores letter case and a trailing slash, and
| the query string (utm_*, fbclid…) is carried over. Blog posts whose slug was changed in
| admin are handled separately by the blog_slug_redirects table.
*/

return [
    // URLs from the old sitemap that no longer exist
    '/category/cat-foods'           => '/category/cat-food',
    '/category/litters'             => '/category/cat-litter',
    '/category/cat-clothes-&-beds'  => '/category/cat-accessories',
    '/subcategory/pouches'          => '/subcategory/pouch',
    '/subcategory/wet-food'         => '/subcategory/pouch',
    '/blog/how-many-times-a-day-should-a-cat-be-fed-1782409277' => '/blogs',

    // Slugs that contained "&" (renamed by migration 2026_10_15_000001)
    '/category/cat-care-&-health'   => '/category/cat-care-and-health',
    '/page/terms-&-conditions'      => '/page/terms-and-conditions',
];
