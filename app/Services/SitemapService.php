<?php

namespace App\Services;

use App\Models\Blog;
use App\Models\Brand;
use App\Models\Category;
use App\Models\CreatePage;
use App\Models\Product;
use App\Models\Subcategory;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;

class SitemapService
{
    private static bool $regenerationQueued = false;

    /**
     * Rebuild sitemap.xml once, after the response has been sent (admin saves stay fast).
     * Several saves in one request still rebuild only once; a failure is logged, never shown.
     */
    public static function regenerateAfterResponse(): void
    {
        if (self::$regenerationQueued) {
            return;
        }
        self::$regenerationQueued = true;

        app()->terminating(function (): void {
            self::$regenerationQueued = false;
            try {
                app(self::class)->generate();
            } catch (\Throwable $e) {
                Log::warning('Sitemap regeneration failed: ' . $e->getMessage());
            }
        });
    }

    /**
     * Build the public ecommerce sitemap from explicitly approved database content.
     *
     * @return array{path: string, urls: int}
     */
    public function generate(?string $path = null): array
    {
        $path ??= base_path('sitemap.xml');
        $sitemap = Sitemap::create();
        $seen = [];

        $add = function (
            string $url,
            ?DateTimeInterface $lastModified = null,
            ?string $frequency = null,
            ?float $priority = null,
            array $images = []
        ) use ($sitemap, &$seen): void {
            if (isset($seen[$url])) {
                return;
            }

            $tag = Url::create($url);

            if ($lastModified) {
                $tag->setLastModificationDate($lastModified);
            }
            if ($frequency) {
                $tag->setChangeFrequency($frequency);
            }
            if ($priority !== null) {
                $tag->setPriority($priority);
            }
            foreach ($images as $image) {
                $tag->addImage($image);
            }

            $sitemap->add($tag);
            $seen[$url] = true;
        };

        $latestProduct = $this->latestUpdate(Product::query()->where('status', 1)->where('approval_status', 'approved'));
        $latestBlog = $this->latestUpdate(Blog::query()->where('status', 1));

        $add($this->routeUrl('home'), max($latestProduct, $latestBlog), Url::CHANGE_FREQUENCY_DAILY, 1.0);
        $add($this->routeUrl('blogs'), $latestBlog, Url::CHANGE_FREQUENCY_WEEKLY, 0.6);
        $add($this->routeUrl('contact'), null, Url::CHANGE_FREQUENCY_YEARLY, 0.5);

        $excludedCategories = config('sitemap.excluded_category_slugs', []);

        Category::query()
            ->where('status', 1)
            ->whereNotNull('slug')
            ->where('slug', '!=', '')
            ->when($excludedCategories, fn (Builder $query) => $query->whereNotIn('slug', $excludedCategories))
            ->select('id', 'slug', 'updated_at')
            ->orderBy('id')
            ->chunkById(200, function ($categories) use ($add): void {
                foreach ($categories as $category) {
                    $add(
                        $this->routeUrl('category', ['category' => $category->slug]),
                        $category->updated_at,
                        Url::CHANGE_FREQUENCY_WEEKLY,
                        0.8
                    );
                }
            });

        Subcategory::query()
            ->where('status', 1)
            ->whereNotNull('slug')
            ->where('slug', '!=', '')
            ->whereHas('category', function (Builder $query) use ($excludedCategories): void {
                $query->where('status', 1)
                    ->when($excludedCategories, fn (Builder $categoryQuery) => $categoryQuery->whereNotIn('slug', $excludedCategories));
            })
            ->select('id', 'slug', 'category_id', 'updated_at')
            ->orderBy('id')
            ->chunkById(200, function ($subcategories) use ($add): void {
                foreach ($subcategories as $subcategory) {
                    $add(
                        $this->routeUrl('subcategory', ['subcategory' => $subcategory->slug]),
                        $subcategory->updated_at,
                        Url::CHANGE_FREQUENCY_WEEKLY,
                        0.7
                    );
                }
            });

        // Brands with at least one product on sale; lastmod = newest of the brand and its products.
        $brandUpdates = Product::query()
            ->where('status', 1)
            ->where('approval_status', 'approved')
            ->whereNotNull('brand_id')
            ->selectRaw('brand_id, MAX(updated_at) AS latest')
            ->groupBy('brand_id')
            ->pluck('latest', 'brand_id');

        $activeBrands = Brand::query()
            ->where('status', 1)
            ->whereNotNull('slug')
            ->where('slug', '!=', '')
            ->whereIn('id', $brandUpdates->keys())
            ->orderBy('name')
            ->get(['id', 'slug', 'updated_at']);

        if ($activeBrands->isNotEmpty()) {
            $add($this->routeUrl('brands'), $activeBrands->max('updated_at'), Url::CHANGE_FREQUENCY_WEEKLY, 0.6);
        }
        foreach ($activeBrands as $brand) {
            $productsAt = $brandUpdates[$brand->id] ? \Illuminate\Support\Carbon::parse($brandUpdates[$brand->id]) : null;
            $add(
                $this->routeUrl('brand.products', ['slug' => $brand->slug]),
                collect([$brand->updated_at, $productsAt])->filter()->max(),
                Url::CHANGE_FREQUENCY_WEEKLY,
                0.7
            );
        }

        Product::query()
            ->where('status', 1)
            ->where('approval_status', 'approved')
            ->whereNotNull('slug')
            ->where('slug', '!=', '')
            ->whereHas('category', function (Builder $query) use ($excludedCategories): void {
                $query->where('status', 1)
                    ->when($excludedCategories, fn (Builder $categoryQuery) => $categoryQuery->whereNotIn('slug', $excludedCategories));
            })
            ->select('id', 'slug', 'category_id', 'updated_at')
            ->with('images')
            ->orderBy('id')
            ->chunkById(500, function ($products) use ($add): void {
                foreach ($products as $product) {
                    $add(
                        $this->routeUrl('product', ['id' => $product->slug]),
                        $product->updated_at,
                        Url::CHANGE_FREQUENCY_WEEKLY,
                        0.7,
                        $product->images->pluck('image')->filter()->unique()
                            ->map(fn (string $path) => $this->fileUrl($path))->values()->all()
                    );
                }
            });

        Blog::query()
            ->where('status', 1)
            ->whereNotNull('slug')
            ->where('slug', '!=', '')
            ->when(
                config('sitemap.excluded_blog_slugs', []),
                fn (Builder $query, array $slugs) => $query->whereNotIn('slug', $slugs)
            )
            ->select('id', 'slug', 'updated_at')
            ->orderBy('id')
            ->chunkById(200, function ($blogs) use ($add): void {
                foreach ($blogs as $blog) {
                    $add(
                        $this->routeUrl('blog.details', ['slug' => $blog->slug]),
                        $blog->updated_at,
                        Url::CHANGE_FREQUENCY_MONTHLY,
                        0.6
                    );
                }
            });

        CreatePage::query()
            ->where('status', 1)
            ->whereIn('slug', config('sitemap.static_page_slugs', []))
            ->select('id', 'slug', 'updated_at')
            ->orderBy('id')
            ->chunkById(100, function ($pages) use ($add): void {
                foreach ($pages as $page) {
                    $add(
                        $this->routeUrl('page', ['slug' => $page->slug]),
                        $page->updated_at,
                        Url::CHANGE_FREQUENCY_YEARLY,
                        0.5
                    );
                }
            });

        // Write to a temp file and swap it in, so a crawler never reads a half-written sitemap.
        $tmp = $path . '.tmp';
        $sitemap->writeToFile($tmp);
        if (!@rename($tmp, $path)) {
            copy($tmp, $path);
            @unlink($tmp);
        }

        return ['path' => $path, 'urls' => count($seen)];
    }

    private function latestUpdate(Builder $query): ?DateTimeInterface
    {
        $value = $query->max('updated_at');

        return $value ? \Illuminate\Support\Carbon::parse($value) : null;
    }

    /** Absolute URL of a stored file such as "public/uploads/product/a b.webp" (segments encoded). */
    private function fileUrl(string $path): string
    {
        $root = rtrim((string) config('sitemap.canonical_url'), '/');

        return $root . '/' . implode('/', array_map('rawurlencode', explode('/', ltrim($path, '/'))));
    }

    private function routeUrl(string $name, array $parameters = []): string
    {
        $root = rtrim((string) config('sitemap.canonical_url'), '/');
        $path = route($name, $parameters, false);

        return $root.'/'.ltrim($path, '/');
    }
}
