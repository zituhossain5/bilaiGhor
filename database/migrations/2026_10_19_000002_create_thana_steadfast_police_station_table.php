<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Our thana (thanas.id, Bangla) → Steadfast police station. One row per thana.
 * match_type: exact (auto, used), close (auto, used only after an admin confirms it),
 * manual (chosen by an admin; a null station means "no Steadfast area fits"), none (auto, nothing found).
 * The thanas table itself is never changed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('thana_steadfast_police_station', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('thana_id')->unique();
            $table->unsignedBigInteger('steadfast_police_station_id')->nullable()->index();
            $table->string('match_type', 10)->default('none');
            $table->decimal('score', 4, 3)->nullable();
            $table->unsignedBigInteger('matched_by')->nullable();  // admin id for manual choices
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('thana_steadfast_police_station');
    }
};
