@extends('backEnd.layouts.master')
@section('title', 'Edit Fund Transaction')

@section('content')
@php($isInvestment = in_array($transaction->source, ['investment', 'manual_add'], true))
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div><h4 class="mb-1">{{ $isInvestment ? 'Edit Investment' : 'Edit Fund Transaction' }}</h4><small class="text-muted">Changes are recorded in the fund audit log.</small></div>
        <a href="{{ route('admin.fund.index') }}" class="btn btn-sm btn-outline-secondary">Back to Account & Fund</a>
    </div>

    <div class="row"><div class="col-lg-6"><div class="card"><div class="card-body">
        <form action="{{ route('admin.fund.update', $transaction->id) }}" method="POST">@csrf
            @if($isInvestment)
                <div class="mb-3"><label class="form-label">Investment type</label><select name="investment_type" class="form-select @error('investment_type') is-invalid @enderror" required><option value="initial" @selected(old('investment_type', $transaction->investment_type ?: 'initial') === 'initial')>Initial investment</option><option value="additional" @selected(old('investment_type', $transaction->investment_type) === 'additional')>Additional investment</option></select>@error('investment_type')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="mb-3"><label class="form-label">Investment date</label><input type="date" name="transaction_date" value="{{ old('transaction_date', optional($transaction->transaction_date ?: $transaction->created_at)->format('Y-m-d')) }}" class="form-control @error('transaction_date') is-invalid @enderror" required>@error('transaction_date')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            @else
                <div class="mb-3"><label class="form-label">Direction</label><select name="direction" class="form-select @error('direction') is-invalid @enderror" required><option value="in" @selected(old('direction', $transaction->direction) === 'in')>IN (+)</option><option value="out" @selected(old('direction', $transaction->direction) === 'out')>OUT (-)</option></select>@error('direction')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            @endif

            <div class="mb-3"><label class="form-label">Source</label><input type="text" class="form-control" value="{{ $isInvestment ? 'Investment' : str_replace('_', ' ', ucfirst($transaction->source)) }}" readonly></div>
            <div class="mb-3"><label class="form-label">Amount</label><input type="number" step="0.01" min="0.01" name="amount" class="form-control @error('amount') is-invalid @enderror" value="{{ old('amount', $transaction->amount) }}" required>@error('amount')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="mb-3"><label class="form-label">Note</label><textarea name="note" class="form-control" rows="3">{{ old('note', $transaction->note) }}</textarea></div>
            <div class="d-flex gap-2"><button class="btn btn-primary">Save Changes</button><a href="{{ route('admin.fund.index') }}" class="btn btn-outline-secondary">Cancel</a></div>
        </form>
    </div></div></div></div>
</div>
@endsection
