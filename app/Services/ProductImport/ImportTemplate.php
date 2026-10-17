<?php

namespace App\Services\ProductImport;

use App\Models\Product;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\NamedRange;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * The downloadable product import workbook: Products, Variants, Wholesale, Instructions and
 * Lists (live from the shop, used as dropdowns).
 */
class ImportTemplate
{
    public const DATA_ROWS = 1000;
    public const BROWN = '2A1505';
    public const CREAM = 'FDF9F4';
    public const RED = 'C0392B';
    public const ORANGE = 'E8861A';

    public function build(ImportLookups $lookups): Spreadsheet
    {
        $book = new Spreadsheet();
        $book->getProperties()->setCreator('Bilai Ghor')->setTitle('Bilai Ghor – Product Import Template');
        $book->getDefaultStyle()->getFont()->setName('Calibri')->setSize(11);

        $this->listsSheet($book, $lookups);

        $index = 0;
        foreach (ImportColumns::sheets() as $title => $columns) {
            $this->dataSheet($book->createSheet($index++), $title, $columns, $this->examples()[$title]);
        }
        $this->instructionsSheet($book->createSheet($index));

        $book->setActiveSheetIndex(0);

        return $book;
    }

    private function listsSheet(Spreadsheet $book, ImportLookups $lookups): void
    {
        $lists = $lookups->forTemplate();
        $units = Product::query()->whereNotNull('pro_unit')->where('pro_unit', '!=', '')->distinct()->pluck('pro_unit')->all();

        $sheet = $book->getActiveSheet()->setTitle('Lists');
        $columns = [
            ['Categories', 'Categories', $lists['categories']],
            ['Subcategories', 'Subcategories', array_column($lists['subcategories'], 'name')],
            [null, 'Subcategory belongs to category', array_column($lists['subcategories'], 'parent')],
            ['ChildCategories', 'Child categories', array_column($lists['childcategories'], 'name')],
            [null, 'Child category belongs to subcategory', array_column($lists['childcategories'], 'parent')],
            ['Brands', 'Brands', $lists['brands']],
            ['Weights', 'Weights', $lists['weights']],
            ['LifeStages', 'Life stages', $lists['life_stages']],
            ['Flavors', 'Flavors', $lists['flavors']],
            ['Colors', 'Colors', $lists['colors']],
            ['Sizes', 'Sizes', $lists['sizes']],
            ['Units', 'Units', array_values(array_unique(array_merge(ImportColumns::UNITS, $units)))],
            ['Types', 'Types', ImportColumns::TYPES],
            ['Badges', 'Badges', ImportColumns::BADGES],
            ['YesNo', 'Yes / No', ['Yes', 'No']],
        ];

        $longest = 0;
        foreach ($columns as $i => [$rangeName, $title, $values]) {
            $col = Coordinate::stringFromColumnIndex($i + 1);
            $sheet->setCellValue("{$col}1", $title);
            foreach (array_values($values) as $r => $value) {
                $sheet->setCellValueExplicit($col . ($r + 2), (string) $value, DataType::TYPE_STRING);
            }
            $sheet->getColumnDimension($col)->setWidth(max(14, min(34, mb_strlen($title) + 4)));
            if ($rangeName) {
                $last = max(2, count($values) + 1);
                $book->addNamedRange(new NamedRange($rangeName, $sheet, "\${$col}\$2:\${$col}\${$last}"));
            } else {
                $sheet->getStyle("{$col}:{$col}")->getFont()->setItalic(true)->getColor()->setRGB('7A6A5E');
            }
            $longest = max($longest, count($values));
        }
        self::styleHeader($sheet, count($columns), array_fill(0, count($columns), false));
        $sheet->freezePane('A2');
        $sheet->setCellValue('A' . ($longest + 4), 'These lists come live from the shop. Need a new brand, weight, flavor, colour or size? Add it in the admin first, or tick "Auto-create missing items" when importing. Categories are never created by the import.');
    }

    private function dataSheet(Worksheet $sheet, string $title, array $columns, array $rows): void
    {
        $sheet->setTitle($title);
        foreach ($columns as $c => $column) {
            $col = Coordinate::stringFromColumnIndex($c + 1);
            $sheet->setCellValue("{$col}1", $column['header'] . ($column['required'] ? ' *' : ''));
            $sheet->getColumnDimension($col)->setWidth($column['width']);

            $tip = $column['format'] . ($column['allowed'] !== '' ? "\nAllowed: " . $column['allowed'] : '') . "\nIf blank: " . $column['blank'];
            $sheet->getComment("{$col}1")->setWidth('260pt')->setHeight('110pt')->getText()->createTextRun($tip);

            if ($column['list']) {
                $validation = new DataValidation();
                $validation->setType(DataValidation::TYPE_LIST)
                    ->setFormula1($column['list'])
                    ->setAllowBlank(true)
                    ->setShowDropDown(true)
                    ->setShowErrorMessage(true)
                    ->setErrorStyle($column['strict'] ? DataValidation::STYLE_STOP : DataValidation::STYLE_WARNING)
                    ->setErrorTitle($column['header'])
                    ->setError($column['strict']
                        ? 'Please pick a value from the list (see the Lists sheet).'
                        : 'Not in the shop\'s list. It will be an error on import unless "Auto-create missing items" is ticked.');
                $sheet->setDataValidation("{$col}2:{$col}" . (self::DATA_ROWS + 1), $validation);
            }
        }

        foreach ($rows as $r => $row) {
            foreach ($row as $c => $value) {
                if ($value !== '' && $value !== null) {
                    $sheet->setCellValue(Coordinate::stringFromColumnIndex($c + 1) . ($r + 2), $value);
                }
            }
        }

        self::styleHeader($sheet, count($columns), array_column($columns, 'required'));
        $lastCol = Coordinate::stringFromColumnIndex(count($columns));
        $sheet->freezePane('C2');
        $sheet->setAutoFilter("A1:{$lastCol}1");
        $exampleRange = "A2:{$lastCol}" . (count($rows) + 1);
        $sheet->getStyle($exampleRange)->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
        // Example rows are tinted so they're easy to spot and replace.
        $sheet->getStyle($exampleRange)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFF6E9');
        $sheet->getTabColor()->setRGB($title === ImportColumns::SHEET_PRODUCTS ? self::ORANGE : 'AA8453');

        if ($title === ImportColumns::SHEET_PRODUCTS) {
            $sheet->getStyle('G2:G' . (self::DATA_ROWS + 1))->getAlignment()->setWrapText(true);
            $sheet->getRowDimension(2)->setRowHeight(120);
            $sheet->getRowDimension(3)->setRowHeight(60);
            $sheet->getStyle('I2:L' . (self::DATA_ROWS + 1))->getNumberFormat()->setFormatCode('#,##0.##');
        }
    }

    private function instructionsSheet(Worksheet $sheet): void
    {
        $sheet->setTitle('Instructions');
        $sheet->getTabColor()->setRGB(self::BROWN);
        foreach (['A' => 14, 'B' => 24, 'C' => 11, 'D' => 44, 'E' => 36, 'F' => 34, 'G' => 52] as $col => $width) {
            $sheet->getColumnDimension($col)->setWidth($width);
        }

        $line = 1;
        $sheet->setCellValue("A{$line}", 'Bilai Ghor – Product import template');
        $sheet->getStyle("A{$line}")->getFont()->setBold(true)->setSize(16)->getColor()->setRGB(self::BROWN);
        $line += 2;

        foreach ($this->rules() as $heading => $items) {
            $sheet->setCellValue("A{$line}", $heading);
            $sheet->getStyle("A{$line}")->getFont()->setBold(true)->setSize(12)->getColor()->setRGB(self::BROWN);
            $line++;
            foreach ($items as $item) {
                $sheet->setCellValue("A{$line}", '•');
                $sheet->setCellValue("B{$line}", $item);
                $sheet->mergeCells("B{$line}:G{$line}");
                $sheet->getStyle("B{$line}")->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);
                $sheet->getRowDimension($line)->setRowHeight(max(15, 15 * (int) ceil(mb_strlen($item) / 160)));
                $line++;
            }
            $line++;
        }

        $sheet->setCellValue("A{$line}", 'Every column');
        $sheet->getStyle("A{$line}")->getFont()->setBold(true)->setSize(12)->getColor()->setRGB(self::BROWN);
        $line++;
        $headRow = $line;
        foreach (['Sheet', 'Column', 'Required', 'Format', 'Allowed values', 'Example', 'If left blank'] as $i => $header) {
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($i + 1) . $line, $header);
        }
        self::styleHeader($sheet, 7, array_fill(0, 7, false), $line);
        $line++;

        foreach (ImportColumns::sheets() as $sheetName => $columns) {
            foreach ($columns as $column) {
                $allowed = $column['allowed'] === '' && $column['list'] ? 'See Lists sheet' : $column['allowed'];
                $values = [$sheetName, $column['header'], $column['required'] ? 'Required' : 'Optional', $column['format'], $allowed, $column['example'], $column['blank']];
                foreach ($values as $i => $value) {
                    $sheet->setCellValueExplicit(Coordinate::stringFromColumnIndex($i + 1) . $line, (string) $value, DataType::TYPE_STRING);
                }
                if ($column['required']) {
                    $sheet->getStyle("C{$line}")->getFont()->setBold(true)->getColor()->setRGB(self::RED);
                }
                $line++;
            }
        }
        $range = "A{$headRow}:G" . ($line - 1);
        $sheet->getStyle($range)->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);
        $sheet->getStyle($range)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('E5DACB');
    }

    private function rules(): array
    {
        return [
            'How it works' => [
                '1. Fill in the Products sheet (one row per product). Add colour/size variants in the Variants sheet and wholesale tiers in the Wholesale sheet, linked by SKU.',
                '2. In the admin: Products → Import Products → upload this file (and, optionally, a ZIP with the images named in the file).',
                '3. Pick a mode: "Create new only", "Update existing only" or "Create and update". Check the preview (OK / Warning / Error per product), then press Import.',
                '4. Download the result file: your rows plus a "Result" column. Fix the failed rows and upload just those again.',
            ],
            'Format rules' => [
                'Required columns have a red header and a *. The orange-tinted rows in each sheet are examples: replace or delete them.',
                'Yes/No columns accept Yes / No, 1 / 0 or TRUE / FALSE.',
                'Prices are plain numbers in BDT, without ৳ or commas (85, not ৳85 or 1,250).',
                'Images: either https:// links, or the file names of images inside the ZIP you upload with this file. Separate several with |. Alt texts go in the matching column, in the same order, also separated by |.',
                'Image files: JPG, PNG, WebP or GIF, max 5 MB each. A link that cannot be downloaded is an error for that product.',
                'Dropdowns come from the Lists sheet (your shop\'s current categories, brands, weights, life stages, flavors, colours, sizes, units, types and badges).',
            ],
            'Auto-filled when a cell is empty (never overwrites what you typed)' => [
                'Short Name, Meta Title, Meta Description, Meta Keywords, image alt texts, Meta Image (first gallery image), SKU for new products, and the defaults: Type = Physical, Status = Yes, other toggles = No, Stock = 0.',
                'Plain-text descriptions are turned into clean HTML: blank line = paragraph, "- " or "• " lines = bullet list, a line ending with ":" = bold heading. HTML is accepted but cleaned (no scripts, styles or unsafe links).',
                'The preview marks every auto-filled value so you can see what was added.',
            ],
            'Updating existing products' => [
                'Products are matched by SKU (the product code shown in the admin, e.g. P0241, or your own code).',
                'Only cells that have a value are changed. Empty cells never erase existing data. Nothing is ever deleted (products, variants, images). Images in the file are added to the gallery (the same picture is not added twice).',
                'Stock is only changed when "Update stock from file" is ticked, and then it is recorded in the stock log as a correction.',
            ],
            'Errors and warnings' => [
                'Error (the product is skipped): missing required value, unknown category/subcategory, invalid price, variant or wholesale row with an unknown SKU, duplicate SKU in the file, image that cannot be downloaded or is not a valid image.',
                'Warning (still imported): very long name, Old Price lower than New Price, no images, no description.',
                'Unknown brand / weight / life stage / flavor / colour / size: an error, unless "Auto-create missing items" is ticked (the preview lists what would be created). Categories are never auto-created.',
            ],
        ];
    }

    /** Two realistic example products (one with variants, one with wholesale tiers). */
    private function examples(): array
    {
        return [
            ImportColumns::SHEET_PRODUCTS => [
                ['BG-WHI-OF80', 'Whiskas Adult Pouch Ocean Fish Flavor Wet Cat Food – 80g', '', 'Cat Food', 'Pouch', '',
                    "Whiskas Ocean Fish is a complete and balanced wet food for adult cats (1+ years), made with real ocean fish in a tasty jelly.\n\nKey benefits:\n- Real ocean fish as the first ingredient\n- Omega 3 & 6 for a healthy skin and shiny coat\n- Taurine for healthy eyes and heart\n\nFeeding guide:\nServe 2–3 pouches a day for a 4 kg adult cat. Keep fresh water available.",
                    'Supplier: Mars BD', 70, 95, 85, 48, 'pouch', 'Whiskas', '80 gm', 'Adult', 'Ocean Fish',
                    'https://example.com/images/whiskas-ocean-fish-front.jpg|https://example.com/images/whiskas-ocean-fish-back.jpg', '', '',
                    'Physical', 0, 'No', 'Yes', 'No', 'No', 'Yes', 145, 'No', 'Best Seller', 'Yes', '', '', '', '', ''],
                ['BG-COLLAR-PB', 'Adjustable Pearl Bow Cat Collar with Bell for Cat', 'Pearl Bow Cat Collar', 'Cat Accessories', 'Cat Collar', '',
                    '<p>Soft adjustable collar with a pearl bow and a small bell, so you always hear where your cat is.</p><ul><li>Adjustable 20–30 cm</li><li>Safety buckle</li></ul>',
                    '', 180, 450, 350, 30, 'pcs', 'Other', '', '', '',
                    'pearl-collar-1.jpg|pearl-collar-2.jpg|pearl-collar-3.jpg', 'Pearl bow cat collar|Pearl bow cat collar on a cat|', 'https://youtu.be/dQw4w9WgXcQ',
                    'Physical', 0, 'No', 'Yes', 'Yes', 'No', 'No', 0, 'Yes', 'New Arrival', 'No',
                    'Pearl Bow Cat Collar with Bell – Bilai Ghor', 'cat collar, pearl collar, cat bell collar', 'Adjustable pearl bow cat collar with bell. Soft, safe buckle, 20–30 cm. Order online in Bangladesh from Bilai Ghor.', '', ''],
            ],
            ImportColumns::SHEET_VARIANTS => [
                ['BG-COLLAR-PB', 'Pink', 'S', 350, 10, 'pearl-collar-pink.jpg', ''],
                ['BG-COLLAR-PB', 'Pink', 'M', 370, 8, 'pearl-collar-pink.jpg', ''],
                ['BG-COLLAR-PB', 'Red', 'S', 350, 12, 'https://example.com/images/pearl-collar-red.jpg', 'Red pearl bow cat collar'],
            ],
            ImportColumns::SHEET_WHOLESALE => [
                ['BG-WHI-OF80', 12, 47, 80, 0],
                ['BG-WHI-OF80', 48, '', 76, 0],
            ],
        ];
    }

    public static function styleHeader(Worksheet $sheet, int $count, array $required, int $row = 1): void
    {
        foreach (range(1, $count) as $i) {
            $col = Coordinate::stringFromColumnIndex($i);
            $style = $sheet->getStyle("{$col}{$row}");
            $isRequired = (bool) ($required[$i - 1] ?? false);
            $style->getFont()->setBold(true)->getColor()->setRGB($isRequired ? 'FFFFFF' : self::CREAM);
            $style->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($isRequired ? self::RED : self::BROWN);
            $style->getAlignment()->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
        }
        $sheet->getRowDimension($row)->setRowHeight(30);
    }
}
