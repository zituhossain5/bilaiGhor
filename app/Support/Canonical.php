<?php

namespace App\Support;

/**
 * Self-referencing canonical URL: the current path without query strings (sort, filters, utm_*, fbclid…).
 * Paginated listings keep their page number, so page 2 is canonical to page 2.
 */
class Canonical
{
    public static function current(?string $url = null): string
    {
        $url ??= url()->current();
        $page = (int) request()->query('page', 1);

        return $page > 1 ? $url . '?page=' . $page : $url;
    }
}
