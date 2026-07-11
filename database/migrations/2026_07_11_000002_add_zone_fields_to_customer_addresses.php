<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Address form now uses District → Zone (delivery_zones).
     * Existing name/phone/email/address columns are reused as-is.
     */
    public function up(): void
    {
        Schema::table('customer_addresses', function (Blueprint $table) {
            $table->string('post_code', 20)->nullable()->after('email');
            // delivery_zones.id is INT UNSIGNED (increments)
            $table->unsignedInteger('zone_id')->nullable()->index()->after('district_id');

            $table->foreign('zone_id')->references('id')->on('delivery_zones')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('customer_addresses', function (Blueprint $table) {
            $table->dropForeign(['zone_id']);
            $table->dropColumn(['post_code', 'zone_id']);
        });
    }
};
