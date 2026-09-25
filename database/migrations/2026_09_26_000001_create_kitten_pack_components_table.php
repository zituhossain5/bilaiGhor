<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The real inventory products a pack is made of. A pack holds no stock of
        // its own: availability is derived from these rows, and selling a pack
        // reserves / deducts each component through InventoryService.
        Schema::create('kitten_pack_components', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('kitten_pack_id');
            $table->unsignedBigInteger('product_id');
            // Units of this product in ONE pack.
            $table->unsignedInteger('quantity')->default(1);
            $table->timestamps();

            $table->foreign('kitten_pack_id')->references('id')->on('kitten_packs')->cascadeOnDelete();
            // Restrict: deleting a component product must not silently shrink a pack
            // that is still being sold.
            $table->foreign('product_id')->references('id')->on('products')->restrictOnDelete();
            $table->unique(['kitten_pack_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kitten_pack_components');
    }
};
