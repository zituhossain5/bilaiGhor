<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Storefront searches that returned nothing. One row per distinct (normalized) query,
 * with a counter, so admins can see what customers look for but cannot find.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('search_misses', function (Blueprint $table) {
            $table->id();
            $table->string('query', 191);
            $table->string('normalized_query', 191)->unique();
            $table->unsignedInteger('search_count')->default(1);
            $table->timestamp('last_searched_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('search_misses');
    }
};
