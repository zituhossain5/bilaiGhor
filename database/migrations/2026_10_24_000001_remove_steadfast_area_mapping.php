<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('thana_steadfast_police_station');
        Schema::dropIfExists('steadfast_police_stations');

        if (Schema::hasTable('courier_settings')) {
            DB::table('courier_settings')->where('key', 'steadfast_area_in_address')->delete();
        }
    }

    public function down(): void
    {
        // Deleted external area mappings cannot be reconstructed.
    }
};
