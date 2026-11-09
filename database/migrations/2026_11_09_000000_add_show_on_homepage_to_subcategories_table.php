<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subcategories', function (Blueprint $table): void {
            $table->boolean('show_on_homepage')->default(true)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('subcategories', function (Blueprint $table): void {
            $table->dropColumn('show_on_homepage');
        });
    }
};
