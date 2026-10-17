<?php

namespace App\Services\ProductImport;

use RuntimeException;

/**
 * Runs an import plan in small steps, so each web request stays short (the shop has no queue
 * worker): first the image links are downloaded and checked, then valid products are saved.
 * Progress lives in plan.json; a lock stops two steps of the same import running at once.
 */
class ProductImportRunner
{
    public const IMAGES_PER_STEP = 6;
    public const PRODUCTS_PER_STEP = 5;

    /** Downloads up to IMAGES_PER_STEP pending image links. */
    public function checkImages(array &$plan): void
    {
        $images = new ImportImages(ImportStore::imagesDir($plan['id']));
        $budget = self::IMAGES_PER_STEP;

        foreach ($plan['products'] as &$entry) {
            if ($entry['status'] === 'error' || $entry['pending_images'] === 0) {
                continue;
            }
            foreach ($this->imageSlots($entry) as $slot) {
                $image = &$this->slot($entry, $slot);
                if ($image['state'] !== 'pending') {
                    unset($image);
                    continue;
                }
                if ($budget-- <= 0) {
                    unset($image);
                    break 2;
                }
                $cached = $plan['url_cache'][$image['source']] ?? null;
                if (!$cached) {
                    try {
                        $cached = ['staged' => $images->fetchUrl($image['source']), 'error' => null];
                    } catch (RuntimeException $e) {
                        $cached = ['staged' => null, 'error' => $e->getMessage()];
                    }
                    $plan['url_cache'][$image['source']] = $cached;
                }
                if ($cached['staged']) {
                    $image['state'] = 'ok';
                    $image['staged'] = $cached['staged'];
                } else {
                    $image['state'] = 'error';
                    $entry['messages'][] = [
                        'level' => 'error', 'sheet' => $image['sheet'], 'row' => $image['row'],
                        'column' => $plan['headers'][$image['sheet']][$image['column']] ?? $image['column'],
                        'text' => "Image '{$image['source']}' {$cached['error']}.",
                    ];
                    $entry['status'] = 'error';
                }
                unset($image);
            }
            $entry['pending_images'] = $entry['status'] === 'error' ? 0 : ProductImportPlanner::pendingImages($entry);
        }
        unset($entry);

        if ($this->pendingImageCount($plan) === 0) {
            $plan['phase'] = 'review';
        }
    }

    /** Saves up to PRODUCTS_PER_STEP valid products that are not saved yet. */
    public function importStep(array &$plan, ?int $adminId): void
    {
        $plan['phase'] = 'importing';
        $plan['started_at'] ??= now()->toDateTimeString();
        $importer = new ProductImporter(new ImportImages(ImportStore::imagesDir($plan['id'])), $plan['options'], $adminId);

        $done = 0;
        foreach ($plan['products'] as &$entry) {
            if ($entry['status'] === 'error' || $entry['result'] !== null) {
                continue;
            }
            if ($done++ >= self::PRODUCTS_PER_STEP) {
                break;
            }
            $entry['result'] = $importer->import($entry);
            ImportStore::save($plan); // after every product, so a broken request never saves one twice
        }
        unset($entry);

        if ($this->remaining($plan) === 0) {
            $plan['phase'] = 'done';
            $plan['finished_at'] = now()->toDateTimeString();
        }
    }

    public function pendingImageCount(array $plan): int
    {
        return (int) collect($plan['products'])->where('status', '!=', 'error')->sum('pending_images');
    }

    public function remaining(array $plan): int
    {
        return collect($plan['products'])->where('status', '!=', 'error')->whereNull('result')->count();
    }

    /** Counts for the preview and the summary. */
    public function summary(array $plan): array
    {
        $products = collect($plan['products']);
        $results = $products->pluck('result')->filter();

        return [
            'total' => $products->count(),
            'ok' => $products->where('status', 'ok')->count(),
            'warning' => $products->where('status', 'warning')->count(),
            'error' => $products->where('status', 'error')->count(),
            'importable' => $products->where('status', '!=', 'error')->count(),
            'create' => $products->where('status', '!=', 'error')->where('action', 'create')->count(),
            'update' => $products->where('status', '!=', 'error')->where('action', 'update')->count(),
            'created' => $results->where('status', 'created')->count(),
            'updated' => $results->where('status', 'updated')->count(),
            'failed' => $results->where('status', 'failed')->count(),
            'skipped' => $products->where('status', 'error')->count(),
            'remaining' => $this->remaining($plan),
            'pending_images' => $this->pendingImageCount($plan),
        ];
    }

    /** @return array<int, array{0:string, 1:int|null}> where an entry's images sit */
    private function imageSlots(array $entry): array
    {
        $slots = [];
        foreach (array_keys($entry['images']) as $i) {
            $slots[] = ['images', $i];
        }
        if ($entry['meta_image']) {
            $slots[] = ['meta_image', null];
        }
        foreach ($entry['variants'] as $i => $variant) {
            if ($variant['image']) {
                $slots[] = ['variants', $i];
            }
        }

        return $slots;
    }

    private function &slot(array &$entry, array $slot): array
    {
        [$where, $i] = $slot;
        if ($where === 'images') {
            return $entry['images'][$i];
        }
        if ($where === 'meta_image') {
            return $entry['meta_image'];
        }

        return $entry['variants'][$i]['image'];
    }
}
