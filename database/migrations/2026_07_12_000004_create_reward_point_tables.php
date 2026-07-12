<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Reward points — transaction ledger is the source of truth.
     * `points` is signed: earned/refunded are positive, spent/reversed negative.
     * UNIQUE(order_id, type) is the hard guarantee against double earning,
     * double spending, and double restoration for the same order.
     */
    public function up(): void
    {
        Schema::create('reward_point_transactions', function (Blueprint $table) {
            $table->id();
            // customers.id and orders.id are INT UNSIGNED in this schema — FK types must match.
            $table->unsignedInteger('customer_id')->index();
            $table->unsignedInteger('order_id')->nullable();
            $table->string('type', 20); // earned | spent | refunded | reversed | adjustment
            $table->integer('points');  // signed
            $table->integer('balance_after')->nullable();
            $table->string('description')->nullable();
            $table->timestamps();

            $table->unique(['order_id', 'type']);
            $table->foreign('customer_id')->references('id')->on('customers')->cascadeOnDelete();
            $table->foreign('order_id')->references('id')->on('orders')->nullOnDelete();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedInteger('reward_points_used')->default(0)->after('discount');
            $table->decimal('reward_discount_amount', 10, 2)->default(0)->after('reward_points_used');
            $table->unsignedInteger('reward_points_earned')->default(0)->after('reward_discount_amount');
            $table->timestamp('reward_points_awarded_at')->nullable()->after('reward_points_earned');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['reward_points_used', 'reward_discount_amount', 'reward_points_earned', 'reward_points_awarded_at']);
        });
        Schema::dropIfExists('reward_point_transactions');
    }
};
