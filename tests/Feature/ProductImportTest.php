<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\InventoryMovement;
use App\Models\InventoryStock;
use App\Models\Product;
use App\Models\Productimage;
use App\Models\ProductVariantPrice;
use App\Models\ProductWholesalePrice;
use App\Models\Subcategory;
use App\Models\User;
use App\Services\ProductImport\HtmlDescription;
use App\Services\ProductImport\ImportColumns;
use App\Services\ProductImport\ImportImages;
use App\Services\ProductImport\ImportStore;
use App\Services\ProductImport\ProductAutoFill;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use ZipArchive;

/**
 * Bulk product import: template, auto-filled fields, preview errors, import through the same
 * rules as the product form (stock ledger, images, variants), update safety and the result file.
 * No real network calls: image URLs are faked.
 */
class ProductImportTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;
    private Category $category;
    private Subcategory $subcategory;
    private array $tempFiles = [];
    private array $importIds = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('mystore_testing', DB::connection()->getDatabaseName(), 'Tests must use the separate testing database.');

        ImportImages::$checkHosts = false;
        Http::preventStrayRequests();
        Http::fake([
            'https://img.test/missing.jpg' => Http::response('', 404),
            'https://img.test/not-an-image.jpg' => Http::response('<?php echo 1; ?>', 200),
            // A different picture per URL (the importer skips a picture the product already has).
            'https://img.test/*' => fn ($request) => Http::response($this->png(crc32($request->url()) % 7), 200, ['Content-Type' => 'image/png']),
        ]);

        $this->admin = $this->makeAdmin();
        $this->subcategory = Subcategory::whereIn('category_id', Category::where('status', 1)->pluck('id'))->firstOrFail();
        $this->category = Category::findOrFail($this->subcategory->category_id);
    }

    protected function tearDown(): void
    {
        foreach (Productimage::whereIn('product_id', Product::where('product_code', 'like', 'IMPT-%')->pluck('id'))->pluck('image') as $path) {
            File::delete(public_path($path));
        }
        foreach (Product::where('product_code', 'like', 'IMPT-%')->whereNotNull('meta_image')->pluck('meta_image') as $path) {
            if (str_contains($path, 'uploads/product/meta/')) {
                File::delete(public_path($path));
            }
        }
        foreach ($this->importIds as $id) {
            File::deleteDirectory(ImportStore::dir($id));
        }
        foreach ($this->tempFiles as $file) {
            File::delete($file);
        }
        ImportImages::$checkHosts = true;
        parent::tearDown();
    }

    // ================================================================ template

    public function test_the_template_has_every_sheet_header_and_live_dropdown_list(): void
    {
        $brand = Brand::create(['name' => 'Zeta Template Brand', 'slug' => 'zeta-template-brand-' . Str::random(4), 'status' => 1]);

        $response = $this->actingAs($this->admin, 'admin')->get(route('products.import.template'))->assertOk();
        $path = $this->temp('xlsx');
        File::put($path, $response->streamedContent());
        $book = IOFactory::load($path);

        $this->assertSame(['Products', 'Variants', 'Wholesale', 'Instructions', 'Lists'], $book->getSheetNames());

        $products = $book->getSheetByName('Products');
        $headers = $products->rangeToArray('A1:' . $products->getHighestColumn() . '1')[0];
        $this->assertSame(array_map(fn ($c) => $c['header'] . ($c['required'] ? ' *' : ''), ImportColumns::products()), $headers);
        $this->assertSame('C0392B', $products->getStyle('B1')->getFill()->getStartColor()->getRGB(), 'required header is red');

        $validations = collect($products->getDataValidationCollection())->map->getFormula1();
        $this->assertSame('Categories', $validations['D2:D1001'] ?? null);
        $this->assertSame('Brands', $validations['N2:N1001'] ?? null);
        $this->assertSame('YesNo', $validations['X2:X1001'] ?? null);

        $brands = $book->getNamedRange('Brands');
        $range = str_replace('$', '', substr($brands->getRange(), strrpos($brands->getRange(), '!') + 1));
        $values = $book->getSheetByName('Lists')->rangeToArray($range);
        $this->assertContains($brand->name, array_column($values, 0), 'Lists come live from the database');
        $this->assertContains($this->category->name, array_column($book->getSheetByName('Lists')->rangeToArray('A2:A50'), 0));
        $this->assertStringContainsString('Auto-filled', implode(' ', array_column($book->getSheetByName('Instructions')->rangeToArray('A1:A40'), 0)));
    }

    // ================================================================ auto-fill rules

    public function test_auto_fill_rules(): void
    {
        $this->assertSame('Whiskas Ocean Fish', ProductAutoFill::shortName('Whiskas Adult Pouch Ocean Fish Flavor Wet Cat Food – 80g'));
        $this->assertSame('Royal Canin Kitten Price in Bangladesh', ProductAutoFill::metaTitle('Royal Canin Kitten'));
        $this->assertSame('Whiskas Ocean Fish Price in Bangladesh', ProductAutoFill::metaTitle('Whiskas Adult Pouch Ocean Fish Flavor Wet Cat Food – 80g', 'Whiskas Ocean Fish'));
        $this->assertLessThanOrEqual(60, mb_strlen(ProductAutoFill::metaTitle(str_repeat('Long words here ', 10))));

        $description = '<p>' . str_repeat('Healthy food for cats. ', 20) . '</p>';
        $meta = ProductAutoFill::metaDescription($description, ['name' => 'X'], 'Bilai Ghor');
        $this->assertLessThanOrEqual(155, mb_strlen($meta));
        $this->assertStringStartsWith('Healthy food for cats.', $meta);
        $this->assertSame(
            'Buy Tuna Pouch by Whiskas (80 gm) online in Bangladesh from Bilai Ghor. Shop Cat Food at the best price with home delivery.',
            ProductAutoFill::metaDescription('', ['name' => 'Tuna Pouch', 'brand' => 'Whiskas', 'weight' => '80 gm', 'category' => 'Cat Food'], 'Bilai Ghor')
        );
        $this->assertSame('Whiskas Tuna, Whiskas, Cat Food, Pouch, Adult, Tuna', ProductAutoFill::metaKeywords(['Whiskas Tuna', 'Whiskas', 'Cat Food', 'Pouch', 'Adult', 'Tuna', 'whiskas']));
        $this->assertSame(['Cat Bed', 'Cat Bed – back view', 'Cat Bed – side view', 'Cat Bed – detail view', 'Cat Bed – image 5'],
            array_map(fn ($i) => ProductAutoFill::imageAlt('Cat Bed', $i), range(0, 4)));

        $this->assertSame(
            "<p>Complete food.<br>For adults.</p>\n<p><strong>Key benefits:</strong></p>\n<ul><li>Real fish</li><li>Omega &amp; 3</li></ul>",
            HtmlDescription::fromCell("Complete food.\nFor adults.\n\nKey benefits:\n- Real fish\n• Omega & 3", 'X')
        );
        $clean = HtmlDescription::fromCell('<p onclick="x()">Hi <script>alert(1)</script><a href="javascript:alert(1)">link</a><img src="https://img.test/a.jpg"></p><style>p{}</style>', 'Cat Bed');
        $this->assertSame('<p>Hi <a>link</a><img src="https://img.test/a.jpg" alt="Cat Bed"></p>', $clean);
    }

    public function test_empty_cells_are_auto_filled_and_marked_in_the_preview(): void
    {
        $plan = $this->upload([
            'Products' => [$this->productRow(['sku' => 'IMPT-AUTO', 'name' => 'Whiskas Adult Pouch Ocean Fish Flavor Wet Cat Food – 80g',
                'description' => "Complete food.\n\nKey benefits:\n- Real fish", 'images' => 'https://img.test/a.png|https://img.test/b.png'])],
        ]);
        $entry = $plan['products'][0];

        $this->assertSame('Whiskas Ocean Fish', $entry['auto']['short_name']);
        $this->assertSame('Whiskas Ocean Fish Price in Bangladesh', $entry['auto']['meta_title']);
        $this->assertSame('Complete food. Key benefits: Real fish', $entry['auto']['meta_description']);
        $this->assertStringStartsWith('Whiskas Adult Pouch Ocean Fish Flavor Wet Cat Food – 80g, ', $entry['auto']['meta_keywords']);
        $this->assertSame('first gallery image', $entry['auto']['meta_image']);
        $this->assertSame('formatted as HTML', $entry['auto']['description']);
        $this->assertSame([1, 0, 0], [$entry['fields']['status'], $entry['fields']['topsale'], $entry['fields']['is_wholesale']]);
        $this->assertSame(['Whiskas Adult Pouch Ocean Fish Flavor Wet Cat Food – 80g', 'Whiskas Adult Pouch Ocean Fish Flavor Wet Cat Food – 80g – back view'], array_column($entry['images'], 'alt'));

        $this->actingAs($this->admin, 'admin')->get(route('products.import.show', $plan['id']))
            ->assertOk()->assertSee('Short name')->assertSee('Meta title')->assertSee('Defaults (');

        $plan = $this->runImport($plan['id']);
        $product = Product::where('product_code', 'IMPT-AUTO')->firstOrFail();
        $this->assertSame('Whiskas Ocean Fish', $product->short_name);
        $this->assertSame('Whiskas Ocean Fish Price in Bangladesh', $product->meta_title);
        $this->assertSame("<p>Complete food.</p>\n<p><strong>Key benefits:</strong></p>\n<ul><li>Real fish</li></ul>", $product->description);
        $images = Productimage::where('product_id', $product->id)->orderBy('id')->get();
        $this->assertCount(2, $images);
        $this->assertSame($images[0]->image, $product->meta_image, 'meta image = first gallery image');
        $this->assertFileExists(public_path($images[0]->image));
        $this->assertSame('created', $plan['products'][0]['result']['status']);
    }

    // ================================================================ import = form

    public function test_an_imported_product_matches_one_made_with_the_form(): void
    {
        $this->actingAs($this->admin, 'admin')->post(route('products.store'), [
            'name' => 'Form Twin Product', 'category_id' => $this->category->id, 'subcategory_id' => $this->subcategory->id,
            'new_price' => 120, 'purchase_price' => 80, 'old_price' => 150, 'stock' => 7, 'description' => '<p>Same.</p>',
            'product_type' => 'physical', 'status' => 1, 'product_code' => 'IMPT-FORM',
        ])->assertSessionHasNoErrors();
        $form = Product::where('name', 'Form Twin Product')->latest('id')->firstOrFail();

        $plan = $this->upload(['Products' => [$this->productRow([
            'sku' => 'IMPT-TWIN', 'name' => 'Import Twin Product', 'purchase_price' => 80, 'old_price' => 150,
            'new_price' => 120, 'stock' => 7, 'description' => '<p>Same.</p>',
        ])]]);
        $this->runImport($plan['id']);
        $imported = Product::where('product_code', 'IMPT-TWIN')->firstOrFail();

        foreach (['product_type', 'is_digital', 'approval_status', 'status', 'category_id', 'subcategory_id', 'new_price', 'purchase_price', 'old_price', 'stock', 'advance_amount', 'free_delivery', 'topsale', 'best_seller', 'new_arrival', 'is_wholesale', 'description', 'vendor_id'] as $column) {
            $this->assertEquals($form->{$column}, $imported->{$column}, "column {$column}");
        }
        $this->assertSame('import-twin-product-' . $imported->id, $imported->slug, 'slug rule of the form');
        $this->assertSame(7, (int) InventoryStock::where('product_id', $imported->id)->value('on_hand'));
        $this->assertSame(
            InventoryMovement::where('product_id', $form->id)->pluck('movement_type')->all(),
            InventoryMovement::where('product_id', $imported->id)->pluck('movement_type')->all(),
            'opening stock recorded the same way'
        );
    }

    public function test_variants_and_wholesale_tiers_are_linked_by_sku_with_zip_images(): void
    {
        [$color1, $color2] = DB::table('colors')->where('status', 1)->orderBy('id')->limit(2)->pluck('colorName')->all();
        $size = DB::table('sizes')->where('status', 1)->orderBy('id')->value('sizeName');
        $zip = $this->zip(['collar-1.png', 'Collar-Pink.PNG']);

        $plan = $this->upload([
            'Products' => [$this->productRow(['sku' => 'IMPT-COLLAR', 'name' => 'Pearl Collar Test', 'description' => 'Soft collar.', 'images' => 'collar-1.png', 'wholesale' => 'Yes'])],
            'Variants' => [
                ['sku' => 'impt-collar', 'color' => $color1, 'size' => $size, 'price' => 350, 'stock' => 4, 'image' => 'collar-pink.png'],
                ['sku' => 'IMPT-COLLAR', 'color' => $color2, 'price' => 360, 'stock' => 2],
            ],
            'Wholesale' => [['sku' => 'IMPT-COLLAR', 'min_qty' => 10, 'max_qty' => 49, 'price' => 300]],
        ], $zip);
        $this->assertSame('ok', $plan['products'][0]['status'], json_encode($plan['products'][0]['messages']));
        $this->runImport($plan['id']);

        $product = Product::where('product_code', 'IMPT-COLLAR')->firstOrFail();
        $variants = ProductVariantPrice::where('product_id', $product->id)->orderBy('id')->get();
        $this->assertSame([350.0, 360.0], $variants->pluck('price')->map(fn ($p) => (float) $p)->all());
        $this->assertSame([4, 2], $variants->pluck('stock')->map(fn ($s) => (int) $s)->all());
        $variantImage = Productimage::where('product_id', $product->id)->whereNotNull('color_id')->firstOrFail();
        $this->assertSame('Pearl Collar Test – ' . $color1 . ' ' . $size, $variantImage->image_alt);
        $this->assertSame(1, ProductWholesalePrice::where('product_id', $product->id)->where('min_quantity', 10)->count());
    }

    // ================================================================ errors

    public function test_rows_with_errors_are_skipped_and_valid_rows_import(): void
    {
        $plan = $this->upload([
            'Products' => [
                $this->productRow(['sku' => 'IMPT-GOOD', 'name' => 'Good Row']),
                $this->productRow(['sku' => 'IMPT-BADCAT', 'name' => 'Bad Category', 'category' => 'No Such Category']),
                $this->productRow(['sku' => 'IMPT-BADPRICE', 'name' => 'Bad Price', 'new_price' => 'abc']),
                $this->productRow(['sku' => 'IMPT-BADBRAND', 'name' => 'Bad Brand', 'brand' => 'Nekko Pro Unknown']),
                $this->productRow(['sku' => 'IMPT-BADIMG', 'name' => 'Bad Image', 'images' => 'https://img.test/missing.jpg']),
                $this->productRow(['sku' => 'IMPT-NOTIMG', 'name' => 'Not An Image', 'images' => 'https://img.test/not-an-image.jpg']),
                $this->productRow(['sku' => 'IMPT-WARN', 'name' => 'Warning Row', 'old_price' => 50, 'new_price' => 90]),
            ],
            'Variants' => [['sku' => 'IMPT-NOPE', 'color' => 'Red', 'price' => 10]],
        ]);

        $byName = collect($plan['products'])->keyBy('name');
        $this->assertSame('Products row 3, Category: Category \'No Such Category\' not found (categories are never created by the import).',
            \App\Services\ProductImport\ImportResultExport::describe($byName['Bad Category']['messages'][0]));
        $this->assertSame('error', $byName['Bad Price']['status']);
        $this->assertStringContainsString("Brand 'Nekko Pro Unknown' not found", $byName['Bad Brand']['messages'][0]['text']);
        $this->assertStringContainsString('could not be downloaded', collect($byName['Bad Image']['messages'])->pluck('text')->implode(' '));
        $this->assertStringContainsString('is not a JPG, PNG, GIF or WebP image', collect($byName['Not An Image']['messages'])->pluck('text')->implode(' '));
        $this->assertSame('warning', $byName['Warning Row']['status']);
        $this->assertStringContainsString("SKU 'IMPT-NOPE' not found", $plan['orphans'][0]['text']);

        $this->runImport($plan['id']);
        $this->assertTrue(Product::where('product_code', 'IMPT-GOOD')->exists());
        $this->assertTrue(Product::where('product_code', 'IMPT-WARN')->exists());
        foreach (['IMPT-BADCAT', 'IMPT-BADPRICE', 'IMPT-BADBRAND', 'IMPT-BADIMG', 'IMPT-NOTIMG'] as $sku) {
            $this->assertFalse(Product::where('product_code', $sku)->exists(), $sku);
        }
    }

    public function test_unknown_list_items_can_be_auto_created_but_categories_never(): void
    {
        $plan = $this->upload(['Products' => [$this->productRow(['sku' => 'IMPT-NEWBRAND', 'name' => 'New Brand Product', 'brand' => 'Fresh Brand Qx'])]], null, ['auto_create' => 1]);
        $this->assertSame(['Brand' => ['Fresh Brand Qx']], $plan['to_create']);
        $this->runImport($plan['id']);

        $brand = Brand::where('name', 'Fresh Brand Qx')->firstOrFail();
        $this->assertSame($brand->id, (int) Product::where('product_code', 'IMPT-NEWBRAND')->value('brand_id'));

        $plan = $this->upload(['Products' => [$this->productRow(['sku' => 'IMPT-NEWCAT', 'category' => 'Brand New Category'])]], null, ['auto_create' => 1]);
        $this->assertSame('error', $plan['products'][0]['status']);
        $this->assertFalse(Category::where('name', 'Brand New Category')->exists());
    }

    public function test_duplicate_skus_in_the_file_are_errors(): void
    {
        $plan = $this->upload(['Products' => [
            $this->productRow(['sku' => 'IMPT-DUP', 'name' => 'First']),
            $this->productRow(['sku' => 'impt-dup', 'name' => 'Second']),
        ]]);

        $this->assertSame(['error', 'error'], array_column($plan['products'], 'status'));
        $this->assertStringContainsString('appears more than once', $plan['products'][1]['messages'][0]['text']);
    }

    // ================================================================ updates

    public function test_update_changes_only_filled_cells_and_never_touches_stock_unless_asked(): void
    {
        $plan = $this->upload(['Products' => [$this->productRow(['sku' => 'IMPT-UPD', 'name' => 'Update Me', 'stock' => 10, 'meta_title' => 'My Own Title', 'note' => 'keep me'])]]);
        $this->runImport($plan['id']);
        $product = Product::where('product_code', 'IMPT-UPD')->firstOrFail();
        $before = $product->only(['name', 'meta_title', 'meta_description', 'note', 'category_id', 'slug', 'short_name']);

        // Only SKU + new price (+ a stock value that must be ignored).
        $plan = $this->upload(['Products' => [['sku' => 'IMPT-UPD', 'new_price' => 199, 'stock' => 99]]], null, ['mode' => 'update']);
        $this->assertSame('warning', $plan['products'][0]['status']);
        $this->runImport($plan['id']);

        $product->refresh();
        $this->assertSame(199, (int) $product->new_price);
        $this->assertSame($before, $product->only(array_keys($before)), 'empty cells never overwrite');
        $this->assertSame(10, (int) InventoryStock::where('product_id', $product->id)->value('on_hand'));

        // With "Update stock from file" it becomes a logged correction.
        $plan = $this->upload(['Products' => [['sku' => 'IMPT-UPD', 'stock' => 25]]], null, ['mode' => 'update', 'update_stock' => 1]);
        $this->runImport($plan['id']);
        $this->assertSame(25, (int) InventoryStock::where('product_id', $product->id)->value('on_hand'));
        $this->assertSame(InventoryMovement::TYPE_CORRECTION, InventoryMovement::where('product_id', $product->id)->latest('id')->value('movement_type'));
    }

    public function test_modes_decide_what_happens_to_existing_and_new_skus(): void
    {
        $plan = $this->upload(['Products' => [$this->productRow(['sku' => 'IMPT-MODE'])]]);
        $this->runImport($plan['id']);

        $create = $this->upload(['Products' => [$this->productRow(['sku' => 'IMPT-MODE'])]], null, ['mode' => 'create']);
        $this->assertStringContainsString('already exists', $create['products'][0]['messages'][0]['text']);

        $update = $this->upload(['Products' => [$this->productRow(['sku' => 'IMPT-MISSING'])]], null, ['mode' => 'update']);
        $this->assertStringContainsString('No product with SKU', $update['products'][0]['messages'][0]['text']);

        $both = $this->upload(['Products' => [$this->productRow(['sku' => 'IMPT-MODE']), $this->productRow(['sku' => 'IMPT-MODE2'])]], null, ['mode' => 'both']);
        $this->assertSame(['update', 'create'], array_column($both['products'], 'action'));
    }

    // ================================================================ result file

    public function test_the_result_file_has_a_result_column_per_row(): void
    {
        $plan = $this->upload([
            'Products' => [$this->productRow(['sku' => 'IMPT-RES1', 'name' => 'Result Ok']), $this->productRow(['sku' => 'IMPT-RES2', 'category' => 'Nope'])],
            'Variants' => [['sku' => 'IMPT-RES2', 'color' => 'Red', 'price' => 10]],
        ]);
        $this->runImport($plan['id']);

        $response = $this->actingAs($this->admin, 'admin')->get(route('products.import.result', $plan['id']))->assertOk();
        $path = $this->temp('xlsx');
        File::put($path, $response->streamedContent());
        $book = IOFactory::load($path);

        $products = $book->getSheetByName('Products');
        $resultCol = Coordinate::stringFromColumnIndex(count(ImportColumns::products()) + 1);
        $this->assertSame('Result', $products->getCell("{$resultCol}1")->getValue());
        $this->assertStringStartsWith('Created (IMPT-RES1)', $products->getCell("{$resultCol}2")->getValue());
        $this->assertStringStartsWith('Skipped: Products row 3, Category:', $products->getCell("{$resultCol}3")->getValue());
        $this->assertSame('Skipped: the product has errors (see the Products sheet).', $book->getSheetByName('Variants')->getCell('H2')->getValue());
        $this->assertSame('Result Ok', $products->getCell('B2')->getValue(), 'original rows are kept');
    }

    public function test_another_admin_cannot_open_someone_elses_import(): void
    {
        $plan = $this->upload(['Products' => [$this->productRow(['sku' => 'IMPT-PRIV'])]]);

        $this->actingAs($this->makeAdmin(), 'admin')->get(route('products.import.show', $plan['id']))->assertNotFound();
    }

    // ================================================================ helpers

    private function productRow(array $overrides = []): array
    {
        return array_merge([
            'sku' => 'IMPT-' . Str::upper(Str::random(6)),
            'name' => 'Import Test Product ' . Str::random(4),
            'category' => $this->category->name,
            'subcategory' => $this->subcategory->subcategoryName,
            'new_price' => 100,
        ], $overrides);
    }

    /** Builds an .xlsx from rows keyed by column key, uploads it and checks all image links. */
    private function upload(array $sheets, ?string $zip = null, array $options = []): array
    {
        $book = new Spreadsheet();
        $book->removeSheetByIndex(0);
        foreach (ImportColumns::sheets() as $name => $columns) {
            $sheet = $book->createSheet()->setTitle($name);
            foreach ($columns as $i => $column) {
                $sheet->setCellValue(Coordinate::stringFromColumnIndex($i + 1) . '1', $column['header'] . ($column['required'] ? ' *' : ''));
            }
            $keys = array_column($columns, 'key');
            foreach ($sheets[$name] ?? [] as $r => $row) {
                foreach ($row as $key => $value) {
                    $sheet->setCellValueExplicit(Coordinate::stringFromColumnIndex(array_search($key, $keys, true) + 1) . ($r + 2), (string) $value, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                }
            }
        }
        $path = $this->temp('xlsx');
        (new Xlsx($book))->save($path);

        $payload = array_merge(['mode' => 'both', 'file' => new UploadedFile($path, 'products.xlsx', null, null, true)], $options);
        if ($zip) {
            $payload['images_zip'] = new UploadedFile($zip, 'images.zip', 'application/zip', null, true);
        }

        $response = $this->actingAs($this->admin, 'admin')->post(route('products.import.upload'), $payload);
        $response->assertSessionHasNoErrors();
        $id = basename(parse_url($response->headers->get('Location'), PHP_URL_PATH));
        $this->importIds[] = $id;

        for ($i = 0; $i < 50 && ImportStore::load($id)['phase'] === 'images'; $i++) {
            $this->actingAs($this->admin, 'admin')->postJson(route('products.import.images', $id))->assertOk();
        }

        return ImportStore::load($id);
    }

    private function runImport(string $id): array
    {
        for ($i = 0; $i < 50 && ImportStore::load($id)['phase'] !== 'done'; $i++) {
            $this->actingAs($this->admin, 'admin')->postJson(route('products.import.run', $id))->assertOk();
        }

        return ImportStore::load($id);
    }

    private function zip(array $names): string
    {
        $path = $this->temp('zip');
        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        foreach ($names as $i => $name) {
            $zip->addFromString('photos/' . $name, $this->png($i + 1));
        }
        $zip->addFromString('photos/readme.txt', 'ignored');
        $zip->close();

        return $path;
    }

    private function png(int $shade = 0): string
    {
        $image = imagecreatetruecolor(4, 4);
        imagefill($image, 0, 0, imagecolorallocate($image, 200, 100 + $shade * 20, 50));
        ob_start();
        imagepng($image);

        return (string) ob_get_clean();
    }

    private function temp(string $extension): string
    {
        $path = storage_path('framework/testing/import-' . Str::random(8) . '.' . $extension);
        File::ensureDirectoryExists(dirname($path));

        return $this->tempFiles[] = $path;
    }

    private function makeAdmin(): User
    {
        $admin = User::create([
            'name' => 'Import Test Admin', 'email' => 'import-' . Str::uuid() . '@example.test',
            'password' => bcrypt('secret'), 'status' => 1,
        ]);
        $admin->assignRole(Role::findOrCreate('Admin', 'admin'));

        return $admin;
    }
}
