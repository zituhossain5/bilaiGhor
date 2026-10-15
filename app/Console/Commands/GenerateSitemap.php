<?php

namespace App\Console\Commands;

use App\Services\SitemapService;
use Illuminate\Console\Command;

class GenerateSitemap extends Command
{
    protected $signature = 'sitemap:generate';

    protected $description = 'Rebuild sitemap.xml (project root) from the live products, categories, blogs and pages';

    public function handle(SitemapService $sitemap): int
    {
        $result = $sitemap->generate(base_path('sitemap.xml'));

        $this->info("Sitemap written to {$result['path']} with {$result['urls']} URLs.");

        return self::SUCCESS;
    }
}
