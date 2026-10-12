<?php

namespace App\Services;

use App\Models\GeneralSetting;
use App\Models\Order;
use Illuminate\Support\Collection;

/**
 * What the printed manual-order receipt shows: one row per item (repeats merged), the dense
 * style for long orders, and how rows split across A4 pages.
 *
 * Every row prints on exactly one line, so a page's capacity can be worked out here. The
 * heights below (mm) match receipt-styles.blade.php; change them together.
 */
class ReceiptLayout
{
    /** A4 height (297) minus the 10mm page margins and the receipt's 6mm top/bottom padding. */
    public const PAGE_HEIGHT = 265.0;

    /** Logo + title line, customer lines (address up to 3 lines) and the table header. */
    public const HEADER_FIRST = 37.0;

    /** "Order ID · Page 2/2" line and the table header on the following pages. */
    public const HEADER_NEXT = 12.0;

    /** Totals, payment line and the thank-you / social / QR footer row. */
    public const CLOSING = 50.0;

    public const ROW_HEIGHT = ['normal' => 4.6, 'dense' => 3.4];

    /** A weight/variant is shown after the name only when it is this short... */
    private const VARIANT_MAX = 10;

    /** ...and name + " · " + variant still fits on the line. */
    private const LINE_MAX = 44;

    public static function build(Order $order, ?GeneralSetting $settings): array
    {
        $lines = self::mergeLines($order->orderdetails);
        $denseAfter = max(1, (int) ($settings?->receipt_dense_after ?: 12));
        $dense = $lines->count() > $denseAfter;
        $commaList = (bool) ($settings?->receipt_comma_list ?? false);

        return [
            'lines' => $lines,
            'dense' => $dense,
            'commaList' => $commaList,
            'commaText' => self::commaText($lines),
            'pages' => $commaList ? [$lines->all()] : self::paginate($lines->all(), $dense ? 'dense' : 'normal'),
            'payment' => self::paymentLine($order),
        ];
    }

    /**
     * One row per product + variant + price: the same item added twice prints once with the
     * quantities added. Lines with a different price (or receipt name) stay separate so the
     * Price column stays true.
     *
     * @return Collection<int, array{name: string, variant: ?string, qty: int, price: float, amount: float}>
     */
    public static function mergeLines(iterable $details): Collection
    {
        $rows = [];

        foreach ($details as $line) {
            $name = $line->display_name;
            $variant = trim((string) $line->manual_variant);
            $key = implode('|', [
                $line->product_id ?: '',
                $line->kitten_pack_id ?: '',
                mb_strtolower($name),
                mb_strtolower($variant),
                $line->variant_price_id ?: '',
                $line->product_size ?: '',
                $line->product_color ?: '',
                (float) $line->sale_price,
            ]);
            $amount = (float) ($line->line_total ?: (($line->sale_price * $line->qty) - ($line->line_discount ?? 0)));

            if (isset($rows[$key])) {
                $rows[$key]['qty'] += (int) $line->qty;
                $rows[$key]['amount'] += $amount;
                continue;
            }

            $rows[$key] = [
                'name' => $name,
                'variant' => self::showVariant($name, $variant) ? $variant : null,
                'qty' => (int) $line->qty,
                'price' => (float) $line->sale_price,
                'amount' => $amount,
            ];
        }

        return collect(array_values($rows));
    }

    /**
     * Split rows into pages. The totals and footer print on the last page only, and a row is
     * never cut in two (each row is one line of a fixed height).
     *
     * @return array<int, array<int, array>>
     */
    public static function paginate(array $rows, string $density = 'normal'): array
    {
        $rowHeight = self::ROW_HEIGHT[$density] ?? self::ROW_HEIGHT['normal'];
        $pages = [];
        $first = true;

        while (true) {
            $available = self::PAGE_HEIGHT - ($first ? self::HEADER_FIRST : self::HEADER_NEXT);
            $count = count($rows);

            if ($count * $rowHeight + self::CLOSING <= $available) {
                $pages[] = $rows;

                return $pages;
            }

            // Fill this page, but leave at least one row to go with the totals.
            $take = max(1, min((int) floor($available / $rowHeight), $count - 1));
            $pages[] = array_splice($rows, 0, $take);
            $first = false;
        }
    }

    /** "Nekko Kitten Tuna Mousse ×5, Whiskas Ocean Fish ×1"; a variant is added only to tell same-named rows apart. */
    public static function commaText(Collection $lines): string
    {
        $nameCounts = $lines->countBy(fn ($row) => mb_strtolower($row['name']));

        return $lines->map(function ($row) use ($nameCounts) {
            $label = $row['name'];
            if ($nameCounts[mb_strtolower($row['name'])] > 1 && $row['variant']) {
                $label .= ' ' . $row['variant'];
            }

            return $label . ' ×' . $row['qty'];
        })->implode(', ');
    }

    /** "Paid via bKash", "Paid ৳500 via bKash · Due ৳300", or "Due ৳1,000 · Cash". */
    public static function paymentLine(Order $order): ?string
    {
        $state = OrderPaymentService::state($order);
        $method = self::methodLabel($state['method'] ?? null);
        $paid = (float) $state['paid'];
        $due = (float) $state['due'];
        $taka = fn (float $amount) => '৳' . number_format($amount, 0);

        if ($paid > 0 && $due <= 0) {
            return 'Paid' . ($method ? ' via ' . $method : '');
        }
        if ($paid > 0) {
            return 'Paid ' . $taka($paid) . ($method ? ' via ' . $method : '') . ' · Due ' . $taka($due);
        }
        if ($due > 0) {
            return 'Due ' . $taka($due) . ($method ? ' · ' . $method : '');
        }

        return $method ? 'Payment: ' . $method : null;
    }

    private static function methodLabel(?string $method): ?string
    {
        return match ($method) {
            null, '' => null,
            'bkash' => 'bKash',
            'nagad' => 'Nagad',
            'rocket' => 'Rocket',
            'bank_transfer' => 'Bank Transfer',
            'cash' => 'Cash',
            'cod' => 'Cash on Delivery',
            default => ucwords(str_replace('_', ' ', $method)),
        };
    }

    /** Short weights only, and not when the name already says it ("Whiskas Tuna 80g" + "80 gm"). */
    private static function showVariant(string $name, string $variant): bool
    {
        if ($variant === '' || mb_strlen($variant) > self::VARIANT_MAX) {
            return false;
        }
        if (mb_strlen($name) + 3 + mb_strlen($variant) > self::LINE_MAX) {
            return false;
        }

        $squash = fn (string $text) => str_replace(['grams', 'gram', 'gms', 'gm'], 'g', preg_replace('/\s+/u', '', mb_strtolower($text)));

        return !str_contains($squash($name), $squash($variant));
    }
}
