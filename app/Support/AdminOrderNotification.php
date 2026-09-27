<?php

namespace App\Support;

use App\Models\AdminOrderSeen;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Dashboard new-orders popup: an order is shown to an admin until that admin acknowledges it
 * (admin_order_seen). Orders the admin created themselves (manual orders) are never shown,
 * and neither are orders placed before the admin's account existed.
 */
class AdminOrderNotification
{
    public const POPUP_LIMIT = 10;

    /**
     * The popup no longer depends on a login flag — it is driven by unseen orders. Kept so the
     * login controllers that call it keep working.
     */
    public static function flagAfterLogin(): void
    {
    }

    public static function unseenOrdersQuery(User $admin): Builder
    {
        return Order::query()
            ->when($admin->created_at, fn ($q, $since) => $q->where('orders.created_at', '>=', $since))
            ->where(fn ($q) => $q->whereNull('orders.created_by')->orWhere('orders.created_by', '!=', $admin->id))
            ->whereNotExists(fn ($q) => $q->selectRaw('1')
                ->from('admin_order_seen')
                ->whereColumn('admin_order_seen.order_id', 'orders.id')
                ->where('admin_order_seen.user_id', $admin->id));
    }

    /**
     * Marks the given orders as seen by the admin. Unknown ids are ignored and repeated calls
     * are harmless.
     *
     * @param  array<int|string>  $orderIds
     */
    public static function markSeen(User $admin, array $orderIds): int
    {
        $ids = Order::whereIn('id', array_map('intval', $orderIds))->pluck('id');
        if ($ids->isEmpty()) {
            return 0;
        }

        $now = now();

        return AdminOrderSeen::insertOrIgnore($ids->map(fn ($id) => [
            'user_id'  => $admin->id,
            'order_id' => $id,
            'seen_at'  => $now,
        ])->all());
    }
}
