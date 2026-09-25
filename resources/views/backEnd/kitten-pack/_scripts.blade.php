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
    // Rows are indexed by position on submit, so gaps left by removals do not matter.
    var nextIndex = $('#pack_items .pack-item-row').length;

    $('#add_item_row').on('click', function() {
        var row = '' +
            '<div class="pack-item-row">' +
                '<input type="text" name="items[' + nextIndex + '][name]" class="form-control" placeholder="Item name">' +
                '<input type="number" name="items[' + nextIndex + '][quantity]" class="form-control" min="1" value="1" placeholder="Qty">' +
                '<label class="pack-item-check">' +
                    '<input type="checkbox" name="items[' + nextIndex + '][is_included]" value="1" checked>' +
                    '<span>Included</span>' +
                '</label>' +
                '<button type="button" class="btn-remove-row" aria-label="Remove item"><i class="fe-x"></i></button>' +
            '</div>';

        $('#pack_items').append(row);
        nextIndex++;
    });

    $('#pack_items').on('click', '.btn-remove-row', function() {
        if ($('#pack_items .pack-item-row').length > 1) {
            $(this).closest('.pack-item-row').remove();
        } else {
            $(this).closest('.pack-item-row').find('input[type="text"], input[type="number"]').val('');
        }
    });

    // ---- Pack components ----
    var $components = $('#pack_components');
    var nextComponent = $components.find('.component-row').length;

    $('#component_product').select2({ width: '100%', placeholder: 'Select a product…' });

    // Mirrors KittenPack::available_stock: the scarcest component decides.
    function refreshComponents() {
        var $rows = $components.find('.component-row');
        var packs = null;

        $rows.each(function() {
            var stock = parseInt($(this).data('stock'), 10) || 0;
            var qty = Math.max(1, parseInt($(this).find('.component-qty').val(), 10) || 1);
            var canBuild = Math.floor(Math.max(0, stock) / qty);
            packs = packs === null ? canBuild : Math.min(packs, canBuild);
        });

        $components.find('.component-empty').prop('hidden', $rows.length > 0);
        $('#component_available').text(packs === null ? 0 : packs);
    }

    function hint(message) {
        $('#component_hint').text(message).toggleClass('d-none', !message);
    }

    $('#add_component').on('click', function() {
        var $option = $('#component_product option:selected');
        var productId = $option.val();
        var qty = Math.max(1, parseInt($('#component_qty').val(), 10) || 1);

        if (!productId) {
            hint('Pick a product first.');
            return;
        }

        // Already listed: edit its quantity there instead of adding a duplicate row.
        var $existing = $components.find('.component-row[data-product-id="' + productId + '"]');
        if ($existing.length) {
            hint('This product is already in the pack — change its quantity below.');
            $existing.addClass('is-highlighted').find('.component-qty').trigger('focus').trigger('select');
            setTimeout(function() { $existing.removeClass('is-highlighted'); }, 1500);
            return;
        }

        var stock = parseInt($option.data('stock'), 10) || 0;
        var name = $.trim($option.text().replace(/\s—\sstock\s-?\d+$/, ''));
        var i = nextComponent++;

        var $row = $('<tr class="component-row">')
            .attr('data-product-id', productId)
            .attr('data-stock', stock);

        $row.append(
            $('<td>').text(name).append(
                $('<input type="hidden">').attr('name', 'components[' + i + '][product_id]').val(productId)
            ),
            $('<td>').append($('<span class="stock-badge">').text(stock)),
            $('<td>').append(
                $('<input type="number" class="form-control component-qty" min="1" required>')
                    .attr('name', 'components[' + i + '][quantity]').val(qty)
            ),
            $('<td>').append('<button type="button" class="btn-remove-row btn-remove-component" aria-label="Remove component"><i class="fe-x"></i></button>')
        );

        $components.find('.component-empty').before($row);
        $('#component_product').val('').trigger('change');
        $('#component_qty').val(1);
        hint('');
        refreshComponents();
    });

    $components.on('click', '.btn-remove-component', function() {
        $(this).closest('.component-row').remove();
        refreshComponents();
    });

    $components.on('input change', '.component-qty', refreshComponents);

    refreshComponents();
});
</script>
