<?php

namespace App\Services;

use App\Models\Blog;
use App\Models\Category;
use App\Models\CreatePage;
use App\Models\Product;
use App\Models\Subcategory;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;

class SitemapService
{
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
            ?float $priority = null
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

            $sitemap->add($tag);
            $seen[$url] = true;
        };

        $add($this->routeUrl('home'), null, Url::CHANGE_FREQUENCY_DAILY, 1.0);
        $add($this->routeUrl('blogs'), null, Url::CHANGE_FREQUENCY_WEEKLY, 0.6);
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
            ->orderBy('id')
            ->chunkById(500, function ($products) use ($add): void {
                foreach ($products as $product) {
                    $add(
                        $this->routeUrl('product', ['id' => $product->slug]),
                        $product->updated_at,
                        Url::CHANGE_FREQUENCY_WEEKLY,
                        0.7
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

        $sitemap->writeToFile($path);

        return ['path' => $path, 'urls' => count($seen)];
    }

    private function routeUrl(string $name, array $parameters = []): string
    {
        $root = rtrim((string) config('sitemap.canonical_url'), '/');
        $path = route($name, $parameters, false);

        return $root.'/'.ltrim($path, '/');
    }
}
