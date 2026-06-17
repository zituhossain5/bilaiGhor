# UI Changelog — BilaiGhor Header & Footer (Figma Redesign)

## Summary
Replaced the existing header and footer in the Laravel frontend layout with a new design
matching the Figma screenshot. All backend logic, controllers, routes, cart, auth, and
homepage content sections are untouched.

---

## 1. Modified Files

| File | Change |
|------|--------|
| `resources/views/frontEnd/layouts/master.blade.php` | Added font/CSS links in `<head>`; replaced header HTML (old `.main-header` / `.menu-area`); replaced footer HTML (old `.footer-v2`); added mega menu JS block |

## 2. Newly Created Files

| File | Purpose |
|------|---------|
| `public/frontEnd/css/bilai-header-footer.css` | All header & footer CSS. Contains CSS variables at the top for easy colour/font changes. |
| `UI_CHANGELOG_BILAI_HEADER_FOOTER.md` | This file |

---

## 3. What Each File Does

### `public/frontEnd/css/bilai-header-footer.css`
Complete stylesheet for the new header and footer. Scoped exclusively to `.bilai-*`
class names. Includes:
- CSS design tokens (`:root` variables)
- Top info bar (`.bilai-topbar`)
- Mobile header (`.bilai-mobile-header`) + mobile search (`.bilai-mobile-search`)
- Main desktop header (`.bilai-main-header`) with logo, search, and action buttons
- Navigation bar (`.bilai-nav`) with "ALL CATEGORIES" button
- Mega menu (`.bilai-mega-menu`, `.bilai-mega-col`)
- Footer grid (`.bilai-footer`) — brand column, category list, quick links, contact+newsletter
- Footer bottom bar with copyright

### `resources/views/frontEnd/layouts/master.blade.php`
The single Blade layout file. Changes:
- `<head>`: added Google Fonts (DM Sans) `<link>` and `bilai-header-footer.css` `<link>`
- Lines ~509–730 (old header): replaced with new `{{-- BilaiGhor Figma Header Start --}}` block
- Lines ~730–810 (old footer): replaced with new `{{-- BilaiGhor Figma Footer Start --}}` block
- After sidebar-cart JS: added mega menu vanilla-JS init block

---

## 4. How to Change Colors Later

Open `public/frontEnd/css/bilai-header-footer.css` and edit the `:root` block at the **very top**:

```css
:root {
  --bilai-primary:       #F28C00;   /* orange — buttons, accents */
  --bilai-primary-dark:  #C96F00;   /* darker orange — hover */
  --bilai-brown:         #3A1F0F;   /* nav bar background, footer background */
  --bilai-brown-dark:    #241207;   /* top info bar, footer bottom bar */
  --bilai-cream:         #FFF6E8;   /* main header background */
  --bilai-light:         #FFFDF8;   /* search input background */
  --bilai-border:        #F0DCC2;   /* border colour */
  --bilai-text:          #2B1A10;   /* main dark text */
  --bilai-muted:         #7A6A5E;   /* placeholder / muted text */
}
```

Changing any value here automatically propagates to every element that uses that token.

---

## 5. How to Change Font Later

In the same `:root` block, update:

```css
--bilai-font-main: 'DM Sans', sans-serif;
```

If switching to a different Google Font, also update the `<link>` tag in
`master.blade.php` inside `<head>` (look for the comment `{{-- BilaiGhor Figma — DM Sans font --}}`).

---

## 6. Where Mega Menu Blade Code Is

File: `resources/views/frontEnd/layouts/master.blade.php`

Search for: `{{-- BilaiGhor Figma Header Start --}}`

The mega menu HTML is inside the `<nav class="bilai-nav">` block, wrapped in:
```html
<div class="bilai-nav__cat-wrapper" id="bilaiCatWrapper">
    <button class="bilai-nav__cat-btn" id="bilaiCatBtn">...</button>
    <div class="bilai-mega-menu" id="bilaiMegaMenu">
        <div class="bilai-mega-menu__grid">
            @foreach($menucategories as $category) ...
```

It uses the same `$menucategories` variable (with eager-loaded `subcategories`) that the
old header used. Shows up to 5 subcategories per column; shows "View all" link if more exist.

---

## 7. Where Mobile Menu JS Is

The **sidebar mobile menu** (slides in from the left) is controlled by existing files:
- `public/frontEnd/js/mobile-menu.js`
- `public/frontEnd/js/mobile-menu-init.js`

These are untouched. The hamburger buttons in the new header have class `toggle` which
triggers the existing JS to open `.mobile-menu` sidebar.

The **mega menu** (desktop category dropdown) JS is a new vanilla-JS block added directly
in `master.blade.php` after the sidebar-cart JS (search for `BilaiGhor Mega Menu`).

---

## 8. Assumptions Made

1. **`$contact` is globally available** — The existing chat widget used `$contact->hotline`
   and `$contact->whatsapp` without `isset()` checks, so it must be shared globally
   (likely via ionCube-encrypted `AppServiceProvider.php`). Contact fields are wrapped in
   `optional()` as an extra safety net.

2. **`$socialicons`, `$pages`, `$pagesright`, `$menucategories` are globally available** —
   Same reasoning; the old footer used them without checks.

3. **`$contact->email` field name** — The field may be `email` or `mail` depending on
   the Contact model. The template checks both: `$contact->email ?? $contact->mail`.

4. **App download links** — Only shown when `google_play_link` or `app_store_link` are
   set in general settings. Otherwise the section is hidden.

5. **`cart_count()` AJAX compatibility** — The existing `cart_count()` function replaces
   the HTML of `#cart-qty`. In the new design `#cart-qty` is a hidden `<span>` so AJAX
   updates don't break the visible cart button. Cart count updates come from `mobile_cart()`
   which updates all `.mobilecart-qty` elements (used on the new cart badge).

6. **Logo image** — Uses `dark_logo` from general settings (same as before). If
   `white_logo` is set it is preferred in the footer for better visibility on dark bg.

7. **Old `.footer-v2` CSS** — The old footer CSS is kept as inline `<style>` in
   `master.blade.php` (lines ~60-405). It is now dead code (no `.footer-v2` HTML elements
   exist). It is harmless and was left in place to avoid risky edits to a large inline
   style block. Can be removed manually in a future cleanup.

---

## 9. Remaining Manual Steps

- [ ] **Test on mobile** — Check hamburger opens sidebar menu, search bar is usable,
      cart icon shows correct count.
- [ ] **Test mega menu** — On desktop, click "ALL CATEGORIES" to verify dropdown opens
      and shows categories with subcategories.
- [ ] **Verify contact fields** — If `$contact->email` is not found in the footer/topbar,
      the Contact model field might be named differently. Check `App\Models\Contact` schema.
- [ ] **Check `white_logo`** — Footer logo uses `white_logo` for visibility on dark bg.
      If not set in admin settings, it falls back to `dark_logo`. Upload a white/light logo
      in admin → General Settings for best appearance.
- [ ] **Verify newsletter route** — `route('frontend.newsletter.subscribe')` must exist.
      This is the same route used in the old footer.
- [ ] **Optional: Remove old footer-v2 CSS** — The inline `<style>` block containing
      `.footer-v2`, `.footer-v2__wave`, etc. (~lines 60-405 in master.blade.php) is now
      dead code. Can be safely deleted.
- [ ] **Optional: Remove `wsit-menu.js` / `wsit-menu.css`** — These files handled the
      old sidebar category dropdown. They're still loaded but have no matching elements;
      they silently do nothing. Safe to remove the `<link>` and `<script>` includes.
- [ ] **Font rendering** — DM Sans loads from Google Fonts CDN. Verify it loads correctly
      in production. If CDN is blocked, self-host the font files.
