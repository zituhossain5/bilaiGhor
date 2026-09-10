(function () {
    'use strict';

    function notify(type, message) {
        if (window.toastr && typeof window.toastr[type] === 'function') {
            window.toastr[type](message);
            return;
        }

        if (type === 'error') {
            window.alert(message);
        }
    }

    function stateKey(value) {
        return String(value || '')
            .trim()
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-|-$/g, '');
    }

    async function updateStatus(select) {
        var previousValue = select.dataset.previousValue;
        var field = select.dataset.field;
        var payload = { order_id: Number(select.dataset.orderId) };
        payload[field] = select.value;

        select.disabled = true;
        select.classList.add('is-saving');

        try {
            var token = document.querySelector('meta[name="csrf-token"]');
            var response = await fetch(select.dataset.updateUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token ? token.content : ''
                },
                body: JSON.stringify(payload)
            });
            var data = await response.json().catch(function () { return {}; });

            if (!response.ok || data.status !== 'success') {
                var validationErrors = data.errors ? Object.values(data.errors).flat() : [];
                throw new Error(validationErrors[0] || data.message || 'Status update failed.');
            }

            var savedValue = field === 'order_status' ? String(data.order_status) : String(data.payment_status);
            select.value = savedValue;
            select.dataset.previousValue = savedValue;
            select.dataset.state = data.status_key || stateKey(data.status_label || savedValue);
            notify('success', data.message || 'Status updated successfully.');
        } catch (error) {
            select.value = previousValue;
            notify('error', error.message || 'Status update failed.');
        } finally {
            select.disabled = false;
            select.classList.remove('is-saving');
        }
    }

    document.addEventListener('change', function (event) {
        var select = event.target.closest('.admin-inline-status-select');
        if (select) {
            updateStatus(select);
        }
    });
})();
