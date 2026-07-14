{{-- Shared listing JS: price range slider, filter auto-submit, sort submit,
     sidebar section toggles, SEO accordion toggle (no-op when absent).
     Expects: $min_price, $max_price. Push inside @push('script'). --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script>
<script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.11.2/jquery-ui.min.js"></script>
<script>
$(function () {
    var minP   = {{ $min_price ?? 0 }};
    var maxP   = {{ $max_price ?? 10000 }};
    var curMin = {{ request('min_price') ?: ($min_price ?? 0) }};
    var curMax = {{ request('max_price') ?: ($max_price ?? 10000) }};

    $("#bilai-price-range").slider({
        range: true, step: 5, min: minP, max: maxP,
        values: [curMin, curMax],
        slide: function (event, ui) {
            $("#bilai-min-val").text(ui.values[0]);
            $("#bilai-max-val").text(ui.values[1]);
            $("#bilai_min_price").val(ui.values[0]);
            $("#bilai_max_price").val(ui.values[1]);
        },
        stop: function () { $("#bilaiCatFilterForm").submit(); }
    });
    $("#bilai-min-val").text(curMin);
    $("#bilai-max-val").text(curMax);

    $(".bilai-cat-auto-submit").on("change", function () {
        $("#bilaiCatFilterForm").submit();
    });

    $("#bilaiSortSelect").on("change", function () {
        $("#bilaiSortForm").submit();
    });

    $(".bilai-cat-filter-toggle").on("click", function () {
        var target = $(this).data("target");
        $("#" + target).slideToggle(200);
        $(this).find(".bilai-cat-toggle-icon").toggleClass("fa-chevron-up fa-chevron-down");
    });

    $("#bilaiSeoToggle").on("click", function () {
        var expanded = $(this).attr("aria-expanded") === "true";
        $(this).attr("aria-expanded", String(!expanded));
        $(this).find(".bilai-seo-icon").toggleClass("fa-chevron-down fa-chevron-up");
        $("#bilaiSeoBody").slideToggle(250);
    });
});
</script>
