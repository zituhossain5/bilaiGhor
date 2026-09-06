@extends('backEnd.layouts.master')
@section('title','Expenses')

@php
    use Illuminate\Support\Facades\Auth;
    // Check if current user is Admin (Super Admin or has Admin role)
    $isAdmin = false;
    $user = Auth::guard('admin')->user();
    if ($user) {
        if ($user->id == 1) {
            $isAdmin = true;
        } else {
            $spatieRoles = $user->getRoleNames()->map(function($role) {
                return strtolower($role);
            })->toArray();
            $isAdmin = in_array('admin', $spatieRoles);
        }
    }
@endphp

@section('content')
<div class="container-fluid">

    <div class="d-flex align-items-center mb-3">
        <h4 class="mb-0">Expenses / খরচ</h4>
    </div>

    {{-- ======= SUMMARY CARDS ======= --}}
    <div class="row mb-4">

      {{-- Available Balance --}}
<div class="col-md-3 mb-3">
    <div class="card bg-success text-white" style="color:#fff !important;">
        <div class="card-body" style="color:#fff !important;">
            <h5 class="mb-1" style="color:#fff !important;">Available Fund Balance</h5>
            <h2 class="mb-0" style="color:#fff !important;">{{ number_format($balance, 2) }} ৳</h2>
            <small class="opacity-75 d-block mt-1" style="color:#fff !important;">
                বর্তমানে তহবিলে অবশিষ্ট ব্যালেন্স
            </small>
        </div>
    </div>
</div>

{{-- This Year Expense --}}
<div class="col-md-3 mb-3">
    <div class="card bg-primary text-white" style="color:#fff !important;">
        <div class="card-body" style="color:#fff !important;">
            <h5 class="mb-1" style="color:#fff !important;">This Year ({{ $currentYear }})</h5>
            <h3 class="mb-0" style="color:#fff !important;">{{ number_format($yearlyExpense, 2) }} ৳</h3>
            <small class="opacity-75 d-block mt-1" style="color:#fff !important;">
                এই বছরে মোট খরচ হয়েছে
            </small>
        </div>
    </div>
</div>

{{-- This Month Expense --}}
<div class="col-md-3 mb-3">
    <div class="card bg-info text-white" style="color:#fff !important;">
        <div class="card-body" style="color:#fff !important;">
            <h5 class="mb-1" style="color:#fff !important;">
                This Month ({{ \Carbon\Carbon::createFromDate(now()->year, $currentMonth, 1)->format('F') }})
            </h5>
            <h3 class="mb-0" style="color:#fff !important;">{{ number_format($monthlyExpense, 2) }} ৳</h3>
            <small class="opacity-75 d-block mt-1" style="color:#fff !important;">
                এই মাসে মোট খরচ হয়েছে
            </small>
        </div>
    </div>
</div>

{{-- Today Expense --}}
<div class="col-md-3 mb-3">
    <div class="card bg-danger text-white" style="color:#fff !important;">
        <div class="card-body" style="color:#fff !important;">
            <h5 class="mb-1" style="color:#fff !important;">Today ({{ now()->format('d M, Y') }})</h5>
            <h3 class="mb-0" style="color:#fff !important;">{{ number_format($todayExpense, 2) }} ৳</h3>
            <small class="opacity-75 d-block mt-1" style="color:#fff !important;">
                আজকে মোট খরচ হয়েছে
            </small>
        </div>
    </div>
</div>


    </div>

    <div class="card shadow-sm mb-4 border-start border-4 border-warning">
        <div class="card-header bg-white">
            <h5 class="mb-1">Account Reconciliation</h5>
            <small class="text-muted">The fund balance is liquid money only. Inventory value is reported separately and is never added to this balance.</small>
        </div>
        <div class="card-body">
            <div class="row g-3 mb-3">
                <div class="col-xl-3 col-md-6">
                    <div class="text-muted small">Ledger Fund Balance</div>
                    <div class="h4 mb-0">&#2547;{{ number_format($accounting['fund_balance'], 2) }}</div>
                    <small>Fund in &#8722; fund out</small>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="text-muted small">Owner Funding Recorded</div>
                    <div class="h4 mb-0">&#2547;{{ number_format($accounting['owner_funding'], 2) }}</div>
                    <small>Only manual fund additions</small>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="text-muted small">Sales Inflow Recorded</div>
                    <div class="h4 mb-0">&#2547;{{ number_format($accounting['sales_inflow'], 2) }}</div>
                    <small>Completed-order fund credits</small>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="text-muted small">Reconciliation Adjustment</div>
                    <div class="h4 mb-0">&#2547;{{ number_format($accounting['reconciliation_net'], 2) }}</div>
                    <small>Net correction to verified balances</small>
                </div>
            </div>

            <div class="alert alert-light border py-2">
                <strong>Formula:</strong> all recorded inflows &#8722; all recorded outflows =
                &#2547;{{ number_format($accounting['fund_balance'], 2) }}.
                Stock worth &#2547;{{ number_format($accounting['inventory_on_hand_cost'], 2) }} at cost is an asset, not available cash.
            </div>

            <div class="row g-4">
                <div class="col-xl-7">
                    <h6>Fund balance by source</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered align-middle mb-2">
                            <thead class="table-light">
                                <tr>
                                    <th>Source</th>
                                    <th>Direction</th>
                                    <th class="text-end">Entries</th>
                                    <th class="text-end">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($accounting['fund_sources'] as $source)
                                    <tr>
                                        <td>{{ ucwords(str_replace('_', ' ', $source['source'])) }}</td>
                                        <td>
                                            <span class="badge {{ $source['direction'] === 'in' ? 'bg-success' : 'bg-danger' }}">
                                                {{ strtoupper($source['direction']) }}
                                            </span>
                                        </td>
                                        <td class="text-end">{{ number_format($source['transaction_count']) }}</td>
                                        <td class="text-end">&#2547;{{ number_format($source['total'], 2) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center text-muted">No fund transactions recorded.</td></tr>
                                @endforelse
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <th colspan="3">Total inflow</th>
                                    <th class="text-end">&#2547;{{ number_format($accounting['fund_in'], 2) }}</th>
                                </tr>
                                <tr>
                                    <th colspan="3">Total outflow</th>
                                    <th class="text-end">&#2547;{{ number_format($accounting['fund_out'], 2) }}</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <h6 class="mt-4">Inventory and liabilities</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered mb-2">
                    <thead class="table-light">
                        <tr>
                            <th>Stock measure</th>
                            <th class="text-end">Amount</th>
                            <th>Meaning</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Available stock at cost</td>
                            <td class="text-end">&#2547;{{ number_format($accounting['inventory_available_cost'], 2) }}</td>
                            <td>Available quantity x latest purchase cost</td>
                        </tr>
                        <tr>
                            <td>Reserved stock at cost</td>
                            <td class="text-end">&#2547;{{ number_format($accounting['inventory_reserved_cost'], 2) }}</td>
                            <td>Stock committed to active orders</td>
                        </tr>
                        <tr>
                            <td>Available stock retail value</td>
                            <td class="text-end">&#2547;{{ number_format($accounting['available_retail_value'], 2) }}</td>
                            <td>Available quantity x selling price; this is not cost or cash</td>
                        </tr>
                        <tr>
                            <td>Potential gross margin</td>
                            <td class="text-end">&#2547;{{ number_format($accounting['potential_gross_margin'], 2) }}</td>
                            <td>Retail value &#8722; available stock cost; not yet realized profit</td>
                        </tr>
                        <tr>
                            <td>Supplier due</td>
                            <td class="text-end">&#2547;{{ number_format($accounting['supplier_due'], 2) }}</td>
                            <td>Outstanding purchase liability</td>
                        </tr>
                        <tr>
                            <td>Tracked net assets</td>
                            <td class="text-end">&#2547;{{ number_format($accounting['tracked_net_assets'], 2) }}</td>
                            <td>Fund balance + physical inventory cost &#8722; supplier due</td>
                        </tr>
                    </tbody>
                </table>
                    </div>
                </div>

                <div class="col-xl-5">
                    <div class="border rounded p-3 bg-light">
                        <h6 class="mb-1">Match the ledger to real life</h6>
                        <p class="small text-muted mb-3">Count money currently available in each place. Saving creates one auditable adjustment; it does not delete old records.</p>

                        @if($isAdmin)
                            <form method="POST" action="{{ route('admin.expenses.reconcile') }}" id="fund_reconciliation_form" data-ledger-balance="{{ $accounting['fund_balance'] }}">
                                @csrf
                                <div class="row g-2">
                                    <div class="col-sm-6">
                                        <label class="form-label small">Cash</label>
                                        <input type="number" name="cash_balance" value="{{ old('cash_balance', 0) }}" min="0" step="0.01" class="form-control reconciliation-balance @error('cash_balance') is-invalid @enderror" required>
                                        @error('cash_balance')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="form-label small">Bank</label>
                                        <input type="number" name="bank_balance" value="{{ old('bank_balance', 0) }}" min="0" step="0.01" class="form-control reconciliation-balance @error('bank_balance') is-invalid @enderror" required>
                                        @error('bank_balance')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="form-label small">Mobile wallets</label>
                                        <input type="number" name="mobile_wallet_balance" value="{{ old('mobile_wallet_balance', 0) }}" min="0" step="0.01" class="form-control reconciliation-balance @error('mobile_wallet_balance') is-invalid @enderror" required>
                                        @error('mobile_wallet_balance')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="form-label small">Other liquid funds</label>
                                        <input type="number" name="other_balance" value="{{ old('other_balance', 0) }}" min="0" step="0.01" class="form-control reconciliation-balance @error('other_balance') is-invalid @enderror" required>
                                        @error('other_balance')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                                <div class="mt-2">
                                    <label class="form-label small">Reason / statement reference</label>
                                    <textarea name="note" rows="2" class="form-control @error('note') is-invalid @enderror" required>{{ old('note') }}</textarea>
                                    @error('note')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="form-check mt-3">
                                    <input class="form-check-input @error('confirmed') is-invalid @enderror" type="checkbox" name="confirmed" value="1" id="reconciliation_confirm" required>
                                    <label class="form-check-label small" for="reconciliation_confirm">I counted/verified these balances against cash and statements.</label>
                                    @error('confirmed')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="alert alert-info py-2 mt-3 mb-0 small">
                                    Verified total: <strong id="reconciliation_total">&#2547;0.00</strong><br>
                                    Ledger adjustment: <strong id="reconciliation_difference">&#8722;&#2547;{{ number_format($accounting['fund_balance'], 2) }}</strong>
                                </div>
                                <button class="btn btn-warning w-100 mt-3" type="submit" onclick="return confirm('Create a balance reconciliation adjustment?');">Reconcile Fund Balance</button>
                            </form>
                        @else
                            <div class="alert alert-secondary mb-0">Only an administrator can reconcile the balance.</div>
                        @endif

                        @if($accounting['latest_reconciliation'])
                            @php($lastReconciliation = $accounting['latest_reconciliation'])
                            <hr>
                            <div class="small">
                                <strong>Last reconciliation:</strong> {{ $lastReconciliation->created_at->format('d M Y, h:i A') }}<br>
                                Verified balance: &#2547;{{ number_format((float) $lastReconciliation->actual_balance, 2) }}<br>
                                Adjustment: {{ (float) $lastReconciliation->difference >= 0 ? '+' : '' }}&#2547;{{ number_format((float) $lastReconciliation->difference, 2) }}<br>
                                By: {{ optional($lastReconciliation->reconciledBy)->name ?? 'Unknown admin' }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            @if(
                $accounting['duplicate_sale_groups'] > 0 ||
                $accounting['unlinked_sale_transactions'] > 0 ||
                $accounting['purchases_missing_items'] > 0 ||
                $accounting['expense_fund_mismatches'] > 0 ||
                $accounting['supplier_payment_fund_mismatches'] > 0 ||
                $accounting['legacy_vendor_commission_inflow'] > 0
            )
                <div class="alert alert-warning mb-0 mt-3">
                    <strong>Historical data needs review:</strong>
                    {{ $accounting['duplicate_sale_groups'] }} duplicate sale-credit group(s),
                    {{ $accounting['unlinked_sale_transactions'] }} sale credit(s) totaling &#2547;{{ number_format($accounting['unlinked_sale_total'], 2) }} no longer linked to an order, and
                    {{ $accounting['purchases_missing_items'] }} purchase(s) without item rows,
                    {{ $accounting['expense_fund_mismatches'] }} expense/fund mismatch(es), and
                    {{ $accounting['supplier_payment_fund_mismatches'] }} supplier-payment/fund mismatch(es).
                    Legacy vendor commissions added &#2547;{{ number_format($accounting['legacy_vendor_commission_inflow'], 2) }} on top of full sale credits; new transactions no longer do this.
                    These records are disclosed here but not silently deleted because their original business evidence is unavailable.
                </div>
            @endif
        </div>
    </div>

    {{-- ======= FORM & EXPORT ROW ======= --}}
    <div class="row">

        {{-- Add Expense --}}
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm">
                <div class="card-header">
                    <strong>+ Add Expense</strong>
                </div>
                <div class="card-body">

                    <form action="{{ route('admin.expenses.store') }}" method="POST">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label">Title *</label>
                            <input type="text"
                                   name="title"
                                   class="form-control @error('title') is-invalid @enderror"
                                   value="{{ old('title') }}"
                                   required>
                            @error('title')
                            <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Amount (৳) *</label>
                            <input type="number"
                                   step="0.01"
                                   name="amount"
                                   class="form-control @error('amount') is-invalid @enderror"
                                   value="{{ old('amount') }}"
                                   required>
                            @error('amount')
                            <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Date *</label>
                            <input type="date"
                                   name="expense_date"
                                   class="form-control @error('expense_date') is-invalid @enderror"
                                   value="{{ old('expense_date', now()->format('Y-m-d')) }}"
                                   required>
                            @error('expense_date')
                            <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Category (optional)</label>
                            <input type="text"
                                   name="category"
                                   class="form-control"
                                   value="{{ old('category') }}">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Note (optional)</label>
                            <textarea name="note"
                                      class="form-control"
                                      rows="3">{{ old('note') }}</textarea>
                        </div>

                        <button type="submit" class="btn btn-danger">
                            Save Expense
                        </button>
                    </form>

                </div>
            </div>
        </div>

        {{-- Export Report --}}
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm">
                <div class="card-header">
                    <strong>📤 Export Report</strong>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.expenses.export') }}" method="GET" target="_blank">
                        <div class="mb-3">
                            <label class="form-label">From Date</label>
                            <input type="date" name="from_date" class="form-control"
                                   value="{{ request('from_date') }}">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">To Date</label>
                            <input type="date" name="to_date" class="form-control"
                                   value="{{ request('to_date') }}">
                        </div>

                        <button type="submit" class="btn btn-outline-primary w-100">
                            ⬇ Download CSV
                        </button>
                    </form>
                </div>
            </div>
        </div>

    </div>

    {{-- ======= HISTORY TABLE ======= --}}
    <div class="card shadow-sm mt-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <strong>🧾 Expense History</strong>
            <div>
                <a href="{{ route('admin.expenses.logs') }}" class="btn btn-sm btn-outline-info">
                    <i data-feather="file-text" class="me-1" style="width:14px;height:14px;"></i> View Logs / Reports
                </a>
            </div>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-bordered table-hover align-middle">
                <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Date</th>
                    <th>Title</th>
                    <th>Category</th>
                    <th class="text-end">Amount (৳)</th>
                    <th>Note</th>
                    @if($isAdmin)
                    <th>Actions</th>
                    @endif
                </tr>
                </thead>
                <tbody>
                @forelse($expenses as $exp)
                    <tr>
                        <td>{{ $loop->iteration + ($expenses->currentPage() - 1)*$expenses->perPage() }}</td>
                        <td>{{ \Carbon\Carbon::parse($exp->expense_date)->format('d M, Y') }}</td>
                        <td>
                            {{ $exp->title }}
                            @if($exp->updated_by)
                                <span class="badge bg-warning ms-1" title="This expense has been edited">
                                    <i class="fe-edit" style="width:12px;height:12px;"></i> Edited
                                </span>
                            @endif
                        </td>
                        <td>{{ $exp->category ?? '-' }}</td>
                        <td class="text-end">{{ number_format($exp->amount, 2) }}</td>
                        <td>{{ $exp->note }}</td>
                        @if($isAdmin)
                        <td>
                            <div class="d-flex gap-2">
                                <a href="{{ route('admin.expenses.edit', $exp->id) }}" class="btn btn-sm btn-outline-primary" title="Edit">
                                    <i class="fe-edit"></i>
                                </a>
                                <form method="POST" action="{{ route('admin.expenses.destroy', $exp->id) }}" class="d-inline delete-form">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger delete-confirm" title="Delete" onclick="return confirm('Are you sure you want to delete this expense?');">
                                        <i class="fe-trash-2"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $isAdmin ? 7 : 6 }}" class="text-center text-muted">
                            কোনো খরচের রেকর্ড পাওয়া যায়নি।
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>

            {{ $expenses->links() }}
        </div>
    </div>

</div>
@endsection

@section('script')
<script>
    (function () {
        const form = document.getElementById('fund_reconciliation_form');
        if (!form) return;

        const fields = form.querySelectorAll('.reconciliation-balance');
        const totalOutput = document.getElementById('reconciliation_total');
        const differenceOutput = document.getElementById('reconciliation_difference');
        const ledgerBalance = Number(form.dataset.ledgerBalance || 0);
        const money = new Intl.NumberFormat('en-BD', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

        function updateReconciliationPreview() {
            let total = 0;
            fields.forEach(function (field) {
                total += Math.max(0, Number(field.value) || 0);
            });

            const difference = total - ledgerBalance;
            totalOutput.textContent = '\u09F3' + money.format(total);
            differenceOutput.textContent = (difference >= 0 ? '+' : '-') + '\u09F3' + money.format(Math.abs(difference));
        }

        fields.forEach(function (field) {
            field.addEventListener('input', updateReconciliationPreview);
        });
        updateReconciliationPreview();
    })();
</script>
@endsection
