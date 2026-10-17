<?php

namespace App\Services\ProductImport;

use App\Support\ShortName;

/**
 * Values filled in for empty import cells. Each rule is used only when the cell (and, for an
 * existing product, the saved value) is empty; nothing typed in the file is ever replaced.
 */
class ProductAutoFill
{
    public const TITLE_MAX = 60;
    public const DESCRIPTION_MAX = 155;
    private const TITLE_SUFFIX = ' Price in Bangladesh';
    private const VIEWS = ['back view', 'side view', 'detail view'];

    /** Same as the product form's "Auto-generate" button. */
    public static function shortName(string $name): string
    {
        return ShortName::suggest($name);
    }

    /**
     * "{Name} Price in Bangladesh" within ~60 characters. A long name is replaced by the short name
     * ("Whiskas Ocean Fish Price in Bangladesh"); failing that, the name is cut at a word boundary.
     */
    public static function metaTitle(string $name, ?string $shortName = null): string
    {
        $room = self::TITLE_MAX - mb_strlen(self::TITLE_SUFFIX);
        foreach ([$name, $shortName] as $candidate) {
            $candidate = self::squash((string) $candidate);
            if ($candidate !== '' && mb_strlen($candidate) <= $room) {
                return $candidate . self::TITLE_SUFFIX;
            }
        }

        return self::cut(self::squash($name), self::TITLE_MAX);
    }

    /** First ~155 characters of the description text, or a sentence from the product's details. */
    public static function metaDescription(string $descriptionHtml, array $details, string $siteName): string
    {
        $text = self::squash(html_entity_decode(strip_tags(str_replace(['<br>', '</p>', '</li>'], [' ', ' ', ' '], $descriptionHtml)), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        if ($text !== '') {
            return mb_strlen($text) <= self::DESCRIPTION_MAX ? $text : rtrim(self::cut($text, self::DESCRIPTION_MAX - 1), ' ,.;:–-') . '…';
        }

        $name = $details['name'];
        $by = filled($details['brand'] ?? null) && !str_contains(mb_strtolower($name), mb_strtolower($details['brand'])) ? ' by ' . $details['brand'] : '';
        $weight = filled($details['weight'] ?? null) && !str_contains(mb_strtolower($name), mb_strtolower($details['weight'])) ? ' (' . $details['weight'] . ')' : '';
        $category = filled($details['category'] ?? null) ? ' Shop ' . $details['category'] . ' at the best price with home delivery.' : '';
        $sentence = "Buy {$name}{$by}{$weight} online in Bangladesh from {$siteName}.{$category}";

        return mb_strlen($sentence) <= self::DESCRIPTION_MAX ? $sentence : rtrim(self::cut($sentence, self::DESCRIPTION_MAX - 1), ' ,.;:–-') . '…';
    }

    /** Name, brand, category, subcategory, life stage and flavor, without repeats. */
    public static function metaKeywords(array $parts): string
    {
        $seen = [];
        $keywords = [];
        foreach ($parts as $part) {
            $part = self::squash((string) $part);
            $key = mb_strtolower($part);
            if ($part !== '' && !isset($seen[$key])) {
                $seen[$key] = true;
                $keywords[] = $part;
            }
        }

        return implode(', ', $keywords);
    }

    /** Alt text for the gallery image at $position (0 = first): "{Name}", "{Name} – back view", … */
    public static function imageAlt(string $name, int $position): string
    {
        $name = self::squash($name);
        if ($position === 0) {
            return $name;
        }

        return $name . ' – ' . (self::VIEWS[$position - 1] ?? 'image ' . ($position + 1));
    }

    public static function variantAlt(string $name, ?string $color, ?string $size): string
    {
        $what = trim(($color ?? '') . ' ' . ($size ?? ''));

        return self::squash($name) . ($what !== '' ? ' – ' . $what : '');
    }

    private static function squash(string $text): string
    {
        return trim(preg_replace('/\s+/u', ' ', $text));
    }

    private static function cut(string $text, int $max): string
    {
        if (mb_strlen($text) <= $max) {
            return $text;
        }
        $head = mb_substr($text, 0, $max + 1);
        $space = mb_strrpos($head, ' ');

        return rtrim($space !== false && $space > $max / 2 ? mb_substr($head, 0, $space) : mb_substr($text, 0, $max), ' ,.;:–-');
    }
}
