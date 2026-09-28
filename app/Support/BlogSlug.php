<?php

namespace App\Support;

use App\Models\Blog;
use App\Models\BlogSlugRedirect;
use Illuminate\Support\Str;

/**
 * Clean, stable blog URL slugs: lowercase a-z/0-9 words joined by single hyphens, capped at
 * MAX_LENGTH on a word boundary, unique across current slugs AND old (redirecting) slugs.
 */
class BlogSlug
{
    public const MAX_LENGTH = 70;

    /** Old auto-generated slugs ended in "-" + a 10-digit Unix timestamp. */
    public const LEGACY_SUFFIX = '/-\d{10}$/';

    /**
     * Normalise user input or a title into slug form. Non-Latin text (e.g. Bangla) is
     * transliterated with ICU when the intl extension is present, which reads far better
     * than Str::slug's character-by-character ASCII fallback.
     */
    public static function sanitize(?string $value): string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        if (preg_match('/[^\x00-\x7F]/', $value) && function_exists('transliterator_transliterate')) {
            $value = (string) transliterator_transliterate('Any-Latin; Latin-ASCII', $value);
        }

        $slug = Str::slug($value);

        return self::limit($slug);
    }

    /** Cut to MAX_LENGTH without splitting a word. */
    public static function limit(string $slug): string
    {
        if (strlen($slug) <= self::MAX_LENGTH) {
            return $slug;
        }

        $cut = substr($slug, 0, self::MAX_LENGTH + 1);
        $cut = str_contains($cut, '-') ? substr($cut, 0, strrpos($cut, '-')) : substr($cut, 0, self::MAX_LENGTH);

        return trim($cut, '-');
    }

    /**
     * Unique slug for a post: `$base`, then `$base-2`, `$base-3`… only on a real collision with
     * another post's current slug or another post's old slug. The post's own old slugs are free
     * to reuse (the redirect row is removed when the slug is saved).
     */
    public static function unique(string $base, ?int $ignoreBlogId = null): string
    {
        $base = $base !== '' ? $base : 'post';
        $candidate = $base;

        for ($n = 2; self::taken($candidate, $ignoreBlogId); $n++) {
            $suffix = '-' . $n;
            $candidate = self::limit(substr($base, 0, self::MAX_LENGTH - strlen($suffix))) . $suffix;
        }

        return $candidate;
    }

    public static function taken(string $slug, ?int $ignoreBlogId = null): bool
    {
        $inBlogs = Blog::where('slug', $slug)
            ->when($ignoreBlogId, fn ($q) => $q->where('id', '!=', $ignoreBlogId))
            ->exists();

        return $inBlogs || BlogSlugRedirect::where('old_slug', $slug)
            ->when($ignoreBlogId, fn ($q) => $q->where('blog_id', '!=', $ignoreBlogId))
            ->exists();
    }

    /**
     * Persist a new slug for an existing post, keeping the previous one as a 301 redirect.
     */
    public static function change(Blog $blog, string $newSlug): void
    {
        $old = $blog->getOriginal('slug');
        if ($old === $newSlug) {
            return;
        }

        // Reusing one of this post's own old slugs: it is canonical again, not a redirect.
        BlogSlugRedirect::where('blog_id', $blog->id)->where('old_slug', $newSlug)->delete();

        if ($old !== null && $old !== '') {
            BlogSlugRedirect::firstOrCreate(['old_slug' => $old], ['blog_id' => $blog->id]);
        }

        $blog->slug = $newSlug;
    }
}
