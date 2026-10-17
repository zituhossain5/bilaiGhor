<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tracking timeline: every status/message a courier reported for a parcel (webhooks, sync, refresh,
 * history APIs). `dedupe_hash` is unique, so the same webhook delivered twice is stored once.
 * New table only; no foreign keys onto existing tables.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courier_tracking_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('courier_parcel_id')->index();
            $table->unsignedInteger('order_id')->nullable()->index();
            $table->string('courier', 20);
            $table->string('consignment_id', 64)->nullable()->index();
            $table->string('tracking_code', 64)->nullable()->index();

            $table->string('raw_status', 100)->nullable();
            $table->string('normalized_status', 32)->nullable();
            $table->text('message')->nullable();
            $table->string('hub')->nullable();
            $table->string('rider_name')->nullable();
            $table->string('rider_phone', 32)->nullable();

            $table->timestamp('event_time')->nullable()->index();
            $table->boolean('event_time_from_courier')->default(false);
            $table->string('source', 20);                 // dispatch | webhook | sync | api_history | manual_refresh | link | backfill
            $table->json('raw_payload')->nullable();      // pruned after config('courier_tracking.payload_retention_days')
            $table->char('dedupe_hash', 64)->unique();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courier_tracking_events');
    }
};
