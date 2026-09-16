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

        $text = html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', $text);
        $text = trim((string) $text);

        if ($text === '') {
            return null;
        }

        return $limit ? Str::limit($text, $limit, '') : $text;
    }
}
