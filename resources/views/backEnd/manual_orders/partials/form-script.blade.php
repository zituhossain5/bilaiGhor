@include('backEnd.partials.thana-loader')
<script src="{{ asset('public/backEnd/assets/libs/select2/js/select2.min.js') }}"></script>
<script>
(function () {
    var rowIndex = 0;
    var tbody = document.querySelector('#items-table tbody');
    var tpl = document.getElementById('item-row-template').innerHTML;
    var money = function (n) { return '৳' + Number(n || 0).toFixed(2); };
    var initialItems = {{ Illuminate\Support\Js::from(old('items', $initialItems ?? [['qty' => 1, 'unit_price' => 0, 'discount' => 0]])) }};
    var initialDistrictId = @json((string) old('district_id', $selectedDistrictId ?? ''));
    var initialThanaId = @json((string) old('thana_id', $selectedThanaId ?? ''));
    var byId = function (id) { return document.getElementById(id); };

    // The thana's rate is only a suggestion. Once the charge is set by hand it is never
    // overwritten: an existing order keeps its saved charge, and a failed submit keeps
    // what was typed.
    var deliveryInput = document.getElementById('delivery_charge');
    var deliveryManual = @json($isEdit || old('delivery_charge') !== null);
    var suggestedCharge = null;

    /** Fill the Thana list; resolves once it is in place (false if a newer load replaced it). */
    function loadManualThanas(districtId, selectedThanaId) {
        return BgThanaLoader.load(byId('manual_thana'), districtId, selectedThanaId)
            .then(function (current) {
                if (current) {
                    if (byId('manual_thana').value) {
                        syncManualThana();
                    } else {
                        suggestedCharge = null;
                        recalc();
                    }
                }
                return current;
            });
    }

    function syncManualThana() {
        var thana = document.getElementById('manual_thana');
        var option = thana.options[thana.selectedIndex];
        if (!option || !option.value) return;
        suggestedCharge = parseFloat(option.dataset.charge) || 0;
        if (!deliveryManual) {
            deliveryInput.value = suggestedCharge;
        }
        if (!document.getElementById('manual_post_code').value) {
            document.getElementById('manual_post_code').value = option.dataset.postCode || '';
        }
        recalc();
    }

    // Show "Area rate: ৳X · Use area rate" only when the charge differs from the area's rate.
    function syncDeliveryHint() {
        var differs = suggestedCharge !== null
            && Math.abs((parseFloat(deliveryInput.value) || 0) - suggestedCharge) > 0.001;
        document.getElementById('delivery_charge_suggested').textContent = money(suggestedCharge);
        document.getElementById('delivery_charge_hint').hidden = !differs;
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
        syncDeliveryHint();
        document.getElementById('sum-total').textContent = money(grand);
        document.getElementById('sum-paid').textContent = money(paid);
        document.getElementById('sum-due').textContent = money(due);
        document.getElementById('sum-payment-status').textContent = paymentStatus;
    }

    // ------------------------------------------------------------------
    // Customer picker, autofill and mobile-number lookup
    // ------------------------------------------------------------------
    var NONE = '__none__';
    var $customer = $('#customer_id');
    var searchUrl = $customer.data('search-url');
    var textFields = { name: 'customer_name', phone: 'customer_phone', email: 'customer_email', address: 'customer_address', post_code: 'manual_post_code' };
    var autofilled = null;   // what the last autofill wrote, field id => value
    var applySeq = 0;        // a newer pick cancels an older pick still waiting for its thanas

    function customerLabel(c) {
        return [c.name || 'Unnamed', c.phone, c.location].filter(Boolean).join(' — ') + (c.manual ? ' (Manual)' : '');
    }

    /** "Name — Mobile — District/Thana" + Manual badge, built as text nodes (never HTML). */
    function customerLine(c) {
        var line = document.createElement('span');
        line.className = 'mo-cust-line';
        var name = document.createElement('span');
        name.className = 'mo-cust-name';
        name.textContent = c.name || 'Unnamed';
        line.appendChild(name);
        var meta = [c.phone, c.location].filter(Boolean).join(' — ');
        if (meta) {
            var metaEl = document.createElement('span');
            metaEl.className = 'mo-cust-meta';
            metaEl.textContent = '— ' + meta;
            line.appendChild(metaEl);
        }
        if (c.manual) {
            var badge = document.createElement('span');
            badge.className = 'mo-badge-manual';
            badge.textContent = 'Manual';
            line.appendChild(badge);
        }
        return line;
    }

    function setCustomerChecks(hasCustomer) {
        byId('update-customer-wrap').hidden = !hasCustomer;
        byId('save-customer-wrap').hidden = hasCustomer;
        if (hasCustomer) byId('save_as_customer').checked = false;
        else byId('update_customer').checked = false;
    }

    /**
     * Fill the order from a customer: every customer field is replaced (blank where the customer
     * has nothing), then District → wait for its Thanas → Thana, which also applies the area rate
     * exactly as a manual pick does. All fields stay editable and only change this order.
     */
    function applyCustomer(c) {
        var seq = ++applySeq;
        var written = {};
        hideSuggestions();
        hideHint();

        Object.keys(textFields).forEach(function (key) {
            var value = c[key] ? String(c[key]) : '';
            byId(textFields[key]).value = value;
            written[textFields[key]] = value;
        });

        var district = byId('manual_district');
        var districtId = c.district_id ? String(c.district_id) : '';
        var known = Array.prototype.some.call(district.options, function (o) { return o.value === districtId; });
        district.value = known ? districtId : '';
        written.manual_district = district.value;

        return loadManualThanas(district.value, known ? c.thana_id : null).then(function () {
            if (seq !== applySeq) return;
            written.manual_thana = byId('manual_thana').value;
            written.manual_post_code = byId('manual_post_code').value;
            autofilled = written;
        });
    }

    /** "No registered customer": take back what the autofill wrote, keep anything typed since. */
    function clearAutofill() {
        applySeq++;
        if (!autofilled) return;
        var districtWas = byId('manual_district').value;
        Object.keys(autofilled).forEach(function (id) {
            var el = byId(id);
            if (el && el.value === autofilled[id]) el.value = '';
        });
        if (byId('manual_district').value !== districtWas) loadManualThanas('', null);
        autofilled = null;
    }

    function customerCleared() {
        $customer.find('option[value="' + NONE + '"]').remove();
        clearAutofill();
        setCustomerChecks(false);
    }

    /** Put a customer into the picker (from a phone suggestion) and autofill from it. */
    function pickCustomer(c) {
        $customer.find('option').filter(function () { return this.value && this.value !== ''; }).remove();
        $customer.append(new Option(customerLabel(c), c.id, true, true)).trigger('change');
        setCustomerChecks(true);
        applyCustomer(c);
    }

    $customer.select2({
        width: '100%',
        placeholder: 'No registered customer',
        allowClear: true,
        minimumInputLength: 2,
        ajax: {
            url: searchUrl,
            dataType: 'json',
            delay: 300,
            data: function (params) { return { q: params.term }; },
            processResults: function (data) {
                var rows = (data.results || []).map(function (c) { return $.extend({}, c, { text: customerLabel(c) }); });
                return { results: [{ id: NONE, text: 'No registered customer' }].concat(rows) };
            }
        },
        templateResult: function (c) {
            if (c.loading || c.id === undefined) return c.text;
            if (c.id === NONE) {
                var none = document.createElement('em');
                none.textContent = c.text;
                return $(none);
            }
            return $(customerLine(c));
        },
        language: {
            inputTooShort: function () { return 'Type 2+ letters of a name, or digits of a mobile number'; },
            noResults: function () { return 'No customer found'; },
            searching: function () { return 'Searching…'; }
        }
    });

    $customer.on('select2:select', function (event) {
        var c = event.params.data;
        if (c.id === NONE) {
            $customer.val(null).trigger('change');
            customerCleared();
            return;
        }
        setCustomerChecks(true);
        applyCustomer(c);
    });
    $customer.on('select2:clear', customerCleared);

    // Mobile number: suggestions from 4 digits; an exact single match asks before autofilling.
    var phoneInput = byId('customer_phone');
    var list = byId('phone-suggest');
    var hint = byId('phone-match-hint');
    var phoneTimer = null;
    var phoneSeq = 0;
    var suggestions = [];
    var activeIndex = -1;

    function hideSuggestions() {
        list.hidden = true;
        list.innerHTML = '';
        suggestions = [];
        activeIndex = -1;
        phoneInput.setAttribute('aria-expanded', 'false');
        phoneInput.removeAttribute('aria-activedescendant');
    }

    function hideHint() {
        hint.hidden = true;
        hint.innerHTML = '';
    }

    function setActive(index) {
        activeIndex = index;
        Array.prototype.forEach.call(list.children, function (li, i) {
            li.classList.toggle('is-active', i === index);
            li.setAttribute('aria-selected', i === index ? 'true' : 'false');
            if (i === index) {
                phoneInput.setAttribute('aria-activedescendant', li.id);
                li.scrollIntoView({ block: 'nearest' });
            }
        });
    }

    function showSuggestions(rows) {
        hideSuggestions();
        if (!rows.length) return;
        suggestions = rows;
        rows.forEach(function (c, i) {
            var li = document.createElement('li');
            li.id = 'phone-suggest-' + i;
            li.setAttribute('role', 'option');
            li.appendChild(customerLine(c));
            // mousedown, not click: runs before the input loses focus
            li.addEventListener('mousedown', function (event) {
                event.preventDefault();
                choose(i);
            });
            list.appendChild(li);
        });
        list.hidden = false;
        phoneInput.setAttribute('aria-expanded', 'true');
    }

    function showHint(c) {
        hideHint();
        hint.appendChild(document.createTextNode('Existing customer found: '));
        var strong = document.createElement('strong');
        strong.textContent = c.name || 'Unnamed';
        hint.appendChild(strong);
        if (c.location) hint.appendChild(document.createTextNode(' (' + c.location + ')'));
        hint.appendChild(document.createTextNode(' — '));
        var use = document.createElement('button');
        use.type = 'button';
        use.textContent = 'use?';
        use.addEventListener('click', function () { hideHint(); pickCustomer(c); });
        hint.appendChild(use);
        hint.hidden = false;
    }

    function choose(index) {
        var c = suggestions[index];
        hideSuggestions();
        if (c) pickCustomer(c);
    }

    function lookupPhone() {
        var value = phoneInput.value.trim();
        var seq = ++phoneSeq;
        fetch(searchUrl + '?phone=' + encodeURIComponent(value), { headers: { 'Accept': 'application/json' } })
            .then(function (response) { return response.json(); })
            .then(function (data) {
                if (seq !== phoneSeq || phoneInput.value.trim() !== value) return;
                var current = String($customer.val() || '');
                var rows = (data.results || []).filter(function (c) { return String(c.id) !== current; });
                var exact = rows.filter(function (c) { return c.exact; });
                if (exact.length === 1) {
                    hideSuggestions();
                    showHint(exact[0]);
                    return;
                }
                showSuggestions(exact.length > 1 ? exact : rows);
            })
            .catch(function () {});
    }

    phoneInput.addEventListener('input', function () {
        clearTimeout(phoneTimer);
        hideHint();
        if (phoneInput.value.replace(/\D/g, '').length < 4) {
            phoneSeq++;
            hideSuggestions();
            return;
        }
        phoneTimer = setTimeout(lookupPhone, 300);
    });

    phoneInput.addEventListener('keydown', function (event) {
        if (list.hidden) return;
        if (event.key === 'ArrowDown') {
            event.preventDefault();
            setActive((activeIndex + 1) % suggestions.length);
        } else if (event.key === 'ArrowUp') {
            event.preventDefault();
            setActive(activeIndex <= 0 ? suggestions.length - 1 : activeIndex - 1);
        } else if (event.key === 'Enter') {
            if (activeIndex >= 0) {
                event.preventDefault();
                choose(activeIndex);
            }
        } else if (event.key === 'Escape') {
            event.preventDefault();
            hideSuggestions();
        }
    });

    document.addEventListener('click', function (event) {
        if (!event.target.closest('.mo-phone-wrap')) hideSuggestions();
    });

    // ------------------------------------------------------------------

    document.getElementById('manual_district').addEventListener('change', function () {
        loadManualThanas(this.value, null);
    });
    document.getElementById('manual_thana').addEventListener('change', syncManualThana);

    // Typing a charge makes it stick; the totals already follow it through the calc-input listener.
    deliveryInput.addEventListener('input', function () { deliveryManual = true; });
    document.getElementById('delivery_charge_reset').addEventListener('click', function (event) {
        event.preventDefault();
        if (suggestedCharge === null) return;
        deliveryInput.value = suggestedCharge;
        deliveryManual = false; // follow the area rate again until the next edit
        recalc();
    });

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
