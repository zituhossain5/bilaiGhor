/*
 * Grouped admin sidebar (resources/views/backEnd/layouts/partials/sidebar.blade.php):
 *  - each section label collapses/expands the items after it; the state is remembered per admin
 *    in localStorage (default: all open except Settings); the section of the current page opens
 *  - a collapsed section shows its items' badges (e.g. Inventory low stock) next to the label
 *  - "Search menu…" filters items and submenu items; "/" focuses it, Esc clears it,
 *    Enter opens the first match
 *  - scrolls to the active item on load; icon-only mode gets item names as tooltips
 */
(function () {
    'use strict';

    var root = document.getElementById('sidebar-menu');
    var menu = document.getElementById('side-menu');
    if (!root || !menu) {
        return;
    }

    var storageKey = 'bg.sidebar.groups.' + (root.getAttribute('data-admin-id') || '0');
    var input = document.getElementById('bg-menu-search');
    var empty = root.querySelector('.bg-menu-empty');
    var children = Array.prototype.slice.call(menu.children);
    var groups = [];
    var items = [];

    // ---------------------------------------------------------------- helpers
    function readState() {
        try {
            var saved = JSON.parse(window.localStorage.getItem(storageKey) || '{}');
            return saved && typeof saved === 'object' ? saved : {};
        } catch (e) {
            return {};
        }
    }

    function saveGroup(key, collapsed) {
        try {
            var state = readState();
            state[key] = collapsed ? 'collapsed' : 'expanded';
            window.localStorage.setItem(storageKey, JSON.stringify(state));
        } catch (e) {
            // Private mode / storage blocked: the sidebar still works, it just won't remember.
        }
    }

    function topLink(li) {
        var first = li.firstElementChild;
        return first && first.tagName === 'A' ? first : null;
    }

    // A link's own words: text without badges, arrows and icons.
    function nameOf(a) {
        var parts = [];
        Array.prototype.forEach.call(a.childNodes, function (node) {
            if (node.nodeType === 3) {
                parts.push(node.textContent);
            } else if (node.nodeType === 1 && !/^(svg|i)$/i.test(node.tagName)
                && !node.classList.contains('badge') && !node.classList.contains('menu-arrow')) {
                parts.push(node.textContent);
            }
        });
        return parts.join(' ').replace(/\s+/g, ' ').trim();
    }

    function isCondensed() {
        return document.body.getAttribute('data-leftbar-size') === 'condensed';
    }

    // ---------------------------------------------------------------- structure
    // Every label owns the items after it, up to the next label. Dashboard has no section.
    var current = null;
    children.forEach(function (li) {
        if (li.classList.contains('bg-menu-group')) {
            current = {
                key: li.getAttribute('data-menu-group'),
                label: li,
                button: li.querySelector('.bg-menu-group-toggle'),
                badge: li.querySelector('.bg-menu-group-badge'),
                defaultCollapsed: li.getAttribute('data-default-collapsed') === '1',
                collapsed: false,
                items: []
            };
            groups.push(current);
            return;
        }

        var a = topLink(li);
        var item = {
            li: li,
            group: current,
            name: a ? nameOf(a).toLowerCase() : '',
            subs: Array.prototype.map.call(li.querySelectorAll('.nav-second-level > li'), function (sub) {
                var link = sub.querySelector('a');
                return { li: sub, name: link ? nameOf(link).toLowerCase() : '' };
            }),
            collapse: li.querySelector(':scope > .collapse')
        };
        items.push(item);
        if (current) {
            current.items.push(item);
        }
    });

    function groupIsActive(group) {
        return group.items.some(function (item) {
            return item.li.classList.contains('menuitem-active') || item.li.querySelector('a.active');
        });
    }

    // ---------------------------------------------------------------- collapse + badges
    function updateBadge(group) {
        var total = 0;
        group.items.forEach(function (item) {
            var a = topLink(item.li);
            if (!a) {
                return;
            }
            Array.prototype.forEach.call(a.querySelectorAll('.badge'), function (badge) {
                var count = parseInt(badge.textContent, 10);
                if (count > 0) {
                    total += count;
                }
            });
        });
        group.badge.textContent = total > 99 ? '99+' : String(total);
        group.badge.hidden = !(group.collapsed && total > 0);
        group.badge.setAttribute('title', total + ' item(s) need attention');
    }

    function setCollapsed(group, collapsed) {
        group.collapsed = collapsed;
        group.label.classList.toggle('is-collapsed', collapsed);
        group.button.setAttribute('aria-expanded', String(!collapsed));
        group.items.forEach(function (item) {
            item.li.classList.toggle('bg-group-hidden', collapsed);
        });
        updateBadge(group);
    }

    var saved = readState();
    groups.forEach(function (group) {
        var collapsed = Object.prototype.hasOwnProperty.call(saved, group.key)
            ? saved[group.key] === 'collapsed'
            : group.defaultCollapsed;
        // The section of the current page is always open on load.
        setCollapsed(group, collapsed && !groupIsActive(group));

        group.button.addEventListener('click', function () {
            setCollapsed(group, !group.collapsed);
            saveGroup(group.key, group.collapsed);
        });
    });

    // ---------------------------------------------------------------- search
    var openedBySearch = [];

    function search(raw) {
        var query = raw.replace(/\s+/g, ' ').trim().toLowerCase();

        Array.prototype.forEach.call(root.querySelectorAll('.bg-search-hidden'), function (el) {
            el.classList.remove('bg-search-hidden');
        });
        openedBySearch.forEach(function (collapse) {
            collapse.classList.remove('show');
        });
        openedBySearch = [];
        root.classList.toggle('is-searching', query !== '');

        if (query === '') {
            empty.hidden = true;
            return;
        }

        var found = false;
        items.forEach(function (item) {
            var selfMatch = item.name.indexOf(query) !== -1;
            var subMatch = false;

            item.subs.forEach(function (sub) {
                var match = sub.name !== '' && sub.name.indexOf(query) !== -1;
                subMatch = subMatch || match;
                // A matching item shows its whole submenu; otherwise only the matching entries.
                sub.li.classList.toggle('bg-search-hidden', !selfMatch && !match);
            });

            var visible = selfMatch || subMatch;
            item.li.classList.toggle('bg-search-hidden', !visible);
            found = found || visible;

            if (subMatch && !selfMatch && item.collapse && !item.collapse.classList.contains('show')) {
                item.collapse.classList.add('show');
                openedBySearch.push(item.collapse);
            }
        });

        groups.forEach(function (group) {
            var any = group.items.some(function (item) {
                return !item.li.classList.contains('bg-search-hidden');
            });
            group.label.classList.toggle('bg-search-hidden', !any);
        });

        empty.hidden = found;
    }

    function clearSearch() {
        if (input.value !== '' || root.classList.contains('is-searching')) {
            input.value = '';
            search('');
        }
    }

    function firstMatch() {
        var links = root.querySelectorAll('#side-menu a[href]');
        for (var i = 0; i < links.length; i++) {
            var href = links[i].getAttribute('href') || '';
            if (href.charAt(0) !== '#' && links[i].offsetParent !== null) {
                return links[i];
            }
        }
        return null;
    }

    if (input) {
        input.addEventListener('input', function () {
            search(input.value);
        });
        input.addEventListener('search', function () {
            search(input.value);
        });
        input.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                event.preventDefault();
                clearSearch();
                input.blur();
            } else if (event.key === 'Enter') {
                var link = firstMatch();
                if (link && root.classList.contains('is-searching')) {
                    event.preventDefault();
                    link.click(); // keeps a link's own confirm() (e.g. Clear Cache)
                }
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key !== '/' || event.ctrlKey || event.metaKey || event.altKey) {
                return;
            }
            var target = event.target;
            if (target && (target.isContentEditable || /^(INPUT|TEXTAREA|SELECT)$/.test(target.tagName))) {
                return;
            }
            if (input.offsetParent === null) {
                return; // icon-only sidebar, or the mobile menu is closed
            }
            event.preventDefault();
            input.focus();
            input.select();
        });
    }

    // ---------------------------------------------------------------- icon-only mode
    // Labels and search are hidden by CSS; item names become tooltips, and a search in progress
    // is cleared so no item stays hidden.
    function syncCondensed() {
        var condensed = isCondensed();
        items.forEach(function (item) {
            var a = topLink(item.li);
            if (!a) {
                return;
            }
            if (condensed) {
                a.setAttribute('title', nameOf(a));
            } else {
                a.removeAttribute('title');
            }
        });
        if (condensed && input) {
            clearSearch();
        }
    }

    function syncSoon() {
        window.setTimeout(syncCondensed, 60);
    }

    Array.prototype.forEach.call(document.querySelectorAll('.button-menu-mobile'), function (button) {
        button.addEventListener('click', syncSoon);
    });
    window.addEventListener('resize', syncSoon);
    syncCondensed();

    // ---------------------------------------------------------------- active item
    // The theme highlights exact link matches; the partial also marks an item active on all of
    // its pages. Open that section (the theme may mark it after this script ran) and bring the
    // item into view.
    function revealActive() {
        groups.forEach(function (group) {
            if (group.collapsed && groupIsActive(group)) {
                setCollapsed(group, false);
            }
        });

        var target = menu.querySelector('li.menuitem-active a.active')
            || menu.querySelector(':scope > li.menuitem-active > a');
        var wrapper = document.querySelector('.left-side-menu .simplebar-content-wrapper');
        if (!target || !wrapper || isCondensed()) {
            return;
        }

        var box = wrapper.getBoundingClientRect();
        var rect = target.getBoundingClientRect();
        if (rect.top < box.top + 40 || rect.bottom > box.bottom - 40) {
            wrapper.scrollTop += rect.top - box.top - box.height / 3;
        }
    }

    if (document.readyState === 'complete') {
        window.setTimeout(revealActive, 900);
    } else {
        window.addEventListener('load', function () {
            window.setTimeout(revealActive, 900);
        });
    }
}());
