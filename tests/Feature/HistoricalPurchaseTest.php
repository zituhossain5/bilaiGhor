<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\FundTransaction;
use App\Models\InventoryMovement;
use App\Models\InventoryStock;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\PurchaseLog;
use App\Models\Supplier;
use App\Models\User;
use App\Services\AccountingSummaryService;
use App\Services\HistoricalPurchaseService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class HistoricalPurchaseTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;
    private Supplier $supplier;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::create([
            'name' => 'Historical Purchase Test', 'email' => 'historical-'.Str::uuid().'@example.test',
            'password' => bcrypt('secret'), 'status' => 1,
        ]);
        $this->admin->assignRole(Role::findOrCreate('Admin', 'admin'));
        Auth::guard('admin')->login($this->admin);
        $this->supplier = Supplier::create(['name' => 'Test Supplier '.Str::uuid(), 'current_due' => 0]);
        $this->product = Product::create([
            'product_type' => 'physical', 'name' => 'Test Product '.Str::uuid(),
            'slug' => 'test-product-'.Str::uuid(), 'category_id' => 1,
            'new_price' => 100, 'stock' => 8, 'purchase_price' => 33, 'status' => 1,
        ]);
        InventoryStock::create(['product_id' => $this->product->id, 'on_hand' => 8, 'reserved' => 0]);
    }

    private function payload(array $changes = []): array
    {
        return array_replace([
            'supplier_id' => $this->supplier->id,
            'invoice_no' => 'HIST-'.Str::uuid(), 'purchase_date' => '2026-07-28',
            'purchase_mode' => 'historical', 'accounting_treatment' => 'already_recorded',
            'accounting_reference' => 'Bank statement July 2026',
            'lines' => [['product_id' => $this->product->id, 'qty' => 5, 'unit_cost' => 100]],
            'discount' => 0, 'shipping_cost' => 0, 'paid_amount' => 500,
        ], $changes);
    }

    public function test_historical_purchase_keeps_stock_cost_and_fund_unchanged(): void
    {
        $balance = AccountingSummaryService::fundBalance();
        $mismatches = AccountingSummaryService::snapshot()['supplier_payment_fund_mismatches'];
        $movements = InventoryMovement::where('product_id', $this->product->id)->count();
        $this->post(route('purchases.store'), $this->payload())->assertSessionHasNoErrors();
        $purchase = Purchase::latest('id')->firstOrFail();
        $this->assertSame('historical', $purchase->purchase_mode);
        $this->assertSame(5, (int) $purchase->total_qty);
        $this->assertEquals(500, $purchase->grand_total);
        $this->assertSame('2026-07-28', $purchase->purchase_date);
        $this->assertEquals(8, $this->product->fresh()->stock);
        $this->assertSame(1, PurchaseLog::where('purchase_id', $purchase->id)
            ->where('action', 'historical_create')->count());
        $summary = AccountingSummaryService::businessSummary(
            Carbon::parse('2026-07-01')->startOfDay(), Carbon::parse('2026-07-31')->endOfDay()
        );
        $this->assertEquals(0, $summary['expenses']);
        $this->assertEquals(500, $summary['historical_purchases']);
        $this->assertSame(1, $summary['historical_purchase_count']);
        $this->assertEquals(8, InventoryStock::where('product_id', $this->product->id)->value('on_hand'));
        $this->assertEquals(33, $this->product->fresh()->purchase_price);
        $this->assertSame($movements, InventoryMovement::where('product_id', $this->product->id)->count());
        $this->assertEquals($balance, AccountingSummaryService::fundBalance());
        $this->assertEquals($mismatches, AccountingSummaryService::snapshot()['supplier_payment_fund_mismatches']);
        $this->assertEquals(0, $purchase->due_amount);
        $this->get(route('purchases.index', ['year' => 2026, 'month' => 7]))
            ->assertOk()->assertSee($purchase->invoice_no);
        $this->get(route('purchases.index', ['supplier_id' => $this->supplier->id]))
            ->assertOk()->assertSee($purchase->invoice_no);
        $this->get(route('purchases.invoice', $purchase->id))
            ->assertOk()->assertSee('No current stock adjustment');
        $this->get(route('purchases.edit', $purchase->id))
            ->assertOk()->assertSee('Edit Historical Purchase');
        $this->get(route('purchases.export', ['year' => 2026, 'month' => 7]))
            ->assertOk()->assertHeader('Content-Type', 'text/csv; charset=utf-8');
    }

    public function test_reclassifying_expense_preserves_original_cash_outflow(): void
    {
        $expense = Expense::create([
            'title' => 'Old inventory expense', 'amount' => 500, 'expense_date' => '2026-07-28',
            'created_by' => $this->admin->id,
        ]);
        $fund = FundTransaction::create([
            'direction' => 'out', 'source' => 'expense', 'source_id' => $expense->id,
            'amount' => 500, 'created_by' => $this->admin->id,
        ]);
        $expense->update(['fund_transaction_id' => $fund->id]);
        $before = AccountingSummaryService::fundBalance();
        $this->post(route('purchases.store'), $this->payload([
            'accounting_treatment' => 'reclassify_expense', 'expense_id' => $expense->id,
        ]))->assertSessionHasNoErrors();
        $purchase = Purchase::latest('id')->firstOrFail();
        $this->assertEquals($expense->id, $purchase->reclassified_expense_id);
        $this->assertNotNull($expense->fresh()->reclassified_at);
        $this->assertEquals(0, Expense::includedInAccounting()->whereKey($expense->id)->count());
        $this->assertEquals($before, AccountingSummaryService::fundBalance());
        $this->assertSame(1, FundTransaction::where('source', 'supplier_payment')
            ->where('source_id', $purchase->payments()->first()->id)->count());
        $this->assertEquals(8, $this->product->fresh()->stock);
        $summary = AccountingSummaryService::businessSummary(
            Carbon::parse('2026-07-01')->startOfDay(), Carbon::parse('2026-07-31')->endOfDay()
        );
        $this->assertEquals(0, $summary['expenses']);
        $this->assertEquals(500, $summary['historical_purchases']);
    }

    public function test_new_historical_payment_is_a_single_explicit_outflow(): void
    {
        FundTransaction::create(['direction' => 'in', 'source' => 'investment', 'amount' => 1000]);
        $before = AccountingSummaryService::fundBalance();
        $this->post(route('purchases.store'), $this->payload(['accounting_treatment' => 'new_payment']))
            ->assertSessionHasNoErrors();
        $purchase = Purchase::latest('id')->firstOrFail();
        $this->assertEquals($before - 500, AccountingSummaryService::fundBalance());
        $this->assertSame(1, $purchase->payments()->count());
        $this->assertEquals(8, $this->product->fresh()->stock);
    }

    public function test_repeated_historical_invoice_does_not_create_second_payment(): void
    {
        FundTransaction::create(['direction' => 'in', 'source' => 'investment', 'amount' => 1000]);
        $payload = $this->payload(['accounting_treatment' => 'new_payment']);
        $this->post(route('purchases.store'), $payload)->assertSessionHasNoErrors();
        $balance = AccountingSummaryService::fundBalance();
        $this->post(route('purchases.store'), $payload)->assertSessionHasErrors('invoice_no');
        $this->assertEquals($balance, AccountingSummaryService::fundBalance());
        $this->assertSame(1, Purchase::where('historical_invoice_key', hash('sha256',
            $this->supplier->id.'|2026-07-28|'.strtolower($payload['invoice_no'])))->count());
    }

    public function test_failed_reclassification_rolls_back_purchase_and_fund(): void
    {
        $expense = Expense::create(['title' => 'Wrong amount', 'amount' => 400, 'expense_date' => '2026-07-28', 'created_by' => $this->admin->id]);
        $count = Purchase::count();
        $this->post(route('purchases.store'), $this->payload([
            'accounting_treatment' => 'reclassify_expense', 'expense_id' => $expense->id,
        ]))->assertSessionHasErrors('expense_id');
        $this->assertSame($count, Purchase::count());
        $this->assertNull($expense->fresh()->reclassified_at);
    }

    public function test_normal_purchase_increases_inventory(): void
    {
        FundTransaction::create(['direction' => 'in', 'source' => 'investment', 'amount' => 1000]);
        $this->post(route('purchases.store'), $this->payload([
            'purchase_mode' => 'normal', 'accounting_treatment' => null,
        ]))->assertSessionHasNoErrors();
        $this->assertEquals(13, $this->product->fresh()->stock);
        $this->assertEquals(13, InventoryStock::where('product_id', $this->product->id)->value('on_hand'));
    }

    public function test_historical_edit_changes_quantities_without_stock_or_fund_movement(): void
    {
        $this->post(route('purchases.store'), $this->payload())->assertSessionHasNoErrors();
        $purchase = Purchase::latest('id')->firstOrFail();
        $item = $purchase->items()->firstOrFail();
        $before = AccountingSummaryService::fundBalance();
        $this->post(route('purchases.update', $purchase->id), [
            'purchase_mode' => 'historical', 'invoice_no' => $purchase->invoice_no,
            'purchase_date' => '2026-07-28',
            'lines' => [['item_id' => $item->id, 'qty' => 10, 'unit_cost' => 50]],
        ])->assertSessionHasNoErrors()->assertRedirect(route('purchases.invoice', $purchase->id));
        $this->assertEquals(10, $item->fresh()->qty);
        $this->assertEquals(500, $purchase->fresh()->grand_total);
        $this->assertEquals(8, $this->product->fresh()->stock);
        $this->assertEquals($before, AccountingSummaryService::fundBalance());
    }

    public function test_historical_reversal_restores_expense_without_stock_movement(): void
    {
        $expense = Expense::create([
            'title' => 'Old inventory expense', 'amount' => 500, 'expense_date' => '2026-07-28',
            'created_by' => $this->admin->id,
        ]);
        $fund = FundTransaction::create([
            'direction' => 'out', 'source' => 'expense', 'source_id' => $expense->id,
            'amount' => 500, 'created_by' => $this->admin->id,
        ]);
        $expense->update(['fund_transaction_id' => $fund->id]);
        $balance = AccountingSummaryService::fundBalance();
        $this->post(route('purchases.store'), $this->payload([
            'accounting_treatment' => 'reclassify_expense', 'expense_id' => $expense->id,
        ]))->assertSessionHasNoErrors();
        $purchase = Purchase::latest('id')->firstOrFail();
        $this->delete(route('purchases.destroy', $purchase->id))->assertSessionHasErrors('purchase');
        $this->post(route('purchases.reverse_historical', $purchase->id), [
            'confirmation' => 'REVERSE HISTORICAL PURCHASE',
        ])->assertSessionHasNoErrors();
        $this->assertNull(Purchase::find($purchase->id));
        $this->assertNull($expense->fresh()->reclassified_at);
        $this->assertSame('expense', $fund->fresh()->source);
        $this->assertEquals($balance, AccountingSummaryService::fundBalance());
        $this->assertEquals(8, $this->product->fresh()->stock);
    }

    public function test_multiple_lines_sum_correctly(): void
    {
        $second = Product::create([
            'product_type' => 'physical', 'name' => 'Second Product '.Str::uuid(),
            'slug' => 'second-product-'.Str::uuid(), 'category_id' => 1,
            'new_price' => 100, 'stock' => 7, 'purchase_price' => 40, 'status' => 1,
        ]);
        $this->post(route('purchases.store'), $this->payload([
            'lines' => [
                ['product_id' => $this->product->id, 'qty' => 6, 'unit_cost' => 635],
                ['product_id' => $second->id, 'qty' => 4, 'unit_cost' => 180],
            ],
            'paid_amount' => 4530,
        ]))->assertSessionHasNoErrors();
        $purchase = Purchase::latest('id')->firstOrFail();
        $this->assertEquals(10, $purchase->total_qty);
        $this->assertEquals(4530, $purchase->grand_total);
        $this->assertEquals(8, $this->product->fresh()->stock);
        $this->assertEquals(7, $second->fresh()->stock);
    }

    public function test_normal_multiple_lines_receive_each_product_once(): void
    {
        $second = Product::create([
            'product_type' => 'physical', 'name' => 'Normal Second '.Str::uuid(),
            'slug' => 'normal-second-'.Str::uuid(), 'category_id' => 1,
            'new_price' => 100, 'stock' => 7, 'purchase_price' => 40, 'status' => 1,
        ]);
        InventoryStock::create(['product_id' => $second->id, 'on_hand' => 7, 'reserved' => 0]);
        FundTransaction::create(['direction' => 'in', 'source' => 'investment', 'amount' => 1000]);
        $this->post(route('purchases.store'), $this->payload([
            'purchase_mode' => 'normal', 'accounting_treatment' => null,
            'lines' => [
                ['product_id' => $this->product->id, 'qty' => 5, 'unit_cost' => 100],
                ['product_id' => $second->id, 'qty' => 2, 'unit_cost' => 50],
            ], 'paid_amount' => 600,
        ]))->assertSessionHasNoErrors();
        $this->assertEquals(13, $this->product->fresh()->stock);
        $this->assertEquals(9, $second->fresh()->stock);
        $this->assertEquals(100, $this->product->fresh()->purchase_price);
        $this->assertEquals(50, $second->fresh()->purchase_price);
        $purchase = Purchase::latest('id')->firstOrFail();
        $this->assertSame(2, $purchase->items()->count());
        $this->assertSame(1, $purchase->payments()->count());
    }

    public function test_staff_without_admin_role_cannot_backfill(): void
    {
        $staff = User::create([
            'name' => 'Purchase Staff', 'email' => 'purchase-staff-'.Str::uuid().'@example.test',
            'password' => bcrypt('secret'), 'status' => 1,
        ]);
        Auth::guard('admin')->login($staff);
        $this->post(route('purchases.store'), $this->payload())->assertForbidden();
    }

    public function test_line_failure_rolls_back_the_whole_historical_invoice(): void
    {
        $other = Product::create([
            'product_type' => 'physical', 'name' => 'Rollback product '.Str::uuid(),
            'slug' => 'rollback-product-'.Str::uuid(), 'category_id' => 1,
            'new_price' => 100, 'stock' => 4, 'purchase_price' => 30, 'status' => 1,
        ]);
        $data = $this->payload(['lines' => [
            ['product_id' => $this->product->id, 'qty' => 1, 'unit_cost' => 100],
            ['product_id' => $other->id, 'qty' => 1, 'unit_cost' => 100],
        ], 'paid_amount' => 200]);
        $before = Purchase::count();
        $written = 0;
        PurchaseItem::creating(function () use (&$written) {
            if (++$written === 2) throw new \RuntimeException('Forced second-line failure');
        });
        try {
            (new HistoricalPurchaseService())->create($data, $this->admin->id);
            $this->fail('The second purchase line should fail.');
        } catch (\RuntimeException $e) {
            $this->assertSame('Forced second-line failure', $e->getMessage());
        } finally {
            PurchaseItem::flushEventListeners();
        }
        $this->assertSame($before, Purchase::count());
        $this->assertEquals(0, $this->supplier->fresh()->current_due);
        $this->assertEquals(8, $this->product->fresh()->stock);
    }

    public function test_example_invoice_line_totals_are_134_units_and_21700(): void
    {
        $rows = [[6,635],[4,180],[3,180],[2,180],[1,180],[5,100],[5,100],
            [5,64],[5,64],[5,76],[5,76],[5,72],[5,72],[5,72],[5,215],
            [5,215],[5,160],[5,65],[5,65],[5,65],[5,65],[5,135],[5,145],
            [12,35],[12,30],[4,1545]];
        $this->assertCount(26, $rows);
        $this->assertSame(134, array_sum(array_column($rows, 0)));
        $this->assertSame(21700, array_sum(array_map(fn ($row) => $row[0] * $row[1], $rows)));

        $lines = [];
        foreach ($rows as $index => [$qty, $cost]) {
            $product = $index === 0 ? $this->product : Product::create([
                'product_type' => 'physical', 'name' => 'Invoice mapped product '.$index.' '.Str::uuid(),
                'slug' => 'invoice-mapped-'.$index.'-'.Str::uuid(), 'category_id' => 1,
                'new_price' => 100, 'stock' => 3, 'purchase_price' => 25, 'status' => 1,
            ]);
            $lines[] = ['product_id' => $product->id, 'qty' => $qty, 'unit_cost' => $cost];
        }
        $this->post(route('purchases.store'), $this->payload([
            'lines' => $lines, 'paid_amount' => 21700,
        ]))->assertSessionHasNoErrors();
        $purchase = Purchase::latest('id')->firstOrFail();
        $this->assertSame(26, $purchase->items()->count());
        $this->assertEquals(134, $purchase->total_qty);
        $this->assertEquals(21700, $purchase->grand_total);
        $this->assertEquals(8, $this->product->fresh()->stock);
        $this->assertEquals(0, InventoryMovement::where('purchase_id', $purchase->id)->count());
    }
}
