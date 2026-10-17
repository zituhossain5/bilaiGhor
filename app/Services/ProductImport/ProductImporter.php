<?php

namespace App\Services\ProductImport;

use App\Models\Product;
use App\Models\Productimage;
use App\Models\ProductVariantPrice;
use App\Models\ProductWholesalePrice;
use App\Services\InventoryService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use RuntimeException;

/**
 * Saves one planned product the way the product form does (ProductController::store/update):
 * same fields, slug and product-code rules, stock through the inventory ledger, gallery and
 * variant images as Productimage rows (so the WebP copies are made), variants and wholesale tiers.
 *
 * Each product is saved in its own transaction. In update mode only the values present in the
 * file change; nothing is deleted.
 */
class ProductImporter
{
    /** Columns set straight from the plan's auto-filled values (only when the cell was empty). */
    private const AUTO_COLUMNS = ['short_name', 'meta_title', 'meta_description', 'meta_keywords', 'meta_image_alt'];

    /** @var string[] files copied to public/uploads for the product being saved (removed if it fails) */
    private array $published = [];

    public function __construct(private ImportImages $images, private array $options, private ?int $adminId)
    {
    }

    /** @return array{status:string, text:string, product_id:?int} */
    public function import(array $entry): array
    {
        $this->published = [];
        try {
            return DB::transaction(fn () => $entry['action'] === 'create' ? $this->create($entry) : $this->update($entry));
        } catch (\Throwable $e) {
            foreach ($this->published as $path) {
                File::delete(public_path($path));
            }
            report($e);

            return ['status' => 'failed', 'text' => 'Not saved: ' . ($e instanceof RuntimeException ? $e->getMessage() : 'unexpected error, see the error log'), 'product_id' => null];
        }
    }

    // ================================================================ create

    private function create(array $entry): array
    {
        $lastId = (int) Product::max('id') + 1;
        $name = $entry['fields']['name'];

        $code = $entry['sku'] !== '' ? $entry['sku'] : $this->freeCode($lastId);
        if (Product::whereRaw('LOWER(product_code) = ?', [mb_strtolower($code)])->exists()) {
            throw new RuntimeException("SKU '{$code}' was used by another product in the meantime");
        }

        $input = array_merge([
            // Defaults of ProductController::store()
            'product_type' => 'physical',
            'is_digital' => 0,
            'purchase_price' => 0,
            'new_price' => 0,
            'advance_amount' => 0,
            'best_seller_sold_count' => 0,
            'approval_status' => 'approved',
            'feature_product' => 0,
            'meta_keywords' => '',
            'digital_file' => null,
            'download_limit' => null,
            'download_expire_days' => null,
            'pro_video' => null,
            'pro_video_type' => null,
            'pro_video_path' => null,
        ], $entry['fields'], $this->autoColumns($entry), $this->lookupIds($entry), [
            'stock' => (int) ($entry['stock'] ?? 0),
            'slug' => strtolower(preg_replace('/[\/\s]+/', '-', $name . '-' . $lastId)),
            'product_code' => $code,
        ]);
        if (empty($input['best_seller'])) {
            $input['best_seller_sold_count'] = 0;
        }

        $product = Product::create($input);
        InventoryService::seedProduct($product, null, 'Opening stock (product import)');

        $gallery = $this->addGallery($product, $entry, []);
        $metaImage = $this->metaImagePath($entry) ?? ($gallery[0] ?? null);
        if ($metaImage) {
            $product->update(['meta_image' => $metaImage]);
        }
        $this->saveVariants($product, $entry, []);
        if ((int) $product->is_wholesale === 1) {
            $this->saveWholesale($product, $entry);
        }

        return ['status' => 'created', 'text' => "Created ({$code})", 'product_id' => $product->id];
    }

    private function freeCode(int $id): string
    {
        do {
            $code = 'P' . str_pad((string) $id++, 4, '0', STR_PAD_LEFT);
        } while (Product::where('product_code', $code)->exists());

        return $code;
    }

    // ================================================================ update

    private function update(array $entry): array
    {
        $product = Product::whereKey($entry['product_id'])->lockForUpdate()->first();
        if (!$product) {
            throw new RuntimeException('the product no longer exists');
        }
        $oldSlug = $product->slug;

        $changes = array_merge($entry['fields'], $this->autoColumns($entry), $this->lookupIds($entry));
        unset($changes['product_type']);
        if (isset($changes['name']) && $changes['name'] !== $product->name) {
            $changes['slug'] = strtolower(preg_replace('/[\/\s]+/', '-', $changes['name'] . '-' . $product->id));
        }
        if (array_key_exists('best_seller', $changes) && (int) $changes['best_seller'] === 0 && !array_key_exists('best_seller_sold_count', $changes)) {
            $changes['best_seller_sold_count'] = 0;
        }
        if ($changes) {
            $product->update($changes);
        }

        if ($entry['stock'] !== null && ($this->options['update_stock'] ?? false)) {
            InventoryService::applyProductStockEdit($product, (int) $entry['stock'], $this->adminId);
        }

        $known = $this->existingHashes($product);
        $gallery = $this->addGallery($product, $entry, $known);
        $metaImage = $this->metaImagePath($entry);
        if ($metaImage) {
            $product->update(['meta_image' => $metaImage]);
        } elseif (blank($product->meta_image) && $gallery) {
            $product->update(['meta_image' => $gallery[0]]);
        }
        $this->saveVariants($product, $entry, $known);
        if ((int) $product->fresh()->is_wholesale === 1) {
            $this->saveWholesale($product, $entry);
        }

        Cache::forget('product_details_' . $oldSlug);
        Cache::forget('product_details_' . $product->slug);

        return ['status' => 'updated', 'text' => 'Updated (' . ($product->product_code ?: '#' . $product->id) . ')', 'product_id' => $product->id];
    }

    // ================================================================ parts

    private function autoColumns(array $entry): array
    {
        $values = [];
        foreach (self::AUTO_COLUMNS as $column) {
            if (!array_key_exists($column, $entry['fields']) && array_key_exists($column, $entry['auto'])) {
                $values[$column] = $entry['auto'][$column];
            }
        }

        return $values;
    }

    /** Brand / weight / life stage / flavor ids, creating missing ones when allowed. */
    private function lookupIds(array $entry): array
    {
        $ids = [];
        $types = ['brand_id' => 'brand', 'weight_id' => 'weight', 'life_stage_id' => 'life_stage', 'flavor_id' => 'flavor'];
        foreach ($entry['lookups'] as $column => $item) {
            if ($item) {
                $ids[$column] = $item['id'] ?? $this->createLookup($types[$column], $item['name']);
            }
        }

        return $ids;
    }

    private function createLookup(string $type, string $name): int
    {
        if (!($this->options['auto_create'] ?? false)) {
            throw new RuntimeException(ImportLookups::CREATABLE[$type][2] . " '{$name}' not found");
        }

        return ImportLookups::findOrCreate($type, $name);
    }

    /** @return string[] paths of the gallery images added */
    private function addGallery(Product $product, array $entry, array $knownHashes): array
    {
        $paths = [];
        foreach ($entry['images'] as $image) {
            $hash = ImportImages::hashOf($image['staged'] ?? '');
            if (isset($knownHashes[$hash])) {
                continue; // the product already has this picture
            }
            $path = $this->publish($image);
            Productimage::create([
                'product_id' => $product->id,
                'image' => $path,
                'image_alt' => $image['alt'] ?: null,
                'color_id' => null,
                'size_id' => null,
            ]);
            $knownHashes[$hash] = $path;
            $paths[] = $path;
        }

        return $paths;
    }

    private function metaImagePath(array $entry): ?string
    {
        return $entry['meta_image'] ? $this->publish($entry['meta_image'], 'uploads/product/meta') : null;
    }

    private function saveVariants(Product $product, array $entry, array $knownHashes): void
    {
        $imagePaths = [];
        foreach ($entry['variants'] as $variant) {
            $colorId = $variant['color'] ? ($variant['color']['id'] ?? $this->createLookup('color', $variant['color']['name'])) : null;
            $sizeId = $variant['size'] ? ($variant['size']['id'] ?? $this->createLookup('size', $variant['size']['name'])) : null;

            $existing = ProductVariantPrice::where('product_id', $product->id)
                ->where(fn ($q) => $colorId ? $q->where('color_id', $colorId) : $q->whereNull('color_id'))
                ->where(fn ($q) => $sizeId ? $q->where('size_id', $sizeId) : $q->whereNull('size_id'))
                ->first();
            $values = ['price' => $variant['price'], 'stock' => (int) $variant['stock']];
            $existing
                ? $existing->update($values)
                : ProductVariantPrice::create(['product_id' => $product->id, 'color_id' => $colorId, 'size_id' => $sizeId] + $values);

            if ($variant['image'] && $colorId) {
                $hash = ImportImages::hashOf($variant['image']['staged'] ?? '');
                $hasImage = Productimage::where('product_id', $product->id)->where('color_id', $colorId)
                    ->where(fn ($q) => $sizeId ? $q->where('size_id', $sizeId) : $q->whereNull('size_id'))
                    ->get(['image'])->contains(fn ($img) => $this->fileHash($img->image) === $hash);
                if ($hasImage) {
                    continue;
                }
                $imagePaths[$hash] ??= $knownHashes[$hash] ?? $this->publish($variant['image']);
                Productimage::create([
                    'product_id' => $product->id,
                    'image' => $imagePaths[$hash],
                    'image_alt' => $variant['alt'] ?: null,
                    'color_id' => $colorId,
                    'size_id' => $sizeId,
                ]);
            }
        }
    }

    private function saveWholesale(Product $product, array $entry): void
    {
        foreach ($entry['wholesale'] as $tier) {
            $values = ['max_quantity' => $tier['max'], 'wholesale_price' => $tier['price'], 'stock' => (int) $tier['stock']];
            $existing = ProductWholesalePrice::where('product_id', $product->id)->where('min_quantity', $tier['min'])->first();
            $existing
                ? $existing->update($values)
                : ProductWholesalePrice::create(['product_id' => $product->id, 'min_quantity' => $tier['min']] + $values);
        }
    }

    private function publish(array $image, string $folder = 'uploads/product'): string
    {
        if (($image['state'] ?? '') !== 'ok' || empty($image['staged'])) {
            throw new RuntimeException("image '{$image['source']}' was not checked");
        }
        $path = $this->images->publish($image['staged'], $folder);
        $this->published[] = $path;

        return $path;
    }

    /** sha1 => path of the product's current images. */
    private function existingHashes(Product $product): array
    {
        $hashes = [];
        foreach (Productimage::where('product_id', $product->id)->pluck('image') as $path) {
            $hash = $this->fileHash($path);
            if ($hash) {
                $hashes[$hash] = $path;
            }
        }

        return $hashes;
    }

    private function fileHash(?string $path): ?string
    {
        if (!$path) {
            return null;
        }
        foreach ([public_path($path), base_path($path)] as $file) {
            if (is_file($file)) {
                return sha1_file($file);
            }
        }

        return null;
    }
}
