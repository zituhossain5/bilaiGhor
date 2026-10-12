<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Short product names for printed receipts.
 *
 * - products.short_name: optional name used on receipts (empty = the full name, shortened)
 * - order_details.invoice_name: the name printed for that line, saved when the line is created
 *   so later product edits don't change old receipts (empty on old lines, which fall back)
 * - general_settings.receipt_dense_after / receipt_comma_list: receipt layout settings
 *
 * Only new nullable/defaulted columns are added; existing rows are untouched.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('products', 'short_name')) {
            Schema::table('products', function (Blueprint $table) {
                $table->string('short_name', 40)->nullable()->after('name');
            });
        }

        if (!Schema::hasColumn('order_details', 'invoice_name')) {
            Schema::table('order_details', function (Blueprint $table) {
                $table->string('invoice_name', 60)->nullable()->after('product_name');
            });
        }

        Schema::table('general_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('general_settings', 'receipt_dense_after')) {
                $table->unsignedSmallInteger('receipt_dense_after')->default(12);
            }
            if (!Schema::hasColumn('general_settings', 'receipt_comma_list')) {
                $table->boolean('receipt_comma_list')->default(false);
            }
        });
    }

    public function down(): void
    {
        Schema::table('general_settings', function (Blueprint $table) {
            $table->dropColumn(['receipt_dense_after', 'receipt_comma_list']);
        });

        Schema::table('order_details', function (Blueprint $table) {
            $table->dropColumn('invoice_name');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('short_name');
        });
    }
};
