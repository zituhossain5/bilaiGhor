<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\ProductSlugRedirect;
use App\Services\ProductSearchService;
use App\Services\SitemapService;
use App\Support\ProductSlug;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Rewrites product slugs to the clean form (Str::slug(name)-{id}) and keeps every old URL working
 * through product_slug_redirects (301). Only `slug` changes on products; nothing else.
 */
class CleanProductSlugs extends Command
{
    protected $signature = 'products:clean-slugs
        {--dry-run : Only list old → new slugs, change nothing}
        {--force : Do not ask for confirmation}';

    protected $description = 'Clean product URL slugs (no %-encoded characters) and 301-redirect the old URLs';

    public function handle(SitemapService $sitemap): int
    {
        if (!Schema::hasTable('product_slug_redirects')) {
            $this->error('Run the migrations first (php artisan migrate) — product_slug_redirects is missing.');

            return self::FAILURE;
        }

        $changes = [];
        foreach (Product::query()->select('id', 'name', 'slug', 'status')->orderBy('id')->cursor() as $product) {
            $new = ProductSlug::make($product->name, $product->id);
            if ($new !== $product->slug) {
                $changes[] = ['id' => $product->id, 'old' => (string) $product->slug, 'new' => $new, 'active' => (bool) $product->status];
            }
        }

        if (!$changes) {
            $this->info('All product slugs are already clean.');

            return self::SUCCESS;
        }

        $this->table(['id', 'old slug', 'new slug', 'active'], array_map(
            fn ($c) => [$c['id'], $c['old'], $c['new'], $c['active'] ? 'yes' : 'no'], $changes
        ));
        $this->info(count($changes) . ' product slug(s) to change.');

        if ($this->option('dry-run')) {
            $this->warn('Dry run: nothing was changed.');

            return self::SUCCESS;
        }

        if (!$this->option('force') && !$this->confirm('Change these slugs and 301-redirect the old URLs?')) {
            $this->warn('Cancelled. Nothing was changed.');

            return self::SUCCESS;
        }

        foreach ($changes as $c) {
            DB::transaction(function () use ($c) {
                if ($c['old'] !== '') {
                    ProductSlugRedirect::updateOrCreate(['old_slug' => $c['old']], ['product_id' => $c['id']]);
                }
                ProductSlugRedirect::where('old_slug', $c['new'])->delete();
                // Query builder: only the slug changes (no updated_at bump, no model events per product).
                DB::table('products')->where('id', $c['id'])->update(['slug' => $c['new']]);
            });
            Cache::forget('product_details_' . $c['old']);
            Cache::forget('product_details_' . $c['new']);
        }

        rescue(fn () => ProductSearchService::flush());
        $result = $sitemap->generate(base_path('sitemap.xml'));

        $this->info(count($changes) . ' slug(s) changed; old URLs now 301 to the new ones.');
        $this->info("Sitemap regenerated: {$result['urls']} URLs.");

        return self::SUCCESS;
    }
}
