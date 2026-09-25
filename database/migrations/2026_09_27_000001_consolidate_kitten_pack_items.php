<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * One list per pack: kitten_pack_items now points at real products and drives
 * both the card copy and stock. The pack is sold as itself (order_details.kitten_pack_id),
 * so the shadow "linked product" and the separate components table are retired.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kitten_pack_items', function (Blueprint $table) {
            $table->unsignedBigInteger('product_id')->nullable()->after('kitten_pack_id');
            // Kept as a snapshot of the product name, and as the label of rows not linked to a product yet.
            $table->string('name')->nullable()->change();

            // nullOnDelete: a deleted product leaves a flagged row, which makes the pack unsellable.
            $table->foreign('product_id')->references('id')->on('products')->nullOnDelete();
        });

        // Link the old free-text rows to products whose name matches exactly (one match only).
        $products = DB::table('products')->get(['id', 'name'])
            ->groupBy(fn ($p) => mb_strtolower(trim($p->name)));

        foreach (DB::table('kitten_pack_items')->whereNull('product_id')->get(['id', 'name']) as $item) {
            $matches = $products->get(mb_strtolower(trim((string) $item->name)));
            if ($matches && $matches->count() === 1) {
                DB::table('kitten_pack_items')->where('id', $item->id)->update(['product_id' => $matches->first()->id]);
            }
        }

        // Fold any rows from the retired components table into the unified list.
        if (Schema::hasTable('kitten_pack_components')) {
            foreach (DB::table('kitten_pack_components')->get() as $component) {
                $exists = DB::table('kitten_pack_items')
                    ->where('kitten_pack_id', $component->kitten_pack_id)
                    ->where('product_id', $component->product_id)
                    ->exists();
                if ($exists) {
                    continue;
                }

                DB::table('kitten_pack_items')->insert([
                    'kitten_pack_id' => $component->kitten_pack_id,
                    'product_id'     => $component->product_id,
                    'name'           => DB::table('products')->where('id', $component->product_id)->value('name'),
                    'quantity'       => max(1, (int) $component->quantity),
                    'is_included'    => 1,
                    'sort_order'     => 1000 + (int) $component->id,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]);
            }

            Schema::drop('kitten_pack_components');
        }

        if (Schema::hasColumn('kitten_packs', 'product_id')) {
            Schema::table('kitten_packs', function (Blueprint $table) {
                $table->dropForeign(['product_id']);
                $table->dropColumn('product_id');
            });
        }

        // A pack order line: product_id stays NULL, the pack is referenced here.
        Schema::table('order_details', function (Blueprint $table) {
            $table->unsignedBigInteger('kitten_pack_id')->nullable()->after('product_id');
            $table->index('kitten_pack_id');
        });
    }

    public function down(): void
    {
        Schema::table('order_details', function (Blueprint $table) {
            $table->dropIndex(['kitten_pack_id']);
            $table->dropColumn('kitten_pack_id');
        });

        Schema::table('kitten_packs', function (Blueprint $table) {
            $table->unsignedBigInteger('product_id')->nullable()->after('old_price');
            $table->foreign('product_id')->references('id')->on('products')->nullOnDelete();
        });

        Schema::create('kitten_pack_components', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('kitten_pack_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedInteger('quantity')->default(1);
            $table->timestamps();

            $table->foreign('kitten_pack_id')->references('id')->on('kitten_packs')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('products')->restrictOnDelete();
            $table->unique(['kitten_pack_id', 'product_id']);
        });

        DB::table('kitten_pack_items')->whereNull('name')->update(['name' => '']);

        Schema::table('kitten_pack_items', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->dropColumn('product_id');
            $table->string('name')->nullable(false)->change();
        });
    }
};
