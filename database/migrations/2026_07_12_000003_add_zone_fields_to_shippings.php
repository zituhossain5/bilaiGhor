<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Checkout now collects Post Code + District + Zone. `shippings` already has
     * district_id, but nothing to store the zone or the post code in — so an order
     * placed with the new form could not persist either value. These two columns
     * are additive and nullable; division_id / upazila_id are kept untouched so all
     * historical shipping rows keep their data.
     */
    public function up(): void
    {
        Schema::table('shippings', function (Blueprint $table) {
            $table->unsignedInteger('zone_id')->nullable()->after('district_id')->index();
            $table->string('post_code', 20)->nullable()->after('zone_id');

            $table->foreign('zone_id')->references('id')->on('delivery_zones')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('shippings', function (Blueprint $table) {
            $table->dropForeign(['zone_id']);
            $table->dropColumn(['zone_id', 'post_code']);
        });
    }
};
