<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (!Schema::hasColumn('customers', 'google_id')) {
                $table->string('google_id', 191)->nullable()->unique()->after('email');
            }

            if (!Schema::hasColumn('customers', 'facebook_id')) {
                $table->string('facebook_id', 191)->nullable()->unique()->after('google_id');
            }
        });

        DB::statement('ALTER TABLE `customers` MODIFY `phone` VARCHAR(55) NULL');
        DB::statement('ALTER TABLE `customers` MODIFY `email` VARCHAR(55) NULL');
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (Schema::hasColumn('customers', 'facebook_id')) {
                $table->dropColumn('facebook_id');
            }

            if (Schema::hasColumn('customers', 'google_id')) {
                $table->dropColumn('google_id');
            }
        });
    }
};
