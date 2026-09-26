<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Every <img> inside rich-text (Summernote) content must carry non-empty alt text.
 * Alt is entered in the editor's Image Attributes dialog, which opens on insert.
 */
class RichTextImagesHaveAlt implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $missing = self::countMissing((string) $value);

        if ($missing > 0) {
            $fail($missing === 1
                ? 'An image in the :attribute has no alt text. Click the image in the editor and use the Image Attributes (alt) button to describe it.'
                : "{$missing} images in the :attribute have no alt text. Click each image in the editor and use the Image Attributes (alt) button to describe it.");
        }
    }

    /** Number of <img> tags with no alt attribute, or an empty/whitespace-only one. */
    public static function countMissing(string $html): int
    {
        if (stripos($html, '<img') === false) {
            return 0;
        }

        preg_match_all('/<img\b[^>]*>/i', $html, $tags);

        return count(array_filter($tags[0], function (string $tag) {
            return !preg_match('/\balt\s*=\s*(["\'])\s*[^"\'\s][^"\']*\1/i', $tag);
        }));
    }
}
