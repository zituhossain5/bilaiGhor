<?php

namespace App\Support;

use Illuminate\Support\Str;

class SeoText
{
    public static function plain(?string $value, ?int $limit = null): ?string
    {
        if ($value === null) {
            return null;
        }

        // A space at block-level tags, so "<p>85g</p><p>Overview</p>" doesn't become "85gOverview".
        $value = preg_replace('#<(?:br|/?(?:p|div|li|ul|ol|h[1-6]|tr|td|th|table|section|article|blockquote))[^>]*>#i', ' $0', $value);
        $text = html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', $text);
        $text = trim((string) $text);

        if ($text === '') {
            return null;
        }

        return $limit ? Str::limit($text, $limit, '') : $text;
    }
}
