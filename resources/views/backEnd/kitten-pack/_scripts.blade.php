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
});
</script>
