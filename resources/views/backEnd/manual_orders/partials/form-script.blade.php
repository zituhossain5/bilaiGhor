<script>
(function () {
    var rowIndex = 0;
    var tbody = document.querySelector('#items-table tbody');
    var tpl = document.getElementById('item-row-template').innerHTML;
    var money = function (n) { return '\u09F3' + Number(n || 0).toFixed(2); };
    var initialItems = {{ Illuminate\Support\Js::from(old('items', $initialItems ?? [['qty' => 1, 'unit_price' => 0, 'discount' => 0]])) }};
    var initialDistrictId = @json((string) old('district_id', $selectedDistrictId ?? ''));
    var initialThanaId = @json((string) old('thana_id', $selectedThanaId ?? ''));

    function loadManualThanas(districtId, selectedThanaId) {
        var thana = document.getElementById('manual_thana');
        thana.disabled = true;
        thana.innerHTML = '<option value="">Loading Thanas...</option>';
        if (!districtId) {
            thana.innerHTML = '<option value="">Select District First</option>';
            return;
        }

        fetch(@json(url('/ajax/delivery/thanas')) + '/' + districtId)
            .then(function (response) { return response.json(); })
            .then(function (response) {
                thana.innerHTML = '<option value="">Select Thana</option>';
                (response.data || []).forEach(function (row) {
                    var option = document.createElement('option');
                    option.value = row.id;
                    option.textContent = row.name_bn || row.name;
                    option.dataset.charge = row.delivery_charge || 0;
                    option.dataset.postCode = row.post_code || '';
                    if (String(row.id) === String(selectedThanaId || '')) option.selected = true;
                    thana.appendChild(option);
                });
                thana.disabled = false;
                syncManualThana();
            });
    }

    function syncManualThana() {
        var thana = document.getElementById('manual_thana');
        var option = thana.options[thana.selectedIndex];
        if (!option || !option.value) return;
        document.getElementById('delivery_charge').value = option.dataset.charge || 0;
        if (!document.getElementById('manual_post_code').value) {
            document.getElementById('manual_post_code').value = option.dataset.postCode || '';
        }
        recalc();
    }

    function selectedProduct(row) {
        var select = row.querySelector('.product-select');
        return select.options[select.selectedIndex];
    }

    function syncProductRow(row) {
        var opt = selectedProduct(row);
        var qtyInput = row.querySelector('.item-qty');
        var hint = row.querySelector('.mo-stock-hint');

        if (!opt || !opt.value) {
            qtyInput.removeAttribute('max');
            qtyInput.setCustomValidity('');
            hint.textContent = '';
            hint.classList.remove('is-warning');
            return;
        }

        var stock = parseInt(opt.dataset.stock || 0, 10);
        var qty = parseInt(qtyInput.value || 0, 10);
        qtyInput.max = stock;

        if (qty > stock) {
            hint.textContent = 'Only ' + stock + ' items are currently available to this order.';
            hint.classList.add('is-warning');
            qtyInput.setCustomValidity(hint.textContent);
        } else {
            hint.textContent = 'Available to this order: ' + stock;
            hint.classList.remove('is-warning');
            qtyInput.setCustomValidity('');
        }
    }

    function addRow(item) {
        tbody.insertAdjacentHTML('beforeend', tpl.replaceAll('__INDEX__', rowIndex++));
        var row = tbody.lastElementChild;

        if (item) {
            row.querySelector('.product-select').value = item.product_id || '';
            row.querySelector('.item-name').value = item.name || '';
            row.querySelector('.item-variant').value = item.variant || '';
            row.querySelector('.item-qty').value = item.qty || 1;
            row.querySelector('.item-price').value = item.unit_price || 0;
            row.querySelector('.item-discount').value = item.discount || 0;
        }

        syncProductRow(row);
        recalc();
    }

    function recalc() {
        var subtotal = 0;
        var itemDiscount = 0;

        tbody.querySelectorAll('tr').forEach(function (row) {
            syncProductRow(row);
            var qty = parseFloat(row.querySelector('.item-qty').value) || 0;
            var price = parseFloat(row.querySelector('.item-price').value) || 0;
            var discount = parseFloat(row.querySelector('.item-discount').value) || 0;
            var gross = qty * price;
            var acceptedDiscount = Math.min(discount, gross);
            subtotal += gross;
            itemDiscount += acceptedDiscount;
            row.querySelector('.line-total').textContent = money(Math.max(0, gross - acceptedDiscount));
        });

        var orderDiscount = parseFloat(document.getElementById('order_discount').value) || 0;
        var delivery = parseFloat(document.getElementById('delivery_charge').value) || 0;
        var grand = Math.max(0, subtotal - itemDiscount - orderDiscount + delivery);
        var paidInput = document.getElementById('paid_amount');
        var paid = parseFloat(paidInput.value) || 0;
        var due = Math.max(0, grand - paid);
        var paymentStatus = paid <= 0 ? 'Unpaid' : (paid < grand ? 'Partial' : 'Paid');

        paidInput.max = grand.toFixed(2);
        paidInput.classList.toggle('is-invalid', paid > grand);
        paidInput.setCustomValidity(paid > grand ? 'Paid amount cannot exceed the grand total.' : '');

        document.getElementById('sum-subtotal').textContent = money(subtotal);
        document.getElementById('sum-item-discount').textContent = money(itemDiscount);
        document.getElementById('sum-order-discount').textContent = money(orderDiscount);
        document.getElementById('sum-delivery').textContent = money(delivery);
        document.getElementById('sum-total').textContent = money(grand);
        document.getElementById('sum-paid').textContent = money(paid);
        document.getElementById('sum-due').textContent = money(due);
        document.getElementById('sum-payment-status').textContent = paymentStatus;
    }

    document.getElementById('customer_id').addEventListener('change', function () {
        var opt = this.options[this.selectedIndex];
        if (!this.value) return;
        document.getElementById('customer_name').value = opt.dataset.name || '';
        document.getElementById('customer_phone').value = opt.dataset.phone || '';
        document.getElementById('customer_email').value = opt.dataset.email || '';
        document.getElementById('customer_address').value = opt.dataset.address || '';
        if (opt.dataset.district) {
            document.getElementById('manual_district').value = opt.dataset.district;
            loadManualThanas(opt.dataset.district, opt.dataset.thana || null);
        }
    });

    document.getElementById('manual_district').addEventListener('change', function () {
        loadManualThanas(this.value, null);
    });
    document.getElementById('manual_thana').addEventListener('change', syncManualThana);

    document.getElementById('add-row').addEventListener('click', function () { addRow(); });
    document.addEventListener('input', function (event) {
        if (event.target.matches('.item-qty,.item-price,.item-discount,.calc-input')) {
            var row = event.target.closest('tr');
            if (row) syncProductRow(row);
            recalc();
        }
    });
    document.addEventListener('change', function (event) {
        if (!event.target.matches('.product-select')) return;
        var opt = event.target.options[event.target.selectedIndex];
        var row = event.target.closest('tr');
        row.querySelector('.item-name').value = opt.dataset.name || '';
        row.querySelector('.item-price').value = opt.dataset.price || 0;
        row.querySelector('.item-variant').value = opt.dataset.variant || '';
        syncProductRow(row);
        recalc();
    });
    document.addEventListener('click', function (event) {
        if (!event.target.matches('.remove-row')) return;
        event.target.closest('tr').remove();
        if (!tbody.children.length) addRow();
        recalc();
    });

    if (Array.isArray(initialItems) && initialItems.length) {
        initialItems.forEach(addRow);
    } else {
        addRow();
    }

    loadManualThanas(document.getElementById('manual_district').value || initialDistrictId || '', initialThanaId || null);
}());
</script>
