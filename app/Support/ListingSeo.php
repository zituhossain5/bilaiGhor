<?php

namespace App\Support;

use Illuminate\Http\Request;

/** SEO policy for public product collections. Query parameters remain available to filters/attribution. */
final class ListingSeo
{
    private const FILTER_PARAMETERS = [
        'subcategory', 'brand', 'weight', 'life_stage', 'flavor',
        'min_price', 'max_price', 'sort', 'sold',
    ];

    private const TRACKING_PARAMETERS = [
        'fbclid', 'gclid', 'ttclid', 'twclid', 'msclkid',
    ];

    public function __construct(
        public readonly string $canonical,
        public readonly string $robots,
    ) {
    }

    /**
     * The optional preferred URL is a verified subcategory belonging to this category.
     * It is supplied by the category controller, which already loads active children.
     */
    public static function collection(Request $request, string $baseUrl, ?string $preferredUrl = null): self
    {
        $query = $request->query();
        $nonTracking = array_filter(
            array_keys($query),
            fn (string $key): bool => !self::isTrackingParameter($key)
        );
        $contentParameters = array_diff($nonTracking, ['page']);
        // Unknown parameters also get noindex: they do not define a curated SEO landing page.
        // Known filter names are listed above so changes to the filter UI can be audited here.
        $hasKnownFilter = array_intersect($contentParameters, self::FILTER_PARAMETERS) !== [];
        $hasUnrecognizedParameter = array_diff($contentParameters, self::FILTER_PARAMETERS) !== [];
        $hasFilter = $hasKnownFilter || $hasUnrecognizedParameter;

        $page = $query['page'] ?? null;
        $validPage = $page === null || (is_scalar($page) && preg_match('/^[1-9][0-9]*$/D', (string) $page) === 1);
        $pageNumber = $validPage && $page !== null ? (int) $page : 1;

        $canonical = $preferredUrl ?? $baseUrl;
        // A subcategory-only filter has the same product set and pagination as its dedicated URL.
        $subcategoryOnly = $preferredUrl !== null && array_diff($contentParameters, ['subcategory']) === [];
        if ($validPage && $pageNumber > 1 && (!$hasFilter || $subcategoryOnly)) {
            $canonical .= '?page=' . $pageNumber;
        }

        return new self(
            $canonical,
            ($hasFilter || !$validPage) ? 'noindex,follow' : 'index,follow'
        );
    }

    public static function search(string $baseUrl): self
    {
        return new self($baseUrl, 'noindex,follow');
    }

    private static function isTrackingParameter(string $key): bool
    {
        $key = strtolower($key);

        return str_starts_with($key, 'utm_') || in_array($key, self::TRACKING_PARAMETERS, true);
    }
}
