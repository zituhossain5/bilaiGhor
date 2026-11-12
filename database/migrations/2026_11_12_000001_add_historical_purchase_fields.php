<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->string('purchase_mode', 20)->default('normal')->index();
            $table->string('accounting_treatment', 30)->nullable();
            $table->unsignedBigInteger('reclassified_expense_id')->nullable()->unique();
        });
        Schema::table('expenses', function (Blueprint $table) {
            $table->dateTime('reclassified_at')->nullable();
            $table->unsignedBigInteger('reclassified_purchase_id')->nullable()->unique();
        });
        Schema::table('purchase_logs', function (Blueprint $table) {
            $table->string('action', 30)->change();
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropUnique(['reclassified_purchase_id']);
            $table->dropColumn(['reclassified_at', 'reclassified_purchase_id']);
        });
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropUnique(['reclassified_expense_id']);
            $table->dropIndex(['purchase_mode']);
            $table->dropColumn(['purchase_mode', 'accounting_treatment', 'reclassified_expense_id']);
        });
    }
};
