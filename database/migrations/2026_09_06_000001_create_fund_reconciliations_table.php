<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fund_reconciliations', function (Blueprint $table) {
            $table->id();
            $table->decimal('system_balance_before', 15, 2);
            $table->decimal('cash_balance', 15, 2)->default(0);
            $table->decimal('bank_balance', 15, 2)->default(0);
            $table->decimal('mobile_wallet_balance', 15, 2)->default(0);
            $table->decimal('other_balance', 15, 2)->default(0);
            $table->decimal('actual_balance', 15, 2);
            $table->decimal('difference', 15, 2);
            $table->foreignId('fund_transaction_id')
                ->nullable()
                ->constrained('fund_transactions')
                ->nullOnDelete();
            $table->text('note');
            $table->foreignId('reconciled_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamps();

            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fund_reconciliations');
    }
};
