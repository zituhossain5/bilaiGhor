@php
    $statusType = $type ?? 'order';
    $isPayment = $statusType === 'payment';
    $paymentState = $paymentState ?? ($isPayment ? \App\Services\OrderPaymentService::state($order) : null);
    $currentValue = $isPayment ? $paymentState['status'] : (string) $order->order_status;
    $currentLabel = $isPayment
        ? ucfirst($currentValue)
        : (optional($order->status)->name ?: (string) $order->order_status);
    $stateKey = \Illuminate\Support\Str::slug($currentLabel);
    $updateUrl = $isPayment
        ? route('admin.order.updatePaymentStatus')
        : route('admin.orders.inline-status', $order);
@endphp

<select
    class="form-select form-select-sm admin-inline-status-select"
    aria-label="Update {{ $isPayment ? 'payment' : 'order' }} status for order {{ $order->invoice_id }}"
    data-order-id="{{ $order->id }}"
    data-field="{{ $isPayment ? 'payment_status' : 'order_status' }}"
    data-update-url="{{ $updateUrl }}"
    data-previous-value="{{ $currentValue }}"
    data-state="{{ $stateKey }}">
    @if($isPayment)
        @if(!in_array($currentValue, \App\Services\OrderPaymentService::adminStatuses(), true))
            <option value="{{ $currentValue }}" selected disabled>{{ $currentLabel }}</option>
        @endif
        @foreach(\App\Services\OrderPaymentService::adminStatuses() as $paymentStatus)
            <option value="{{ $paymentStatus }}" @selected($currentValue === $paymentStatus)>{{ ucfirst($paymentStatus) }}</option>
        @endforeach
    @else
        @if(!$statuses->contains('id', (int) $currentValue))
            <option value="{{ $currentValue }}" selected disabled>{{ $currentLabel }}</option>
        @endif
        @foreach($statuses as $statusOption)
            <option value="{{ $statusOption->id }}" @selected((int) $currentValue === (int) $statusOption->id)>{{ $statusOption->name }}</option>
        @endforeach
    @endif
</select>
