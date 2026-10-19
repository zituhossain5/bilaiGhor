<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Parcel weight in kg, typed by an admin (never calculated from products). Nullable: existing orders stay empty.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('orders', 'parcel_weight_kg')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->decimal('parcel_weight_kg', 5, 2)->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('orders', 'parcel_weight_kg')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropColumn('parcel_weight_kg');
            });
        }
    }
};
