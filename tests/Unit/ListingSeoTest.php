<?php

namespace Tests\Unit;

use App\Support\ListingSeo;
use App\Support\TrafficSourceDetector;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ListingSeoTest extends TestCase
{
    private const SITE = 'https://bilaighor.bd';

    public static function collectionCases(): array
    {
        return [
            'clean category' => ['/category/cat-food', '/category/cat-food', 'index,follow'],
            'clean subcategory' => ['/subcategory/dry-food', '/subcategory/dry-food', 'index,follow'],
            'brand filter' => ['/subcategory/dry-food?brand=44', '/subcategory/dry-food', 'noindex,follow'],
            'multiple filters' => ['/subcategory/dry-food?brand=44&weight%5B%5D=1', '/subcategory/dry-food', 'noindex,follow'],
            'sort' => ['/subcategory/dry-food?sort=price_low_high', '/subcategory/dry-food', 'noindex,follow'],
            'category brand filter' => ['/category/cat-food?brand=34', '/category/cat-food', 'noindex,follow'],
            'category price filter' => ['/category/cat-food?min_price=50&max_price=500', '/category/cat-food', 'noindex,follow'],
            'category subcategory filter without a verified mapping' => ['/category/cat-food?subcategory=unknown', '/category/cat-food', 'noindex,follow'],
            'unfiltered pagination' => ['/subcategory/dry-food?page=2', '/subcategory/dry-food?page=2', 'index,follow'],
            'page one' => ['/subcategory/dry-food?page=1', '/subcategory/dry-food', 'index,follow'],
            'filtered pagination' => ['/subcategory/dry-food?brand=44&page=2', '/subcategory/dry-food', 'noindex,follow'],
            'malformed pagination' => ['/subcategory/dry-food?page=0', '/subcategory/dry-food', 'noindex,follow'],
            'tracking only' => ['/category/cat-food?utm_source=facebook&fbclid=abc', '/category/cat-food', 'index,follow'],
            'tracking and page' => ['/category/cat-food?utm_source=facebook&page=2', '/category/cat-food?page=2', 'index,follow'],
            'unknown parameter' => ['/category/cat-food?random_filter=foo', '/category/cat-food', 'noindex,follow'],
        ];
    }

    #[DataProvider('collectionCases')]
    public function test_collection_policy(string $path, string $canonicalPath, string $robots): void
    {
        $request = Request::create(self::SITE . $path);
        $result = ListingSeo::collection($request, self::SITE . $request->getPathInfo());

        $this->assertSame(self::SITE . $canonicalPath, $result->canonical);
        $this->assertSame($robots, $result->robots);
    }

    public function test_verified_subcategory_filter_prefers_its_dedicated_url(): void
    {
        $request = Request::create(self::SITE . '/category/cat-food?subcategory=dry-food');
        $result = ListingSeo::collection($request, self::SITE . '/category/cat-food', self::SITE . '/subcategory/dry-food');

        $this->assertSame(self::SITE . '/subcategory/dry-food', $result->canonical);
        $this->assertSame('noindex,follow', $result->robots);

        $pageTwo = Request::create(self::SITE . '/category/cat-food?subcategory=dry-food&page=2');
        $result = ListingSeo::collection($pageTwo, self::SITE . '/category/cat-food', self::SITE . '/subcategory/dry-food');
        $this->assertSame(self::SITE . '/subcategory/dry-food?page=2', $result->canonical);
        $this->assertSame('noindex,follow', $result->robots);
    }

    public function test_subcategory_with_another_filter_still_points_to_the_clean_subcategory(): void
    {
        $request = Request::create(self::SITE . '/category/cat-food?subcategory=dry-food&brand=44&page=2');
        $result = ListingSeo::collection($request, self::SITE . '/category/cat-food', self::SITE . '/subcategory/dry-food');

        $this->assertSame(self::SITE . '/subcategory/dry-food', $result->canonical);
        $this->assertSame('noindex,follow', $result->robots);
    }

    public function test_search_is_noindex(): void
    {
        $result = ListingSeo::search(self::SITE . '/search');
        $this->assertSame('noindex,follow', $result->robots);
        $this->assertSame(self::SITE . '/search', $result->canonical);
    }

    public function test_tracking_params_stay_available_for_attribution(): void
    {
        foreach (['utm_source=facebook' => 'facebook', 'fbclid=test123' => 'facebook', 'gclid=test123' => 'google'] as $query => $source) {
            $request = Request::create(self::SITE . '/product/example?' . $query);
            $this->assertSame($source, TrafficSourceDetector::detect($request)['source']);
            $this->assertNotEmpty($request->query());
        }
    }
}
