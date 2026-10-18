<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SEO fields for brand pages (/brand/{slug}). All nullable: an empty field falls back to the
 * generated title / H1 / description. Existing brand data is untouched.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('brands', function (Blueprint $table) {
            $table->string('seo_title')->nullable()->after('slug');
            $table->text('meta_description')->nullable()->after('seo_title');
            $table->string('seo_h1')->nullable()->after('meta_description');
            $table->longText('seo_content')->nullable()->after('seo_h1');
        });
    }

    public function down(): void
    {
        Schema::table('brands', function (Blueprint $table) {
            $table->dropColumn(['seo_title', 'meta_description', 'seo_h1', 'seo_content']);
        });
    }
};
