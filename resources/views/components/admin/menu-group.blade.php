{{--
    A sidebar section: a small uppercase label followed by its menu items.
    Nothing is printed when none of the items inside is visible to the logged-in admin (each
    item keeps its own @can check), so no empty labels appear.
    The label collapses/expands its items (public/backEnd/assets/js/admin-sidebar.js).

    Usage: <x-admin.menu-group group="sales" label="Sales"> ...<li> items... </x-admin.menu-group>
--}}
@props(['group', 'label', 'collapsed' => false])

@if($slot->hasActualContent())
<li class="bg-menu-group" data-menu-group="{{ $group }}" data-default-collapsed="{{ $collapsed ? 1 : 0 }}">
    <button type="button" class="bg-menu-group-toggle" aria-expanded="true">
        <span class="bg-menu-group-label">{{ $label }}</span>
        <span class="bg-menu-group-badge" hidden></span>
        <i class="fe-chevron-down bg-menu-group-chevron" aria-hidden="true"></i>
    </button>
</li>
{{ $slot }}
@endif
