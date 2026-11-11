<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Childcategory;
use App\Models\Contact;
use App\Models\GeneralSetting;
use App\Models\Product;
use App\Models\Subcategory;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

class ListingSeoHtmlTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        GeneralSetting::firstOrCreate(['status' => 1], [
            'name' => 'Bilai Ghor',
            'white_logo' => 'logo.png',
            'dark_logo' => 'logo.png',
            'favicon' => 'favicon.ico',
        ]);
        Contact::firstOrCreate(['status' => 1], [
            'id' => (Contact::max('id') ?? 0) + 1,
            'phone' => '01900000000',
            'email' => 'info@example.test',
            'address' => 'Dhaka',
        ]);
    }

    public function test_collection_pages_emit_one_canonical_and_one_robots_tag(): void
    {
        $suffix = strtolower(Str::random(10));
        $category = Category::create([
            'name' => 'SEO Test Cat Food',
            'slug' => 'seo-test-cat-food-' . $suffix,
            'parent_id' => 0,
            'status' => 1,
        ]);
        $subcategory = Subcategory::create([
            'subcategoryName' => 'SEO Test Dry Food',
            'slug' => 'seo-test-dry-food-' . $suffix,
            'category_id' => $category->id,
            'status' => 1,
        ]);

        $categoryPath = '/category/' . $category->slug;
        $subcategoryPath = '/subcategory/' . $subcategory->slug;
        $categoryUrl = route('category', $category->slug);
        $subcategoryUrl = route('subcategory', $subcategory->slug);

        $this->assertSeo($categoryPath, $categoryUrl, 'index,follow');
        $this->assertSeo($subcategoryPath, $subcategoryUrl, 'index,follow');
        $this->assertSeo($categoryPath . '?subcategory=' . $subcategory->slug, $subcategoryUrl, 'noindex,follow');
        $this->assertSeo($subcategoryPath . '?brand=44', $subcategoryUrl, 'noindex,follow');
        $this->assertSeo($subcategoryPath . '?brand=44&weight%5B%5D=1', $subcategoryUrl, 'noindex,follow');
        $this->assertSeo($subcategoryPath . '?sort=4', $subcategoryUrl, 'noindex,follow');
        $this->assertSeo($subcategoryPath . '?page=2', $subcategoryUrl . '?page=2', 'index,follow');
        $this->assertSeo($subcategoryPath . '?brand=44&page=2', $subcategoryUrl, 'noindex,follow');
        $this->assertSeo($subcategoryPath . '?utm_source=facebook&fbclid=test123', $subcategoryUrl, 'index,follow');
        $this->assertSeo('/shop?brand=44', route('shop'), 'noindex,follow');
        $this->assertSeo('/shop?page=2', route('shop') . '?page=2', 'index,follow');
        $this->assertSeo('/all-products', route('shop'), 'index,follow');
        $this->assertSeo('/search?keyword=seo-test-' . $suffix, route('search'), 'noindex,follow');

        $childcategory = Childcategory::create([
            'childcategoryName' => 'SEO Test Child',
            'slug' => 'seo-test-child-' . $suffix,
            'subcategory_id' => $subcategory->id,
            'status' => 1,
        ]);
        $this->assertSeo('/products/' . $childcategory->slug . '?sort=4', route('products', $childcategory->slug), 'noindex,follow');

        $product = Product::create([
            'name' => 'SEO Test Product',
            'slug' => 'seo-test-product-' . $suffix,
            'category_id' => $category->id,
            'subcategory_id' => $subcategory->id,
            'product_code' => 'SEO-' . $suffix,
            'purchase_price' => 100,
            'new_price' => 150,
            'stock' => 3,
            'status' => 1,
            'approval_status' => 'approved',
        ]);
        $this->assertSeo('/product/' . $product->slug . '?utm_source=facebook&fbclid=test123', route('product', $product->slug), 'index,follow');
    }

    private function assertSeo(string $path, string $canonical, string $robots): void
    {
        $html = $this->get($path)->assertOk()->getContent();

        preg_match_all('/<link rel="canonical" href="([^"]+)"/i', $html, $canonicalMatches);
        preg_match_all('/<meta name="robots" content="([^"]+)"/i', $html, $robotsMatches);

        $this->assertSame([$canonical], $canonicalMatches[1], $path . ' canonical');
        $this->assertSame([$robots], array_map(fn (string $value) => str_replace(' ', '', $value), $robotsMatches[1]), $path . ' robots');
    }
}
