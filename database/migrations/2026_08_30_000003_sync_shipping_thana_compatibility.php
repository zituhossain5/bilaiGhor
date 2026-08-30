<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('shippings', 'thana_id') && Schema::hasColumn('shippings', 'upazila_id')) {
            DB::table('shippings')
                ->whereNotNull('thana_id')
                ->update(['upazila_id' => DB::raw('thana_id')]);
        }
    }

    public function down(): void
    {
        // The compatibility column cannot reconstruct its pre-migration value.
    }
};
