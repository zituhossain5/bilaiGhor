<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Per-admin "seen" tracking for the dashboard new-orders popup: an order stays in the
 * popup for an admin until that admin acknowledges it. Per admin (not a column on
 * orders) so one admin acknowledging does not hide the order from the others.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_order_seen', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            // orders.id is INT UNSIGNED (increments), so this cannot be a foreignId().
            $table->unsignedInteger('order_id');
            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
            $table->timestamp('seen_at')->useCurrent();

            $table->unique(['user_id', 'order_id']);
            $table->index('order_id');
        });

        // Baseline: everything that exists today counts as already seen by current admin-panel
        // users, so the first popup after deploy shows only genuinely new orders.
        $excluded = DB::table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->whereIn(DB::raw('LOWER(roles.name)'), ['vendor', 'reseller'])
            ->pluck('model_has_roles.model_id');

        $panelUserIds = DB::table('users')
            ->whereNotIn('id', $excluded)
            ->when(Schema::hasColumn('users', 'role'), fn ($q) => $q->where(
                fn ($q) => $q->whereNull('role')->orWhereNotIn('role', ['vendor', 'reseller'])
            ))
            ->pluck('id');

        foreach ($panelUserIds as $userId) {
            DB::statement(
                'INSERT IGNORE INTO admin_order_seen (user_id, order_id, seen_at) SELECT ?, id, NOW() FROM orders',
                [$userId]
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_order_seen');
    }
};
