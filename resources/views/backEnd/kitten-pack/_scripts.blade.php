<script src="{{ asset('public/backEnd/') }}/assets/libs/select2/js/select2.min.js"></script>
<script>
function readURL(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            $('#preview_image').attr('src', e.target.result);
            $('#new_preview').removeClass('d-none');
            $('#current_image').hide();
            $('#upload_placeholder').hide();
        };
        reader.readAsDataURL(input.files[0]);
    }
}

$(function() {
    var $items = $('#pack_items');
    // Rows are posted as an indexed array; gaps left by removals do not matter.
    var nextIndex = $items.find('.pack-item-row').length;
    var $picker = $('#item_product');

    $picker.select2({ width: '100%', placeholder: 'Select a product…' });

    // Rows carried over from the old free-text list pick their product from the same list.
    $items.find('.item-link-select').each(function() {
        $(this).append($picker.find('option[value!=""]').clone()).select2({ width: '100%', placeholder: 'Select a product…' });
    });

    function hint(message) {
        $('#item_hint').text(message).toggleClass('d-none', !message);
    }

    function productName(productId) {
        return $.trim($picker.find('option[value="' + productId + '"]').text());
    }

    function productStock(productId) {
        return parseInt($picker.find('option[value="' + productId + '"]').data('stock'), 10) || 0;
    }

    function highlight($row) {
        $row.addClass('is-highlighted').find('.item-qty').trigger('focus').trigger('select');
        setTimeout(function() { $row.removeClass('is-highlighted'); }, 1500);
    }

    // Mirrors InventoryService::packAvailable(): included rows only, the scarcest product decides.
    function refresh() {
        var $rows = $items.find('.pack-item-row');
        var count = 0;
        var packs = null;
        var unlinked = 0;

        $rows.each(function() {
            var $row = $(this);
            if (!$row.find('.item-included').is(':checked')) {
                return;
            }
            var qty = Math.max(1, parseInt($row.find('.item-qty').val(), 10) || 1);
            count += qty;

            if (!$row.attr('data-product-id')) {
                unlinked++;
                return;
            }
            var canMake = Math.floor(Math.max(0, parseInt($row.attr('data-stock'), 10) || 0) / qty);
            packs = packs === null ? canMake : Math.min(packs, canMake);
        });

        $items.find('.item-empty').prop('hidden', $rows.length > 0);
        $('#item_count').text(count);
        $('#packs_available').text(unlinked || packs === null ? 0 : packs);

        var blocked = unlinked
            ? unlinked + ' included item(s) still need a product — the pack shows Stock Out until they are linked.'
            : (packs === null ? 'Add at least one included item to sell this pack.' : '');
        $('#packs_blocked').text(blocked).toggleClass('d-none', !blocked);
    }

    $('#add_item').on('click', function() {
        var productId = $picker.val();
        var qty = Math.max(1, parseInt($('#item_qty').val(), 10) || 1);

        if (!productId) {
            hint('Pick a product first.');
            return;
        }

        // Already listed: edit its quantity there instead of adding a duplicate row.
        var $existing = $items.find('.pack-item-row[data-product-id="' + productId + '"]');
        if ($existing.length) {
            hint('This product is already in the pack — change its quantity below.');
            highlight($existing);
            return;
        }

        var i = nextIndex++;
        var stock = productStock(productId);
        var $row = $('<tr class="pack-item-row">').attr('data-product-id', productId).attr('data-stock', stock);

        $row.append(
            $('<td>').append(
                $('<span class="item-name">').text(productName(productId)),
                $('<input type="hidden">').attr('name', 'items[' + i + '][product_id]').val(productId)
            ),
            $('<td>').append($('<span class="stock-badge">').text(stock)),
            $('<td>').append(
                $('<input type="number" class="form-control item-qty" min="1" required aria-label="Quantity">')
                    .attr('name', 'items[' + i + '][quantity]').val(qty)
            ),
            $('<td>').append(
                $('<label class="pack-item-check">').append(
                    $('<input type="checkbox" value="1" class="item-included" checked>').attr('name', 'items[' + i + '][is_included]'),
                    '<span>Included</span>'
                )
            ),
            $('<td>').append('<button type="button" class="btn-remove-row btn-remove-item" aria-label="Remove item"><i class="fe-x"></i></button>')
        );

        $items.find('.item-empty').before($row);
        $picker.val('').trigger('change');
        $('#item_qty').val(1);
        hint('');
        refresh();
    });

    // Linking an old free-text row to a product turns it into a normal row.
    $items.on('change', '.item-link-select', function() {
        var $select = $(this);
        var $row = $select.closest('.pack-item-row');
        var productId = $select.val();

        if (productId) {
            var $existing = $items.find('.pack-item-row[data-product-id="' + productId + '"]').not($row);
            if ($existing.length) {
                hint('That product is already in the pack — change its quantity there, and remove this row.');
                $select.val('').trigger('change.select2');
                highlight($existing);
                return;
            }
        }

        $row.attr('data-product-id', productId || '')
            .attr('data-stock', productId ? productStock(productId) : 0)
            .toggleClass('needs-product', !productId);
        $row.find('.stock-badge').text(productId ? productStock(productId) : '—');
        hint('');
        refresh();
    });

    $items.on('click', '.btn-remove-item', function() {
        $(this).closest('.pack-item-row').remove();
        refresh();
    });

    $items.on('input change', '.item-qty, .item-included', refresh);

    refresh();
});
</script>
