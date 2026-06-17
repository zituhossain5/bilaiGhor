<?php

namespace App\Console\Commands;

use App\Services\LicenseVerificationService;
use Illuminate\Console\Command;

class LicenseCacheStatus extends Command
{
    protected $signature = 'license:cache-status';

    protected $description = '১০০ ঘণ্টা verified লাইসেন্স ক্যাশ স্ট্যাটাস দেখুন';

    public function handle(LicenseVerificationService $license): int
    {
        $s = $license->getCacheStatus();

        $this->info('=== License 100h Cache Status ===');
        $this->line('Cache driver: ' . ($s['driver'] ?? 'n/a'));
        $this->line('Cache key: ' . $s['cache_key']);
        $this->line('Active: ' . ($s['active'] ? 'YES' : 'NO'));
        $this->line('State: ' . $s['state']);
        $this->line('Configured hours: ' . $s['hours_configured']);
        $this->line('Cached at: ' . ($s['cached_at'] ?? '—'));
        $this->line('Expires at: ' . ($s['expires_at'] ?? '—'));
        $this->line('Remaining: ' . ($s['remaining_label'] ?? '—'));
        $this->line('API skipped: ' . ($s['api_skipped'] ? 'YES' : 'NO'));
        $this->line('Admin locked: ' . ($s['admin_locked'] ? 'YES' : 'NO'));

        return self::SUCCESS;
    }
}
