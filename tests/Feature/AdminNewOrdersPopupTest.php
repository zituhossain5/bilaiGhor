<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\DashboardController;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminNewOrdersPopupTest extends TestCase
{
    use DatabaseTransactions;

    public function test_acknowledged_orders_do_not_return_and_only_new_orders_appear_afterwards(): void
    {
        $admin = $this->admin();
        $first = $this->order();

        $this->assertSame([$first->id], $this->popupOrderIds($admin));

        $this->acknowledge($admin, [$first->id]);

        // Same session and a fresh login both come back empty: nothing new arrived.
        $this->assertSame([], $this->popupOrderIds($admin));
        Auth::guard('admin')->logout();
        $this->assertSame([], $this->popupOrderIds($admin));

        $second = $this->order();

        $this->assertSame([$second->id], $this->popupOrderIds($admin));
    }

    public function test_orders_the_admin_created_themselves_are_not_shown(): void
    {
        $admin = $this->admin();
        $other = $this->admin();

        $mine = $this->order(['is_manual_order' => 1, 'created_by' => $admin->id]);
        $theirs = $this->order(['is_manual_order' => 1, 'created_by' => $other->id]);

        $this->assertSame([$theirs->id], $this->popupOrderIds($admin));
        $this->assertSame([$mine->id], $this->popupOrderIds($other));
    }

    public function test_acknowledging_is_per_admin(): void
    {
        $admin = $this->admin();
        $colleague = $this->admin();
        $order = $this->order();

        $this->acknowledge($admin, [$order->id]);

        $this->assertSame([], $this->popupOrderIds($admin));
        $this->assertSame([$order->id], $this->popupOrderIds($colleague));
    }

    public function test_acknowledge_is_idempotent_ignores_unknown_ids_and_validates_input(): void
    {
        $admin = $this->admin();
        $order = $this->order();

        $this->assertSame(1, $this->acknowledge($admin, [$order->id, 999999999]));
        $this->assertSame(0, $this->acknowledge($admin, [$order->id]));

        $this->expectException(ValidationException::class);
        $this->acknowledge($admin, ['not-a-number']);
    }

    public function test_popup_is_capped_and_lists_newest_first(): void
    {
        $admin = $this->admin();
        $ids = collect(range(1, 12))->map(fn () => $this->order()->id);

        $this->assertSame($ids->reverse()->take(10)->values()->all(), $this->popupOrderIds($admin));
    }

    private function popupOrderIds(User $admin): array
    {
        Auth::guard('admin')->login($admin);
        $data = app(DashboardController::class)->newOrdersForPopup()->getData(true);

        $this->assertSame($data['show'], $data['count'] > 0);

        return array_column($data['orders'], 'id');
    }

    private function acknowledge(User $admin, array $orderIds): int
    {
        Auth::guard('admin')->login($admin);
        $request = Request::create('/admin/dashboard/dismiss-new-orders-popup', 'POST', ['order_ids' => $orderIds]);

        return app(DashboardController::class)->dismissNewOrdersPopup($request)->getData(true)['marked'];
    }

    private function admin(): User
    {
        $admin = User::create([
            'name' => 'Popup Test Admin',
            'email' => 'popup-' . Str::uuid() . '@example.test',
            'password' => bcrypt('secret'),
            'status' => 1,
        ]);
        // Orders placed before an admin's account existed are never shown, so keep the
        // test orders (created "now") clearly after the account.
        $admin->forceFill(['created_at' => now()->subMinute()])->save();
        $admin->assignRole(Role::findOrCreate('Admin', 'admin'));

        return $admin;
    }

    private function order(array $overrides = []): Order
    {
        return Order::create(array_merge([
            'invoice_id' => 'POPUP-' . Str::upper(Str::random(12)),
            'amount' => 100,
            'discount' => 0,
            'shipping_charge' => 0,
            'customer_id' => null,
            'order_status' => 1,
            'payment_status' => 'pending',
            'is_manual_order' => 0,
        ], $overrides));
    }
}
