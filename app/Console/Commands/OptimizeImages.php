<?php

namespace App\Console\Commands;

use App\Support\WebpVariant;
use Illuminate\Console\Command;

class OptimizeImages extends Command
{
    protected $signature = 'images:optimize
                            {--dry-run : Only report what would be created and how much it saves; write nothing}
                            {--force : Re-encode copies that are already up to date}
                            {--folder=* : Limit to these folders under uploads/ (e.g. --folder=product --folder=banner)}';

    protected $description = 'Create resized .webp copies next to uploaded JPG/PNG images (originals are never modified or deleted)';

    public function handle(): int
    {
        if (!function_exists('imagewebp')) {
            $this->error('This PHP build has no WebP support in GD (imagewebp() is missing).');

            return self::FAILURE;
        }
        // Decoding a large PNG needs width × height × 4 bytes; keep headroom on small CLI limits.
        if ($this->bytes((string) ini_get('memory_limit')) < 512 * 1024 * 1024) {
            @ini_set('memory_limit', '512M');
        }

        $files = $this->collectFiles();
        if ($files === []) {
            $this->info('No JPG/PNG images found under uploads/.');

            return self::SUCCESS;
        }

        $count = array_sum(array_map('count', $files));
        $dryRun = (bool) $this->option('dry-run');
        $this->info(sprintf('%s %d images (max width %dpx for %s, %dpx for the rest, quality %d)%s',
            $dryRun ? 'Checking' : 'Optimizing', $count, WebpVariant::WIDE_MAX_WIDTH,
            implode('/', WebpVariant::WIDE_FOLDERS), WebpVariant::MAX_WIDTH, WebpVariant::QUALITY,
            $dryRun ? ' — dry run, nothing is written' : ''));

        $folders = [];
        $counts = ['created' => 0, 'fresh' => 0, 'larger' => 0, 'unsupported' => 0, 'failed' => 0];
        $failures = [];

        $bar = $this->output->createProgressBar($count);
        foreach ($files as $folder => $list) {
            foreach ($list as $file) {
                $r = WebpVariant::generate($file, $dryRun, (bool) $this->option('force'));
                $counts[$r['status']]++;

                $row = &$folders[$folder];
                $row ??= ['files' => 0, 'before' => 0, 'after' => 0];
                $row['files']++;
                $row['before'] += $r['original'];
                // What the browser downloads afterwards: the WebP when there is one, else the original.
                $row['after'] += in_array($r['status'], ['created', 'fresh'], true) ? $r['webp'] : $r['original'];
                unset($row);

                if ($r['status'] === 'failed') {
                    $failures[] = [$this->relative($file), $r['error'] ?? ''];
                }
                $bar->advance();
            }
        }
        $bar->finish();
        $this->newLine(2);

        ksort($folders);
        $total = ['files' => 0, 'before' => 0, 'after' => 0];
        $rows = [];
        foreach ($folders as $folder => $row) {
            $rows[] = [$folder, $row['files'], $this->mb($row['before']), $this->mb($row['after']), $this->saved($row)];
            foreach ($total as $k => $v) {
                $total[$k] += $row[$k];
            }
        }
        $rows[] = ['<info>TOTAL</info>', $total['files'], $this->mb($total['before']), $this->mb($total['after']), $this->saved($total)];
        $this->table(['Folder', 'Images', 'Before (originals)', $dryRun ? 'After (estimated)' : 'After (served)', 'Saved'], $rows);

        $this->line(sprintf('%s: %d · already up to date: %d · kept original (WebP not smaller): %d · skipped (CMYK/unreadable): %d · failed: %d',
            $dryRun ? 'Would create' : 'Created', $counts['created'], $counts['fresh'], $counts['larger'], $counts['unsupported'], $counts['failed']));
        foreach ($failures as [$file, $error]) {
            $this->warn("  failed: {$file} — {$error}");
        }

        return $counts['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * JPG/PNG files under uploads/, grouped by first folder. Both public/uploads and a root-level
     * uploads/ (a symlink on the live server) are scanned; real paths keep each file once.
     *
     * @return array<string, list<string>>
     */
    private function collectFiles(): array
    {
        $only = array_map('strtolower', (array) $this->option('folder'));
        $seen = [];
        $files = [];

        foreach ([public_path('uploads'), base_path('uploads')] as $root) {
            if (!is_dir($root)) {
                continue;
            }
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::FOLLOW_SYMLINKS)
            );
            foreach ($iterator as $item) {
                if (!$item->isFile() || !WebpVariant::supports($item->getFilename())) {
                    continue;
                }
                $real = $item->getRealPath() ?: $item->getPathname();
                if (isset($seen[$real])) {
                    continue;
                }
                $seen[$real] = true;

                $sub = ltrim(str_replace('\\', '/', substr($item->getPathname(), strlen($root))), '/');
                $folder = str_contains($sub, '/') ? strtolower(strstr($sub, '/', true)) : '(root)';
                if ($only !== [] && !in_array($folder, $only, true)) {
                    continue;
                }
                $files[$folder][] = $real;
            }
        }

        ksort($files);

        return $files;
    }

    private function saved(array $row): string
    {
        return $row['before'] > 0 ? round(100 * (1 - $row['after'] / $row['before'])) . '%' : '—';
    }

    private function mb(int $bytes): string
    {
        return number_format($bytes / 1048576, 2) . ' MB';
    }

    private function relative(string $file): string
    {
        return ltrim(str_replace([base_path(), '\\'], ['', '/'], $file), '/');
    }

    private function bytes(string $limit): int
    {
        if ($limit === '-1') {
            return PHP_INT_MAX;
        }
        $value = (int) $limit;

        return match (strtolower(substr($limit, -1))) {
            'g' => $value * 1024 ** 3,
            'm' => $value * 1024 ** 2,
            'k' => $value * 1024,
            default => $value,
        };
    }
}
