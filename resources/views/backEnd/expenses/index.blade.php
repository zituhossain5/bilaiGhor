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
            <small class="text-muted">Cash, stock cost, and retail value are separate measures. Inventory is not included in the fund balance.</small>
        </div>
        <div class="card-body">
            <div class="row g-3 mb-3">
                <div class="col-xl-3 col-md-6">
                    <div class="text-muted small">Cash / Fund Balance</div>
                    <div class="h4 mb-0">&#2547;{{ number_format($accounting['fund_balance'], 2) }}</div>
                    <small>Fund in &#8722; fund out</small>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="text-muted small">Physical Inventory at Cost</div>
                    <div class="h4 mb-0">&#2547;{{ number_format($accounting['inventory_on_hand_cost'], 2) }}</div>
                    <small>Available cost + reserved cost</small>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="text-muted small">Supplier Due</div>
                    <div class="h4 mb-0">&#2547;{{ number_format($accounting['supplier_due'], 2) }}</div>
                    <small>Outstanding purchase liability</small>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="text-muted small">Tracked Net Assets</div>
                    <div class="h4 mb-0">&#2547;{{ number_format($accounting['tracked_net_assets'], 2) }}</div>
                    <small>Cash + inventory cost &#8722; supplier due</small>
                </div>
            </div>

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
                    </tbody>
                </table>
            </div>

            @if($accounting['duplicate_sale_groups'] > 0 || $accounting['purchases_missing_items'] > 0)
                <div class="alert alert-warning mb-0 mt-3">
                    Historical reconciliation needs review:
                    {{ $accounting['duplicate_sale_groups'] }} order(s) have duplicate sale credits and
                    {{ $accounting['purchases_missing_items'] }} purchase(s) have no item rows.
                    Existing financial records were not changed automatically.
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
