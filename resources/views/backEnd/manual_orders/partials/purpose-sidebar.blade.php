@php
    $purpose = $order->order_purpose ?: \App\Support\OrderPurpose::SALE;
    $nonSale = \App\Support\OrderPurpose::isNonSale($purpose);
    $cost = \App\Services\AccountingSummaryService::orderProductCost($order);
    $parcel = $order->courierParcel;
    $canClassify = \App\Support\OrderPurpose::canClassify(auth('admin')->user());
    $history = $canClassify ? $order->purposeChanges()->with('changer:id,name')->latest()->get() : collect();
@endphp
<section class="mo-purpose-card mb-3" aria-labelledby="mo-purpose-title">
    <h5 id="mo-purpose-title" class="mb-3">Order Purpose &amp; Accounting</h5>
    <div class="mo-summary-row"><span>Purpose</span><strong><span class="badge {{ $nonSale ? 'bg-warning text-dark' : 'bg-light text-dark' }}">{{ \App\Support\OrderPurpose::label($purpose) }}</span></strong></div>
    @if($nonSale)
        <div class="mo-summary-row"><span>Customer Paid</span><strong>৳{{ number_format((float) ($paymentState['paid'] ?? $order->paid_amount ?? 0), 2) }}</strong></div>
        <div class="mo-summary-row"><span>Customer Revenue</span><strong>৳{{ number_format((float) $order->amount, 2) }}</strong></div>
        <div class="mo-summary-row"><span>Product Cost</span><strong>৳{{ number_format($cost['total'], 2) }}</strong></div>
        <div class="mo-summary-row"><span>Inventory</span><strong>{{ (int) $order->order_status === \App\Services\InventoryService::COMPLETE_STATUS ? 'Handled at fulfillment' : 'Follows order status' }}</strong></div>
        <div class="mo-summary-row"><span>Classification Fund Effect</span><strong>৳0.00</strong></div>
        <div class="mo-summary-row"><span>Profit Effect</span><strong>{{ (int) $order->order_status === \App\Services\InventoryService::COMPLETE_STATUS ? '-৳'.number_format($cost['total'], 2) : 'Recognized on fulfillment' }}</strong></div>
        @if($parcel?->delivery_charge !== null)
            <div class="mo-summary-row"><span>Courier Charge</span><strong>৳{{ number_format((float) $parcel->delivery_charge, 2) }} reported</strong></div>
            <div class="mo-summary-row"><span>Settlement</span><strong>{{ $parcel->delivery_charge_paid ? 'Recorded' : 'Not confirmed' }}</strong></div>
        @endif
        @if($cost['estimatedLines'] || $cost['missingLines'])
            <p class="small text-warning mt-2 mb-0">{{ $cost['estimatedLines'] }} estimated cost line(s); {{ $cost['missingLines'] }} missing cost line(s). Estimated value: ৳{{ number_format($cost['estimated'], 2) }}.</p>
        @endif
    @elseif((float) $order->amount === 0.0)
        <p class="small text-warning mt-2 mb-0">This normal sale has a zero total. Review its pricing and discount before recording a customer payment.</p>
    @endif

    @if($canClassify)
        <button type="button" id="mo-purpose-toggle" class="btn btn-sm btn-outline-warning mt-3" aria-expanded="{{ $errors->has('order_purpose') || $errors->has('reason') ? 'true' : 'false' }}" aria-controls="mo-purpose-editor">Change Purpose</button>
        <div id="mo-purpose-editor" class="mt-3" @unless($errors->has('order_purpose') || $errors->has('reason')) hidden @endunless>
            <div class="mb-2"><label for="mo-purpose-select">Order Purpose</label><select id="mo-purpose-select" name="order_purpose" form="manual-purpose-form" class="form-select form-select-sm" required>
                @foreach(\App\Support\OrderPurpose::LABELS as $value => $label)
                    <option value="{{ $value }}" @selected(old('order_purpose', $purpose) === $value)>{{ $label }}</option>
                @endforeach
            </select></div>
            <div class="mb-2"><label for="mo-purpose-reason">Reason for change</label><input id="mo-purpose-reason" name="reason" form="manual-purpose-form" value="{{ old('reason') }}" class="form-control form-control-sm" minlength="5" maxlength="2000" required></div>
            @if($errors->has('order_purpose') || $errors->has('reason'))<p class="text-danger small">{{ $errors->first('order_purpose') ?: $errors->first('reason') }}</p>@endif
            <div class="d-flex gap-2"><button type="submit" form="manual-purpose-form" class="btn btn-sm btn-warning">Save Purpose</button><button type="button" id="mo-purpose-cancel" class="btn btn-sm btn-outline-secondary">Cancel</button></div>
        </div>
        @if($history->isNotEmpty())
            <details class="mt-3 small"><summary>Purpose History ({{ $history->count() }})</summary><ul class="mt-2 mb-0 ps-3">
                @foreach($history as $change)
                    <li class="mb-2">{{ $change->created_at?->format('d/m/Y H:i') }}<br>{{ \App\Support\OrderPurpose::label($change->old_purpose) }} → {{ \App\Support\OrderPurpose::label($change->new_purpose) }}<br>By: {{ $change->changer?->name ?: 'Admin' }}<br>Reason: {{ $change->reason }}</li>
                @endforeach
            </ul></details>
        @endif
    @endif
</section>
