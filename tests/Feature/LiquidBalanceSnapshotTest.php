<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\FundController;
use App\Models\FundTransaction;
use App\Models\LiquidBalanceSnapshot;
use App\Models\User;
use App\Services\AccountingSummaryService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class LiquidBalanceSnapshotTest extends TestCase
{
    use DatabaseTransactions;

    public function test_verified_balances_are_recorded_once_without_changing_accounting(): void
    {
        $admin = User::query()->find(1) ?? User::factory()->create(['id' => 1]);
        Auth::guard('admin')->setUser($admin);
        $key = (string) Str::uuid();
        $before = AccountingSummaryService::fundTotals();
        $fundCount = FundTransaction::count();
        $payload = [
            'idempotency_key' => $key,
            'cash_balance' => 100,
            'bank_balance' => 200.25,
            'mobile_wallet_balance' => 300,
            'other_liquid_balance' => 50,
            'courier_reported_balance' => 14966,
            'verified' => '1',
        ];

        $controller = app(FundController::class);
        $controller->recordLiquidBalances(Request::create('/admin/fund/liquid-balances', 'POST', $payload));
        $controller->recordLiquidBalances(Request::create('/admin/fund/liquid-balances', 'POST', $payload));

        $snapshot = LiquidBalanceSnapshot::where('idempotency_key', $key)->sole();
        $this->assertEquals(650.25, $snapshot->money_on_hand);
        $this->assertEquals(14966, $snapshot->courier_reported_balance);
        $this->assertSame($fundCount, FundTransaction::count());
        $this->assertSame($before, AccountingSummaryService::fundTotals());

        $page = $controller->index(Request::create('/admin/fund', 'GET', ['mode' => 'all']))
            ->with('errors', new ViewErrorBag())
            ->render();
        $this->assertStringContainsString('Verified money on hand', $page);
        $this->assertStringContainsString('650.25', $page);
        $this->assertStringContainsString('14,966.00', $page);
    }

    public function test_unverified_or_missing_account_balances_are_rejected(): void
    {
        $admin = User::query()->find(1) ?? User::factory()->create(['id' => 1]);
        Auth::guard('admin')->setUser($admin);

        try {
            app(FundController::class)->recordLiquidBalances(Request::create('/admin/fund/liquid-balances', 'POST', [
                'idempotency_key' => (string) Str::uuid(),
                'cash_balance' => 100,
                'bank_balance' => 200,
                'mobile_wallet_balance' => 0,
            ]));
            $this->fail('Expected validation to reject unverified or missing balances.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('other_liquid_balance', $exception->errors());
            $this->assertArrayHasKey('verified', $exception->errors());
        }
    }
}
