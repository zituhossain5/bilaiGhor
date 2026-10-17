<?php

namespace App\Services\ProductImport;

use App\Models\GeneralSetting;
use App\Models\Product;
use App\Models\Productimage;
use App\Support\ShortName;
use RuntimeException;

/**
 * Turns an uploaded workbook into an import plan: one entry per product with the values to save,
 * what was auto-filled, its images, variants and wholesale tiers, and every error/warning with
 * its sheet, row and column. Nothing is written to the shop's database here.
 */
class ProductImportPlanner
{
    public const MODES = ['create' => 'Create new only', 'update' => 'Update existing only', 'both' => 'Create and update'];
    public const MAX_IMAGES = 15;
    private const NAME_RECOMMENDED = 120;

    private ImportLookups $lookups;
    private array $options;
    private array $headers;
    /** @var array<string, Product> lower SKU => existing product */
    private array $existing = [];
    private array $zipFiles = [];
    private array $zipErrors = [];
    private bool $hasZip = false;
    private array $toCreate = [];
    private string $siteName;

    /**
     * @param array{mode:string, auto_create:bool, update_stock:bool} $options
     */
    public function analyze(string $id, string $xlsxPath, ?string $zipPath, array $options, int $adminId, string $fileName): array
    {
        $this->options = $options + ['auto_create' => false, 'update_stock' => false];
        $this->lookups = ImportLookups::load();
        $this->siteName = GeneralSetting::query()->value('name') ?: 'Bilai Ghor';

        $read = (new ImportReader())->read($xlsxPath);
        $this->headers = $read['headers'];

        $images = new ImportImages(ImportStore::imagesDir($id));
        if ($zipPath) {
            $this->hasZip = true;
            ['files' => $this->zipFiles, 'errors' => $this->zipErrors] = $images->extractZip($zipPath);
        }

        $productRows = $read['sheets'][ImportColumns::SHEET_PRODUCTS];
        $variantRows = $read['sheets'][ImportColumns::SHEET_VARIANTS];
        $wholesaleRows = $read['sheets'][ImportColumns::SHEET_WHOLESALE];
        if (!$productRows && !$variantRows && !$wholesaleRows) {
            throw new RuntimeException('The file has no product rows. Fill in the Products sheet (the example rows count as products).');
        }

        $skus = collect([$productRows, $variantRows, $wholesaleRows])->flatten(1)
            ->map(fn ($r) => ImportLookups::key($r['cells']['sku'] ?? ''))->filter()->unique()->values()->all();
        if ($skus) {
            Product::query()->whereNotNull('product_code')->get(['id', 'name', 'product_code', 'short_name', 'category_id', 'subcategory_id', 'childcategory_id', 'brand_id', 'weight_id', 'life_stage_id', 'flavor_id', 'description', 'meta_title', 'meta_description', 'meta_keywords', 'meta_image', 'meta_image_alt', 'is_wholesale'])
                ->each(function (Product $product) use ($skus) {
                    $key = ImportLookups::key($product->product_code);
                    if (in_array($key, $skus, true)) {
                        $this->existing[$key] ??= $product;
                    }
                });
        }

        // ------------------------------------------------ products
        $entries = [];
        $skuCounts = collect($productRows)->map(fn ($r) => ImportLookups::key($r['cells']['sku'] ?? ''))->filter()->countBy()->all();
        foreach ($productRows as $index => $row) {
            $entries[] = $this->productEntry($index, $row, $skuCounts);
        }

        // ------------------------------------------------ variants + wholesale (linked by SKU)
        $orphans = [];
        $byKey = [];
        foreach ($entries as $i => $entry) {
            if ($entry['key'] && ($skuCounts[$entry['key']] ?? 0) === 1) {
                $byKey[$entry['key']] = $i;
            }
        }
        foreach ([ImportColumns::SHEET_VARIANTS => $variantRows, ImportColumns::SHEET_WHOLESALE => $wholesaleRows] as $sheet => $rows) {
            foreach ($rows as $row) {
                $key = ImportLookups::key($row['cells']['sku'] ?? '');
                if ($key === '') {
                    $orphans[] = $this->message('error', $sheet, $row['row'], 'sku', 'SKU is required to link this row to a product.');
                    continue;
                }
                if (!isset($byKey[$key])) {
                    if (($skuCounts[$key] ?? 0) > 1) {
                        $orphans[] = $this->message('error', $sheet, $row['row'], 'sku', "SKU '{$row['cells']['sku']}' appears more than once in the Products sheet.");
                        continue;
                    }
                    if (isset($this->existing[$key]) && $this->options['mode'] !== 'create') {
                        // Variants/tiers for a product that's already in the shop and not in the Products sheet.
                        $entries[] = $this->linkedOnlyEntry(count($entries), $this->existing[$key]);
                        $byKey[$key] = count($entries) - 1;
                    } else {
                        $why = isset($this->existing[$key]) ? 'is in the shop but the mode is "Create new only"' : 'not found in the Products sheet or the shop';
                        $orphans[] = $this->message('error', $sheet, $row['row'], 'sku', "SKU '{$row['cells']['sku']}' {$why}.");
                        continue;
                    }
                }
                $entry = &$entries[$byKey[$key]];
                if ($sheet === ImportColumns::SHEET_VARIANTS) {
                    $this->addVariant($entry, $row);
                } else {
                    $this->addWholesale($entry, $row);
                }
                unset($entry);
            }
        }

        foreach ($entries as &$entry) {
            $this->finishEntry($entry);
        }
        unset($entry);

        return [
            'id' => $id,
            'admin_id' => $adminId,
            'file' => $fileName,
            'has_zip' => $this->hasZip,
            'created_at' => now()->toDateTimeString(),
            'options' => $this->options,
            'headers' => $this->headers,
            'to_create' => array_map(fn ($names) => array_values($names), $this->toCreate),
            'orphans' => $orphans,
            'zip_errors' => array_values($this->zipErrors),
            'url_cache' => [],
            'phase' => 'images',
            'products' => $entries,
        ];
    }

    // ================================================================ products

    private function productEntry(int $index, array $row, array $skuCounts): array
    {
        $c = $row['cells'];
        $sku = trim($c['sku'] ?? '');
        $key = ImportLookups::key($sku);
        $existing = $key !== '' ? ($this->existing[$key] ?? null) : null;

        $entry = $this->blankEntry($index, $row['row'], $sku, $key);
        $entry['name'] = trim($c['name'] ?? '') ?: ($existing->name ?? '');
        $entry['action'] = $existing ? 'update' : 'create';
        $entry['product_id'] = $existing?->id;
        $P = ImportColumns::SHEET_PRODUCTS;
        $r = $row['row'];

        // ---------------- key + mode
        if ($key !== '' && ($skuCounts[$key] ?? 0) > 1) {
            $this->add($entry, 'error', $P, $r, 'sku', "SKU '{$sku}' appears more than once in the file.");
        }
        if (mb_strlen($sku) > 100) {
            $this->add($entry, 'error', $P, $r, 'sku', 'SKU is longer than 100 characters.');
        }
        $mode = $this->options['mode'];
        if ($existing && $mode === 'create') {
            $this->add($entry, 'error', $P, $r, 'sku', "A product with SKU '{$sku}' already exists ({$existing->name}); the mode is \"Create new only\".");
        }
        if (!$existing && $mode === 'update') {
            $this->add($entry, 'error', $P, $r, 'sku', $sku === '' ? 'SKU is required to update a product.' : "No product with SKU '{$sku}' in the shop; the mode is \"Update existing only\".");
        }
        $creating = $entry['action'] === 'create';

        // ---------------- name
        $name = trim($c['name'] ?? '');
        if ($name === '' && $creating) {
            $this->add($entry, 'error', $P, $r, 'name', 'Product Name is required.');
        } elseif ($name !== '') {
            if (mb_strlen($name) > 255) {
                $this->add($entry, 'error', $P, $r, 'name', 'Product Name is longer than 255 characters.');
            } elseif (mb_strlen($name) > self::NAME_RECOMMENDED) {
                $this->add($entry, 'warning', $P, $r, 'name', 'Product Name is longer than ' . self::NAME_RECOMMENDED . ' characters (recommended).');
            }
            $entry['fields']['name'] = $name;
        }
        $effectiveName = $name !== '' ? $name : (string) ($existing->name ?? '');

        // ---------------- short name
        $short = trim($c['short_name'] ?? '');
        if ($short !== '') {
            if (mb_strlen($short) > ShortName::max()) {
                $this->add($entry, 'error', $P, $r, 'short_name', 'Short Name is longer than ' . ShortName::max() . ' characters.');
            }
            $entry['fields']['short_name'] = $short;
        } elseif ($effectiveName !== '' && ($creating || blank($existing->short_name))) {
            $this->auto($entry, 'short_name', ProductAutoFill::shortName($effectiveName));
        }

        // ---------------- categories
        $categoryId = $existing?->category_id;
        $categoryName = trim($c['category'] ?? '');
        if ($categoryName !== '') {
            $category = $this->lookups->category($categoryName);
            if (!$category) {
                $this->add($entry, 'error', $P, $r, 'category', "Category '{$categoryName}' not found (categories are never created by the import).");
            } else {
                $categoryId = $category['id'];
                $entry['fields']['category_id'] = $categoryId;
                $entry['labels']['category'] = $category['name'];
            }
        } elseif ($creating) {
            $this->add($entry, 'error', $P, $r, 'category', 'Category is required.');
        } else {
            $entry['labels']['category'] = $this->categoryName($categoryId);
        }

        $subName = trim($c['subcategory'] ?? '');
        $subId = $existing?->subcategory_id;
        if ($subName !== '') {
            $sub = $categoryId ? $this->lookups->subcategory($subName, (int) $categoryId) : null;
            if (!$sub) {
                $this->add($entry, 'error', $P, $r, 'subcategory', "SubCategory '{$subName}' not found in category '" . ($entry['labels']['category'] ?? $categoryName) . "'.");
            } else {
                $subId = $sub['id'];
                $entry['fields']['subcategory_id'] = $subId;
                $entry['labels']['subcategory'] = $sub['name'];
            }
        } elseif (!$creating && isset($entry['fields']['category_id']) && $subId
            && !collect($this->lookups->subcategories)->contains(fn ($s) => $s['id'] === (int) $subId && $s['category_id'] === (int) $categoryId)) {
            $this->add($entry, 'error', $P, $r, 'subcategory', 'The product\'s current SubCategory does not belong to the new Category; give a SubCategory too.');
        }

        $childName = trim($c['childcategory'] ?? '');
        if ($childName !== '') {
            $child = $subId ? $this->lookups->childcategory($childName, (int) $subId) : null;
            if (!$child) {
                $this->add($entry, 'error', $P, $r, 'childcategory', "Child Category '{$childName}' not found in that SubCategory.");
            } else {
                $entry['fields']['childcategory_id'] = $child['id'];
            }
        }

        // ---------------- lists (brand, weight, life stage, flavor)
        $names = [];
        foreach (['brand' => 'brand_id', 'weight' => 'weight_id', 'life_stage' => 'life_stage_id', 'flavor' => 'flavor_id'] as $type => $column) {
            $value = trim($c[$type] ?? '');
            if ($value === '') {
                $names[$type] = $existing ? $this->listName($type, $existing->{$column}) : '';
                continue;
            }
            $entry['lookups'][$column] = $this->lookup($entry, $type, $value, $P, $r, $type);
            $names[$type] = $entry['lookups'][$column]['name'] ?? $value;
        }

        // ---------------- numbers
        foreach (['purchase_price' => 'purchase_price', 'old_price' => 'old_price', 'new_price' => 'new_price', 'advance' => 'advance_amount'] as $col => $field) {
            $value = $this->number($entry, $c[$col] ?? '', $P, $r, $col, 0);
            if ($value !== null) {
                $entry['fields'][$field] = $field === 'advance_amount' ? $value : (int) round($value);
            }
        }
        if ($creating && !isset($entry['fields']['new_price']) && !$this->hasError($entry, 'new_price')) {
            $this->add($entry, 'error', $P, $r, 'new_price', 'New Price is required.');
        } elseif (isset($entry['fields']['new_price']) && $entry['fields']['new_price'] <= 0) {
            $this->add($entry, 'error', $P, $r, 'new_price', 'New Price must be more than 0.');
        }
        if (isset($entry['fields']['old_price'], $entry['fields']['new_price']) && $entry['fields']['old_price'] > 0
            && $entry['fields']['old_price'] < $entry['fields']['new_price']) {
            $this->add($entry, 'warning', $P, $r, 'old_price', 'Old Price is lower than New Price.');
        }
        $sold = $this->integer($entry, $c['sold'] ?? '', $P, $r, 'sold');
        if ($sold !== null) {
            $entry['fields']['best_seller_sold_count'] = $sold;
        }

        $stock = $this->integer($entry, $c['stock'] ?? '', $P, $r, 'stock');
        if ($creating) {
            $entry['stock'] = $stock ?? 0;
            if ($stock === null) {
                $this->auto($entry, 'stock', 0);
            }
        } elseif ($stock !== null) {
            if ($this->options['update_stock']) {
                $entry['stock'] = $stock;
            } else {
                $this->add($entry, 'warning', $P, $r, 'stock', 'Stock not changed: tick "Update stock from file" to change it.');
            }
        }

        // ---------------- text
        foreach (['note' => ['note', 65535], 'unit' => ['pro_unit', 191], 'meta_title' => ['meta_title', 255], 'meta_keywords' => ['meta_keywords', 255], 'meta_description' => ['meta_description', 65535], 'meta_image_alt' => ['meta_image_alt', 191]] as $col => [$field, $max]) {
            $value = trim($c[$col] ?? '');
            if ($value === '') {
                continue;
            }
            if (mb_strlen($value) > $max) {
                $this->add($entry, 'error', $P, $r, $col, $this->headers[$P][$col] . " is longer than {$max} characters.");
            }
            $entry['fields'][$field] = $value;
        }

        // ---------------- type, toggles, badge, video
        $type = trim($c['type'] ?? '');
        if ($type !== '' && ImportLookups::key($type) !== 'physical') {
            $this->add($entry, 'error', $P, $r, 'type', "Type '{$type}' can't be imported (only Physical; digital products need a file upload in the form).");
        }
        if ($creating) {
            $entry['fields']['product_type'] = 'physical';
            if ($type === '') {
                $this->auto($entry, 'product_type', 'Physical');
            }
        }

        $toggles = ['free_delivery' => 'free_delivery', 'status' => 'status', 'hot' => 'topsale', 'flash' => 'flashsale', 'best_seller' => 'best_seller', 'new_arrival' => 'new_arrival', 'wholesale' => 'is_wholesale'];
        foreach ($toggles as $col => $field) {
            $value = $this->bool($entry, $c[$col] ?? '', $P, $r, $col);
            if ($value !== null) {
                $entry['fields'][$field] = $value;
            } elseif ($creating) {
                $entry['fields'][$field] = $field === 'status' ? 1 : 0;
                $this->auto($entry, $field, $field === 'status' ? 'Yes' : 'No');
            }
        }

        $badge = trim($c['badge'] ?? '');
        if ($badge !== '') {
            $match = collect(ImportColumns::BADGES)->first(fn ($b) => ImportLookups::key($b) === ImportLookups::key($badge));
            if (!$match) {
                $this->add($entry, 'error', $P, $r, 'badge', "Badge '{$badge}' is not one of: " . implode(', ', ImportColumns::BADGES) . '.');
            } else {
                $entry['fields']['product_badge'] = $match;
            }
        }

        $video = trim($c['video'] ?? '');
        if ($video !== '') {
            $videoId = self::youTubeId($video);
            if (!$videoId) {
                $this->add($entry, 'error', $P, $r, 'video', 'Video must be a YouTube link or video ID.');
            } else {
                $entry['fields']['pro_video'] = $videoId;
                $entry['fields']['pro_video_type'] = 'youtube';
            }
        }

        // ---------------- description
        $description = trim($c['description'] ?? '');
        if ($description !== '') {
            $entry['fields']['description'] = HtmlDescription::fromCell($description, $effectiveName);
            if (!HtmlDescription::looksLikeHtml($description)) {
                $entry['auto']['description'] = 'formatted as HTML';
            }
        } elseif ($creating) {
            $this->add($entry, 'warning', $P, $r, 'description', 'No description.');
        }
        $effectiveDescription = $entry['fields']['description'] ?? (string) ($existing->description ?? '');

        // ---------------- SEO
        if (!isset($entry['fields']['meta_title']) && $effectiveName !== '' && ($creating || blank($existing->meta_title))) {
            $shortName = $entry['fields']['short_name'] ?? $entry['auto']['short_name'] ?? $existing?->short_name;
            $this->auto($entry, 'meta_title', ProductAutoFill::metaTitle($effectiveName, $shortName));
        }
        if (!isset($entry['fields']['meta_description']) && $effectiveName !== '' && ($creating || blank($existing->meta_description))) {
            $this->auto($entry, 'meta_description', ProductAutoFill::metaDescription($effectiveDescription, [
                'name' => $effectiveName, 'brand' => $names['brand'] ?? '', 'weight' => $names['weight'] ?? '',
                'category' => $entry['labels']['category'] ?? '',
            ], $this->siteName));
        }
        if (!isset($entry['fields']['meta_keywords']) && $effectiveName !== '' && ($creating || blank($existing->meta_keywords))) {
            $this->auto($entry, 'meta_keywords', mb_substr(ProductAutoFill::metaKeywords([
                $effectiveName, $names['brand'] ?? '', $entry['labels']['category'] ?? '', $entry['labels']['subcategory'] ?? '',
                $names['life_stage'] ?? '', $names['flavor'] ?? '',
            ]), 0, 255));
        }

        // ---------------- images
        $sources = $this->split($c['images'] ?? '');
        $alts = explode('|', (string) ($c['image_alts'] ?? ''));
        if (count($sources) > self::MAX_IMAGES) {
            $this->add($entry, 'error', $P, $r, 'images', 'More than ' . self::MAX_IMAGES . ' gallery images.');
        }
        $offset = $existing ? Productimage::where('product_id', $existing->id)->whereNull('color_id')->whereNull('size_id')->count() : 0;
        foreach (array_slice($sources, 0, self::MAX_IMAGES) as $i => $source) {
            $alt = trim($alts[$i] ?? '');
            $image = $this->image($entry, $source, $P, $r, 'images');
            $image['alt'] = $alt !== '' ? mb_substr($alt, 0, 255) : ProductAutoFill::imageAlt($effectiveName, $offset + $i);
            $image['alt_auto'] = $alt === '';
            $entry['images'][] = $image;
        }
        if (collect($entry['images'])->contains('alt_auto', true)) {
            $entry['auto']['image_alts'] = collect($entry['images'])->where('alt_auto', true)->pluck('alt')->implode(' | ');
        }
        if (!$sources && $creating) {
            $this->add($entry, 'warning', $P, $r, 'images', 'No images.');
        }

        $metaImage = trim($c['meta_image'] ?? '');
        if ($metaImage !== '') {
            $entry['meta_image'] = $this->image($entry, $metaImage, $P, $r, 'meta_image');
        } elseif ($entry['images'] && ($creating || blank($existing->meta_image))) {
            $this->auto($entry, 'meta_image', 'first gallery image');
        }
        if (!isset($entry['fields']['meta_image_alt']) && $effectiveName !== '' && ($creating || blank($existing->meta_image_alt))
            && ($entry['meta_image'] || $entry['images'])) {
            $this->auto($entry, 'meta_image_alt', $effectiveName);
        }

        $entry['labels']['brand'] = $names['brand'] ?? '';

        return $entry;
    }

    /** An existing product that only gets variants/wholesale tiers (not in the Products sheet). */
    private function linkedOnlyEntry(int $index, Product $product): array
    {
        $entry = $this->blankEntry($index, null, $product->product_code, ImportLookups::key($product->product_code));
        $entry['action'] = 'update';
        $entry['product_id'] = $product->id;
        $entry['name'] = $product->name;
        $entry['labels']['category'] = $this->categoryName($product->category_id);

        return $entry;
    }

    // ================================================================ variants + wholesale

    private function addVariant(array &$entry, array $row): void
    {
        $c = $row['cells'];
        $V = ImportColumns::SHEET_VARIANTS;
        $r = $row['row'];
        $colorName = trim($c['color'] ?? '');
        $sizeName = trim($c['size'] ?? '');
        $variant = ['row' => $r, 'color' => null, 'size' => null, 'price' => null, 'stock' => 0, 'image' => null, 'alt' => null, 'alt_auto' => false];

        if ($colorName === '' && $sizeName === '') {
            $this->add($entry, 'error', $V, $r, 'color', 'A variant needs a Color, a Size or both.');
        }
        if ($colorName !== '') {
            $variant['color'] = $this->lookup($entry, 'color', $colorName, $V, $r, 'color');
        }
        if ($sizeName !== '') {
            $variant['size'] = $this->lookup($entry, 'size', $sizeName, $V, $r, 'size');
        }

        $price = $this->number($entry, $c['price'] ?? '', $V, $r, 'price', 0);
        if ($price === null && !$this->hasError($entry, 'price', $V, $r)) {
            $this->add($entry, 'error', $V, $r, 'price', 'Price is required.');
        } elseif ($price !== null && $price <= 0) {
            $this->add($entry, 'error', $V, $r, 'price', 'Price must be more than 0.');
        }
        $variant['price'] = $price;
        $variant['stock'] = $this->integer($entry, $c['stock'] ?? '', $V, $r, 'stock') ?? 0;

        $signature = ImportLookups::key($colorName) . '|' . ImportLookups::key($sizeName);
        if (collect($entry['variants'])->contains(fn ($v) => (ImportLookups::key($v['color']['name'] ?? '') . '|' . ImportLookups::key($v['size']['name'] ?? '')) === $signature)) {
            $this->add($entry, 'error', $V, $r, 'color', "The same Color/Size ('{$colorName}' / '{$sizeName}') appears twice for this product.");
        }

        $imageSource = trim($c['image'] ?? '');
        if ($imageSource !== '') {
            if ($colorName === '') {
                $this->add($entry, 'warning', $V, $r, 'image', 'Variant image ignored: images are only saved for variants with a Color (as in the product form).');
            } else {
                $variant['image'] = $this->image($entry, $imageSource, $V, $r, 'image');
                $alt = trim($c['image_alt'] ?? '');
                $variant['alt'] = $alt !== '' ? mb_substr($alt, 0, 255) : ProductAutoFill::variantAlt($entry['name'], $colorName ?: null, $sizeName ?: null);
                $variant['alt_auto'] = $alt === '';
                if ($variant['alt_auto']) {
                    $entry['auto']['variant_alts'] = trim(($entry['auto']['variant_alts'] ?? '') . ' | ' . $variant['alt'], ' |');
                }
            }
        }

        $entry['variants'][] = $variant;
    }

    private function addWholesale(array &$entry, array $row): void
    {
        $c = $row['cells'];
        $W = ImportColumns::SHEET_WHOLESALE;
        $r = $row['row'];

        $min = $this->integer($entry, $c['min_qty'] ?? '', $W, $r, 'min_qty');
        if ($min === null && !$this->hasError($entry, 'min_qty', $W, $r)) {
            $this->add($entry, 'error', $W, $r, 'min_qty', 'Min Qty is required.');
        } elseif ($min !== null && $min < 1) {
            $this->add($entry, 'error', $W, $r, 'min_qty', 'Min Qty must be 1 or more.');
        }
        $max = $this->integer($entry, $c['max_qty'] ?? '', $W, $r, 'max_qty');
        if ($max !== null && $min !== null && $max < $min) {
            $this->add($entry, 'error', $W, $r, 'max_qty', 'Max Qty is lower than Min Qty.');
        }
        $price = $this->number($entry, $c['price'] ?? '', $W, $r, 'price', 0);
        if ($price === null && !$this->hasError($entry, 'price', $W, $r)) {
            $this->add($entry, 'error', $W, $r, 'price', 'Wholesale Price is required.');
        } elseif ($price !== null && $price <= 0) {
            $this->add($entry, 'error', $W, $r, 'price', 'Wholesale Price must be more than 0.');
        }
        if ($min !== null && collect($entry['wholesale'])->contains('min', $min)) {
            $this->add($entry, 'error', $W, $r, 'min_qty', "Two tiers start at Min Qty {$min}.");
        }

        $entry['wholesale'][] = ['row' => $r, 'min' => $min, 'max' => $max, 'price' => $price, 'stock' => $this->integer($entry, $c['stock'] ?? '', $W, $r, 'stock') ?? 0];
    }

    private function finishEntry(array &$entry): void
    {
        // Wholesale tiers are only saved for wholesale products, as in the form.
        if ($entry['wholesale']) {
            $isWholesale = $entry['fields']['is_wholesale'] ?? ($entry['product_id'] ? (int) ($this->productById($entry['product_id'])?->is_wholesale) : 0);
            if (!$isWholesale) {
                $first = $entry['wholesale'][0]['row'];
                $this->add($entry, 'warning', ImportColumns::SHEET_WHOLESALE, $first, 'sku', 'Wholesale tiers ignored: the product\'s Wholesale is not Yes.');
                $entry['wholesale'] = [];
            }
        }

        $levels = collect($entry['messages'])->pluck('level');
        $entry['status'] = $levels->contains('error') ? 'error' : ($levels->contains('warning') ? 'warning' : 'ok');
        $entry['pending_images'] = $entry['status'] === 'error' ? 0 : $this->pendingImages($entry);
    }

    public static function pendingImages(array $entry): int
    {
        $images = array_merge($entry['images'], array_filter([$entry['meta_image']]), array_filter(array_column($entry['variants'], 'image')));

        return collect($images)->where('state', 'pending')->count();
    }

    // ================================================================ cells

    private function blankEntry(int $index, ?int $row, string $sku, string $key): array
    {
        return [
            'n' => $index, 'row' => $row, 'sku' => $sku, 'key' => $key, 'action' => 'create', 'product_id' => null,
            'name' => '', 'fields' => [], 'lookups' => [], 'labels' => [], 'auto' => [], 'stock' => null,
            'images' => [], 'meta_image' => null, 'variants' => [], 'wholesale' => [],
            'messages' => [], 'status' => 'ok', 'pending_images' => 0, 'result' => null,
        ];
    }

    /** A list value (brand, weight, …): its id, or marked for creation when allowed. */
    private function lookup(array &$entry, string $type, string $value, string $sheet, int $row, string $column): ?array
    {
        $found = $this->lookups->find($type, $value);
        if ($found) {
            return $found;
        }
        $label = ImportLookups::CREATABLE[$type][2];
        if ($this->options['auto_create']) {
            $clean = trim(preg_replace('/\s+/u', ' ', $value));
            $this->toCreate[$label][ImportLookups::key($clean)] = $clean;
            $this->add($entry, 'warning', $sheet, $row, $column, "{$label} '{$clean}' will be created.");

            return ['id' => null, 'name' => $clean];
        }
        $this->add($entry, 'error', $sheet, $row, $column, "{$label} '{$value}' not found (tick \"Auto-create missing items\" to add it).");

        return null;
    }

    private function image(array &$entry, string $source, string $sheet, int $row, string $column): array
    {
        $source = trim($source);
        if (ImportImages::isUrl($source)) {
            return ['source' => $source, 'state' => 'pending', 'staged' => null, 'sheet' => $sheet, 'row' => $row, 'column' => $column];
        }
        $key = ImportImages::zipKey($source);
        if (isset($this->zipFiles[$key])) {
            return ['source' => $source, 'state' => 'ok', 'staged' => $this->zipFiles[$key], 'sheet' => $sheet, 'row' => $row, 'column' => $column];
        }
        $reason = $this->zipErrors[$key] ?? (!$this->hasZip
            ? "'{$source}' is not a link, and no ZIP of images was uploaded."
            : "'{$source}' is not a link and was not found in the ZIP.");
        $this->add($entry, 'error', $sheet, $row, $column, $reason);

        return ['source' => $source, 'state' => 'error', 'staged' => null, 'sheet' => $sheet, 'row' => $row, 'column' => $column];
    }

    private function number(array &$entry, string $value, string $sheet, int $row, string $column, float $min): ?float
    {
        $value = trim(str_replace(['৳', ',', 'Tk', 'tk', 'BDT', ' '], '', $value));
        if ($value === '') {
            return null;
        }
        if (!is_numeric($value)) {
            $this->add($entry, 'error', $sheet, $row, $column, $this->headers[$sheet][$column] . " '{$value}' is not a number.");

            return null;
        }
        $number = (float) $value;
        if ($number < $min) {
            $this->add($entry, 'error', $sheet, $row, $column, $this->headers[$sheet][$column] . ' cannot be negative.');

            return null;
        }
        if ($sheet === ImportColumns::SHEET_PRODUCTS && in_array($column, ['purchase_price', 'old_price', 'new_price'], true) && floor($number) != $number) {
            $this->add($entry, 'warning', $sheet, $row, $column, $this->headers[$sheet][$column] . " {$value} is rounded to " . round($number) . ' (prices are whole Taka).');
        }

        return $number;
    }

    private function integer(array &$entry, string $value, string $sheet, int $row, string $column): ?int
    {
        $value = trim(str_replace([',', ' '], '', $value));
        if ($value === '') {
            return null;
        }
        if (!preg_match('/^\d+(\.0+)?$/', $value)) {
            $this->add($entry, 'error', $sheet, $row, $column, $this->headers[$sheet][$column] . " '{$value}' must be a whole number, 0 or more.");

            return null;
        }

        return (int) $value;
    }

    private function bool(array &$entry, string $value, string $sheet, int $row, string $column): ?int
    {
        $key = ImportLookups::key($value);
        if ($key === '') {
            return null;
        }
        if (in_array($key, ['yes', 'y', '1', 'true', 'on'], true)) {
            return 1;
        }
        if (in_array($key, ['no', 'n', '0', 'false', 'off'], true)) {
            return 0;
        }
        $this->add($entry, 'error', $sheet, $row, $column, $this->headers[$sheet][$column] . " '{$value}' must be Yes or No.");

        return null;
    }

    private function split(string $value): array
    {
        return array_values(array_filter(array_map('trim', explode('|', $value)), fn ($v) => $v !== ''));
    }

    private function auto(array &$entry, string $field, mixed $value): void
    {
        $entry['auto'][$field] = $value;
    }

    private function add(array &$entry, string $level, string $sheet, ?int $row, string $column, string $text): void
    {
        $entry['messages'][] = $this->message($level, $sheet, $row, $column, $text);
    }

    private function message(string $level, string $sheet, ?int $row, string $column, string $text): array
    {
        return ['level' => $level, 'sheet' => $sheet, 'row' => $row, 'column' => $this->headers[$sheet][$column] ?? $column, 'text' => $text];
    }

    private function hasError(array $entry, string $column, ?string $sheet = null, ?int $row = null): bool
    {
        $sheet ??= ImportColumns::SHEET_PRODUCTS;
        $header = $this->headers[$sheet][$column] ?? $column;

        return collect($entry['messages'])->contains(fn ($m) => $m['level'] === 'error' && $m['sheet'] === $sheet && $m['column'] === $header && ($row === null || $m['row'] === $row));
    }

    private function categoryName(?int $id): string
    {
        return collect($this->lookups->categories)->firstWhere('id', $id)['name'] ?? '';
    }

    private function listName(string $type, mixed $id): string
    {
        return $id ? (collect($this->lookups->lists[$type])->firstWhere('id', (int) $id)['name'] ?? '') : '';
    }

    private function productById(int $id): ?Product
    {
        return collect($this->existing)->first(fn ($p) => $p->id === $id);
    }

    /** Same rules as the product form (ProductController::getYouTubeVideoId). */
    public static function youTubeId(string $input): ?string
    {
        $input = trim($input);
        if (preg_match('/^[a-zA-Z0-9_-]{11}$/', $input)) {
            return $input;
        }
        preg_match('/(?:https?:\/\/)?(?:www\.|m\.)?(?:youtube\.com\/(?:[^\/\n\s]+\/\S+\/|(?:v|e(?:mbed)?|shorts)\/|.*[?&]v=)|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $input, $m);

        return $m[1] ?? null;
    }
}
