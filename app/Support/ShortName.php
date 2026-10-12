<?php

namespace App\Support;

use App\Models\Product;

/**
 * Short product names for printed receipts.
 *
 * suggest()  turns "Whiskas Adult Pouch Ocean Fish Flavor Wet Cat Food – 80g" into
 *            "Whiskas Ocean Fish" (weights, filler words and redundant packaging words removed).
 * fallback() is what prints when a product has no short name: the full name cut at a word
 *            boundary with "…".
 */
class ShortName
{
    /** Weights, volumes and pack sizes: "- 70g", "80 gm", "1.5kg", "(5x14g)", "5*15g", "10 Ltr", "1pcs", "(1 Tablet)". */
    private const MEASURE = '/\(?\s*(?<![\p{L}\p{N}.])\d+(?:[.,]\d+)?\s*(?:[x×*]\s*\d+(?:[.,]\d+)?\s*)?'
        . '(?:kgs?|g|gms?|grams?|mg|ml|l|ltrs?|litres?|liters?|lbs?|oz|pcs|pc|pieces?|tabs?|tablets?)(?![\p{L}\p{N}])\.?\s*\)?/iu';

    /** Ages: "1+ Years", "2-12 Months". */
    private const AGE = '/(?<![\p{L}\p{N}.])\d+(?:\s*[-–]\s*\d+)?\s*\+?\s*(?:years?|yrs?|months?)(?![\p{L}\p{N}])\+?/iu';

    /** Words that can't end (or start) a name once the rest was removed. */
    private const DANGLING = '(?:and|with|in|of|for|&|\+)';

    public static function max(): int
    {
        return (int) config('receipt.short_name_max', 40);
    }

    /** A suggested short name for a full product name. The admin can always edit it. */
    public static function suggest(?string $name): string
    {
        $original = self::clean($name);
        if ($original === '') {
            return '';
        }

        $text = preg_replace([self::MEASURE, self::AGE], ' ', $original);

        $fillers = collect(config('receipt.filler_words', []))->sortByDesc(fn ($phrase) => mb_strlen($phrase));
        foreach ($fillers as $phrase) {
            $text = preg_replace(self::wordPattern($phrase), ' ', $text);
        }
        $text = self::tidy($text);

        // Packaging words go only when enough words are left to tell the product apart.
        $keepBelow = (int) config('receipt.format_words_keep_below', 3);
        foreach (config('receipt.format_words', []) as $word) {
            $without = self::tidy(preg_replace(self::wordPattern($word), ' ', $text));
            if ($without !== $text && self::wordCount($without) >= $keepBelow) {
                $text = $without;
            }
        }

        $text = self::cut($text, self::max());

        return $text !== '' ? $text : self::cut($original, self::max());
    }

    /** The full name, cut at a word boundary to fit $max characters (with "…" when cut). */
    public static function fallback(?string $name, ?int $max = null): string
    {
        $max ??= self::max();
        $text = self::clean($name);

        if (mb_strlen($text) <= $max) {
            return $text;
        }

        return self::cut($text, $max - 1) . '…';
    }

    /** The name a receipt line uses: the product's short name, otherwise the line's name shortened. */
    public static function forLine(?Product $product, ?string $name): string
    {
        $short = self::clean($product?->short_name);

        return $short !== '' ? $short : self::fallback($name ?: $product?->name);
    }

    private static function clean(?string $text): string
    {
        return trim(preg_replace('/[\s\x{00A0}]+/u', ' ', (string) $text));
    }

    /** Whole-word, case-insensitive match for a phrase ("Cat  Food" matches "Cat Food"). */
    private static function wordPattern(string $phrase): string
    {
        $words = array_map(fn ($word) => preg_quote($word, '/'), preg_split('/\s+/', trim($phrase)));

        return '/(?<![\p{L}\p{N}])' . implode('\s+', $words) . '(?![\p{L}\p{N}])/iu';
    }

    /** Collapse leftovers: empty brackets, stray dashes/commas, dangling "and"/"with". */
    private static function tidy(string $text): string
    {
        $text = preg_replace('/\(\s*\)|\[\s*\]/u', ' ', $text);
        $text = preg_replace('/\s+[-–—|,;:\/]+(?=\s|$)/u', ' ', ' ' . $text . ' ');
        $text = self::clean($text);

        do {
            $before = $text;
            $text = preg_replace('/^[\s\-–—|,;:\/&+.]+|[\s\-–—|,;:\/&+]+$/u', '', $text);
            $text = preg_replace('/\s+' . self::DANGLING . '$/iu', '', $text);
            $text = preg_replace('/^' . self::DANGLING . '\s+/iu', '', $text);
        } while ($text !== $before);

        return self::clean($text);
    }

    /** Cut to $max characters at the last space (or hard, when the only space is too early). */
    private static function cut(string $text, int $max): string
    {
        if (mb_strlen($text) <= $max) {
            return $text;
        }

        $head = mb_substr($text, 0, $max + 1);
        $space = mb_strrpos($head, ' ');
        $head = ($space !== false && $space >= (int) floor($max / 2))
            ? mb_substr($head, 0, $space)
            : mb_substr($text, 0, $max);

        return self::tidy($head);
    }

    private static function wordCount(string $text): int
    {
        return preg_match_all('/[\p{L}\p{N}][\p{L}\p{N}\'.-]*/u', $text);
    }
}
