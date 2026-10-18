<?php

namespace App\Support;

use App\Models\Product;
use App\Models\ProductSlugRedirect;
use Illuminate\Support\Str;

/**
 * Product URL slugs: Str::slug(name) + "-{id}", e.g. "smartheart-pouch-kitten-tuna-in-jelly-85g-238".
 * Only a-z, 0-9 and hyphens, so nothing is percent-encoded (–, (, ), *, +, & and . are dropped).
 * The id keeps every slug unique.
 */
class ProductSlug
{
    /** Longest name part, cut on a word boundary. */
    public const NAME_MAX = 80;

    public static function make(?string $name, int|string $id): string
    {
        // "2.5kg" must not become "25kg"; "&" reads better as "and".
        $name = preg_replace('/(\d)[.,](\d)/', '$1-$2', (string) $name);
        $base = Str::slug($name, '-', 'en', ['@' => 'at', '&' => 'and']);

        if (strlen($base) > self::NAME_MAX) {
            $cut = substr($base, 0, self::NAME_MAX + 1);
            $base = trim(str_contains($cut, '-') ? substr($cut, 0, strrpos($cut, '-')) : substr($cut, 0, self::NAME_MAX), '-');
        }

        return ($base !== '' ? $base : 'product') . '-' . $id;
    }

    /**
     * Keep the old URL working after a slug change: remember $oldSlug for this product.
     * A redirect row that now equals a live slug is removed so it can never shadow a product.
     */
    public static function remember(Product $product, ?string $oldSlug, string $newSlug): void
    {
        $oldSlug = trim((string) $oldSlug);
        if ($oldSlug === '' || $oldSlug === $newSlug) {
            return;
        }

        ProductSlugRedirect::updateOrCreate(['old_slug' => $oldSlug], ['product_id' => $product->id]);
        ProductSlugRedirect::where('old_slug', $newSlug)->delete();
    }
}
