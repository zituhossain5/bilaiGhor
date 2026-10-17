<?php

namespace App\Services\ProductImport;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;
use ZipArchive;

/**
 * Images for an import: taken from the uploaded ZIP or downloaded from a URL, checked, and kept
 * in the import's private staging folder (storage/app/...) until the product is saved. Nothing
 * from the file is ever extracted to a public folder or executed; only checked images are
 * copied into public/uploads with a random name.
 */
class ImportImages
{
    public const MAX_BYTES = 5 * 1024 * 1024;
    public const MAX_ZIP_BYTES = 100 * 1024 * 1024;
    public const MAX_ZIP_FILES = 500;
    public const TIMEOUT = 15;
    public const TYPES = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_GIF => 'gif', IMAGETYPE_WEBP => 'webp'];

    /** Tests switch this off so faked URLs need no DNS lookup. */
    public static bool $checkHosts = true;

    public function __construct(private string $stagingDir)
    {
        File::ensureDirectoryExists($this->stagingDir);
    }

    public static function isUrl(string $source): bool
    {
        return (bool) preg_match('#^https?://#i', trim($source));
    }

    /** Lower-cased file name used to match a cell value with a ZIP entry. */
    public static function zipKey(string $name): string
    {
        return mb_strtolower(trim(basename(str_replace('\\', '/', $name))));
    }

    /**
     * Checks every image in the ZIP (in memory) and stages the good ones.
     *
     * @return array{files: array<string,string>, errors: array<int,string>} zip key => staged file name
     */
    public function extractZip(string $zipPath): array
    {
        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            throw new RuntimeException('The ZIP file could not be opened.');
        }

        $files = [];
        $errors = [];
        $total = 0;
        try {
            if ($zip->numFiles > self::MAX_ZIP_FILES) {
                throw new RuntimeException('The ZIP file has more than ' . self::MAX_ZIP_FILES . ' files.');
            }
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $stat = $zip->statIndex($i);
                $name = (string) ($stat['name'] ?? '');
                if ($name === '' || str_ends_with($name, '/') || str_contains($name, '__MACOSX') || str_starts_with(basename($name), '.')) {
                    continue;
                }
                $key = self::zipKey($name);
                if (!preg_match('/\.(jpe?g|png|gif|webp)$/i', $key)) {
                    continue;
                }
                $size = (int) ($stat['size'] ?? 0);
                $total += $size;
                if ($size > self::MAX_BYTES) {
                    $errors[$key] = "\"{$key}\" in the ZIP is larger than 5 MB.";
                    continue;
                }
                if ($total > self::MAX_ZIP_BYTES) {
                    throw new RuntimeException('The images in the ZIP add up to more than 100 MB.');
                }
                $bytes = $zip->getFromIndex($i, self::MAX_BYTES + 1);
                try {
                    $files[$key] = $this->stage((string) $bytes);
                } catch (RuntimeException $e) {
                    $errors[$key] = "\"{$key}\" in the ZIP: " . $e->getMessage();
                }
            }
        } finally {
            $zip->close();
        }

        return ['files' => $files, 'errors' => $errors];
    }

    /** Downloads an image URL into the staging folder; returns the staged file name. */
    public function fetchUrl(string $url): string
    {
        $url = trim($url);
        $parts = parse_url($url);
        if (!$parts || !in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true) || empty($parts['host'])) {
            throw new RuntimeException('not a valid http(s) link');
        }
        self::assertPublicHost($parts['host']);

        try {
            $response = Http::timeout(self::TIMEOUT)
                ->connectTimeout(5)
                ->withHeaders(['User-Agent' => 'BilaiGhor-ProductImport/1.0', 'Accept' => 'image/*'])
                ->withOptions([
                    'allow_redirects' => [
                        'max' => 3,
                        'protocols' => ['http', 'https'],
                        'on_redirect' => fn ($request, $response, $uri) => self::assertPublicHost($uri->getHost()),
                    ],
                    'on_headers' => function ($response) {
                        if ((int) $response->getHeaderLine('Content-Length') > self::MAX_BYTES) {
                            throw new RuntimeException('larger than 5 MB');
                        }
                    },
                    'progress' => function ($total, $downloaded) {
                        if ($downloaded > self::MAX_BYTES) {
                            throw new RuntimeException('larger than 5 MB');
                        }
                    },
                ])
                ->get($url);
        } catch (\Throwable $e) {
            $reason = $e->getPrevious()?->getMessage() ?: $e->getMessage();
            throw new RuntimeException(str_contains($reason, 'larger than') ? 'larger than 5 MB' : 'could not be downloaded');
        }

        if (!$response->successful()) {
            throw new RuntimeException('could not be downloaded (HTTP ' . $response->status() . ')');
        }

        return $this->stage($response->body());
    }

    /** Checks the bytes are a JPG/PNG/GIF/WebP image within the size limit and stages them. */
    public function stage(string $bytes): string
    {
        if ($bytes === '') {
            throw new RuntimeException('is empty');
        }
        if (strlen($bytes) > self::MAX_BYTES) {
            throw new RuntimeException('larger than 5 MB');
        }
        $info = @getimagesizefromstring($bytes);
        if (!$info || !isset(self::TYPES[$info[2]]) || $info[0] < 1 || $info[1] < 1) {
            throw new RuntimeException('is not a JPG, PNG, GIF or WebP image');
        }

        $name = sha1($bytes) . '.' . self::TYPES[$info[2]];
        $path = $this->stagingDir . DIRECTORY_SEPARATOR . $name;
        if (!is_file($path)) {
            File::put($path, $bytes);
        }

        return $name;
    }

    public function stagedPath(string $name): string
    {
        return $this->stagingDir . DIRECTORY_SEPARATOR . basename($name);
    }

    /**
     * Copies a staged image into public/{folder} with a random name, the way the product form
     * stores uploads; returns the path saved in the database (e.g. "uploads/product/x.jpg").
     */
    public function publish(string $stagedName, string $folder = 'uploads/product'): string
    {
        $source = $this->stagedPath($stagedName);
        if (!is_file($source)) {
            throw new RuntimeException('image file is missing; please upload the file again');
        }
        $extension = pathinfo($stagedName, PATHINFO_EXTENSION);
        $name = time() . '-' . Str::lower(Str::random(12)) . '.' . $extension;
        File::ensureDirectoryExists(public_path($folder));
        File::copy($source, public_path($folder . '/' . $name));

        return $folder . '/' . $name;
    }

    /** SHA-1 of a staged image (its file name), to skip pictures a product already has. */
    public static function hashOf(string $stagedName): string
    {
        return pathinfo($stagedName, PATHINFO_FILENAME);
    }

    private static function assertPublicHost(string $host): void
    {
        if (!self::$checkHosts) {
            return;
        }
        $host = trim($host, '[]');
        $ips = filter_var($host, FILTER_VALIDATE_IP) ? [$host] : (gethostbynamel($host) ?: []);
        if (!$ips) {
            throw new RuntimeException('could not be downloaded (unknown host)');
        }
        foreach ($ips as $ip) {
            if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                throw new RuntimeException('points to a private network address');
            }
        }
    }
}
