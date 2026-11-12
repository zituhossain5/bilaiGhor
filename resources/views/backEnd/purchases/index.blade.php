@extends('backEnd.layouts.master')
@section('title','Purchases')

@php
    use Illuminate\Support\Facades\Auth;
@endphp

@section('css')
<link href="{{ asset('public/backEnd/assets/libs/select2/css/select2.min.css') }}" rel="stylesheet">
<style>
    /* --- Modern Card Style --- */
    .card {
        border: none;
        border-radius: 10px;
        box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
        transition: all 0.3s ease;
    }
    .card-header {
        background-color: #fff;
        border-bottom: 1px solid #e3e6f0;
        padding: 1rem 1.35rem;
        font-weight: 700;
        color: var(--bilai-admin-orange, #e8861a);
        border-radius: 10px 10px 0 0 !important;
    }
    
    /* --- Stats Widgets --- */
    .stats-card {
        display: flex;
        align-items: center;
        padding: 1.5rem;
    }
    .stats-icon {
        width: 50px;
        height: 50px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        margin-right: 1rem;
        flex-shrink: 0;
    }
    .bg-soft-primary { background-color: #fff3df; color: var(--bilai-admin-orange-dark, #c96f00); }
    .bg-soft-success { background-color: rgba(28, 200, 138, 0.1); color: #1cc88a; }
    .bg-soft-info    { background-color: #f4eadc; color: var(--bilai-admin-brown-soft, #3c2a1e); }
    .bg-soft-danger  { background-color: rgba(231, 74, 59, 0.1); color: #e74a3b; }

    .stats-label { font-size: 0.8rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 0.25rem; }
    .stats-value { font-size: 1.5rem; font-weight: 700; color: #5a5c69; margin-bottom: 0; }
    .stats-sub   { font-size: 0.75rem; color: #858796; }

    /* --- Form Elements --- */
    .form-label { font-weight: 600; font-size: 0.85rem; color: #5a5c69; margin-bottom: 0.3rem; }
    .form-control, .form-select {
        border-radius: 6px;
        border: 1px solid #d1d3e2;
        padding: 0.5rem 0.75rem;
    }
    .form-control:focus { border-color: #bac8f3; box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25); }

    /* --- Table Styles --- */
    .table thead th {
        background-color: #f8f9fc;
        color: var(--bilai-admin-orange, #e8861a);
        font-size: 0.85rem;
        font-weight: 700;
        text-transform: uppercase;
        border-bottom: 2px solid #e3e6f0;
        padding: 1rem 0.75rem;
    }
    .table tbody td {
        font-size: 0.9rem;
        vertical-align: middle;
        padding: 0.75rem;
        color: #5a5c69;
    }
    .table-hover tbody tr:hover { background-color: #f8f9fc; }

    /* --- Action Buttons --- */
    .btn-action {
        width: 32px; height: 32px;
        display: inline-flex; align-items: center; justify-content: center;
        border-radius: 6px; transition: all 0.2s;
    }
    .pay-input { width: 80px; font-size: 0.8rem; border-radius: 4px; border: 1px solid #d1d3e2; padding: 4px; }
    .pay-btn { padding: 4px 10px; font-size: 0.8rem; border-radius: 4px; font-weight: 600; }
</style>
@endsection

@section('content')
<div class="container-fluid mb-5">

    <div class="d-sm-flex align-items-center justify-content-between mb-4 mt-2">
        <h1 class="h3 mb-0 text-gray-800 fw-bold">🛒 Purchase Management</h1>
        <a href="{{ route('purchases.logs') }}" class="d-none d-sm-inline-block btn btn-sm btn-info shadow-sm">
            <i class="fe-file-text me-1"></i> View Reports / Logs
        </a>
    </div>

    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card admin-stat-card admin-stat-card--success h-100 py-2 border-start border-4 border-success">
                <div class="card-body stats-card">
                    <div class="stats-icon bg-soft-success"><i class="fe-calendar"></i></div>
                    <div>
                        <div class="stats-label text-success">This Year ({{ $currentYear }})</div>
                        <div class="stats-value">{{ number_format($yearlyTotal, 2) }} ৳</div>
                        <div class="stats-sub">Total Purchase</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card admin-stat-card admin-stat-card--info h-100 py-2 border-start border-4 border-info">
                <div class="card-body stats-card">
                    <div class="stats-icon bg-soft-info"><i class="fe-bar-chart-2"></i></div>
                    <div>
                        <div class="stats-label text-info">
                            {{ \Carbon\Carbon::createFromDate(now()->year, $currentMonth, 1)->format('F') }}
                        </div>
                        <div class="stats-value">{{ number_format($monthlyTotal, 2) }} ৳</div>
                        <div class="stats-sub">Monthly Purchase</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card admin-stat-card admin-stat-card--primary h-100 py-2 border-start border-4 border-primary">
                <div class="card-body stats-card">
                    <div class="stats-icon bg-soft-primary"><i class="fe-shopping-bag"></i></div>
                    <div>
                        <div class="stats-label text-primary">Today ({{ now()->format('d M') }})</div>
                        <div class="stats-value">{{ number_format($todayTotal, 2) }} ৳</div>
                        <div class="stats-sub">Daily Purchase</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card admin-stat-card admin-stat-card--danger h-100 py-2 border-start border-4 border-danger">
                <div class="card-body stats-card">
                    <div class="stats-icon bg-soft-danger"><i class="fe-alert-circle"></i></div>
                    <div>
                        <div class="stats-label text-danger">Supplier Due</div>
                        <div class="stats-value">{{ number_format($totalDue, 2) }} ৳</div>
                        <div class="stats-sub">Total Liability</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8 mb-4">
            <div class="card mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary"><i class="fe-plus-circle me-1"></i> New Purchase Entry</h6>
                </div>
                <div class="card-body">
                    <form action="{{ route('purchases.store') }}" method="POST">
                        @csrf
                        @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Supplier *</label>
                                <select name="supplier_id" class="form-control form-select" required>
                                    <option value="">-- Select Supplier --</option>
                                    @foreach($suppliers as $s)
                                        <option value="{{ $s->id }}">{{ $s->name }} ({{ $s->phone }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Invoice No *</label>
                                <input type="text" name="invoice_no" id="purchase-invoice" class="form-control" value="{{ old('invoice_no', 'PUR-'.time()) }}" required readonly>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Date *</label>
                                <input type="date" name="purchase_date" class="form-control" value="{{ old('purchase_date', now()->format('Y-m-d')) }}" required>
                            </div>
                        </div>

                        @if(Auth::guard('admin')->id() == 1 || Auth::guard('admin')->user()?->getRoleNames()->map(fn ($role) => strtolower($role))->contains('admin'))
                        <div class="mb-3">
                            <label class="form-label" for="purchase-mode">Inventory Effect</label>
                            <select id="purchase-mode" name="purchase_mode" class="form-control form-select">
                                <option value="normal" @selected(old('purchase_mode') !== 'historical')>Normal Purchase</option>
                                <option value="historical" @selected(old('purchase_mode') === 'historical')>Historical / Backfill Purchase</option>
                            </select>
                        </div>
                        <div id="historical-purchase-options" class="alert alert-warning" hidden>
                            <strong>Historical Purchase</strong><p class="mb-2">Use this only to record a purchase that physically occurred in the past but was not entered into the Purchase module at that time. This mode records the real historical quantity and cost but DOES NOT increase current inventory. Current stock will not change.</p>
                            <label class="form-label" for="accounting-treatment">Accounting Treatment *</label>
                            <select id="accounting-treatment" name="accounting_treatment" class="form-control form-select mb-2">
                                <option value="">-- Choose treatment --</option>
                                <option value="reclassify_expense" @selected(old('accounting_treatment') === 'reclassify_expense')>Reclassify Existing Expense (no new fund movement)</option>
                                <option value="already_recorded" @selected(old('accounting_treatment') === 'already_recorded')>Already recorded elsewhere (no new fund movement)</option>
                                <option value="new_payment" @selected(old('accounting_treatment') === 'new_payment')>Not previously recorded (new fund payment)</option>
                            </select>
                            <div id="historical-expense-field" hidden>
                                <label class="form-label" for="historical-expense">Existing Expense *</label>
                                <select id="historical-expense" name="expense_id" class="form-control form-select mb-2">
                                    <option value="">-- Select expense --</option>
                                    @foreach($reclassifiableExpenses as $expense)
                                        <option value="{{ $expense->id }}" @selected(old('expense_id') == $expense->id)>{{ $expense->expense_date }} | {{ $expense->title }} | ৳{{ number_format($expense->amount, 2) }} | {{ $expense->category }} | {{ $expense->note }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div id="historical-reference-field" hidden>
                                <label class="form-label" for="historical-reference">Existing payment reference *</label>
                                <input id="historical-reference" name="accounting_reference" class="form-control" value="{{ old('accounting_reference') }}" maxlength="255">
                            </div>
                        </div>
                        @else
                            <input type="hidden" name="purchase_mode" value="normal">
                        @endif

                        <hr class="sidebar-divider my-3">
                        <h6 class="text-xs font-weight-bold text-uppercase text-gray-500 mb-3">Product Details</h6>

                        <div id="purchase-lines">
                            @foreach(old('lines', [['product_id' => '', 'qty' => 1, 'unit_cost' => '']]) as $index => $line)
                            <div class="row purchase-line align-items-end">
                                <div class="col-md-5 mb-2">
                                    <label class="form-label">Product *</label>
                                    <select name="lines[{{ $index }}][product_id]" class="form-control form-select purchase-product" required>
                                        <option value="">-- Select Product --</option>
                                        @foreach($products as $p)
                                            <option value="{{ $p->id }}" @selected(($line['product_id'] ?? '') == $p->id)>{{ $p->name }} (Stock: {{ $p->stock }})</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-2 mb-2"><label class="form-label">Qty *</label><input type="number" name="lines[{{ $index }}][qty]" class="form-control purchase-qty" min="1" value="{{ $line['qty'] ?? 1 }}" required></div>
                                <div class="col-md-2 mb-2"><label class="form-label">Unit Cost *</label><input type="number" name="lines[{{ $index }}][unit_cost]" class="form-control purchase-cost" min="0" step="0.01" value="{{ $line['unit_cost'] ?? '' }}" required></div>
                                <div class="col-md-2 mb-2"><label class="form-label">Line Total</label><output class="form-control purchase-line-total">0.00</output></div>
                                <div class="col-md-1 mb-2"><button type="button" class="btn btn-outline-danger purchase-remove-line" aria-label="Remove product row">×</button></div>
                            </div>
                            @endforeach
                        </div>
                        <button type="button" id="purchase-add-line" class="btn btn-outline-primary btn-sm">+ Add Product Row</button>
                        <div class="mt-2 fw-bold">Quantity: <span id="purchase-total-qty">0</span> | Subtotal: ৳<span id="purchase-subtotal">0.00</span> | Total: ৳<span id="purchase-grand-total">0.00</span></div>

                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Discount</label>
                                <input type="number" step="0.01" name="discount" class="form-control" value="0">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Shipping Cost</label>
                                <input type="number" step="0.01" name="shipping_cost" class="form-control" value="0">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label text-success fw-bold">Paid Amount</label>
                                <input type="number" step="0.01" name="paid_amount" class="form-control border-success" value="0">
                                <small id="purchase-paid-help" class="text-muted" style="font-size:10px;">Deducted from fund</small>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Note</label>
                                <input type="text" name="note" class="form-control" placeholder="Optional">
                            </div>
                        </div>

                        <div class="mt-3 text-end">
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="fe-save me-1"></i> Save Purchase
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4 mb-4">
            <div class="card h-100">
                <div class="card-header py-3 bg-white">
                    <h6 class="m-0 font-weight-bold text-dark"><i class="fe-download me-1"></i> Export Report</h6>
                </div>
                <div class="card-body">
                    <form action="{{ route('purchases.export') }}" method="GET" target="_blank">
                        @if(request('supplier_id'))<input type="hidden" name="supplier_id" value="{{ request('supplier_id') }}">@endif
                        <div class="mb-3">
                            <label class="form-label">Filter by Month/Year</label>
                            <div class="input-group">
                                <input type="number" name="month" class="form-control" placeholder="Month (1-12)" value="{{ request('month') }}">
                                <input type="number" name="year" class="form-control" placeholder="Year" value="{{ request('year') }}">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Date Range</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0">From</span>
                                <input type="date" name="from_date" class="form-control" value="{{ request('from_date') }}">
                            </div>
                            <div class="input-group mt-2">
                                <span class="input-group-text bg-light border-end-0" style="width: 58px;">To</span>
                                <input type="date" name="to_date" class="form-control" value="{{ request('to_date') }}">
                            </div>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-outline-dark">
                                <i class="fe-download-cloud me-2"></i> Download CSV
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary"><i class="fe-list me-1"></i> Recent Purchase History
                @if(request('supplier_id')) — {{ $suppliers->firstWhere('id', request('supplier_id'))?->name ?? 'Supplier' }} <a class="btn btn-sm btn-outline-secondary" href="{{ route('purchases.index') }}">Clear filter</a>@endif
            </h6>
        </div>
        <div class="card-body p-0">
            <div id="purchase-table-wrapper" class="table-responsive">
                <table class="table table-bordered table-hover mb-0" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th class="text-center" width="5%">#</th>
                            <th width="12%">Date</th>
                            <th width="15%">Invoice</th>
                            <th width="15%">Supplier</th>
                            <th class="text-end" width="10%">Total</th>
                            <th class="text-end" width="10%">Paid</th>
                            <th class="text-end" width="10%">Due</th>
                            <th class="text-center" width="23%">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($purchases as $p)
                        @php
                            $user = Auth::guard('admin')->user();
                            $isAdmin = false;
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
                        <tr>
                            <td class="text-center">{{ $loop->iteration + ($purchases->currentPage()-1)*$purchases->perPage() }}</td>
                            <td>
                                {{ \Carbon\Carbon::parse($p->purchase_date)->format('d M, Y') }}
                            </td>
                            <td>
                                <span class="fw-bold text-dark">{{ $p->invoice_no }}</span>
                                @if($p->purchase_mode === 'historical')<span class="badge bg-warning text-dark">Historical</span>@endif
                                @if($p->updated_by)
                                    <i class="fe-edit-2 text-warning ms-1" title="Edited" style="font-size: 10px;"></i>
                                @endif
                            </td>
                            <td>
                                @if($p->supplier)
                                    <div class="fw-bold text-secondary">{{ $p->supplier->name }}</div>
                                    <small class="text-muted">{{ $p->supplier->phone }}</small>
                                @else
                                    <span class="text-danger">Deleted</span>
                                @endif
                            </td>
                            <td class="text-end fw-bold">{{ number_format($p->grand_total,2) }}</td>
                            <td class="text-end text-success">{{ number_format($p->paid_amount,2) }}</td>
                            <td class="text-end text-danger">{{ number_format($p->due_amount,2) }}</td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center align-items-center gap-1">
                                    <a href="{{ route('purchases.invoice',$p->id) }}" class="btn btn-action btn-outline-secondary" target="_blank" title="Invoice">
                                        <i class="fe-file-text"></i>
                                    </a>

                                    @if($p->due_amount > 0)
                                        <form action="{{ route('purchases.pay_due',$p->id) }}" method="POST" class="d-flex align-items-center bg-light rounded p-1 border">
                                            @csrf
                                            <input type="number" step="0.01" name="amount" class="pay-input me-1" placeholder="Pay Due" required>
                                            <input type="hidden" name="payment_date" value="{{ now()->format('Y-m-d') }}">
                                            <button class="btn btn-success pay-btn text-white" title="Pay Now">
                                                <i class="fe-check"></i>
                                            </button>
                                        </form>
                                    @else
                                        <span class="badge bg-success bg-soft-success text-success border border-success px-2">Paid</span>
                                    @endif

                                    @if($isAdmin)
                                        <a href="{{ route('purchases.edit', $p->id) }}" class="btn btn-action btn-outline-primary ms-1" title="Edit">
                                            <i class="fe-edit"></i>
                                        </a>
                                        @if($p->purchase_mode !== 'historical')
                                        <form method="POST" action="{{ route('purchases.destroy', $p->id) }}" class="d-inline delete-form">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-action btn-outline-danger delete-confirm ms-1" title="Delete" onclick="return confirm('Confirm delete? This affects stock & fund.');">
                                                <i class="fe-trash-2"></i>
                                            </button>
                                        </form>
                                        @endif
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5">
                                <img src="https://cdn-icons-png.flaticon.com/512/4076/4076432.png" width="60" class="mb-3 opacity-50">
                                <p class="text-muted mb-0">No purchase records found.</p>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
                
                <div id="purchase-pagination" class="p-3 d-flex justify-content-end">
                    {{ $purchases->links('pagination::bootstrap-4') }}
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@section('script')
<script src="{{ asset('public/backEnd/assets/libs/select2/js/select2.min.js') }}"></script>
<script>
    (() => {
        const lines = document.getElementById('purchase-lines');
        const lineTemplate = lines.querySelector('.purchase-line').cloneNode(true);
        if (window.jQuery?.fn?.select2) {
            jQuery(lines).find('.purchase-product').select2({ width: '100%', placeholder: 'Search product' });
        }
        const mode = document.getElementById('purchase-mode');
        const treatment = document.getElementById('accounting-treatment');
        const options = document.getElementById('historical-purchase-options');
        const invoice = document.getElementById('purchase-invoice');
        const originalInvoice = invoice.value;
        let nextIndex = Math.max(...Array.from(lines.querySelectorAll('.purchase-product')).map(select =>
            Number(select.name.match(/lines\[(\d+)\]/)?.[1] ?? -1))) + 1;

        function refresh() {
            const historical = mode?.value === 'historical';
            if (options) options.hidden = !historical;
            if (invoice) {
                invoice.readOnly = !historical;
                if (!historical) invoice.value = originalInvoice;
            }
            if (treatment) treatment.required = historical;
            const expenseField = document.getElementById('historical-expense-field');
            const referenceField = document.getElementById('historical-reference-field');
            if (expenseField) expenseField.hidden = !historical || treatment.value !== 'reclassify_expense';
            if (referenceField) referenceField.hidden = !historical || treatment.value !== 'already_recorded';
            const expense = document.getElementById('historical-expense');
            const reference = document.getElementById('historical-reference');
            if (expense) expense.required = historical && treatment.value === 'reclassify_expense';
            if (reference) reference.required = historical && treatment.value === 'already_recorded';
            document.getElementById('purchase-paid-help').textContent = historical
                ? 'Reclassification or existing payment does not deduct the fund again.' : 'Deducted from fund';

            let qty = 0, subtotal = 0;
            lines.querySelectorAll('.purchase-line').forEach(row => {
                const count = Number(row.querySelector('.purchase-qty').value) || 0;
                const cost = Number(row.querySelector('.purchase-cost').value) || 0;
                qty += count;
                subtotal += count * cost;
                row.querySelector('.purchase-line-total').textContent = (count * cost).toFixed(2);
            });
            const discount = Number(document.querySelector('[name="discount"]').value) || 0;
            const shipping = Number(document.querySelector('[name="shipping_cost"]').value) || 0;
            document.getElementById('purchase-total-qty').textContent = qty;
            document.getElementById('purchase-subtotal').textContent = subtotal.toFixed(2);
            document.getElementById('purchase-grand-total').textContent = (subtotal - discount + shipping).toFixed(2);
        }

        document.getElementById('purchase-add-line').addEventListener('click', () => {
            const row = lineTemplate.cloneNode(true);
            row.querySelectorAll('[name]').forEach(input => {
                input.name = input.name.replace(/lines\[\d+\]/, `lines[${nextIndex}]`);
                if (input.classList.contains('purchase-product')) input.value = '';
                if (input.classList.contains('purchase-qty')) input.value = '1';
                if (input.classList.contains('purchase-cost')) input.value = '';
            });
            lines.appendChild(row);
            if (window.jQuery?.fn?.select2) jQuery(row).find('.purchase-product').select2({ width: '100%', placeholder: 'Search product' });
            nextIndex++;
            refresh();
        });
        lines.addEventListener('click', event => {
            if (!event.target.closest('.purchase-remove-line')) return;
            if (lines.querySelectorAll('.purchase-line').length > 1) {
                const row = event.target.closest('.purchase-line');
                if (window.jQuery?.fn?.select2) jQuery(row).find('.purchase-product').select2('destroy');
                row.remove();
            }
            refresh();
        });
        document.querySelector('form[action="{{ route('purchases.store') }}"]').addEventListener('input', refresh);
        document.querySelector('form[action="{{ route('purchases.store') }}"]').addEventListener('change', refresh);
        refresh();
    })();
    // AJAX Pagination Script
    $(document).on('click', '#purchase-pagination a', function(e){
        e.preventDefault();
        let url = $(this).attr('href');
        // Add loading state opacity
        $('#purchase-table-wrapper').css('opacity', '0.5');
        
        $.get(url, function(response){
            let html = $(response).find('#purchase-table-wrapper').html();
            $('#purchase-table-wrapper').html(html);
            $('#purchase-table-wrapper').css('opacity', '1');
        });
    });
</script>
@endsection
