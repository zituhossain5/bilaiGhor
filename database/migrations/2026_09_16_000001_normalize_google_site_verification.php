<?php

use App\Support\GoogleSiteVerification;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('seo_settings') ||
            ! Schema::hasColumn('seo_settings', 'search_console_verification')) {
            return;
        }

        DB::table('seo_settings')
            ->select('id', 'search_console_verification')
            ->orderBy('id')
            ->each(function ($setting): void {
                $normalized = GoogleSiteVerification::normalize(
                    $setting->search_console_verification
                );

                if ($normalized !== $setting->search_console_verification) {
                    DB::table('seo_settings')
                        ->where('id', $setting->id)
                        ->update(['search_console_verification' => $normalized]);
                }
            });
    }

    public function down(): void
    {
        // Normalization is intentionally irreversible; the token remains valid.
    }
};
