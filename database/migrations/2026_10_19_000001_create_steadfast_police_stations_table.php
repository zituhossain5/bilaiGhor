<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Steadfast's own area list (GET /police_stations), refreshed by `php artisan steadfast:sync-areas`
 * or the "Sync Steadfast areas" button. `id` is Steadfast's police station id.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('steadfast_police_stations', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->unsignedBigInteger('district_id')->index();   // Steadfast district id
            $table->string('district_name', 100);                 // e.g. "Cox's Bazar", "Dhaka City"
            $table->string('name', 150);                          // e.g. "Cox's Bazar Sadar"
            $table->unsignedBigInteger('hub_id')->nullable();
            $table->string('ps_type', 50)->nullable();
            $table->text('search_tags')->nullable();
            $table->boolean('status')->default(true);             // false = no longer in Steadfast's list
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('steadfast_police_stations');
    }
};
