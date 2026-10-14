<?php

namespace App\Support;

use Intervention\Image\Facades\Image;

/**
 * Resized .webp copies of uploaded JPG/PNG images, stored next to the original as
 * "<original>.webp" (e.g. "photo.png" → "photo.png.webp"). The original file and the path saved
 * in the database are never changed; views pick the copy up through <x-picture>.
 */
class WebpVariant
{
    public const QUALITY = 80;

    /** Max width for full-width images (sliders, banners, campaign headers). */
    public const WIDE_MAX_WIDTH = 1600;

    /** Max width for everything else (products, categories, blogs, ...). */
    public const MAX_WIDTH = 800;

    /** Upload folders (under uploads/) whose images are shown full-width. */
    public const WIDE_FOLDERS = ['banner', 'campaign'];

    /** @var array<string, string|null> */
    private static array $memo = [];

    public static function supports(string $file): bool
    {
        return (bool) preg_match('/\.(jpe?g|png)$/i', $file);
    }

    public static function fileFor(string $original): string
    {
        return $original . '.webp';
    }

    /**
     * View path of the up-to-date .webp copy of a view path ("public/uploads/x.png" or
     * "uploads/x.png"), for asset(); null when there is none (or it is older than the original).
     */
    public static function src(?string $path): ?string
    {
        $path = trim((string) $path);
        if ($path === '' || str_contains($path, '://') || !self::supports($path)) {
            return null;
        }
        if (array_key_exists($path, self::$memo)) {
            return self::$memo[$path];
        }

        $original = ImageSize::resolve($path);

        return self::$memo[$path] = ($original && self::isFresh($original)) ? self::fileFor($path) : null;
    }

    public static function isFresh(string $original): bool
    {
        $webp = self::fileFor($original);

        return is_file($webp) && @filemtime($webp) >= @filemtime($original);
    }

    public static function maxWidthFor(string $original): int
    {
        $dirs = explode('/', str_replace('\\', '/', strtolower($original)));
        $upload = array_search('uploads', $dirs, true);
        $folder = $upload === false ? null : ($dirs[$upload + 1] ?? null);

        return in_array($folder, self::WIDE_FOLDERS, true) ? self::WIDE_MAX_WIDTH : self::MAX_WIDTH;
    }

    /**
     * Encode the .webp copy of an absolute JPG/PNG file.
     *
     * Statuses: "created", "fresh" (up-to-date copy already there), "larger" (WebP would not be
     * smaller, so none is written and the original keeps being served), "unsupported", "failed".
     *
     * @return array{status:string, original:int, webp:int, error?:string}
     */
    public static function generate(string $original, bool $dryRun = false, bool $force = false): array
    {
        $originalBytes = (int) @filesize($original);
        $result = fn (string $status, int $webp = 0, array $extra = []) => ['status' => $status, 'original' => $originalBytes, 'webp' => $webp] + $extra;

        if (!is_file($original) || !self::supports($original)) {
            return $result('unsupported');
        }
        if (!$force && self::isFresh($original)) {
            return $result('fresh', (int) filesize(self::fileFor($original)));
        }

        // GD decodes CMYK JPEGs with wrong colours; keep serving those as they are.
        $info = @getimagesize($original);
        if (!$info || ($info[2] === IMAGETYPE_JPEG && ($info['channels'] ?? 3) === 4)) {
            return $result('unsupported');
        }

        try {
            $image = Image::make($original);
            if ($info[2] === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
                $image->orientate(); // the copy carries no EXIF, so bake the rotation in
            }
            $image->widen(self::maxWidthFor($original), fn ($constraint) => $constraint->upsize());
            $data = (string) $image->encode('webp', self::QUALITY);
            $image->destroy();
        } catch (\Throwable $e) {
            return $result('failed', 0, ['error' => $e->getMessage()]);
        }

        $webpBytes = strlen($data);
        if ($webpBytes === 0 || $webpBytes >= $originalBytes) {
            return $result('larger', $webpBytes);
        }

        if (!$dryRun) {
            $target = self::fileFor($original);
            if (@file_put_contents($target . '.tmp', $data) === false || !@rename($target . '.tmp', $target)) {
                @unlink($target . '.tmp');

                return $result('failed', 0, ['error' => 'could not write ' . $target]);
            }
            self::$memo = [];
        }

        return $result('created', $webpBytes);
    }

    /** Upload hook: make the copy for a freshly saved view path. Never throws — an upload must not fail over this. */
    public static function forUpload(?string $path): void
    {
        try {
            $original = ImageSize::resolve(trim((string) $path));
            if ($original && self::supports($original)) {
                self::generate($original);
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
