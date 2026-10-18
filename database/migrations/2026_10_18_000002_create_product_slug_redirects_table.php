<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Old product slugs → product, so /product/{old-slug} 301-redirects to the current URL
 * (same idea as blog_slug_redirects). Filled by products:clean-slugs and by admin renames.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_slug_redirects', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id')->index();   // products.id is BIGINT UNSIGNED
            $table->string('old_slug')->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_slug_redirects');
    }
};
