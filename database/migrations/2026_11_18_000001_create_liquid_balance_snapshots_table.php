<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('liquid_balance_snapshots', function (Blueprint $table) {
            $table->id();
            $table->uuid('idempotency_key')->unique();
            $table->decimal('cash_balance', 15, 2);
            $table->decimal('bank_balance', 15, 2);
            $table->decimal('mobile_wallet_balance', 15, 2);
            $table->decimal('other_liquid_balance', 15, 2);
            $table->decimal('courier_reported_balance', 15, 2)->nullable();
            $table->text('note')->nullable();
            $table->dateTime('verified_at');
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index('verified_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('liquid_balance_snapshots');
    }
};
