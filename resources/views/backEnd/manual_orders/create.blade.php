@extends('backEnd.layouts.master')
@section('title', 'Create Manual Invoice')

@section('css')
<style>
.mo-create .card { border: 1px solid #e7edf5; border-radius: 10px; box-shadow: 0 8px 24px rgba(15,23,42,.05); }
.mo-create label { font-weight: 700; font-size: 12px; color: #344054; }
.mo-items th { font-size: 12px; color: #667085; white-space: nowrap; }
.mo-summary { background: #fff8ec; border: 1px solid #f3cf9e; border-radius: 10px; padding: 16px; position: sticky; top: 90px; }
.mo-summary-row { display: flex; justify-content: space-between; padding: 7px 0; border-bottom: 1px dashed #e7c48e; }
.mo-summary-row.total { margin-top: 8px; padding: 12px; background: #3a1f0f; color: #fff; border-radius: 8px; border: none; font-weight: 800; }
</style>
@endsection

@section('content')
<div class="container-fluid mo-create">
    <div class="page-title-box d-flex align-items-center justify-content-between">
        <h4 class="page-title">Create Manual Invoice</h4>
        <a href="{{ route('admin.manual_orders.index') }}" class="btn btn-light">Back to List</a>
    </div>

    @if($errors->any())
        <div class="alert alert-danger">
            <strong>Please fix the highlighted fields.</strong>
            <ul class="mb-0 mt-2">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('admin.manual_orders.store') }}" method="POST" id="manual-order-form">
        @csrf
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
                                        <option value="{{ $customer->id }}" data-name="{{ $customer->name }}" data-phone="{{ $customer->phone }}" data-email="{{ $customer->email }}" data-address="{{ $customer->address }}">{{ $customer->name }} {{ $customer->phone ? '- '.$customer->phone : '' }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label>Order Source *</label>
                                <select name="order_source" class="form-select" required>
                                    @foreach($sources as $source)
                                        <option value="{{ $source }}">{{ ucwords(str_replace('_', ' ', $source)) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label>Customer Name *</label>
                                <input type="text" name="customer_name" id="customer_name" value="{{ old('customer_name') }}" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label>Mobile Number *</label>
                                <input type="text" name="customer_phone" id="customer_phone" value="{{ old('customer_phone') }}" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label>Email</label>
                                <input type="email" name="customer_email" id="customer_email" value="{{ old('customer_email') }}" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label>Payment Method *</label>
                                <select name="payment_method" class="form-select" required>
                                    @foreach($methods as $method)
                                        <option value="{{ $method }}">{{ ucwords(str_replace('_', ' ', $method)) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12">
                                <label>Delivery Address *</label>
                                <textarea name="customer_address" id="customer_address" class="form-control" rows="2" required>{{ old('customer_address') }}</textarea>
                            </div>
                            <div class="col-md-6">
                                <label>Transaction ID / Reference</label>
                                <input type="text" name="transaction_id" value="{{ old('transaction_id') }}" maxlength="100" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label>Notes</label>
                                <input type="text" name="notes" value="{{ old('notes') }}" maxlength="1000" class="form-control">
                            </div>
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
                        <input type="number" step="0.01" min="0" name="delivery_charge" id="delivery_charge" value="{{ old('delivery_charge', 0) }}" class="form-control calc-input">
                    </div>
                    <div class="mb-3">
                        <label>Order Discount</label>
                        <input type="number" step="0.01" min="0" name="order_discount" id="order_discount" value="{{ old('order_discount', 0) }}" class="form-control calc-input">
                    </div>
                    <div class="mb-3">
                        <label>Paid Amount</label>
                        <input type="number" step="0.01" min="0" name="paid_amount" id="paid_amount" value="{{ old('paid_amount', 0) }}" class="form-control calc-input">
                    </div>
                    <div class="mo-summary-row"><span>Subtotal</span><strong id="sum-subtotal">৳0.00</strong></div>
                    <div class="mo-summary-row"><span>Item Discount</span><strong id="sum-item-discount">৳0.00</strong></div>
                    <div class="mo-summary-row"><span>Order Discount</span><strong id="sum-order-discount">৳0.00</strong></div>
                    <div class="mo-summary-row"><span>Delivery</span><strong id="sum-delivery">৳0.00</strong></div>
                    <div class="mo-summary-row total"><span>Grand Total</span><strong id="sum-total">৳0.00</strong></div>
                    <div class="mo-summary-row"><span>Paid</span><strong id="sum-paid">৳0.00</strong></div>
                    <div class="mo-summary-row"><span>Due</span><strong id="sum-due">৳0.00</strong></div>
                    <button type="submit" class="btn btn-success w-100 mt-3">Save Manual Order</button>
                </div>
            </div>
        </div>
    </form>
</div>

<template id="item-row-template">
    <tr>
        <td>
            <select class="form-select product-select" name="items[__INDEX__][product_id]">
                <option value="">Custom/manual item</option>
                @foreach($products as $product)
                    <option value="{{ $product->id }}" data-name="{{ $product->name }}" data-price="{{ $product->new_price }}" data-stock="{{ $product->stock }}" data-variant="{{ optional($product->weight)->name }}">
                        {{ $product->name }} (Stock: {{ $product->stock }})
                    </option>
                @endforeach
            </select>
            <input type="text" class="form-control mt-1 item-name" name="items[__INDEX__][name]" placeholder="Custom item name">
        </td>
        <td><input type="text" class="form-control item-variant" name="items[__INDEX__][variant]" placeholder="Weight/variant"></td>
        <td><input type="number" class="form-control item-qty" name="items[__INDEX__][qty]" value="1" min="1" required></td>
        <td><input type="number" class="form-control item-price" name="items[__INDEX__][unit_price]" value="0" min="0" step="0.01" required></td>
        <td><input type="number" class="form-control item-discount" name="items[__INDEX__][discount]" value="0" min="0" step="0.01"></td>
        <td><strong class="line-total">৳0.00</strong></td>
        <td><button type="button" class="btn btn-sm btn-outline-danger remove-row">×</button></td>
    </tr>
</template>
@endsection

@section('script')
<script>
(function () {
    var rowIndex = 0;
    var tbody = document.querySelector('#items-table tbody');
    var tpl = document.getElementById('item-row-template').innerHTML;
    var money = function (n) { return '৳' + Number(n || 0).toFixed(2); };

    function addRow() {
        tbody.insertAdjacentHTML('beforeend', tpl.replaceAll('__INDEX__', rowIndex++));
        recalc();
    }

    function recalc() {
        var subtotal = 0;
        var itemDiscount = 0;
        tbody.querySelectorAll('tr').forEach(function (row) {
            var qty = parseFloat(row.querySelector('.item-qty').value) || 0;
            var price = parseFloat(row.querySelector('.item-price').value) || 0;
            var discount = Math.min(parseFloat(row.querySelector('.item-discount').value) || 0, qty * price);
            var total = Math.max(0, (qty * price) - discount);
            subtotal += qty * price;
            itemDiscount += discount;
            row.querySelector('.line-total').textContent = money(total);
        });

        var orderDiscount = Math.min(parseFloat(document.getElementById('order_discount').value) || 0, Math.max(0, subtotal - itemDiscount));
        var delivery = parseFloat(document.getElementById('delivery_charge').value) || 0;
        var grand = Math.max(0, subtotal - itemDiscount - orderDiscount + delivery);
        var paid = Math.min(parseFloat(document.getElementById('paid_amount').value) || 0, grand);
        var due = Math.max(0, grand - paid);

        document.getElementById('sum-subtotal').textContent = money(subtotal);
        document.getElementById('sum-item-discount').textContent = money(itemDiscount);
        document.getElementById('sum-order-discount').textContent = money(orderDiscount);
        document.getElementById('sum-delivery').textContent = money(delivery);
        document.getElementById('sum-total').textContent = money(grand);
        document.getElementById('sum-paid').textContent = money(paid);
        document.getElementById('sum-due').textContent = money(due);
    }

    document.getElementById('customer_id').addEventListener('change', function () {
        var opt = this.options[this.selectedIndex];
        if (!this.value) return;
        document.getElementById('customer_name').value = opt.dataset.name || '';
        document.getElementById('customer_phone').value = opt.dataset.phone || '';
        document.getElementById('customer_email').value = opt.dataset.email || '';
        document.getElementById('customer_address').value = opt.dataset.address || '';
    });

    document.getElementById('add-row').addEventListener('click', addRow);
    document.addEventListener('input', function (e) {
        if (e.target.matches('.item-qty,.item-price,.item-discount,.calc-input')) recalc();
    });
    document.addEventListener('change', function (e) {
        if (!e.target.matches('.product-select')) return;
        var opt = e.target.options[e.target.selectedIndex];
        var row = e.target.closest('tr');
        row.querySelector('.item-name').value = opt.dataset.name || '';
        row.querySelector('.item-price').value = opt.dataset.price || 0;
        row.querySelector('.item-variant').value = opt.dataset.variant || '';
        recalc();
    });
    document.addEventListener('click', function (e) {
        if (!e.target.matches('.remove-row')) return;
        e.target.closest('tr').remove();
        if (!tbody.children.length) addRow();
        recalc();
    });

    addRow();
}());
</script>
@endsection
