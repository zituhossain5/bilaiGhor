<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            // Audit trail: intentionally NO foreign keys, so history survives
            // deletion of the referenced product/order/purchase rows.
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('variant_id')->nullable();
            $table->string('movement_type', 32);
            // Units affected (always positive); signed effect lives in the *_change columns.
            $table->unsignedInteger('quantity');
            $table->integer('on_hand_change')->default(0);
            $table->integer('reserved_change')->default(0);
            $table->integer('previous_on_hand')->nullable();
            $table->integer('new_on_hand')->nullable();
            $table->integer('previous_reserved')->nullable();
            $table->integer('new_reserved')->nullable();
            $table->string('reference_type', 40)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            // orders.id is INT UNSIGNED; purchases.id is BIGINT UNSIGNED
            $table->unsignedInteger('order_id')->nullable();
            $table->unsignedBigInteger('purchase_id')->nullable();
            $table->string('reason')->nullable();
            $table->text('notes')->nullable();
            // users.id (admin) is BIGINT UNSIGNED
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['product_id', 'created_at']);
            $table->index('movement_type');
            $table->index(['order_id', 'movement_type']);
            $table->index(['purchase_id', 'movement_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
    }
};
