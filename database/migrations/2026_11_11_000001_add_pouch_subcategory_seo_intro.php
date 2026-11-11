<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const OLD_TITLE = 'Cat Pouch Price in BD | Whiskas, Nekko & More | Bilai Ghor';
    private const OLD_H1 = 'Cat Food Pouch Price in Bangladesh';
    private const OLD_DESCRIPTION_HASH = 'c93459157e4c20d2954be8ce375c7dd9711bd596283e7f24f927b7520d4bdf27';

    public function up(): void
    {
        if (!Schema::hasColumn('subcategories', 'seo_intro')) {
            Schema::table('subcategories', function (Blueprint $table): void {
                $table->text('seo_intro')->nullable()->after('meta_description');
            });
        }

        $pouch = DB::table('subcategories')->where('slug', 'pouch')->first();
        if (!$pouch || (int) $pouch->status !== 1
            || !DB::table('categories')->where('id', $pouch->category_id)->where('slug', 'cat-food')->where('status', 1)->exists()) {
            return;
        }

        // Do not overwrite an admin's later edits or a different production record.
        if ($pouch->meta_title !== self::OLD_TITLE || $pouch->seo_h1 !== self::OLD_H1) {
            Log::warning('Pouch SEO migration skipped: existing SEO fields differ from the audited content.');
            return;
        }

        $content = require base_path('database/seo/pouch.php');
        $changes = [
            'meta_title' => $content['meta_title'],
            'seo_h1' => $content['seo_h1'],
            'meta_description' => $content['meta_description'],
            'seo_intro' => $content['seo_intro'],
            'updated_at' => now(),
        ];

        if (hash('sha256', (string) $pouch->full_description) === self::OLD_DESCRIPTION_HASH) {
            $changes['full_description'] = $content['full_description'];
        } else {
            Log::warning('Pouch SEO migration preserved a changed full description; review it in the subcategory editor.');
        }

        DB::table('subcategories')->where('id', $pouch->id)->update($changes);
    }

    public function down(): void
    {
        // Content is deliberately not rolled back: later admin edits must never be overwritten.
        if (Schema::hasColumn('subcategories', 'seo_intro')) {
            Schema::table('subcategories', fn (Blueprint $table) => $table->dropColumn('seo_intro'));
        }
    }
};
