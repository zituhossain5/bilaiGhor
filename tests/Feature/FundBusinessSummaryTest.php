<?php

namespace Tests\Feature;

use App\Helpers\FundHelper;
use App\Http\Controllers\Admin\FundController;
use App\Models\Expense;
use App\Models\FundTransaction;
use App\Models\Order;
use App\Models\OrderDetails;
use App\Services\AccountingSummaryService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Tests\TestCase;

class FundBusinessSummaryTest extends TestCase
{
    use DatabaseTransactions;

    public function test_summary_uses_valid_sales_historical_cost_and_recorded_expenses(): void
    {
        $from = Carbon::parse('2035-04-01')->startOfDay();
        $to = Carbon::parse('2035-04-30')->endOfDay();

        FundTransaction::create([
            'direction' => 'in',
            'source' => 'investment',
            'investment_type' => 'additional',
            'transaction_date' => '2035-04-02',
            'amount' => 50000,
            'note' => 'Summary test investment',
        ]);

        $normalOrder = $this->paidCompletedOrder(1000);
        $manualOrder = $this->paidCompletedOrder(600, 'manual');
        $unpaidOrder = $this->order(900, 6, 'unpaid');

        OrderDetails::create([
            'order_id' => $normalOrder->id,
            'product_id' => null,
            'product_name' => 'Historical cost item',
            'purchase_price' => 300,
            'sale_price' => 500,
            'qty' => 2,
        ]);
        OrderDetails::create([
            'order_id' => $manualOrder->id,
            'product_id' => null,
            'product_name' => 'Manual order item',
            'purchase_price' => 200,
            'sale_price' => 600,
            'qty' => 1,
        ]);
        OrderDetails::create([
            'order_id' => $unpaidOrder->id,
            'product_id' => null,
            'product_name' => 'Excluded unpaid item',
            'purchase_price' => 50,
            'sale_price' => 900,
            'qty' => 1,
        ]);

        $this->recognizeSale($normalOrder, '2035-04-10 12:00:00');
        $this->recognizeSale($manualOrder, '2035-04-15 12:00:00');

        Expense::create([
            'title' => 'April operating expense',
            'amount' => 250,
            'expense_date' => '2035-04-20',
            'category' => 'Operations',
            'created_by' => 1,
        ]);
        Expense::create([
            'title' => 'Outside period expense',
            'amount' => 999,
            'expense_date' => '2035-05-01',
            'created_by' => 1,
        ]);

        $summary = AccountingSummaryService::businessSummary($from, $to);

        $this->assertSame(2, $summary['sales_orders']);
        $this->assertEquals(1600, $summary['sales_revenue']);
        $this->assertEquals(800, $summary['cogs']);
        $this->assertEquals(800, $summary['gross_profit']);
        $this->assertSame(1, $summary['expense_count']);
        $this->assertEquals(250, $summary['expenses']);
        $this->assertEquals(550, $summary['net_profit']);
        $this->assertEquals(50000, $summary['period_investment']);
        $this->assertSame(0, $summary['cost_fallback_lines']);
        $this->assertSame(0, $summary['zero_cost_lines']);
    }

    public function test_period_presets_are_day_bounded_and_custom_is_day_first_in_label(): void
    {
        Carbon::setTestNow('2035-04-18 10:30:00');

        $current = AccountingSummaryService::period('current_month');
        $previous = AccountingSummaryService::period('previous_month');
        $month = AccountingSummaryService::period('month', 2034, 9);
        $year = AccountingSummaryService::period('year', 2033);
        $custom = AccountingSummaryService::period('custom', fromDate: '2035-02-03', toDate: '2035-02-19');

        $this->assertSame('2035-04-01 00:00:00', $current['from']->format('Y-m-d H:i:s'));
        $this->assertSame('2035-04-30 23:59:59', $current['to']->format('Y-m-d H:i:s'));
        $this->assertSame('March 2035', $previous['label']);
        $this->assertSame('2034-09-01', $month['from']->toDateString());
        $this->assertSame('2034-09-30', $month['to']->toDateString());
        $this->assertSame('2033-01-01', $year['from']->toDateString());
        $this->assertSame('2033-12-31', $year['to']->toDateString());
        $this->assertSame('03/02/2035 - 19/02/2035', $custom['label']);

        Carbon::setTestNow();
    }

    public function test_no_sales_and_no_expenses_period_returns_zero_profit_values(): void
    {
        $summary = AccountingSummaryService::businessSummary(
            Carbon::parse('2099-01-01')->startOfDay(),
            Carbon::parse('2099-01-31')->endOfDay(),
        );

        $this->assertSame(0, $summary['sales_orders']);
        $this->assertEquals(0, $summary['sales_revenue']);
        $this->assertEquals(0, $summary['cogs']);
        $this->assertSame(0, $summary['expense_count']);
        $this->assertEquals(0, $summary['expenses']);
        $this->assertEquals(0, $summary['gross_profit']);
        $this->assertEquals(0, $summary['net_profit']);
    }

    public function test_withdrawal_is_dated_filtered_and_idempotent(): void
    {
        FundTransaction::create([
            'direction' => 'in',
            'source' => 'investment',
            'investment_type' => 'additional',
            'transaction_date' => '2036-06-01',
            'amount' => 5000,
        ]);
        $balanceBefore = AccountingSummaryService::fundBalance();
        $key = (string) Str::uuid();
        $controller = app(FundController::class);
        $payload = [
            'amount' => 2000,
            'transaction_date' => '2036-06-12',
            'note' => 'Owner cash withdrawal',
            'idempotency_key' => $key,
        ];

        $controller->withdraw(Request::create('/admin/fund/withdraw', 'POST', $payload));
        $controller->withdraw(Request::create('/admin/fund/withdraw', 'POST', $payload));

        $this->assertSame(1, FundTransaction::query()->where('idempotency_key', $key)->count());
        $this->assertEquals($balanceBefore - 2000, AccountingSummaryService::fundBalance());
        $this->assertDatabaseHas('fund_transactions', [
            'source' => 'withdraw',
            'direction' => 'out',
            'transaction_date' => '2036-06-12',
            'amount' => 2000,
        ]);

        $summary = AccountingSummaryService::businessSummary(
            Carbon::parse('2036-06-01')->startOfDay(),
            Carbon::parse('2036-06-30')->endOfDay(),
        );
        $this->assertSame(1, $summary['withdrawal_count']);
        $this->assertEquals(2000, $summary['withdrawals']);
    }

    public function test_csv_export_contains_the_same_filtered_summary_and_history(): void
    {
        FundTransaction::create([
            'direction' => 'in',
            'source' => 'investment',
            'investment_type' => 'additional',
            'transaction_date' => '2037-07-05',
            'amount' => 4321,
            'note' => 'CSV period investment',
        ]);

        $response = app(FundController::class)->export(Request::create('/admin/fund/export', 'GET', [
            'mode' => 'custom',
            'from_date' => '2037-07-01',
            'to_date' => '2037-07-31',
        ]));

        ob_start();
        $response->sendContent();
        $csv = ob_get_clean();

        $this->assertStringContainsString('Bilai Ghor Account & Fund Report', $csv);
        $this->assertStringContainsString('"Investment Added",4321.00', $csv);
        $this->assertStringContainsString('CSV period investment', $csv);
        $this->assertStringContainsString('01/07/2037 - 31/07/2037', $csv);
    }

    public function test_pdf_export_renders_the_filtered_account_report(): void
    {
        $response = app(FundController::class)->exportPdf(Request::create('/admin/fund/export/pdf', 'GET', [
            'mode' => 'custom',
            'from_date' => '2099-02-01',
            'to_date' => '2099-02-28',
        ]));

        $this->assertSame('application/pdf', $response->headers->get('content-type'));
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    private function paidCompletedOrder(int $amount, ?string $source = null): Order
    {
        return $this->order($amount, 6, 'paid', $source);
    }

    private function order(int $amount, int $status, string $payment, ?string $source = null): Order
    {
        return Order::create([
            'invoice_id' => 'SUMMARY-' . Str::upper(Str::random(12)),
            'amount' => $amount,
            'discount' => 0,
            'shipping_charge' => 0,
            'customer_id' => 0,
            'order_status' => $status,
            'payment_status' => $payment,
            'order_source' => $source,
        ]);
    }

    private function recognizeSale(Order $order, string $recognizedAt): void
    {
        $transaction = FundHelper::creditSale($order, 'Summary test sale');
        DB::table('fund_transactions')->where('id', $transaction->id)->update([
            'created_at' => $recognizedAt,
            'updated_at' => $recognizedAt,
        ]);
    }
}
