<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\FundTransaction;
use App\Models\Order;
use App\Models\OrderDetails;
use App\Models\Product;
use App\Models\User;
use App\Http\Controllers\Admin\FundController;
use App\Http\Controllers\Admin\ReportController;
use App\Services\AccountingSummaryService;
use App\Support\OrderPurpose;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class PromotionalGiveawayAccountingTest extends TestCase
{
    use DatabaseTransactions;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_delivered_unpaid_giveaway_uses_saved_cost_once_without_cash_or_stock_effect(): void
    {
        Carbon::setTestNow('2035-04-10 10:00:00');
        $order = $this->order(0, 'unpaid');
        $order->discount = 678;
        $order->save();
        foreach ([315, 64, 64, 10, 10, 10, 10, 10, 130] as $cost) {
            $this->line($order, $cost);
        }
        // Total historical cost: 623 here, with the remaining 50 on five units.
        $this->line($order, 10, 5);
        $expectedCost = (float) $order->orderdetails()->sum(DB::raw('purchase_price * qty'));
        $this->assertEquals(673, $expectedCost);

        DB::table('inventory_movements')->insert([
            'product_id' => 999999, 'order_id' => $order->id,
            'movement_type' => 'sale_completed', 'quantity' => 14,
            'on_hand_change' => -14, 'created_at' => '2035-04-10 12:00:00', 'updated_at' => '2035-04-10 12:00:00',
        ]);
        DB::table('courier_parcels')->insert([
            'order_id' => $order->id, 'courier' => 'steadfast', 'status' => 'delivered',
            'delivery_charge' => 155, 'delivery_charge_paid' => null,
            'delivered_at' => '2035-04-10 12:00:00',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $stockBefore = DB::table('inventory_movements')->where('order_id', $order->id)->count();
        $fundBefore = AccountingSummaryService::fundBalance();
        $updatedBefore = $order->updated_at->toDateTimeString();
        $admin = $this->admin();
        Carbon::setTestNow('2035-11-18 10:00:00');
        $this->actingAs($admin, 'admin')->post(route('admin.orders.purpose.update', $order), [
            'order_purpose' => OrderPurpose::PROMOTIONAL_GIVEAWAY,
            'reason' => 'Historical promotional product delivery',
        ])->assertRedirect();

        $this->assertDatabaseHas('order_purpose_changes', [
            'order_id' => $order->id, 'old_purpose' => 'sale',
            'new_purpose' => 'promotional_giveaway', 'changed_by' => $admin->id,
        ]);
        $this->assertEquals(0, $order->fresh()->amount);
        $this->assertSame('unpaid', $order->fresh()->payment_status);
        $this->assertEquals(678, $order->fresh()->discount);
        $this->assertSame($updatedBefore, $order->fresh()->updated_at->toDateTimeString());
        $this->assertEquals($fundBefore, AccountingSummaryService::fundBalance());
        $this->assertSame($stockBefore, DB::table('inventory_movements')->where('order_id', $order->id)->count());
        $this->assertSame(0, FundTransaction::where('source_id', $order->id)->where('direction', 'out')->count());

        $april = AccountingSummaryService::businessSummary(Carbon::parse('2035-04-01'), Carbon::parse('2035-04-30')->endOfDay());
        $november = AccountingSummaryService::businessSummary(Carbon::parse('2035-11-01'), Carbon::parse('2035-11-30')->endOfDay());
        $this->assertEquals(0, $april['sales_revenue']);
        $this->assertEquals(0, $april['cogs']);
        $this->assertEquals(673, $april['promotional_giveaway_cost']);
        $this->assertEquals(-673, $april['net_profit']);
        $this->assertEquals(0, $november['promotional_giveaway_cost']);

        $fundCsv = app(FundController::class)->export(Request::create('/admin/fund/export', 'GET', [
            'mode' => 'custom', 'from_date' => '2035-04-01', 'to_date' => '2035-04-30',
        ]));
        ob_start();
        $fundCsv->sendContent();
        $this->assertStringContainsString('"Promotional Giveaways",673.00', ob_get_clean());

        $profitCsv = app(ReportController::class)->profitLoss(Request::create('/admin/reports/profit-loss', 'GET', [
            'type' => 'range', 'from_date' => '2035-04-01', 'to_date' => '2035-04-30', 'export' => 'csv',
        ]));
        ob_start();
        $profitCsv->sendContent();
        $this->assertStringContainsString('"Promotional Giveaways",673', ob_get_clean());

        $this->actingAs($admin, 'admin')->post(route('admin.orders.purpose.update', $order), [
            'order_purpose' => OrderPurpose::PROMOTIONAL_GIVEAWAY,
            'reason' => 'Repeat submission must be idempotent',
        ])->assertRedirect();
        $this->assertSame(1, DB::table('order_purpose_changes')->where('order_id', $order->id)->count());

        $this->actingAs($admin, 'admin')->post(route('admin.orders.purpose.update', $order), [
            'order_purpose' => OrderPurpose::SALE,
            'reason' => 'Correct classification back to normal sale',
        ])->assertRedirect();
        $this->assertSame(OrderPurpose::SALE, $order->fresh()->order_purpose);
        $this->assertEquals(0, $order->fresh()->paid_amount);
        $this->assertEquals(0, $order->fresh()->amount);
        $this->assertEquals(678, $order->fresh()->discount);
        $this->assertSame($stockBefore, DB::table('inventory_movements')->where('order_id', $order->id)->count());
        $this->assertEquals($fundBefore, AccountingSummaryService::fundBalance());
        $this->assertSame(2, DB::table('order_purpose_changes')->where('order_id', $order->id)->count());
    }

    public function test_normal_sale_and_zero_total_sale_are_not_giveaways_and_replacement_is_separate(): void
    {
        Carbon::setTestNow('2036-05-10 09:00:00');
        $sale = $this->order(1000, 'paid');
        $this->line($sale, 600);
        $zeroSale = $this->order(0, 'unpaid');
        $this->line($zeroSale, 50);
        $replacement = $this->order(0, 'unpaid', OrderPurpose::CUSTOMER_REPLACEMENT);
        $this->line($replacement, 80);

        $summary = AccountingSummaryService::businessSummary(Carbon::parse('2036-05-01'), Carbon::parse('2036-05-31')->endOfDay());
        $this->assertEquals(1000, $summary['sales_revenue']);
        $this->assertEquals(600, $summary['cogs']);
        $this->assertEquals(400, $summary['gross_profit']);
        $this->assertEquals(0, $summary['promotional_giveaway_cost']);
        $this->assertEquals(80, $summary['customer_replacement_cost']);
        $this->assertEquals(320, $summary['net_profit']);
    }

    public function test_staff_cannot_reclassify_and_nonzero_order_cannot_be_giveaway(): void
    {
        $order = $this->order(500, 'paid');
        $admin = $this->admin();
        $staff = User::create(['name' => 'Staff', 'email' => Str::random(10).'@example.test', 'password' => bcrypt('password'), 'status' => 1]);
        $this->actingAs($staff, 'admin')->post(route('admin.orders.purpose.update', $order), [
            'order_purpose' => OrderPurpose::PROMOTIONAL_GIVEAWAY, 'reason' => 'Unauthorized test',
        ])->assertForbidden();

        $this->actingAs($admin, 'admin')->post(route('admin.orders.purpose.update', $order), [
            'order_purpose' => OrderPurpose::PROMOTIONAL_GIVEAWAY, 'reason' => 'Must refuse positive sale',
        ])->assertSessionHasErrors('order_purpose');
        $this->assertSame('sale', $order->fresh()->order_purpose);
    }

    public function test_missing_saved_cost_is_reported_as_estimated_without_blocking_profit(): void
    {
        Carbon::setTestNow('2037-06-10 12:00:00');
        $product = Product::create([
            'name' => 'Cost fallback item', 'slug' => 'fallback-' . Str::random(8),
            'category_id' => 0, 'new_price' => 100, 'purchase_price' => 42,
            'stock' => 0, 'status' => 1,
        ]);
        $order = $this->order(0, 'unpaid', OrderPurpose::FREE_SAMPLE);
        OrderDetails::create([
            'order_id' => $order->id, 'product_id' => $product->id,
            'product_name' => $product->name, 'purchase_price' => null,
            'sale_price' => 100, 'qty' => 2,
        ]);

        $summary = AccountingSummaryService::businessSummary(Carbon::parse('2037-06-01'), Carbon::parse('2037-06-30')->endOfDay());
        $this->assertEquals(84, $summary['promotional_giveaway_cost']);
        $this->assertEquals(84, $summary['promotional_estimated_cost']);
        $this->assertSame(1, $summary['promotional_fallback_lines']);
        $this->assertEquals(-84, $summary['net_profit']);
    }

    public function test_paid_status_on_non_sale_does_not_post_a_sales_fund_credit(): void
    {
        $order = $this->order(0, 'unpaid', OrderPurpose::CUSTOMER_GIFT);
        $order->payment_status = 'paid';
        $order->save();

        $this->assertSame(0, FundTransaction::where('source', 'sale')->where('source_id', $order->id)->count());
        $this->expectException(\LogicException::class);
        \App\Helpers\FundHelper::creditSale($order, 'Must reject non-sale credit');
    }

    private function admin(): User
    {
        $user = User::create([
            'name' => 'Accounting Admin', 'email' => Str::random(10).'@example.test',
            'password' => bcrypt('password'), 'status' => 1,
        ]);
        $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'admin']);
        $user->assignRole($role);

        return $user;
    }

    private function order(int $amount, string $payment, string $purpose = OrderPurpose::SALE): Order
    {
        return Order::create([
            'invoice_id' => 'PURPOSE-' . Str::upper(Str::random(12)),
            'amount' => $amount, 'discount' => 0, 'shipping_charge' => 0,
            'customer_id' => 0, 'order_status' => 6,
            'payment_status' => $payment, 'order_purpose' => $purpose,
        ]);
    }

    private function line(Order $order, int $cost, int $qty = 1): void
    {
        OrderDetails::create([
            'order_id' => $order->id, 'product_id' => null, 'product_name' => 'Historical item',
            'purchase_price' => $cost, 'sale_price' => 678, 'qty' => $qty,
        ]);
    }
}
