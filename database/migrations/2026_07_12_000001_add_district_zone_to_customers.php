<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Profile Edit moves from the legacy district-name + area (legacy_district_areas)
     * pair to the District → Zone system used by customer addresses.
     * Legacy `district` / `area` columns are intentionally KEPT (data preserved).
     */
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->unsignedBigInteger('district_id')->nullable()->after('area')->index();
            $table->unsignedInteger('zone_id')->nullable()->after('district_id')->index();

            $table->foreign('district_id')->references('id')->on('districts')->nullOnDelete();
            $table->foreign('zone_id')->references('id')->on('delivery_zones')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropForeign(['district_id']);
            $table->dropForeign(['zone_id']);
            $table->dropColumn(['district_id', 'zone_id']);
        });
    }
};
