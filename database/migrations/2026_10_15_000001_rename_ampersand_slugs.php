<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Data only (no schema change): "&" in a slug becomes "&amp;" / "%26" in links and sitemaps.
 * The old URLs keep working as 301s via config/seo_redirects.php.
 */
return new class extends Migration
{
    private const RENAMES = [
        'categories'   => ['cat-care-&-health' => 'cat-care-and-health'],
        'create_pages' => ['terms-&-conditions' => 'terms-and-conditions'],
    ];

    public function up(): void
    {
        $this->rename(fn (array $pairs) => $pairs);
    }

    public function down(): void
    {
        $this->rename(fn (array $pairs) => array_flip($pairs));
    }

    private function rename(callable $direction): void
    {
        DB::transaction(function () use ($direction) {
            foreach (self::RENAMES as $table => $pairs) {
                foreach ($direction($pairs) as $from => $to) {
                    // Skip if the new slug is already taken, so a re-run never creates duplicates.
                    if (DB::table($table)->where('slug', $to)->exists()) {
                        continue;
                    }
                    // Keep updated_at: the content didn't change (set explicitly in case of ON UPDATE CURRENT_TIMESTAMP).
                    DB::table($table)->where('slug', $from)->update(['slug' => $to, 'updated_at' => DB::raw('`updated_at`')]);
                }
            }
        });
    }
};
