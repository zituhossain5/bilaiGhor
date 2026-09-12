<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fund_transactions', function (Blueprint $table) {
            $table->string('investment_type', 20)->nullable()->after('source')->index();
            $table->date('transaction_date')->nullable()->after('amount')->index();
        });

        $ownerFunding = DB::table('fund_transactions')
            ->where('direction', 'in')
            ->whereIn('source', ['manual_add', 'investment'])
            ->orderBy('created_at')
            ->orderBy('id')
            ->get(['id', 'source', 'investment_type', 'transaction_date', 'created_at']);

        $hasInitial = $ownerFunding->contains(fn ($row) => $row->investment_type === 'initial');

        foreach ($ownerFunding as $row) {
            $type = $row->investment_type;
            if (!in_array($type, ['initial', 'additional'], true)) {
                $type = $hasInitial ? 'additional' : 'initial';
                $hasInitial = true;
            }

            DB::table('fund_transactions')->where('id', $row->id)->update([
                'source' => 'investment',
                'investment_type' => $type,
                'transaction_date' => $row->transaction_date
                    ?: ($row->created_at ? date('Y-m-d', strtotime($row->created_at)) : now()->toDateString()),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('fund_transactions')
            ->where('source', 'investment')
            ->update(['source' => 'manual_add']);

        Schema::table('fund_transactions', function (Blueprint $table) {
            $table->dropIndex(['investment_type']);
            $table->dropIndex(['transaction_date']);
            $table->dropColumn(['investment_type', 'transaction_date']);
        });
    }
};
