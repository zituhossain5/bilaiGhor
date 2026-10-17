<?php

namespace App\Services\ProductImport;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * The uploaded workbook with a "Result" column on Products, Variants and Wholesale, so the
 * failed rows can be fixed and uploaded again.
 */
class ImportResultExport
{
    public const GREEN = 'D9F2E3';
    public const RED = 'F9D6D5';
    public const AMBER = 'FFF1CC';

    public function build(array $plan, string $uploadPath): Spreadsheet
    {
        $reader = IOFactory::createReader('Xlsx');
        $book = $reader->load($uploadPath);
        $rows = $this->rowResults($plan);

        foreach ([ImportColumns::SHEET_PRODUCTS, ImportColumns::SHEET_VARIANTS, ImportColumns::SHEET_WHOLESALE] as $name) {
            $sheet = $book->getSheetByName($name);
            if (!$sheet) {
                continue;
            }
            // Put "Result" after the last header (re-using it if this file is itself a result file).
            $lastIndex = Coordinate::columnIndexFromString($sheet->getHighestDataColumn(1));
            $index = $lastIndex + 1;
            for ($i = 1; $i <= $lastIndex; $i++) {
                if (ImportColumns::normalizeHeader((string) $sheet->getCell(Coordinate::stringFromColumnIndex($i) . '1')->getValue()) === 'result') {
                    $index = $i;
                    break;
                }
            }
            $col = Coordinate::stringFromColumnIndex($index);
            $sheet->setCellValue("{$col}1", 'Result');
            $header = $sheet->getStyle("{$col}1");
            $header->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
            $header->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(ImportTemplate::BROWN);
            $sheet->getColumnDimension($col)->setWidth(60);

            foreach ($rows[$name] as $row => [$state, $text]) {
                $sheet->setCellValueExplicit("{$col}{$row}", $text, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                $style = $sheet->getStyle("{$col}{$row}");
                $style->getAlignment()->setWrapText(true);
                $style->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()
                    ->setRGB(['ok' => self::GREEN, 'error' => self::RED][$state] ?? self::AMBER);
            }
        }

        return $book;
    }

    /** @return array<string, array<int, array{0:string, 1:string}>> sheet => row => [state, text] */
    public function rowResults(array $plan): array
    {
        $out = [ImportColumns::SHEET_PRODUCTS => [], ImportColumns::SHEET_VARIANTS => [], ImportColumns::SHEET_WHOLESALE => []];

        foreach ($plan['products'] as $entry) {
            $productState = $this->state($entry);
            $errors = collect($entry['messages'])->where('level', 'error');
            $skipped = 'Skipped: ' . $errors->map(fn ($m) => self::describe($m))->implode(' ');

            if ($entry['row']) {
                $own = collect($entry['messages'])->where('sheet', ImportColumns::SHEET_PRODUCTS)->where('row', $entry['row']);
                $text = match ($productState[0]) {
                    'error' => $skipped,
                    default => $productState[1] . $this->warnings($own),
                };
                $out[ImportColumns::SHEET_PRODUCTS][$entry['row']] = [$productState[0], $text];
            }

            foreach ([ImportColumns::SHEET_VARIANTS => $entry['variants'], ImportColumns::SHEET_WHOLESALE => $entry['wholesale']] as $sheet => $lines) {
                foreach ($lines as $line) {
                    $own = collect($entry['messages'])->where('sheet', $sheet)->where('row', $line['row']);
                    if ($productState[0] === 'error') {
                        $text = $own->where('level', 'error')->isNotEmpty()
                            ? 'Skipped: ' . $own->where('level', 'error')->map(fn ($m) => self::describe($m))->implode(' ')
                            : 'Skipped: the product has errors (see the Products sheet).';
                        $out[$sheet][$line['row']] = ['error', $text];
                    } else {
                        $out[$sheet][$line['row']] = [$productState[0], $productState[1] . $this->warnings($own)];
                    }
                }
            }
            // Tiers dropped because the product isn't wholesale
            foreach (collect($entry['messages'])->where('sheet', ImportColumns::SHEET_WHOLESALE) as $message) {
                $out[ImportColumns::SHEET_WHOLESALE][$message['row']] ??= ['warning', $message['text']];
            }
        }

        foreach ($plan['orphans'] as $message) {
            $out[$message['sheet']][$message['row']] = ['error', 'Skipped: ' . $message['text']];
        }

        return $out;
    }

    /** "Row 7, Brand: 'Nekko Pro' not found" */
    public static function describe(array $message): string
    {
        $where = $message['row'] ? "{$message['sheet']} row {$message['row']}" : $message['sheet'];

        return "{$where}, {$message['column']}: {$message['text']}";
    }

    /** @return array{0:string, 1:string} */
    private function state(array $entry): array
    {
        if ($entry['status'] === 'error') {
            return ['error', ''];
        }
        $result = $entry['result'];
        if (!$result) {
            return ['warning', 'Not imported (the import was not run).'];
        }
        if ($result['status'] === 'failed') {
            return ['error', $result['text']];
        }

        return ['ok', $result['text'] . '.'];
    }

    private function warnings($messages): string
    {
        $warnings = $messages->where('level', 'warning')->pluck('text');

        return $warnings->isEmpty() ? '' : ' Warnings: ' . $warnings->implode(' ');
    }
}
