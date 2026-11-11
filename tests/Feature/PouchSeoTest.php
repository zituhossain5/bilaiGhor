<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Contact;
use App\Models\GeneralSetting;
use App\Models\Product;
use App\Models\Subcategory;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class PouchSeoTest extends TestCase
{
    use DatabaseTransactions;

    public function test_pouch_page_seo_content_grid_filters_and_pagination(): void
    {
        URL::forceRootUrl('https://bilaighor.bd');
        URL::forceScheme('https');
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

        $content = require base_path('database/seo/pouch.php');
        $category = Category::create(['name' => 'Cat Food', 'slug' => 'cat-food', 'parent_id' => 0, 'status' => 1]);
        $pouch = Subcategory::create([
            'subcategoryName' => 'Pouch',
            'slug' => 'pouch',
            'category_id' => $category->id,
            'status' => 1,
        ] + $content);
        foreach (['dry-food', 'can-food'] as $slug) {
            Subcategory::create(['subcategoryName' => $slug, 'slug' => $slug, 'category_id' => $category->id, 'status' => 1]);
        }
        $brand = Brand::create(['name' => 'Test Pouch Brand', 'slug' => 'test-pouch-brand', 'status' => 1]);
        Product::create([
            'name' => 'Test Kitten Tuna Wet Food Pouch 70g',
            'slug' => 'test-kitten-tuna-pouch-70g',
            'product_code' => 'SEO-POUCH-TEST',
            'category_id' => $category->id,
            'subcategory_id' => $pouch->id,
            'brand_id' => $brand->id,
            'purchase_price' => 60,
            'old_price' => 90,
            'new_price' => 85,
            'stock' => 5,
            'status' => 1,
            'approval_status' => 'approved',
        ]);

        $base = 'https://bilaighor.bd/subcategory/pouch';
        $html = $this->get('/subcategory/pouch')->assertOk()->getContent();
        $this->assertSeo($html, $base, 'index,follow');
        $this->assertSame(1, preg_match_all('/<title\b/i', $html));
        $this->assertStringContainsString('<title>' . e($content['meta_title']) . '</title>', $html);
        $this->assertStringContainsString('<meta name="description" content="' . e($content['meta_description']) . '"', $html);
        $this->assertSame(1, preg_match_all('/<h1\b/i', $html));
        $this->assertStringContainsString('<h1>' . e($content['seo_h1']) . '</h1>', $html);
        $this->assertStringContainsString('"@type":"BreadcrumbList"', $html);
        $this->assertStringContainsString('"item":"https://bilaighor.bd/category/cat-food"', $html);
        $this->assertStringContainsString('"item":"https://bilaighor.bd/subcategory/pouch"', $html);
        $this->assertStringContainsString('Test Kitten Tuna Wet Food Pouch 70g', $html);
        $this->assertStringContainsString('&#2547;85', $html);
        $this->assertStringContainsString('href="' . route('subcategory', 'dry-food') . '"', $html);
        $this->assertStringContainsString('href="' . route('subcategory', 'can-food') . '"', $html);
        $this->assertStringContainsString('href="' . route('category', 'cat-food') . '"', $html);
        $this->assertLessThan(strpos($html, 'bilai-cat-grid'), strpos($html, 'bilai-listing-seo-intro'));
        $this->assertLessThan(strpos($html, 'Wet Cat Food Price in Bangladesh</h2>'), strpos($html, 'bilai-cat-grid'));

        $filtered = $this->get('/subcategory/pouch?brand=' . $brand->id)->assertOk()->getContent();
        $this->assertSeo($filtered, $base, 'noindex,follow');
        $this->assertStringContainsString('Test Kitten Tuna Wet Food Pouch 70g', $filtered);
        $this->assertStringContainsString('Test Pouch Brand', $filtered);
        $this->assertSeo($this->get('/subcategory/pouch?sort=4')->assertOk()->getContent(), $base, 'noindex,follow');
        $this->assertSeo($this->get('/subcategory/pouch?page=2')->assertOk()->getContent(), $base . '?page=2', 'index,follow');
        $this->assertSeo($this->get('/subcategory/pouch?brand=' . $brand->id . '&page=2')->assertOk()->getContent(), $base, 'noindex,follow');
    }

    private function assertSeo(string $html, string $canonical, string $robots): void
    {
        preg_match_all('/<link rel="canonical" href="([^"]+)"/i', $html, $canonicalMatches);
        preg_match_all('/<meta name="robots" content="([^"]+)"/i', $html, $robotsMatches);
        $this->assertSame([$canonical], $canonicalMatches[1]);
        $this->assertSame([$robots], $robotsMatches[1]);
    }
}
