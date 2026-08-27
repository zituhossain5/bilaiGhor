@php
    $selectedCustomerId = old('customer_id', $isEdit ? $order->customer_id : null);
    $customerName = old('customer_name', $isEdit ? $order->manual_customer_name : '');
    $customerPhone = old('customer_phone', $isEdit ? $order->manual_customer_phone : '');
    $customerEmail = old('customer_email', $isEdit ? $order->manual_customer_email : '');
    $customerAddress = old('customer_address', $isEdit ? $order->manual_customer_address : '');
    $paymentMethod = old('payment_method', $isEdit ? $order->payment_method : 'cash');
@endphp

@if(isset($errors) && $errors->any())
    <div class="alert alert-danger">
        <strong>Please fix the highlighted fields.</strong>
        <ul class="mb-0 mt-2">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
@endif

<form action="{{ $isEdit ? route('admin.manual_orders.update', $order) : route('admin.manual_orders.store') }}" method="POST" id="manual-order-form">
    @csrf
    @if($isEdit) @method('PUT') @endif
    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card mb-3">
                <div class="card-body">
                    <h5 class="mb-3">Customer Information</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label>Existing Customer</label>
                            <select name="customer_id" id="customer_id" class="form-select">
                                <option value="">No registered customer</option>
                                @foreach($customers as $customer)
                                    <option value="{{ $customer->id }}" data-name="{{ $customer->name }}" data-phone="{{ $customer->phone }}" data-email="{{ $customer->email }}" data-address="{{ $customer->address }}" @selected((string) $selectedCustomerId === (string) $customer->id)>
                                        {{ $customer->name }} {{ $customer->phone ? '- '.$customer->phone : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label>Order Source *</label>
                            <select name="order_source" class="form-select" required>
                                @foreach($sources as $source)
                                    <option value="{{ $source }}" @selected(old('order_source', $isEdit ? $order->order_source : 'manual') === $source)>{{ ucwords(str_replace('_', ' ', $source)) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label>Customer Name *</label>
                            <input type="text" name="customer_name" id="customer_name" value="{{ $customerName }}" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label>Mobile Number *</label>
                            <input type="text" name="customer_phone" id="customer_phone" value="{{ $customerPhone }}" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label>Email</label>
                            <input type="email" name="customer_email" id="customer_email" value="{{ $customerEmail }}" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label>Payment Method *</label>
                            <select name="payment_method" class="form-select" required>
                                @foreach($methods as $method)
                                    <option value="{{ $method }}" @selected($paymentMethod === $method)>{{ ucwords(str_replace('_', ' ', $method)) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label>Delivery Address *</label>
                            <textarea name="customer_address" id="customer_address" class="form-control" rows="2" required>{{ $customerAddress }}</textarea>
                        </div>
                        <div class="col-md-6">
                            <label>Transaction ID / Reference</label>
                            <input type="text" name="transaction_id" value="{{ old('transaction_id', $isEdit ? $order->transaction_id : '') }}" maxlength="100" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label>Notes</label>
                            <input type="text" name="notes" value="{{ old('notes', $isEdit ? ($order->note ?: $order->order_note) : '') }}" maxlength="1000" class="form-control">
                        </div>
                        @if($isEdit)
                            <div class="col-md-6">
                                <label>Order Status *</label>
                                <select name="order_status" class="form-select" required>
                                    @foreach($statuses as $status)
                                        <option value="{{ $status->id }}" @selected((int) old('order_status', $order->order_status) === (int) $status->id)>{{ $status->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Product Items</h5>
                        <button type="button" class="btn btn-sm btn-primary" id="add-row">Add Item</button>
                    </div>
                    <div class="table-responsive">
                        <table class="table mo-items align-middle" id="items-table">
                            <thead>
                                <tr>
                                    <th style="min-width:220px;">Product / Custom Item</th>
                                    <th style="min-width:130px;">Variant</th>
                                    <th style="width:90px;">Qty</th>
                                    <th style="width:130px;">Unit Price</th>
                                    <th style="width:120px;">Discount</th>
                                    <th style="width:130px;">Line Total</th>
                                    <th style="width:50px;"></th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="mo-summary">
                <h5 class="mb-3">Totals</h5>
                <div class="mb-3">
                    <label>Delivery Charge</label>
                    <input type="number" step="0.01" min="0" name="delivery_charge" id="delivery_charge" value="{{ old('delivery_charge', $isEdit ? $order->shipping_charge : 0) }}" class="form-control calc-input">
                </div>
                <div class="mb-3">
                    <label>Order Discount</label>
                    <input type="number" step="0.01" min="0" name="order_discount" id="order_discount" value="{{ old('order_discount', $isEdit ? $order->order_discount : 0) }}" class="form-control calc-input">
                </div>
                <div class="mb-3">
                    <label>Paid Amount</label>
                    <input type="number" step="0.01" min="0" name="paid_amount" id="paid_amount" value="{{ old('paid_amount', $isEdit ? $order->paid_amount : 0) }}" class="form-control calc-input">
                    <div class="invalid-feedback">Paid amount cannot exceed the grand total.</div>
                </div>
                <div class="mo-summary-row"><span>Subtotal</span><strong id="sum-subtotal">&#2547;0.00</strong></div>
                <div class="mo-summary-row"><span>Item Discount</span><strong id="sum-item-discount">&#2547;0.00</strong></div>
                <div class="mo-summary-row"><span>Order Discount</span><strong id="sum-order-discount">&#2547;0.00</strong></div>
                <div class="mo-summary-row"><span>Delivery</span><strong id="sum-delivery">&#2547;0.00</strong></div>
                <div class="mo-summary-row total"><span>Grand Total</span><strong id="sum-total">&#2547;0.00</strong></div>
                <div class="mo-summary-row"><span>Paid</span><strong id="sum-paid">&#2547;0.00</strong></div>
                <div class="mo-summary-row"><span>Due</span><strong id="sum-due">&#2547;0.00</strong></div>
                <div class="mo-summary-row"><span>Payment Status</span><strong id="sum-payment-status" class="mo-payment-status">Unpaid</strong></div>
                <button type="submit" class="btn btn-success w-100 mt-3">{{ $isEdit ? 'Update Manual Order' : 'Save Manual Order' }}</button>
            </div>
        </div>
    </div>
</form>

<template id="item-row-template">
    <tr>
        <td>
            <select class="form-select product-select" name="items[__INDEX__][product_id]">
                <option value="">Custom/manual item</option>
                @foreach($products as $product)
                    <option value="{{ $product->id }}" data-name="{{ $product->name }}" data-price="{{ $product->new_price }}" data-stock="{{ $product->editable_stock }}" data-variant="{{ optional($product->weight)->name }}">
                        {{ $product->name }} (Available to this order: {{ $product->editable_stock }})
                    </option>
                @endforeach
            </select>
            <input type="text" class="form-control mt-1 item-name" name="items[__INDEX__][name]" placeholder="Custom item name">
        </td>
        <td><input type="text" class="form-control item-variant" name="items[__INDEX__][variant]" placeholder="Weight/variant"></td>
        <td>
            <input type="number" class="form-control item-qty" name="items[__INDEX__][qty]" value="1" min="1" required>
            <span class="mo-stock-hint"></span>
        </td>
        <td><input type="number" class="form-control item-price" name="items[__INDEX__][unit_price]" value="0" min="0" step="0.01" required></td>
        <td><input type="number" class="form-control item-discount" name="items[__INDEX__][discount]" value="0" min="0" step="0.01"></td>
        <td><strong class="line-total">&#2547;0.00</strong></td>
        <td><button type="button" class="btn btn-sm btn-outline-danger remove-row" aria-label="Remove item">&times;</button></td>
    </tr>
</template>
