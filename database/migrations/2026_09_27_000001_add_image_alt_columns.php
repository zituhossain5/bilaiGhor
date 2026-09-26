<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SEO/accessibility alt text for every public content image.
 * Convention: `<image column>_alt`, matching the existing banners.image_alt.
 */
return new class extends Migration
{
    /** table => [image column => alt column] */
    private const COLUMNS = [
        'productimages'    => ['image' => 'image_alt'],
        'products'         => ['meta_image' => 'meta_image_alt'],
        'kitten_packs'     => ['image' => 'image_alt'],
        'blogs'            => ['image' => 'image_alt'],
        'testimonials'     => ['image' => 'image_alt'],
        'categories'       => ['image' => 'image_alt'],
        'subcategories'    => ['image' => 'image_alt'],
        'brands'           => ['image' => 'image_alt'],
        'campaigns'        => [
            'banner'      => 'banner_alt',
            'image_one'   => 'image_one_alt',
            'image_two'   => 'image_two_alt',
            'image_three' => 'image_three_alt',
        ],
        'campaign_reviews' => ['image' => 'image_alt'],
        'popups'           => ['image' => 'image_alt'],
        'general_settings' => ['og_baner' => 'og_baner_alt'],
    ];

    public function up(): void
    {
        foreach (self::COLUMNS as $table => $columns) {
            Schema::table($table, function (Blueprint $blueprint) use ($table, $columns) {
                foreach ($columns as $imageColumn => $altColumn) {
                    if (Schema::hasColumn($table, $altColumn)) {
                        continue;
                    }
                    $column = $blueprint->string($altColumn)->nullable();
                    if (Schema::hasColumn($table, $imageColumn)) {
                        $column->after($imageColumn);
                    }
                }
            });
        }
    }

    public function down(): void
    {
        foreach (self::COLUMNS as $table => $columns) {
            Schema::table($table, function (Blueprint $blueprint) use ($table, $columns) {
                $existing = array_values(array_filter($columns, fn ($alt) => Schema::hasColumn($table, $alt)));
                if ($existing) {
                    $blueprint->dropColumn($existing);
                }
            });
        }
    }
};
