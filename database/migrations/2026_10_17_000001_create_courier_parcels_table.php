<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row per courier parcel: its latest known state as the courier reports it.
 * New table only — `orders` and every other existing table are untouched. Courier-reported money
 * (COD, delivery charge) lives here, never over the order's own amounts.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courier_parcels', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('order_id')->nullable()->index();   // orders.id is INT UNSIGNED; null = not linked yet
            $table->string('courier', 20);                               // steadfast | pathao | redx
            $table->string('consignment_id', 64)->nullable();
            $table->string('tracking_code', 64)->nullable()->index();
            $table->string('invoice', 64)->nullable();

            $table->string('status', 32)->default('unknown')->index();  // normalized (config/courier_tracking.php)
            $table->string('raw_status', 100)->nullable();
            $table->timestamp('status_at')->nullable();                 // event time of the status shown

            $table->decimal('courier_cod', 12, 2)->nullable();
            $table->decimal('delivery_charge', 10, 2)->nullable();
            $table->boolean('delivery_charge_paid')->nullable();
            $table->string('weight', 32)->nullable();
            $table->unsignedSmallInteger('items_in_lot')->nullable();

            $table->string('recipient_name')->nullable();
            $table->string('recipient_phone', 32)->nullable();
            $table->text('recipient_address')->nullable();
            $table->string('recipient_area')->nullable();
            $table->string('rider_name')->nullable();
            $table->string('rider_phone', 32)->nullable();
            $table->string('hub_name')->nullable();
            $table->string('hub_phone', 32)->nullable();
            $table->text('note')->nullable();

            // Stepper dates
            $table->timestamp('entered_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('picked_at')->nullable();
            $table->timestamp('out_for_delivery_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('returned_at')->nullable();

            $table->timestamp('last_event_at')->nullable()->index();
            $table->timestamp('last_synced_at')->nullable();
            $table->text('last_sync_error')->nullable();
            $table->timestamp('last_sync_error_at')->nullable();

            // Courier → order status bookkeeping (never apply an event older than the last one applied)
            $table->unsignedInteger('order_status_applied')->nullable();
            $table->timestamp('order_status_applied_at')->nullable();

            $table->string('link_source', 20)->default('dispatch');      // dispatch | backfill | link | order | webhook
            $table->unsignedBigInteger('linked_by')->nullable();
            $table->timestamp('linked_at')->nullable();
            $table->boolean('is_current')->default(true);

            $table->timestamps();

            $table->unique(['courier', 'consignment_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courier_parcels');
    }
};
