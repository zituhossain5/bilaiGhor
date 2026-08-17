
<script type="text/javascript">
    window.dataLayer = window.dataLayer || [];
    (function () {
        var listName = <?php echo json_encode($listName, 15, 512) ?>;
        var listSlug = <?php echo json_encode($listSlug, 15, 512) ?>;
        var listItems = [
            <?php $__currentLoopData = $products; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            {
                item_id: "<?php echo e($value->id); ?>",
                item_name: <?php echo json_encode($value->name, 15, 512) ?>,
                price: <?php echo e((float) $value->new_price); ?>,
                item_brand: <?php echo json_encode(optional($value->brand)->name, 15, 512) ?>,
                item_category: <?php echo json_encode(optional($value->category)->name ?? $listName, 15, 512) ?>,
                item_list_id: listSlug,
                item_list_name: listName,
                index: <?php echo e($loop->iteration); ?>,
                slug: <?php echo json_encode($value->slug, 15, 512) ?>,
                currency: "BDT"
            }<?php if(!$loop->last): ?>,<?php endif; ?>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        ];
        if (listItems.length) {
            window.dataLayer.push({ ecommerce: null });
            window.dataLayer.push({
                event: "view_item_list",
                ecommerce: {
                    item_list_id: listSlug, item_list_name: listName,
                    items: listItems.map(function (item) {
                        return { item_id: item.item_id, item_name: item.item_name, index: item.index,
                            price: item.price, item_brand: item.item_brand, item_category: item.item_category,
                            item_list_id: item.item_list_id, item_list_name: item.item_list_name, currency: item.currency };
                    })
                }
            });
        }
        if (typeof fbq === "function") {
            fbq("trackCustom", <?php echo json_encode($fbEvent, 15, 512) ?>, {
                content_category: listName,
                content_ids: listItems.map(function (i) { return i.item_id; }),
                currency: "BDT"
            });
        }
    })();
</script>
<?php /**PATH C:\laragon\www\bilaiGhor\resources\views\frontEnd\layouts\partials\listing-analytics-js.blade.php ENDPATH**/ ?>