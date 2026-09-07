<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\ExpenseController;
use App\Models\Expense;
use App\Models\FundTransaction;
use App\Models\User;
use App\Services\AccountingSummaryService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ExpenseStandaloneWorkflowTest extends TestCase
{
    use DatabaseTransactions;

    public function test_expense_larger_than_fund_balance_can_be_created_updated_and_deleted(): void
    {
        $admin = User::create([
            'name' => 'Expense Test Admin',
            'email' => 'expense-' . Str::uuid() . '@example.test',
            'password' => bcrypt('secret'),
            'status' => 1,
        ]);
        $admin->assignRole(Role::findOrCreate('Admin', 'admin'));
        Auth::guard('admin')->login($admin);

        $controller = app(ExpenseController::class);
        $ledgerBalance = AccountingSummaryService::fundBalance();
        $balanceAdjustment = 1030 - $ledgerBalance;
        if (abs($balanceAdjustment) >= 0.01) {
            FundTransaction::create([
                'direction' => $balanceAdjustment > 0 ? 'in' : 'out',
                'source' => 'reconciliation',
                'amount' => abs($balanceAdjustment),
                'note' => 'Set the regression-test ledger balance to 1,030 BDT.',
                'created_by' => $admin->id,
            ]);
        }

        $this->assertEquals(1030, AccountingSummaryService::fundBalance());
        $amount = 2000;
        $title = 'Standalone expense ' . Str::uuid();
        $today = now()->format('Y-m-d');
        $yearTotalBefore = $this->yearTotal();
        $monthTotalBefore = $this->monthTotal();
        $todayTotalBefore = $this->todayTotal();

        $response = $controller->store($this->expenseRequest([
            'title' => $title,
            'amount' => $amount,
            'expense_date' => $today,
            'category' => 'Operations',
            'note' => 'Must save even when it exceeds the ledger balance.',
        ]));

        $this->assertTrue($response->isRedirect(route('admin.expenses.index')));
        $expense = Expense::query()->where('title', $title)->firstOrFail();
        $this->assertSame($admin->id, (int) $expense->created_by);
        $this->assertEquals($yearTotalBefore + $amount, $this->yearTotal());
        $this->assertEquals($monthTotalBefore + $amount, $this->monthTotal());
        $this->assertEquals($todayTotalBefore + $amount, $this->todayTotal());
        $this->assertSame(1, $this->expenseFundTransactions($expense)->count());

        $updatedAmount = $amount + 500;
        $controller->update($this->expenseRequest([
            'title' => $title,
            'amount' => $updatedAmount,
            'expense_date' => $today,
            'category' => 'Office',
            'note' => 'Updated standalone expense.',
        ]), $expense->id);

        $expense->refresh();
        $this->assertEquals($updatedAmount, $expense->amount);
        $this->assertEquals($updatedAmount, $this->expenseFundTransactions($expense)->value('amount'));
        $this->assertSame(1, $this->expenseFundTransactions($expense)->count());
        $this->assertEquals($yearTotalBefore + $updatedAmount, $this->yearTotal());
        $this->assertEquals($monthTotalBefore + $updatedAmount, $this->monthTotal());
        $this->assertEquals($todayTotalBefore + $updatedAmount, $this->todayTotal());

        $controller->destroy($expense->id);

        $this->assertDatabaseMissing('expenses', ['id' => $expense->id]);
        $this->assertSame(0, $this->expenseFundTransactions($expense)->count());
        $this->assertEquals($yearTotalBefore, $this->yearTotal());
        $this->assertEquals($monthTotalBefore, $this->monthTotal());
        $this->assertEquals($todayTotalBefore, $this->todayTotal());
    }

    private function expenseRequest(array $data): Request
    {
        return Request::create('/admin/expenses', 'POST', $data);
    }

    private function expenseFundTransactions(Expense $expense)
    {
        return FundTransaction::query()
            ->includedInAccounting()
            ->where('direction', 'out')
            ->where('source', 'expense')
            ->where('source_id', $expense->id);
    }

    private function yearTotal(): float
    {
        return (float) Expense::includedInAccounting()->whereYear('expense_date', now()->year)->sum('amount');
    }

    private function monthTotal(): float
    {
        return (float) Expense::includedInAccounting()
            ->whereYear('expense_date', now()->year)
            ->whereMonth('expense_date', now()->month)
            ->sum('amount');
    }

    private function todayTotal(): float
    {
        return (float) Expense::includedInAccounting()->whereDate('expense_date', now()->toDateString())->sum('amount');
    }
}
