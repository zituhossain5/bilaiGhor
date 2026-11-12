@extends('backEnd.layouts.master')
@section('title', 'Edit Historical Purchase')
@section('content')
<div class="container-fluid mb-5">
    <h3>Edit Historical Purchase {{ $purchase->invoice_no }}</h3>
    <div class="alert alert-warning">This purchase never changed current stock. Editing historical quantities and costs will not change stock, current product cost, or fund payments. Product mapping and accounting treatment are locked after creation.</div>
    @if($errors->any())<div class="alert alert-danger"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <form method="POST" action="{{ route('purchases.update', $purchase->id) }}" class="card p-4">
        @csrf
        <input type="hidden" name="purchase_mode" value="historical">
        <div class="row mb-3">
            <div class="col-md-4"><label>Supplier</label><input class="form-control" value="{{ $purchase->supplier?->name }}" readonly></div>
            <div class="col-md-4"><label>Invoice *</label><input class="form-control" name="invoice_no" value="{{ old('invoice_no', $purchase->invoice_no) }}" required></div>
            <div class="col-md-4"><label>Historical date *</label><input type="date" class="form-control" name="purchase_date" value="{{ old('purchase_date', \Carbon\Carbon::parse($purchase->purchase_date)->format('Y-m-d')) }}" required></div>
        </div>
        <p>Accounting treatment: <strong>{{ str_replace('_', ' ', $purchase->accounting_treatment) }}</strong>
            @if($purchase->reclassified_expense_id) — Expense #{{ $purchase->reclassified_expense_id }} @endif</p>
        <div class="table-responsive"><table class="table"><thead><tr><th>Product</th><th>Quantity</th><th>Unit cost</th></tr></thead><tbody>
            @foreach($purchase->items as $item)
                <tr><td>{{ $item->product?->name }}<input type="hidden" name="lines[{{ $loop->index }}][item_id]" value="{{ $item->id }}"></td>
                    <td><input type="number" min="1" class="form-control" name="lines[{{ $loop->index }}][qty]" value="{{ old('lines.'.$loop->index.'.qty', $item->qty) }}" required></td>
                    <td><input type="number" min="0" step="0.01" class="form-control" name="lines[{{ $loop->index }}][unit_cost]" value="{{ old('lines.'.$loop->index.'.unit_cost', $item->unit_cost) }}" required></td></tr>
            @endforeach
        </tbody></table></div>
        <p>Discount: ৳{{ number_format($purchase->discount, 2) }} | Shipping: ৳{{ number_format($purchase->shipping_cost, 2) }} | Paid: ৳{{ number_format($purchase->paid_amount, 2) }}</p>
        <label>Note</label><textarea name="note" class="form-control mb-3">{{ old('note', $purchase->note) }}</textarea>
        <div><button class="btn btn-primary">Save historical details</button> <a href="{{ route('purchases.invoice', $purchase->id) }}" class="btn btn-outline-secondary">Cancel</a></div>
    </form>
</div>
@endsection
