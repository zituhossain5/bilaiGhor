<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_details', function (Blueprint $table) {
            if (!Schema::hasColumn('order_details', 'product_discount')) {
                $table->integer('product_discount')->default(0)->after('sale_price');
            }
            if (!Schema::hasColumn('order_details', 'manual_variant')) {
                $table->string('manual_variant', 155)->nullable()->after('product_name');
            }
            if (!Schema::hasColumn('order_details', 'is_manual_item')) {
                $table->boolean('is_manual_item')->default(false)->after('manual_variant');
            }
            if (!Schema::hasColumn('order_details', 'line_discount')) {
                $table->decimal('line_discount', 12, 2)->default(0)->after('product_discount');
            }
            if (!Schema::hasColumn('order_details', 'line_total')) {
                $table->decimal('line_total', 12, 2)->default(0)->after('line_discount');
            }
        });

        DB::statement('ALTER TABLE `order_details` MODIFY `product_id` INT NULL');
    }

    public function down(): void
    {
        Schema::table('order_details', function (Blueprint $table) {
            foreach (['manual_variant', 'is_manual_item', 'line_discount', 'line_total'] as $column) {
                if (Schema::hasColumn('order_details', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
