{{--
    Product "Short Name" field with a live character counter and an Auto-generate button that
    asks the server for a suggestion from the product name typed in the same form.
    Usage: <x-admin.short-name-field :value="old('short_name', $product->short_name ?? null)" />
--}}
@props(['value' => null, 'source' => 'name'])
@php $max = \App\Support\ShortName::max(); @endphp

<div class="form-group mb-3" data-short-name-field data-source="{{ $source }}">
    <label for="short_name" class="form-label">Short Name</label>
    <div class="input-group">
        <input type="text" id="short_name" name="short_name" maxlength="{{ $max }}" value="{{ $value }}"
               class="form-control @error('short_name') is-invalid @enderror"
               placeholder="e.g. Whiskas Ocean Fish" autocomplete="off" />
        <button type="button" class="btn btn-outline-secondary" data-short-name-generate
                data-url="{{ route('products.short_names.suggest') }}">
            <i class="fe-zap me-1"></i>Auto-generate
        </button>
    </div>
    <div class="d-flex justify-content-between mt-1">
        <small class="text-muted">Used on invoices. Leave empty to print the full name, shortened.</small>
        <small class="text-muted ms-2 text-nowrap" data-short-name-count>0/{{ $max }}</small>
    </div>
    @error('short_name') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
</div>

<script>
(function () {
    var field = document.currentScript.previousElementSibling;
    var input = field.querySelector('[name="short_name"]');
    var counter = field.querySelector('[data-short-name-count]');
    var button = field.querySelector('[data-short-name-generate]');
    var max = parseInt(input.getAttribute('maxlength'), 10);
    var notify = function (type, message) {
        if (window.toastr) { window.toastr[type](message); } else { alert(message); }
    };

    function count() {
        counter.textContent = input.value.length + '/' + max;
        counter.classList.toggle('text-danger', input.value.length >= max);
    }

    button.addEventListener('click', function () {
        var form = field.closest('form');
        var source = form && form.querySelector('[name="' + field.dataset.source + '"]');
        var name = source ? source.value.trim() : '';
        if (!name) {
            notify('warning', 'Enter the product name first.');
            return;
        }

        button.disabled = true;
        fetch(button.dataset.url + '?name=' + encodeURIComponent(name), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin'
        })
            .then(function (response) {
                if (!response.ok) { throw new Error(response.status); }
                return response.json();
            })
            .then(function (data) {
                input.value = data.short_name || '';
                count();
                input.focus();
            })
            .catch(function () { notify('error', 'Could not suggest a short name. Please type one.'); })
            .finally(function () { button.disabled = false; });
    });

    input.addEventListener('input', count);
    count();
}());
</script>
