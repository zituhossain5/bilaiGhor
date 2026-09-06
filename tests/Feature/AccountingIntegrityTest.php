<?php

namespace Tests\Feature;

use App\Helpers\FundHelper;
use App\Models\FundTransaction;
use App\Models\Order;
use App\Services\AccountingSummaryService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class AccountingIntegrityTest extends TestCase
{
    use DatabaseTransactions;

    public function test_legacy_vendor_and_reseller_public_routes_are_disabled(): void
    {
        $this->get('/sellers')->assertNotFound();
        $this->get('/vendor/dashboard')->assertNotFound();
        $this->get('/r/legacy-shop')->assertNotFound();
    }

    public function test_balance_excludes_legacy_business_sources_and_archived_rows(): void
    {
        $before = AccountingSummaryService::fundBalance();

        FundTransaction::create([
            'direction' => 'in',
            'source' => 'manual_add',
            'amount' => 125,
            'note' => 'Accounting test owner funding',
        ]);
        FundTransaction::create([
            'direction' => 'in',
            'source' => 'vendor_commission',
            'amount' => 900,
        ]);
        FundTransaction::create([
            'direction' => 'out',
            'source' => 'withdraw',
            'amount' => 50,
            'excluded_from_accounting_at' => now(),
            'accounting_exclusion_reason' => 'Test exclusion',
        ]);

        $this->assertEquals($before + 125, AccountingSummaryService::fundBalance());
    }

    public function test_completed_paid_order_is_credited_exactly_once(): void
    {
        $order = Order::create([
            'invoice_id' => 'ACCOUNTING-'.Str::upper(Str::random(10)),
            'amount' => 875,
            'discount' => 0,
            'shipping_charge' => 0,
            'order_status' => 6,
            'payment_status' => 'paid',
        ]);

        $first = FundHelper::creditSale($order, 'Accounting integrity test');
        $second = FundHelper::creditSale($order, 'Duplicate callback test');

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, FundTransaction::includedInAccounting()
            ->where('source', 'sale')
            ->where('source_id', $order->id)
            ->count());
    }

    public function test_unpaid_order_cannot_create_a_sale_credit(): void
    {
        $order = Order::create([
            'invoice_id' => 'ACCOUNTING-'.Str::upper(Str::random(10)),
            'amount' => 500,
            'discount' => 0,
            'shipping_charge' => 0,
            'order_status' => 6,
            'payment_status' => 'pending',
        ]);

        $transaction = FundTransaction::create([
            'direction' => 'in',
            'source' => 'sale',
            'source_id' => $order->id,
            'amount' => 500,
        ]);

        $this->assertFalse($transaction->exists);
        $this->assertDatabaseMissing('fund_transactions', [
            'source' => 'sale',
            'source_id' => $order->id,
        ]);
    }

    public function test_archived_sale_does_not_block_a_new_valid_credit(): void
    {
        $order = Order::create([
            'invoice_id' => 'ACCOUNTING-'.Str::upper(Str::random(10)),
            'amount' => 640,
            'discount' => 0,
            'shipping_charge' => 0,
            'order_status' => 6,
            'payment_status' => 'paid',
        ]);
        FundTransaction::withoutEvents(fn () => FundTransaction::create([
            'direction' => 'in',
            'source' => 'sale',
            'source_id' => $order->id,
            'amount' => 640,
            'excluded_from_accounting_at' => now(),
            'accounting_exclusion_reason' => 'Historical invalid credit',
        ]));

        $active = FundHelper::creditSale($order, 'Restored valid sale');

        $this->assertNull($active->excluded_from_accounting_at);
        $this->assertSame(1, FundTransaction::includedInAccounting()
            ->where('source', 'sale')
            ->where('source_id', $order->id)
            ->count());
    }

    public function test_cleanup_command_can_delete_legacy_candidates_without_deleting_owner_funding(): void
    {
        Storage::fake('local');

        $legacySale = FundTransaction::withoutEvents(fn () => FundTransaction::create([
            'direction' => 'in',
            'source' => 'sale',
            'source_id' => 999999,
            'amount' => 425,
            'note' => 'Orphan sale cleanup test',
        ]));
        $legacyCommission = FundTransaction::create([
            'direction' => 'in',
            'source' => 'vendor_commission',
            'source_id' => 999999,
            'amount' => 75,
            'note' => 'Legacy commission cleanup test',
        ]);
        $ownerFunding = FundTransaction::create([
            'direction' => 'in',
            'source' => 'manual_add',
            'amount' => 1000,
            'note' => 'Real owner funding should require evidence',
        ]);

        $this->artisan('accounting:cleanup-legacy', [
            '--delete' => true,
            '--force' => true,
            '--keep-expenses' => true,
        ])->assertExitCode(0);

        $this->assertDatabaseMissing('fund_transactions', ['id' => $legacySale->id]);
        $this->assertDatabaseMissing('fund_transactions', ['id' => $legacyCommission->id]);
        $this->assertDatabaseHas('fund_transactions', ['id' => $ownerFunding->id]);
        $this->assertDatabaseHas('accounting_cleanup_runs', [
            'summary->cleanup_mode' => 'delete',
        ]);
    }

    public function test_cleanup_command_rebuilds_missing_completed_paid_order_sale_credits(): void
    {
        Storage::fake('local');

        $order = Order::create([
            'invoice_id' => 'ACCOUNTING-'.Str::upper(Str::random(10)),
            'amount' => 835,
            'discount' => 0,
            'shipping_charge' => 0,
            'order_status' => 6,
            'payment_status' => 'paid',
        ]);

        $this->artisan('accounting:cleanup-legacy', [
            '--force' => true,
            '--keep-expenses' => true,
        ])->assertExitCode(0);

        $this->assertDatabaseHas('fund_transactions', [
            'direction' => 'in',
            'source' => 'sale',
            'source_id' => $order->id,
            'amount' => 835,
        ]);
        $this->assertDatabaseHas('accounting_cleanup_runs', [
            'summary->sale_entries_created' => 1,
        ]);
    }
}
