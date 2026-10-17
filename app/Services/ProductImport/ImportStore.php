<?php

namespace App\Services\ProductImport;

use Illuminate\Contracts\Cache\Lock;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Where an import lives between upload, preview and import: a private folder per import under
 * storage/app/product-imports/{id} holding the uploaded workbook, the checked images and plan.json.
 */
class ImportStore
{
    public const KEEP_DAYS = 3;

    public static function root(): string
    {
        return storage_path('app' . DIRECTORY_SEPARATOR . 'product-imports');
    }

    public static function newId(): string
    {
        return (string) Str::uuid();
    }

    public static function isValidId(string $id): bool
    {
        return (bool) preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', $id);
    }

    public static function dir(string $id): string
    {
        abort_unless(self::isValidId($id), 404);

        return self::root() . DIRECTORY_SEPARATOR . $id;
    }

    public static function imagesDir(string $id): string
    {
        return self::dir($id) . DIRECTORY_SEPARATOR . 'images';
    }

    public static function load(string $id): ?array
    {
        $path = self::dir($id) . DIRECTORY_SEPARATOR . 'plan.json';
        if (!is_file($path)) {
            return null;
        }
        $plan = json_decode((string) file_get_contents($path), true);

        return is_array($plan) ? $plan : null;
    }

    public static function save(array $plan): void
    {
        $dir = self::dir($plan['id']);
        File::ensureDirectoryExists($dir);
        $tmp = $dir . DIRECTORY_SEPARATOR . 'plan.json.tmp';
        File::put($tmp, json_encode($plan, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        File::move($tmp, $dir . DIRECTORY_SEPARATOR . 'plan.json');
    }

    /** One batch at a time per import (a double click must never import a product twice). */
    public static function lock(string $id): Lock
    {
        return Cache::lock('product-import:' . $id, 120);
    }

    /** Removes imports older than KEEP_DAYS. */
    public static function prune(): void
    {
        if (!is_dir(self::root())) {
            return;
        }
        $cutoff = now()->subDays(self::KEEP_DAYS)->getTimestamp();
        foreach (File::directories(self::root()) as $dir) {
            if (self::isValidId(basename($dir)) && filemtime($dir) < $cutoff) {
                File::deleteDirectory($dir);
            }
        }
    }
}
