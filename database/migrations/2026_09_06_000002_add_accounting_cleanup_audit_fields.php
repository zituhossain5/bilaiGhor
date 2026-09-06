<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fund_transactions', function (Blueprint $table) {
            $table->timestamp('excluded_from_accounting_at')->nullable()->after('updated_by')->index();
            $table->string('accounting_exclusion_reason', 255)->nullable()->after('excluded_from_accounting_at');
            $table->uuid('accounting_cleanup_run_id')->nullable()->after('accounting_exclusion_reason')->index();
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->timestamp('excluded_from_accounting_at')->nullable()->after('updated_by')->index();
            $table->string('accounting_exclusion_reason', 255)->nullable()->after('excluded_from_accounting_at');
            $table->uuid('accounting_cleanup_run_id')->nullable()->after('accounting_exclusion_reason')->index();
        });

        Schema::create('accounting_cleanup_runs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->timestamp('cutoff_at');
            $table->string('snapshot_path');
            $table->json('options');
            $table->json('summary');
            $table->timestamp('executed_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_cleanup_runs');

        Schema::table('expenses', function (Blueprint $table) {
            $table->dropIndex(['excluded_from_accounting_at']);
            $table->dropIndex(['accounting_cleanup_run_id']);
            $table->dropColumn([
                'excluded_from_accounting_at',
                'accounting_exclusion_reason',
                'accounting_cleanup_run_id',
            ]);
        });

        Schema::table('fund_transactions', function (Blueprint $table) {
            $table->dropIndex(['excluded_from_accounting_at']);
            $table->dropIndex(['accounting_cleanup_run_id']);
            $table->dropColumn([
                'excluded_from_accounting_at',
                'accounting_exclusion_reason',
                'accounting_cleanup_run_id',
            ]);
        });
    }
};
