<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Delivery zones under a district (Division → District → Zone).
     * The existing upazilas table is preserved untouched for future use.
     */
    public function up(): void
    {
        Schema::create('delivery_zones', function (Blueprint $table) {
            $table->increments('id');
            // districts.id is BIGINT UNSIGNED — FK column type must match
            $table->unsignedBigInteger('district_id')->index();
            $table->string('name', 190);
            $table->string('name_bn', 190)->nullable();
            $table->string('post_code', 20)->nullable();
            $table->decimal('delivery_charge', 10, 2)->nullable()->default(0);
            $table->boolean('status')->default(true);
            $table->integer('sort_order')->nullable()->default(0);
            $table->timestamps();

            $table->foreign('district_id')->references('id')->on('districts')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_zones');
    }
};
