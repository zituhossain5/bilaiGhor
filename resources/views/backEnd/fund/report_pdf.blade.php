<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Account & Fund Report</title>
    <style>
        @page { margin: 24px; }
        body { color: #351600; font-family: DejaVu Sans, sans-serif; font-size: 10px; }
        h1 { margin: 0 0 4px; font-size: 20px; }
        .period { margin-bottom: 16px; color: #7c6654; }
        .summary { width: 100%; margin-bottom: 18px; border-collapse: collapse; }
        .summary td { width: 25%; padding: 9px; border: 1px solid #ead8be; vertical-align: top; }
        .label { color: #806f61; font-size: 9px; }
        .value { margin-top: 4px; font-size: 14px; font-weight: bold; }
        table.history { width: 100%; border-collapse: collapse; }
        .history th { background: #ef8611; color: #fff; text-align: left; }
        .history th, .history td { padding: 6px; border: 1px solid #ead8be; }
        .number { text-align: right; }
        .in { color: #198754; font-weight: bold; }
        .out { color: #dc3545; font-weight: bold; }
    </style>
</head>
<body>
    <h1>Bilai Ghor Account & Fund Report</h1>
    <div class="period">Period: {{ $period['label'] }} | Generated: {{ now()->format('d/m/Y h:i A') }}</div>

    <table class="summary">
        <tr>
            <td><div class="label">Available Fund Balance</div><div class="value">{{ number_format($fundTotals['balance'], 2) }} BDT</div></td>
            <td><div class="label">Investment Added</div><div class="value">{{ number_format($summary['period_investment'], 2) }} BDT</div></td>
            <td><div class="label">Sales Revenue</div><div class="value">{{ number_format($summary['sales_revenue'], 2) }} BDT</div></td>
            <td><div class="label">COGS</div><div class="value">{{ number_format($summary['cogs'], 2) }} BDT</div></td>
        </tr>
        <tr>
            <td><div class="label">Gross Profit</div><div class="value">{{ number_format($summary['gross_profit'], 2) }} BDT</div></td>
            <td><div class="label">Expenses</div><div class="value">{{ number_format($summary['expenses'], 2) }} BDT</div></td>
            <td><div class="label">Net Profit</div><div class="value">{{ number_format($summary['net_profit'], 2) }} BDT</div></td>
            <td><div class="label">Withdrawals</div><div class="value">{{ number_format($summary['withdrawals'], 2) }} BDT</div></td>
        </tr>
    </table>

    <h2>Transaction History</h2>
    <table class="history">
        <thead><tr><th>#</th><th>Date & Time</th><th>Type</th><th>Source</th><th class="number">Amount</th><th>Note / Reference</th><th>Created By</th></tr></thead>
        <tbody>
        @forelse($transactions as $transaction)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ ($transaction->transaction_date ?: $transaction->created_at)->format('d/m/Y') }} {{ $transaction->created_at?->format('h:i A') }}</td>
                <td class="{{ $transaction->direction }}">{{ strtoupper($transaction->direction) }}</td>
                <td>{{ str_replace('_', ' ', ucfirst($transaction->source)) }}</td>
                <td class="number">{{ number_format($transaction->amount, 2) }} BDT</td>
                <td>{{ $transaction->note ?: '-' }}</td>
                <td>{{ optional($transaction->creator)->name ?: 'System' }}</td>
            </tr>
        @empty
            <tr><td colspan="7">No transactions found for this period.</td></tr>
        @endforelse
        </tbody>
    </table>
</body>
</html>
