<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedBigInteger('weight_id')->nullable()->after('brand_id');
            $table->unsignedBigInteger('life_stage_id')->nullable()->after('weight_id');
            $table->unsignedBigInteger('flavor_id')->nullable()->after('life_stage_id');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['weight_id', 'life_stage_id', 'flavor_id']);
        });
    }
};
