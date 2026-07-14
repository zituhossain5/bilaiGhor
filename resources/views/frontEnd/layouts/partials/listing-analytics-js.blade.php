{{-- Shared listing analytics (GA4 view_item_list + Facebook Pixel custom event).
     Expects: $products, $listName, $listSlug, $fbEvent. Push inside @push('script'). --}}
<script type="text/javascript">
    window.dataLayer = window.dataLayer || [];
    (function () {
        var listName = @json($listName);
        var listSlug = @json($listSlug);
        var listItems = [
            @foreach($products as $index => $value)
            {
                item_id: "{{ $value->id }}",
                item_name: @json($value->name),
                price: {{ (float) $value->new_price }},
                item_brand: @json(optional($value->brand)->name),
                item_category: @json(optional($value->category)->name ?? $listName),
                item_list_id: listSlug,
                item_list_name: listName,
                index: {{ $loop->iteration }},
                slug: @json($value->slug),
                currency: "BDT"
            }@if(!$loop->last),@endif
            @endforeach
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
            fbq("trackCustom", @json($fbEvent), {
                content_category: listName,
                content_ids: listItems.map(function (i) { return i.item_id; }),
                currency: "BDT"
            });
        }
    })();
</script>
