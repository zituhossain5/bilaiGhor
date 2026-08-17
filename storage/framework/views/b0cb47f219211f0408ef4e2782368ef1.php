
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script>
<script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.11.2/jquery-ui.min.js"></script>
<script>
$(function () {
    var minP   = <?php echo e($min_price ?? 0); ?>;
    var maxP   = <?php echo e($max_price ?? 10000); ?>;
    var curMin = <?php echo e(request('min_price') ?: ($min_price ?? 0)); ?>;
    var curMax = <?php echo e(request('max_price') ?: ($max_price ?? 10000)); ?>;

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

    // Brand search: filter the visible brand rows by name (client-side).
    var $brandSearch = $("#bilaiBrandSearch");
    if ($brandSearch.length) {
        $brandSearch.on("keyup input", function () {
            var q = $.trim(this.value).toLowerCase();
            $("#bilaiBrandList > li").each(function () {
                var name = $(this).find(".bilai-cat-attr-name").text().toLowerCase();
                $(this).toggle(name.indexOf(q) !== -1);
            });
        });
        // Enter inside the search must not submit the filter form.
        $brandSearch.on("keydown", function (e) {
            if (e.key === "Enter" || e.keyCode === 13) { e.preventDefault(); }
        });
    }

    $("#bilaiSeoToggle").on("click", function () {
        var expanded = $(this).attr("aria-expanded") === "true";
        $(this).attr("aria-expanded", String(!expanded));
        $(this).find(".bilai-seo-icon").toggleClass("fa-chevron-down fa-chevron-up");
        $("#bilaiSeoBody").slideToggle(250);
    });
});
</script>
<?php /**PATH C:\laragon\www\bilaiGhor\resources\views\frontEnd\layouts\partials\listing-js.blade.php ENDPATH**/ ?>