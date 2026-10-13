<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\HtmlString;

/**
 * Intrinsic pixel size of a local image, for width/height attributes (lets the browser reserve
 * the right space before the image loads → no layout shift).
 *
 * Accepts the paths the views already pass to asset()/url(): "public/uploads/x.png" (relative to
 * the project root) or "uploads/x.png" (relative to public/). Results are cached per file
 * version (path + mtime), so a replaced upload is re-measured automatically.
 */
class ImageSize
{
    /** @var array<string, array{0:int,1:int}|null> */
    private static array $memo = [];

    /** @return array{0:int,1:int}|null [width, height], or null if unknown */
    public static function of(?string $path): ?array
    {
        $path = trim((string) $path);
        if ($path === '' || str_contains($path, '://')) {
            return null;
        }

        if (array_key_exists($path, self::$memo)) {
            return self::$memo[$path];
        }

        $file = self::resolve($path);
        if ($file === null) {
            return self::$memo[$path] = null;
        }

        $key = 'img-size:' . md5($file . '|' . @filemtime($file));

        return self::$memo[$path] = Cache::rememberForever($key, function () use ($file) {
            $size = @getimagesize($file);
            if (!$size || $size[0] < 1 || $size[1] < 1) {
                return self::svgSize($file);
            }

            return [(int) $size[0], (int) $size[1]];
        });
    }

    /**
     * ' width="W" height="H"' for an <img>, using the file's real size, else the fallback,
     * else nothing (never guess a ratio for images whose CSS relies on the intrinsic size).
     *
     * @param  array{0:int,1:int}|null  $fallback
     */
    public static function attrs(?string $path, ?array $fallback = null): HtmlString
    {
        $size = self::of($path) ?? $fallback;

        return new HtmlString($size ? sprintf(' width="%d" height="%d"', $size[0], $size[1]) : '');
    }

    private static function resolve(string $path): ?string
    {
        $path = ltrim(rawurldecode($path), '/');
        foreach ([base_path($path), public_path($path)] as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    /** @return array{0:int,1:int}|null */
    private static function svgSize(string $file): ?array
    {
        if (strtolower(pathinfo($file, PATHINFO_EXTENSION)) !== 'svg') {
            return null;
        }
        $head = (string) @file_get_contents($file, false, null, 0, 2048);
        if (preg_match('/\bwidth="([\d.]+)(?:px)?"[^>]*\bheight="([\d.]+)(?:px)?"/i', $head, $m)) {
            return [(int) round($m[1]), (int) round($m[2])];
        }
        if (preg_match('/viewBox="[\d.\-]+\s+[\d.\-]+\s+([\d.]+)\s+([\d.]+)"/i', $head, $m)) {
            return [(int) round($m[1]), (int) round($m[2])];
        }

        return null;
    }
}
