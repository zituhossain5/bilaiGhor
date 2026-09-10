(function () {
    'use strict';

    var DISPLAY_FORMAT = 'DD/MM/YYYY';
    var initializedAttr = 'data-admin-date-ready';

    function pad(value) {
        return String(value).padStart(2, '0');
    }

    function isoToDisplay(value) {
        if (!value || !/^\d{4}-\d{2}-\d{2}$/.test(value)) return value || '';
        var parts = value.split('-');
        return parts[2] + '/' + parts[1] + '/' + parts[0];
    }

    function displayToIso(value) {
        if (!value) return value || '';

        var trimmed = String(value).trim();
        if (/^\d{4}-\d{2}-\d{2}$/.test(trimmed)) return trimmed;

        var match = trimmed.match(/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/);
        if (!match) return trimmed;

        var day = parseInt(match[1], 10);
        var month = parseInt(match[2], 10);
        var year = parseInt(match[3], 10);
        var date = new Date(year, month - 1, day);

        if (
            date.getFullYear() !== year ||
            date.getMonth() !== month - 1 ||
            date.getDate() !== day
        ) {
            return trimmed;
        }

        return year + '-' + pad(month) + '-' + pad(day);
    }

    function shouldSkip(input) {
        return input.disabled ||
            input.readOnly ||
            input.classList.contains('no-admin-date') ||
            input.getAttribute('data-no-admin-date') === 'true' ||
            input.type === 'month' ||
            input.type === 'datetime-local' ||
            input.type === 'time';
    }

    function getDateInputs(root) {
        var container = root && root.querySelectorAll ? root : document;
        return container.querySelectorAll('input[type="date"], input[data-admin-date="true"]');
    }

    function prepareInput(input) {
        if (input.getAttribute(initializedAttr) === 'true' || shouldSkip(input)) return;

        var value = input.value;
        input.setAttribute('type', 'text');
        input.setAttribute('placeholder', DISPLAY_FORMAT);
        input.setAttribute('inputmode', 'numeric');
        input.setAttribute('autocomplete', 'off');
        input.classList.add('admin-date-input');
        input.value = isoToDisplay(value);
        input.setAttribute(initializedAttr, 'true');

        if (window.flatpickr) {
            flatpickr(input, {
                allowInput: true,
                dateFormat: 'd/m/Y',
                defaultDate: input.value || null,
                disableMobile: true
            });
        }
    }

    function prepareDates(root) {
        var inputs = getDateInputs(root);
        for (var i = 0; i < inputs.length; i++) {
            prepareInput(inputs[i]);
        }
    }

    function normalizeForm(form) {
        var inputs = form.querySelectorAll('input.admin-date-input, input[data-admin-date="true"]');
        for (var i = 0; i < inputs.length; i++) {
            inputs[i].value = displayToIso(inputs[i].value);
        }
    }

    function restoreDisplay(form) {
        var inputs = form.querySelectorAll('input.admin-date-input, input[data-admin-date="true"]');
        for (var i = 0; i < inputs.length; i++) {
            inputs[i].value = isoToDisplay(inputs[i].value);
        }
    }

    function bindSubmitConversion() {
        document.addEventListener('submit', function (event) {
            var form = event.target;
            if (!form || !form.querySelectorAll) return;
            normalizeForm(form);
            setTimeout(function () {
                restoreDisplay(form);
            }, 0);
        }, true);
    }

    function boot() {
        prepareDates(document);
        bindSubmitConversion();

        if (window.jQuery) {
            jQuery(document).ajaxComplete(function () {
                prepareDates(document);
            });
        }

        if (window.MutationObserver) {
            var observer = new MutationObserver(function (mutations) {
                for (var i = 0; i < mutations.length; i++) {
                    for (var j = 0; j < mutations[i].addedNodes.length; j++) {
                        var node = mutations[i].addedNodes[j];
                        if (node.nodeType === 1) {
                            prepareDates(node);
                        }
                    }
                }
            });
            observer.observe(document.body, { childList: true, subtree: true });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }

    window.BilaiAdminDateFormat = {
        prepare: prepareDates,
        toDisplay: isoToDisplay,
        toIso: displayToIso
    };
})();
