{{--
    District → Thana loader shared by admin forms (customer form, manual invoice).
    BgThanaLoader.load(thanaSelect, districtId, selectedThanaId) fills the select from
    /ajax/delivery/thanas/{district} and resolves true once the list is in place — or false when
    a newer request for the same select replaced it, so a slow, stale reply never wins.
--}}
<script>
window.BgThanaLoader = window.BgThanaLoader || (function () {
    var base = @json(url('/ajax/delivery/thanas'));
    var latest = new WeakMap();

    function placeholder(select, text) {
        select.innerHTML = '';
        var option = document.createElement('option');
        option.value = '';
        option.textContent = text;
        select.appendChild(option);
    }

    function load(select, districtId, selectedThanaId) {
        var token = {};
        latest.set(select, token);
        select.disabled = true;

        if (!districtId) {
            placeholder(select, 'Select District First');
            return Promise.resolve(true);
        }
        placeholder(select, 'Loading Thanas...');

        return fetch(base + '/' + encodeURIComponent(districtId), { headers: { 'Accept': 'application/json' } })
            .then(function (response) { return response.json(); })
            .then(function (response) {
                if (latest.get(select) !== token) return false;
                placeholder(select, 'Select Thana');
                (response.data || []).forEach(function (row) {
                    var option = document.createElement('option');
                    option.value = row.id;
                    option.textContent = row.name_bn || row.name;
                    option.dataset.charge = row.delivery_charge || 0;
                    option.dataset.postCode = row.post_code || '';
                    if (String(row.id) === String(selectedThanaId || '')) option.selected = true;
                    select.appendChild(option);
                });
                select.disabled = false;
                return true;
            })
            .catch(function () {
                if (latest.get(select) !== token) return false;
                placeholder(select, 'Could not load thanas — pick the district again');
                select.disabled = false;
                return false;
            });
    }

    return { load: load };
}());
</script>
