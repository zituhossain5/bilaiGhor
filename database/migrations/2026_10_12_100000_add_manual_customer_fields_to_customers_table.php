<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Manual customers: admin-entered records that live alongside self-registered ones.
 *
 * - source: "registered" (every existing row) or "manual" (added by an admin, cannot log in)
 * - phone_normalized: 01XXXXXXXXX form used for matching and lookup, indexed
 * - post_code, notes: extra details an admin can keep for a manual customer
 * - name, slug and password become optional, so a manual customer needs no placeholder values
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('source', 20)->default('registered')->after('status');
            $table->string('phone_normalized', 20)->nullable()->after('phone');
            $table->string('post_code', 20)->nullable()->after('address');
            $table->text('notes')->nullable()->after('post_code');

            $table->index('phone_normalized');
            $table->index('source');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->string('name', 155)->nullable()->change();
            $table->string('slug', 155)->nullable()->change();
            $table->string('password', 255)->nullable()->change();
        });

        // Existing rows get their matching number (same rules as App\Support\BdPhone).
        DB::table('customers')->whereNotNull('phone')->orderBy('id')->select(['id', 'phone'])
            ->chunkById(500, function ($rows) {
                foreach ($rows as $row) {
                    DB::table('customers')->where('id', $row->id)
                        ->update(['phone_normalized' => \App\Support\BdPhone::normalize($row->phone)]);
                }
            });
    }

    public function down(): void
    {
        // Manual customers may have no name/password; give them empty strings so the
        // columns can become required again.
        DB::table('customers')->whereNull('name')->update(['name' => '']);
        DB::table('customers')->whereNull('slug')->update(['slug' => '']);
        DB::table('customers')->whereNull('password')->update(['password' => '']);

        Schema::table('customers', function (Blueprint $table) {
            $table->string('name', 155)->nullable(false)->change();
            $table->string('slug', 155)->nullable(false)->change();
            $table->string('password', 255)->nullable(false)->change();
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex(['phone_normalized']);
            $table->dropIndex(['source']);
            $table->dropColumn(['source', 'phone_normalized', 'post_code', 'notes']);
        });
    }
};
