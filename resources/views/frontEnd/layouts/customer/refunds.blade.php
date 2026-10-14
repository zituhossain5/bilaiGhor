@php
    $customer = Auth::guard('customer')->user();
@endphp

@extends('frontEnd.layouts.master')

@section('title', 'Refund Requests')

@push('css')
    @include('frontEnd.layouts.customer.partials.figma-account-styles')
    <style>
        .bilai-refunds-main { min-height: 556px; color: var(--account-text); }
        .bilai-refunds-header {
            display: flex; align-items: center; justify-content: space-between; gap: 20px;
            padding-bottom: 30px; border-bottom: 1px solid var(--account-border);
        }
        .bilai-refunds-heading {
            margin: 0; color: var(--account-muted); font-size: 18px; font-weight: 400; line-height: 27px;
        }
        .bilai-refunds-count {
            flex: 0 0 auto; padding: 5px 12px; color: var(--account-text);
            background: var(--account-cream); border: 1px solid var(--account-border);
            border-radius: 12px; font-size: 13px; font-weight: 500; line-height: 20px;
        }
        .bilai-refunds-content { padding-top: 25px; }
        .bilai-refunds-table-wrap {
            width: 100%; overflow-x: auto; border: 1px solid var(--account-border); border-radius: 16px;
        }
        .bilai-refunds-table { width: 100%; min-width: 790px; margin: 0; border-collapse: collapse; }
        .bilai-refunds-table th,
        .bilai-refunds-table td {
            padding: 16px 14px; border-bottom: 1px solid var(--account-border); text-align: left; vertical-align: middle;
        }
        .bilai-refunds-table th {
            color: var(--account-muted); background: var(--account-cream);
            font-size: 14px; font-weight: 600; line-height: 21px;
        }
        .bilai-refunds-table td { color: var(--account-text); font-size: 14px; line-height: 21px; }
        .bilai-refunds-table tbody tr:last-child td { border-bottom: 0; }
        .bilai-refunds-table tbody tr:hover td { background: rgba(251, 245, 230, .55); }
        .bilai-refunds-id,
        .bilai-refunds-order-link { color: #c4552a; font-weight: 600; text-decoration: none; }
        .bilai-refunds-order-link:hover { color: var(--account-primary); text-decoration: underline; }
        .bilai-refunds-caption { display: block; margin-top: 2px; color: var(--account-muted); font-size: 12px; line-height: 18px; }
        .bilai-refunds-amount { white-space: nowrap; font-weight: 600; }
        .bilai-refunds-status {
            display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px;
            border: 1px solid transparent; border-radius: 12px; font-size: 12px; font-weight: 600; line-height: 18px;
        }
        .bilai-refunds-status--pending { color: #8a4b00; background: #fcf0ba; border-color: #edd9a6; }
        .bilai-refunds-status--approved { color: #27654a; background: #e8f5ed; border-color: #b8ddc7; }
        .bilai-refunds-status--rejected { color: #9e3526; background: #fbf0eb; border-color: #e8b5a6; }
        .bilai-refunds-status--processed { color: #27654a; background: #e8f5ed; border-color: #b8ddc7; }
        .bilai-refunds-status--default { color: var(--account-muted); background: var(--account-neutral); border-color: var(--account-border-default); }
        .bilai-refunds-date { white-space: nowrap; }
        .bilai-refunds-actions { display: flex; align-items: center; justify-content: flex-end; gap: 8px; }
        .bilai-refunds-action {
            display: inline-flex; align-items: center; justify-content: center; width: 36px; height: 36px;
            padding: 0; color: var(--account-text); background: var(--account-cream);
            border: 1px solid var(--account-border); border-radius: 10px; cursor: pointer; text-decoration: none;
        }
        .bilai-refunds-action:hover { color: #fff; background: var(--account-secondary); text-decoration: none; }
        .bilai-refunds-action--cancel { color: #c4552a; background: #fbf0eb; border-color: #e8b5a6; }
        .bilai-refunds-action--cancel:hover { color: #fff; background: #c4552a; }
        .bilai-refunds-empty {
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            min-height: 350px; padding: 40px 20px; text-align: center;
        }
        .bilai-refunds-empty-icon {
            display: inline-flex; align-items: center; justify-content: center; width: 72px; height: 72px;
            margin-bottom: 20px; color: var(--account-muted); background: var(--account-neutral);
            border-radius: 50%; font-size: 28px;
        }
        .bilai-refunds-empty-title { margin: 0 0 8px; color: #222; font-size: 20px; font-weight: 600; line-height: 30px; }
        .bilai-refunds-empty-copy { margin: 0 0 24px; color: var(--account-muted); font-size: 16px; line-height: 24px; }
        .bilai-refunds-button {
            display: inline-flex; align-items: center; justify-content: center; gap: 9px; min-height: 48px;
            padding: 10px 20px; color: #222; background: var(--account-primary); border: 0;
            border-radius: 12px; font-size: 16px; font-weight: 600; line-height: 24px; text-decoration: none;
        }
        .bilai-refunds-button:hover { color: #222; filter: brightness(.96); text-decoration: none; }
        .bilai-refunds-pagination { display: flex; justify-content: center; padding-top: 24px; }

        @media (max-width: 575px) {
            .bilai-refunds-main { min-height: 0; }
            .bilai-refunds-header { align-items: flex-start; padding-bottom: 20px; }
            .bilai-refunds-empty { min-height: 280px; padding: 30px 0; }
        }
    </style>
@endpush

@section('content')
<div class="bilai-account-page">
    <div class="bilai-account-container">
        <nav class="bilai-account-breadcrumb" aria-label="Breadcrumb">
            <a href="{{ route('home') }}">Home</a>
            <span class="bilai-account-breadcrumb-separator" aria-hidden="true">&rsaquo;</span>
            <span>Profile</span>
            <span class="bilai-account-breadcrumb-separator" aria-hidden="true">&rsaquo;</span>
            <span class="bilai-account-breadcrumb-current">Refund Requests</span>
        </nav>

        <div class="bilai-account-layout">
            @include('frontEnd.layouts.customer.partials.figma-account-sidebar')

            <main class="bilai-account-main bilai-refunds-main">
                <header class="bilai-refunds-header">
                    <h1 class="bilai-refunds-heading">Refund Requests</h1>
                    @if($refunds->count() > 0)
                        <span class="bilai-refunds-count">{{ $refunds->total() }} {{ \Illuminate\Support\Str::plural('request', $refunds->total()) }}</span>
                    @endif
                </header>

                <div class="bilai-refunds-content">
                    @if($refunds->count() > 0)
                        <div class="bilai-refunds-table-wrap">
                            <table class="bilai-refunds-table">
                                <thead>
                                    <tr>
                                        <th>Refund ID</th>
                                        <th>Order</th>
                                        <th>Total Refund</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                        <th class="text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($refunds as $refund)
                                        @php
                                            $status = strtolower($refund->status ?? 'pending');
                                            $statusIcons = [
                                                'pending' => 'fa-clock',
                                                'approved' => 'fa-check',
                                                'rejected' => 'fa-times',
                                                'processed' => 'fa-check-circle',
                                            ];
                                        @endphp
                                        <tr>
                                            <td><span class="bilai-refunds-id">#{{ $refund->refund_id }}</span></td>
                                            <td>
                                                <a href="{{ route('customer.invoice', ['id' => $refund->order->id]) }}" class="bilai-refunds-order-link">
                                                    #{{ $refund->order->invoice_id ?? $refund->order->id }}
                                                </a>
                                                <span class="bilai-refunds-caption">Invoice ID</span>
                                            </td>
                                            <td class="bilai-refunds-amount">&#2547;{{ number_format($refund->amount + $refund->shipping_charge, 2) }}</td>
                                            <td>
                                                <span class="bilai-refunds-status bilai-refunds-status--{{ in_array($status, ['pending', 'approved', 'rejected', 'processed']) ? $status : 'default' }}">
                                                    <i class="fa {{ $statusIcons[$status] ?? 'fa-circle' }}" aria-hidden="true"></i>
                                                    {{ ucfirst($status) }}
                                                </span>
                                            </td>
                                            <td class="bilai-refunds-date">
                                                {{ $refund->created_at->format('d M, Y') }}
                                                <span class="bilai-refunds-caption">{{ $refund->created_at->format('h:i A') }}</span>
                                            </td>
                                            <td>
                                                <div class="bilai-refunds-actions">
                                                    <a href="{{ route('customer.refunds.show', $refund->id) }}" class="bilai-refunds-action" title="View refund details" aria-label="View refund details">
                                                        <i class="fa fa-eye" aria-hidden="true"></i>
                                                    </a>
                                                    @if($status === 'pending')
                                                        <form action="{{ route('customer.refunds.cancel', $refund->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to cancel this refund request?');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="bilai-refunds-action bilai-refunds-action--cancel" title="Cancel refund request" aria-label="Cancel refund request">
                                                                <i class="fa fa-times" aria-hidden="true"></i>
                                                            </button>
                                                        </form>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        @if($refunds->hasPages())
                            <div class="bilai-refunds-pagination">{{ $refunds->links() }}</div>
                        @endif
                    @else
                        <div class="bilai-refunds-empty">
                            <span class="bilai-refunds-empty-icon" aria-hidden="true"><i class="fa fa-file-lines"></i></span>
                            <h2 class="bilai-refunds-empty-title">No refund requests yet</h2>
                            <p class="bilai-refunds-empty-copy">You have not submitted a refund request for any order.</p>
                            <a href="{{ route('customer.orders') }}" class="bilai-refunds-button">
                                <i class="fa fa-list-alt" aria-hidden="true"></i>
                                View Orders
                            </a>
                        </div>
                    @endif
                </div>
            </main>
        </div>
    </div>
</div>
@endsection
