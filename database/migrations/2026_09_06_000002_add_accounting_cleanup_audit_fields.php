<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addAccountingAuditColumns('fund_transactions');
        $this->addAccountingAuditColumns('expenses');

        if (! Schema::hasTable('accounting_cleanup_runs')) {
            Schema::create('accounting_cleanup_runs', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->dateTime('cutoff_at');
                $table->string('snapshot_path');
                $table->json('options');
                $table->json('summary');
                $table->dateTime('executed_at');
                $table->timestamps();
            });
        }
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

    private function addAccountingAuditColumns(string $tableName): void
    {
        $addExcludedAt = ! Schema::hasColumn($tableName, 'excluded_from_accounting_at');
        $addExclusionReason = ! Schema::hasColumn($tableName, 'accounting_exclusion_reason');
        $addCleanupRunId = ! Schema::hasColumn($tableName, 'accounting_cleanup_run_id');

        if (! $addExcludedAt && ! $addExclusionReason && ! $addCleanupRunId) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($addExcludedAt, $addExclusionReason, $addCleanupRunId) {
            if ($addExcludedAt) {
                $table->dateTime('excluded_from_accounting_at')->nullable()->after('updated_by')->index();
            }

            if ($addExclusionReason) {
                $table->string('accounting_exclusion_reason', 255)->nullable()->after('excluded_from_accounting_at');
            }

            if ($addCleanupRunId) {
                $table->uuid('accounting_cleanup_run_id')->nullable()->after('accounting_exclusion_reason')->index();
            }
        });
    }
};
