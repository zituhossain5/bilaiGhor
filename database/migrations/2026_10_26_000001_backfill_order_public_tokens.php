<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // public_token is already nullable and unique. Preserve existing manual
        // invoice tokens; fill only orders created before token issuance was shared.
        DB::table('orders')
            ->select('id')
            ->where(fn ($query) => $query->whereNull('public_token')->orWhere('public_token', ''))
            ->chunkById(250, function ($orders): void {
                foreach ($orders as $order) {
                    do {
                        $token = bin2hex(random_bytes(32));
                    } while (DB::table('orders')->where('public_token', $token)->exists());

                    DB::table('orders')
                        ->where('id', $order->id)
                        ->where(fn ($query) => $query->whereNull('public_token')->orWhere('public_token', ''))
                        ->update(['public_token' => $token]);
                }
            });
    }

    public function down(): void
    {
        // Tokens may already have been delivered to customers. Never revoke them
        // as a side effect of rolling back a data-only migration.
    }
};
