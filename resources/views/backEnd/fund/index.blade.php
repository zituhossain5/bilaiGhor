@extends('backEnd.layouts.master')
@section('title', 'Account & Fund')

@section('content')
<style>
    .fund-dashboard { color: var(--bilai-text, #3a210f); }
    .fund-toolbar, .fund-panel, .fund-kpi { background: var(--bilai-surface, #fffdf8); border: 1px solid var(--bilai-border, #ead8be); border-radius: 8px; }
    .fund-toolbar, .fund-panel { padding: 18px; }
    .fund-filter-grid { display: grid; grid-template-columns: 1.25fr repeat(4, minmax(120px, .7fr)) auto; gap: 10px; align-items: end; }
    .fund-kpi-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 14px; }
    .fund-kpi { min-height: 116px; padding: 16px; border-top: 4px solid var(--bilai-orange, #ef8611); }
    .fund-kpi--dark { border-top-color: var(--bilai-dark, #351600); }
    .fund-kpi--positive { border-top-color: #198754; }
    .fund-kpi--negative { border-top-color: #dc3545; }
    .fund-kpi__label { color: #806f61; font-size: 12px; font-weight: 600; }
    .fund-kpi__value { margin-top: 8px; color: var(--bilai-dark, #351600); font-size: 24px; font-weight: 700; line-height: 1.2; }
    .fund-kpi__hint { margin-top: 5px; color: #8b7b6e; font-size: 11px; }
    .fund-section-title { margin: 0 0 14px; color: var(--bilai-dark, #351600); font-size: 16px; font-weight: 700; }
    .fund-metric-list { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0 20px; margin: 0; }
    .fund-metric-list > div { display: flex; justify-content: space-between; gap: 16px; padding: 10px 0; border-bottom: 1px solid var(--bilai-border, #ead8be); }
    .fund-metric-list dt, .fund-metric-list dd { margin: 0; }
    .fund-metric-list dd { color: var(--bilai-dark, #351600); font-weight: 700; text-align: right; }
    .fund-table th { white-space: nowrap; }
    .fund-table td { vertical-align: middle; }
    .fund-source { text-transform: capitalize; }
    @media (max-width: 1199px) { .fund-filter-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); } .fund-kpi-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media (max-width: 575px) { .fund-toolbar, .fund-panel { padding: 14px; } .fund-filter-grid, .fund-kpi-grid, .fund-metric-list { grid-template-columns: 1fr; } .fund-kpi { min-height: 0; } .fund-kpi__value { font-size: 21px; } }
</style>

<div class="container-fluid fund-dashboard">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div><h3 class="mb-1">Account & Fund</h3><div class="text-muted">Investment, sales, cost, expenses and profit from one accounting view.</div></div>
        <a href="{{ route('admin.fund.logs') }}" class="btn btn-outline-secondary btn-sm">Audit Logs</a>
    </div>

    <form method="GET" action="{{ route('admin.fund.index') }}" class="fund-toolbar mb-3">
        <h5 class="fund-section-title">Period & Export Report</h5>
        <div class="fund-filter-grid">
            <div><label class="form-label">Period</label><select name="mode" id="fund-period-mode" class="form-select">
                <option value="current_month" @selected($period['mode'] === 'current_month')>Current month</option>
                <option value="previous_month" @selected($period['mode'] === 'previous_month')>Previous month</option>
                <option value="month" @selected($period['mode'] === 'month')>Select month</option>
                <option value="year" @selected($period['mode'] === 'year')>Select year</option>
                <option value="custom" @selected($period['mode'] === 'custom')>Custom range</option>
                <option value="all" @selected($period['mode'] === 'all')>Full history</option>
            </select></div>
            <div data-period-field="year"><label class="form-label">Year</label><input type="number" name="year" min="2000" max="2100" class="form-control" value="{{ request('year', now()->year) }}"></div>
            <div data-period-field="month"><label class="form-label">Month</label><select name="month" class="form-select">@foreach(range(1, 12) as $month)<option value="{{ $month }}" @selected((int) request('month', now()->month) === $month)>{{ \Carbon\Carbon::create(null, $month)->format('F') }}</option>@endforeach</select></div>
            <div data-period-field="from"><label class="form-label">From</label><input type="date" name="from_date" class="form-control" value="{{ request('from_date') }}"></div>
            <div data-period-field="to"><label class="form-label">To</label><input type="date" name="to_date" class="form-control" value="{{ request('to_date') }}"></div>
            <div class="d-flex gap-2 flex-wrap">
                <button class="btn btn-primary" type="submit">Apply</button>
                <button class="btn btn-outline-secondary" type="submit" formaction="{{ route('admin.fund.export') }}">Download CSV</button>
                <button class="btn btn-outline-secondary" type="submit" formaction="{{ route('admin.fund.export.pdf') }}">Download PDF</button>
            </div>
        </div>
    </form>

    <div class="d-flex justify-content-between align-items-center mb-2"><h4 class="mb-0">Financial Summary</h4><small class="text-muted">{{ $period['label'] }}</small></div>
    <div class="fund-kpi-grid mb-4">
        @php
            $cards = [
                ['Available fund balance', $balance, 'Cash ledger inflow minus outflow, not profit', 'fund-kpi--dark'],
                ['Total investment', $summary['total_investment'], 'Initial plus all additional investment', ''],
                ['Investment in period', $summary['period_investment'], 'Owner capital added in this filter', ''],
                ['Sales revenue', $summary['sales_revenue'], number_format($summary['sales_orders']).' completed and paid orders', ''],
                ['Cost of goods sold', $summary['cogs'], 'Historical purchase cost multiplied by quantity', 'fund-kpi--dark'],
                ['Gross profit', $summary['gross_profit'], 'Sales revenue minus COGS', 'fund-kpi--positive'],
                ['Expenses', $summary['expenses'], number_format($summary['expense_count']).' recorded expenses', 'fund-kpi--negative'],
                ['Withdrawals', $summary['withdrawals'], number_format($summary['withdrawal_count']).' withdrawals in period', 'fund-kpi--negative'],
                ['Net profit', $summary['net_profit'], 'Gross profit minus expenses', $summary['net_profit'] >= 0 ? 'fund-kpi--positive' : 'fund-kpi--negative'],
            ];
        @endphp
        @foreach($cards as [$label, $value, $hint, $variant])
            <div class="fund-kpi {{ $variant }}"><div class="fund-kpi__label">{{ $label }}</div><div class="fund-kpi__value">{{ number_format($value, 2) }} &#2547;</div><div class="fund-kpi__hint">{{ $hint }}</div></div>
        @endforeach
    </div>

    @if($summary['cost_fallback_lines'] || $summary['zero_cost_lines'])
        <div class="alert alert-warning">Cost review: {{ $summary['cost_fallback_lines'] }} sold line(s) use current product cost because historical cost is missing, and {{ $summary['zero_cost_lines'] }} line(s) have zero/missing cost.</div>
    @endif

    <div class="row g-3 mb-4">
        <div class="col-xl-4"><div class="fund-panel h-100"><h5 class="fund-section-title">Record Investment</h5>
            <form action="{{ route('admin.fund.add') }}" method="POST">@csrf
                <div class="mb-3"><label class="form-label">Investment type</label><select name="investment_type" class="form-select @error('investment_type') is-invalid @enderror" required>@unless($hasInitialInvestment)<option value="initial" @selected(old('investment_type') === 'initial')>Initial investment</option>@endunless<option value="additional" @selected(old('investment_type', $hasInitialInvestment ? 'additional' : '') === 'additional')>Additional investment</option></select>@error('investment_type')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="mb-3"><label class="form-label">Date</label><input type="date" name="transaction_date" value="{{ old('transaction_date', now()->toDateString()) }}" class="form-control @error('transaction_date') is-invalid @enderror" required>@error('transaction_date')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="mb-3"><label class="form-label">Amount</label><input type="number" name="amount" min="0.01" step="0.01" value="{{ old('amount') }}" class="form-control @error('amount') is-invalid @enderror" required>@error('amount')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="mb-3"><label class="form-label">Note</label><textarea name="note" rows="2" class="form-control">{{ old('note') }}</textarea></div><button class="btn btn-primary w-100">Save Investment</button>
            </form>
        </div></div>
        <div class="col-xl-4"><div class="fund-panel h-100"><h5 class="fund-section-title">Investment Totals</h5><dl class="fund-metric-list d-block">
            <div><dt>Initial investment</dt><dd>{{ number_format($summary['initial_investment'], 2) }} &#2547;</dd></div><div><dt>Additional investment</dt><dd>{{ number_format($summary['additional_investment'], 2) }} &#2547;</dd></div><div><dt>Total investment</dt><dd>{{ number_format($summary['total_investment'], 2) }} &#2547;</dd></div>
        </dl></div></div>
        <div class="col-xl-4"><div class="fund-panel h-100"><h5 class="fund-section-title">Record Withdrawal</h5><form action="{{ route('admin.fund.withdraw') }}" method="POST">@csrf
            <input type="hidden" name="idempotency_key" value="{{ $withdrawalToken }}">
            <div class="mb-3"><label class="form-label">Amount *</label><input type="number" name="amount" min="0.01" step="0.01" value="{{ old('amount') }}" class="form-control @error('amount') is-invalid @enderror" required>@error('amount')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="mb-3"><label class="form-label">Date *</label><input type="date" name="transaction_date" value="{{ old('transaction_date', now()->toDateString()) }}" class="form-control @error('transaction_date') is-invalid @enderror" required>@error('transaction_date')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="mb-3"><label class="form-label">Note / Reason</label><textarea name="note" rows="2" class="form-control">{{ old('note') }}</textarea></div><button class="btn btn-secondary w-100">Save Withdrawal</button>
        </form></div></div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-4"><div class="fund-panel h-100"><h5 class="fund-section-title">Sales Summary</h5><dl class="fund-metric-list d-block">
            <div><dt>Completed and paid orders</dt><dd>{{ number_format($summary['sales_orders']) }}</dd></div><div><dt>Sales revenue</dt><dd>{{ number_format($summary['sales_revenue'], 2) }} &#2547;</dd></div><div><dt>COGS</dt><dd>{{ number_format($summary['cogs'], 2) }} &#2547;</dd></div><div><dt>Gross profit</dt><dd>{{ number_format($summary['gross_profit'], 2) }} &#2547;</dd></div>
        </dl></div></div>
        <div class="col-lg-4"><div class="fund-panel h-100"><h5 class="fund-section-title">Expense Summary</h5><dl class="fund-metric-list d-block">
            <div><dt>Expense records</dt><dd>{{ number_format($summary['expense_count']) }}</dd></div><div><dt>Total expense</dt><dd>{{ number_format($summary['expenses'], 2) }} &#2547;</dd></div><div><dt>Source</dt><dd>Expenses module</dd></div>
        </dl></div></div>
        <div class="col-lg-4"><div class="fund-panel h-100"><h5 class="fund-section-title">Profit Summary</h5><dl class="fund-metric-list d-block">
            <div><dt>Gross profit</dt><dd>{{ number_format($summary['gross_profit'], 2) }} &#2547;</dd></div><div><dt>Expenses</dt><dd>- {{ number_format($summary['expenses'], 2) }} &#2547;</dd></div><div><dt>Net profit</dt><dd>{{ number_format($summary['net_profit'], 2) }} &#2547;</dd></div><div><dt>Formula</dt><dd>Revenue - COGS - Expenses</dd></div>
        </dl></div></div>
    </div>

    <div class="fund-panel mb-4"><h5 class="fund-section-title">Investment History</h5><div class="table-responsive"><table class="table fund-table"><thead><tr><th>Date</th><th>Type</th><th class="text-end">Amount</th><th>Note</th><th>Created by</th>@if($isAdmin)<th>Action</th>@endif</tr></thead><tbody>
        @forelse($investments as $investment)<tr><td>{{ ($investment->transaction_date ?: $investment->created_at)->format('d/m/Y') }}</td><td>{{ ucfirst($investment->investment_type ?: 'initial') }}</td><td class="text-end">{{ number_format($investment->amount, 2) }} &#2547;</td><td>{{ $investment->note ?: '-' }}</td><td>{{ optional($investment->creator)->name ?: 'System' }}</td>@if($isAdmin)<td><a class="btn btn-sm btn-outline-primary" href="{{ route('admin.fund.edit', $investment->id) }}">Edit</a></td>@endif</tr>
        @empty<tr><td colspan="6" class="text-center text-muted">No investment found for this period.</td></tr>@endforelse
    </tbody></table></div>{{ $investments->links('pagination::bootstrap-4') }}</div>

    <div class="fund-panel mb-4">
        <div class="d-flex flex-wrap justify-content-between gap-2 align-items-center mb-2">
            <div><h5 class="fund-section-title mb-0">Transaction History</h5><small class="text-muted">Filtered by {{ $period['label'] }}</small></div>
            <div><strong>Lifetime In:</strong> {{ number_format($total_in, 2) }} &#2547; &nbsp; <strong>Lifetime Out:</strong> {{ number_format($total_out, 2) }} &#2547;</div>
        </div>
        <div class="table-responsive"><table class="table fund-table">
            <thead><tr><th>#</th><th>Date & Time</th><th>Type</th><th>Source</th><th class="text-end">Amount</th><th>Note / Reference</th><th>Created By</th>@if($isAdmin)<th>Actions</th>@endif</tr></thead>
            <tbody>
            @forelse($transactions as $transaction)
                <tr>
                    <td>{{ ($transactions->firstItem() ?? 1) + $loop->index }}</td>
                    <td>{{ ($transaction->transaction_date ?: $transaction->created_at)->format('d/m/Y') }}<br><small class="text-muted">{{ $transaction->created_at?->format('h:i A') }}</small></td>
                    <td><span class="badge {{ $transaction->direction === 'in' ? 'bg-success' : 'bg-danger' }}">{{ strtoupper($transaction->direction) }}</span></td>
                    <td class="fund-source">{{ str_replace('_', ' ', $transaction->source) }}@if($transaction->investment_type)<br><small class="text-muted">{{ ucfirst($transaction->investment_type) }}</small>@endif</td>
                    <td class="text-end">{{ number_format($transaction->amount, 2) }} &#2547;</td>
                    <td>{{ $transaction->note ?: '-' }}</td>
                    <td>{{ optional($transaction->creator)->name ?: 'System' }}</td>
                    @if($isAdmin)<td>@if($transaction->isManuallyEditable())<a class="btn btn-sm btn-outline-primary" href="{{ route('admin.fund.edit', $transaction->id) }}">Edit</a><form method="POST" action="{{ route('admin.fund.destroy', $transaction->id) }}" class="d-inline" onsubmit="return confirm('Delete this transaction?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Delete</button></form>@else<span class="text-muted">System entry</span>@endif</td>@endif
                </tr>
            @empty<tr><td colspan="8" class="text-center text-muted">No transactions found for this period.</td></tr>@endforelse
            </tbody>
        </table></div>
        {{ $transactions->links('pagination::bootstrap-4') }}
    </div>
</div>
@endsection

@section('script')
<script>
    (function () {
        const mode = document.getElementById('fund-period-mode');
        const fields = { year: document.querySelector('[data-period-field="year"]'), month: document.querySelector('[data-period-field="month"]'), from: document.querySelector('[data-period-field="from"]'), to: document.querySelector('[data-period-field="to"]') };
        function syncFields() { Object.values(fields).forEach(field => field.style.display = 'none'); if (mode.value === 'year' || mode.value === 'month') fields.year.style.display = ''; if (mode.value === 'month') fields.month.style.display = ''; if (mode.value === 'custom') { fields.from.style.display = ''; fields.to.style.display = ''; } }
        mode.addEventListener('change', syncFields); syncFields();
    })();
</script>
@endsection
