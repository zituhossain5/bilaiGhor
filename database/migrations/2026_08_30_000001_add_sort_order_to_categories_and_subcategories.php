<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            if (!Schema::hasColumn('categories', 'sort_order')) {
                $table->unsignedInteger('sort_order')->default(0)->after('front_view')->index();
            }
        });

        Schema::table('subcategories', function (Blueprint $table) {
            if (!Schema::hasColumn('subcategories', 'sort_order')) {
                $table->unsignedInteger('sort_order')->default(0)->after('category_id')->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('subcategories', function (Blueprint $table) {
            if (Schema::hasColumn('subcategories', 'sort_order')) {
                $table->dropColumn('sort_order');
            }
        });

        Schema::table('categories', function (Blueprint $table) {
            if (Schema::hasColumn('categories', 'sort_order')) {
                $table->dropColumn('sort_order');
            }
        });
    }
};
