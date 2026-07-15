<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_stocks', function (Blueprint $table) {
            $table->id();
            // products.id is BIGINT UNSIGNED
            $table->unsignedBigInteger('product_id');
            // Phase 2 (variants/batches) — unused in Phase 1, no FK yet.
            $table->unsignedBigInteger('variant_id')->nullable();
            $table->integer('on_hand')->default(0);
            $table->integer('reserved')->default(0);
            $table->unsignedInteger('low_stock_threshold')->default(5);
            $table->timestamp('last_restocked_at')->nullable();
            $table->timestamps();

            // Phase 1 tracks stock per product. A composite unique with a nullable
            // variant_id would NOT deduplicate in MySQL (NULL != NULL), so the
            // product-only unique is the one that actually enforces integrity.
            $table->unique('product_id');
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_stocks');
    }
};
