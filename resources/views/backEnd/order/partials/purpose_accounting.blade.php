@php
    $purposeOrder = $order;
    $purpose = $purposeOrder->order_purpose ?: \App\Support\OrderPurpose::SALE;
    $isNonSale = $purpose !== \App\Support\OrderPurpose::SALE;
    $costSummary = \App\Services\AccountingSummaryService::orderProductCost($purposeOrder);
    $purposeCost = $costSummary['total'];
    $estimatedLines = $costSummary['estimatedLines'];
    $reportedCourierCharge = $purposeOrder->courierParcel?->delivery_charge;
    $purposeEditor = auth('admin')->user();
    $canClassify = \App\Support\OrderPurpose::canClassify($purposeEditor);
@endphp
<div class="card mb-3">
    <div class="card-body">
        <h6>Order purpose &amp; accounting</h6>
        <p class="mb-2">Purpose: <span class="badge bg-warning text-dark">{{ \App\Support\OrderPurpose::label($purpose) }}</span></p>
        @if($isNonSale)
            <div class="small mb-2">
                Customer paid: {{ number_format((float) ($purposeOrder->paid_amount ?? 0), 2) }} ৳ ·
                Product cost: {{ number_format($purposeCost, 2) }} ৳ ·
                Inventory: handled through fulfillment ·
                Classification fund effect: 0.00 ৳ ·
                Profit effect: -{{ number_format($purposeCost, 2) }} ৳
                @if($reportedCourierCharge !== null)
                    · Courier charge reported: {{ number_format((float) $reportedCourierCharge, 2) }} ৳
                    (settlement {{ $purposeOrder->courierParcel->delivery_charge_paid ? 'recorded' : 'not confirmed' }})
                @endif
            </div>
            @if($estimatedLines)<p class="small text-warning mb-2">{{ $estimatedLines }} line(s) lack saved historical cost; current product cost is an estimate.</p>@endif
        @elseif((int) $purposeOrder->order_status === \App\Services\InventoryService::COMPLETE_STATUS && (float) $purposeOrder->amount === 0.0)
            <p class="small text-warning mb-2">Delivered zero-total sale. Review whether this was a giveaway, replacement, or intentional sale.</p>
        @endif
        @if($canClassify)
            <form method="POST" action="{{ route('admin.orders.purpose.update', $purposeOrder) }}" class="row g-2 align-items-end">
                @csrf
                <div class="col-md-4"><label class="form-label">Order Purpose</label><select name="order_purpose" class="form-select form-select-sm" required>
                    @foreach(\App\Support\OrderPurpose::LABELS as $value => $label)
                        <option value="{{ $value }}" @selected($purpose === $value)>{{ $label }}</option>
                    @endforeach
                </select></div>
                <div class="col-md-5"><label class="form-label">Reason for change</label><input name="reason" class="form-control form-control-sm" minlength="5" maxlength="2000" required placeholder="Why is this classification correct?"></div>
                <div class="col-md-3"><button class="btn btn-sm btn-warning w-100" type="submit">Save Purpose</button></div>
            </form>
            @if($errors->has('order_purpose') || $errors->has('reason'))<p class="text-danger small mt-2 mb-0">{{ $errors->first('order_purpose') ?: $errors->first('reason') }}</p>@endif
            @php($purposeHistory = $purposeOrder->purposeChanges()->with('changer:id,name')->latest()->limit(5)->get())
            @if($purposeHistory->isNotEmpty())
                <details class="mt-3 small"><summary>Purpose change history</summary><ul class="mt-2 mb-0">
                    @foreach($purposeHistory as $change)
                        <li>{{ $change->created_at?->format('d/m/Y H:i') }}: {{ \App\Support\OrderPurpose::label($change->old_purpose) }} → {{ \App\Support\OrderPurpose::label($change->new_purpose) }} by {{ $change->changer?->name ?: 'Admin' }} — {{ $change->reason }}</li>
                    @endforeach
                </ul></details>
            @endif
        @endif
    </div>
</div>
