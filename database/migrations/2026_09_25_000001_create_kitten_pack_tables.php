<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kitten_packs', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            // Shown on the card and referenced by the FAQ copy (Starter / Affordable / Premium).
            $table->string('tier_label')->nullable();
            $table->string('badge')->nullable();
            // 'dark' renders the highlighted middle card from the design.
            $table->enum('theme', ['light', 'dark'])->default('light');
            $table->string('image')->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->decimal('old_price', 10, 2)->nullable();
            // Backing product drives cart, checkout, stock and orders.
            $table->unsignedBigInteger('product_id')->nullable();
            $table->boolean('status')->default(1);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('product_id')->references('id')->on('products')->nullOnDelete();
            $table->index(['status', 'sort_order']);
        });

        Schema::create('kitten_pack_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('kitten_pack_id');
            $table->string('name');
            // Pieces of this item in the pack. The "23 Items" badge sums these.
            $table->unsignedInteger('quantity')->default(1);
            // false renders the greyed-out "not in this pack" rows from the design.
            $table->boolean('is_included')->default(1);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('kitten_pack_id')->references('id')->on('kitten_packs')->cascadeOnDelete();
            $table->index(['kitten_pack_id', 'sort_order']);
        });

        Schema::create('kitten_pack_addons', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
            $table->unique('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kitten_pack_addons');
        Schema::dropIfExists('kitten_pack_items');
        Schema::dropIfExists('kitten_packs');
    }
};
