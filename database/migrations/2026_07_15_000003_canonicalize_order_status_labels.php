<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Canonicalize the two order-status display names so admin and customer surfaces
 * show one consistent vocabulary. Only the `name` (display label) changes — the
 * numeric IDs and slugs are untouched, so every piece of logic (which keys off id
 * or slug) is unaffected, and no historical order data is altered. Fully reversible.
 *
 *   3  "On The Way" -> "Shipped"
 *   6  "Completed"  -> "Delivered"
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('order_statuses')->where('id', 3)->where('name', 'On The Way')->update(['name' => 'Shipped']);
        DB::table('order_statuses')->where('id', 6)->where('name', 'Completed')->update(['name' => 'Delivered']);
    }

    public function down(): void
    {
        DB::table('order_statuses')->where('id', 3)->where('name', 'Shipped')->update(['name' => 'On The Way']);
        DB::table('order_statuses')->where('id', 6)->where('name', 'Delivered')->update(['name' => 'Completed']);
    }
};
