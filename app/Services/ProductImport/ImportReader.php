<?php

namespace App\Services\ProductImport;

use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use RuntimeException;

/**
 * Reads an uploaded import workbook into rows keyed by column key (see ImportColumns).
 *
 * Formulas are never calculated (PhpSpreadsheet can run functions such as WEBSERVICE); the value
 * Excel last saved for the cell is used instead.
 */
class ImportReader
{
    public const MAX_PRODUCTS = 1000;
    public const MAX_SUB_ROWS = 5000;

    /**
     * @return array{sheets: array<string, array<int, array{row:int, cells:array<string,string>}>>, headers: array<string, array<string,string>>}
     */
    public function read(string $path): array
    {
        try {
            $reader = IOFactory::createReader('Xlsx');
            $reader->setReadDataOnly(true);
            $reader->setLoadSheetsOnly([ImportColumns::SHEET_PRODUCTS, ImportColumns::SHEET_VARIANTS, ImportColumns::SHEET_WHOLESALE]);
            $book = $reader->load($path);
        } catch (\Throwable $e) {
            throw new RuntimeException('The file could not be read as an Excel (.xlsx) workbook. Please use the template.');
        }

        if (!$book->getSheetByName(ImportColumns::SHEET_PRODUCTS)) {
            throw new RuntimeException('The workbook has no "Products" sheet. Please use the template.');
        }

        $result = ['sheets' => [], 'headers' => []];
        foreach (ImportColumns::sheets() as $name => $columns) {
            $sheet = $book->getSheetByName($name);
            if (!$sheet) {
                $result['sheets'][$name] = [];
                continue;
            }
            [$map, $missing] = $this->headerMap($sheet, $columns);
            if ($missing) {
                throw new RuntimeException("The \"{$name}\" sheet is missing the column(s): " . implode(', ', $missing) . '. Please use the template.');
            }
            $result['headers'][$name] = collect($columns)->pluck('header', 'key')->all();
            $result['sheets'][$name] = $this->rows($sheet, $map, $name === ImportColumns::SHEET_PRODUCTS ? self::MAX_PRODUCTS : self::MAX_SUB_ROWS, $name);
        }

        $book->disconnectWorksheets();

        return $result;
    }

    /** @return array{0: array<string,string>, 1: string[]} column letter => key, missing required headers */
    private function headerMap(Worksheet $sheet, array $columns): array
    {
        $byHeader = collect($columns)->keyBy(fn ($c) => ImportColumns::normalizeHeader($c['header']));
        $map = [];
        $highest = Coordinate::columnIndexFromString($sheet->getHighestDataColumn(1));
        for ($i = 1; $i <= $highest; $i++) {
            $letter = Coordinate::stringFromColumnIndex($i);
            $header = ImportColumns::normalizeHeader((string) $sheet->getCell("{$letter}1")->getValue());
            if ($header !== '' && isset($byHeader[$header]) && !in_array($byHeader[$header]['key'], $map, true)) {
                $map[$letter] = $byHeader[$header]['key'];
            }
        }
        $missing = collect($columns)->where('required', true)->filter(fn ($c) => !in_array($c['key'], $map, true))->pluck('header')->all();

        return [$map, $missing];
    }

    private function rows(Worksheet $sheet, array $map, int $limit, string $name): array
    {
        $rows = [];
        $highestRow = $sheet->getHighestDataRow();
        for ($r = 2; $r <= $highestRow; $r++) {
            $cells = [];
            foreach ($map as $letter => $key) {
                $cells[$key] = $this->value($sheet->getCell("{$letter}{$r}"));
            }
            if (implode('', $cells) === '') {
                continue;
            }
            if (count($rows) >= $limit) {
                throw new RuntimeException("The \"{$name}\" sheet has more than {$limit} rows. Please split the file.");
            }
            $rows[] = ['row' => $r, 'cells' => $cells];
        }

        return $rows;
    }

    private function value(Cell $cell): string
    {
        $value = $cell->isFormula() ? $cell->getOldCalculatedValue() : $cell->getValue();

        if ($value instanceof \PhpOffice\PhpSpreadsheet\RichText\RichText) {
            $value = $value->getPlainText();
        }
        if (is_bool($value)) {
            return $value ? 'TRUE' : 'FALSE';
        }
        if (is_float($value) || is_int($value)) {
            if (Date::isDateTime($cell) && $value > 0) {
                return Date::excelToDateTimeObject($value)->format('Y-m-d');
            }
            // 85.0 → "85", 85.5 → "85.5" (no float noise like 85.499999)
            return rtrim(rtrim(number_format((float) $value, 6, '.', ''), '0'), '.');
        }

        return trim(str_replace(["\r\n", "\r"], "\n", (string) $value));
    }
}
