<?php

namespace App\Services\FraudCheck;

use App\Support\FraudPhone;
use Illuminate\Support\Carbon;

/**
 * Turns a BD Courier courier-check answer into what the Manual Fraud Check page shows.
 *
 * Each courier's rate is shown exactly as the API gives it, never recalculated from its counts
 * (the counts can be an older snapshot). Couriers with no history are listed apart and left out of
 * every average. A parcel range such as "50+" is shown as given and never added to totals.
 */
class FraudReport
{
    public const SOURCE = 'BD Courier';

    public const RISKS = [
        'new'    => ['label' => 'নতুন কাস্টমার', 'text' => 'কোনো কুরিয়ারে এই নম্বরের আগের পার্সেলের তথ্য নেই। প্রয়োজনে ফোনে কনফার্ম করে নিন।'],
        'safe'   => ['label' => 'নিরাপদ', 'text' => 'সফলতার হার ভালো। নিশ্চিন্তে অর্ডার প্রসেস করুন।'],
        'medium' => ['label' => 'মাঝারি ঝুঁকি', 'text' => 'ফোনে কনফার্ম করে নিন; প্রয়োজনে ডেলিভারি চার্জ অগ্রিম নিন।'],
        'high'   => ['label' => 'উচ্চ ঝুঁকি', 'text' => 'বেশিরভাগ পার্সেল ফেরত বা বাতিল হয়েছে। অগ্রিম পেমেন্ট ছাড়া পাঠাবেন না।'],
    ];

    /**
     * @param  string  $method  "average" or "weighted" (FraudCheckSettings::rateMethod())
     * @param  array{safe: float, medium: float}  $thresholds
     */
    public static function fromBdCourier(array $payload, string $method, array $thresholds): array
    {
        $data = is_array($payload['data'] ?? null) ? $payload['data'] : [];
        $summary = is_array($data['summary'] ?? null) ? $data['summary'] : [];
        $sharedSnapshot = self::date(self::pick($summary, 'snapshot_date') ?? self::pick($payload, 'snapshot_date'));

        $couriers = [];
        $noData = [];
        foreach ($data as $key => $info) {
            if ($key === 'summary' || !is_array($info)) {
                continue;
            }
            $row = self::courier((string) $key, $info, $sharedSnapshot);
            if ($row['has_data']) {
                $couriers[] = $row;
            } else {
                $noData[] = $row['name'];
            }
        }

        $overall = self::overall($couriers, $method);
        $counts = self::counts($couriers);
        $reports = array_values(array_filter((array) ($payload['reports'] ?? []), 'is_array'));
        $reportCount = max(count($reports), (int) (self::pick($summary, 'report_count') ?? self::pick($payload, 'report_count') ?? 0));
        $risk = self::risk($overall['rate'], $reportCount, $thresholds);
        $apiRate = is_numeric($summary['success_ratio'] ?? null) ? (float) $summary['success_ratio'] : null;

        $steadfast = collect($couriers)->firstWhere('key', 'steadfast');
        $volume = $steadfast
            ? ['text' => $steadfast['range'] ?? (string) $steadfast['total'], 'is_range' => $steadfast['range'] !== null]
            : null;

        $verdict = is_array($payload['risk_verdict'] ?? null) ? $payload['risk_verdict'] : null;
        $apiNote = self::pick($payload, 'explanation') ?? self::pick($summary, 'explanation');

        $report = [
            'source'       => self::SOURCE,
            'couriers'     => $couriers,
            'no_data'      => $noData,
            'overall'      => $overall,
            'counts'       => $counts,
            'volume'       => $volume,
            'reports'      => $reports,
            'report_count' => $reportCount,
            'risk'         => $risk,
            'api_rate'     => $apiRate,
            'verdict'      => $verdict ? [
                'label'   => is_string($verdict['label'] ?? null) ? $verdict['label'] : null,
                'action'  => is_string($verdict['action'] ?? null) ? $verdict['action'] : null,
                'reasons' => array_values(array_filter((array) ($verdict['reasons'] ?? []), 'is_string')),
            ] : null,
            'api_note'     => $apiNote !== null ? (string) $apiNote : null,
        ];
        $report['explanation'] = self::explain($report, $thresholds);

        return $report;
    }

    /** 99.333 → "99.3", 100.0 → "100" */
    public static function pct(?float $value): string
    {
        return $value === null ? '—' : rtrim(rtrim(number_format($value, 1, '.', ''), '0'), '.');
    }

    private static function courier(string $key, array $info, ?Carbon $sharedSnapshot): array
    {
        $known = (array) config("fraud_check.couriers.{$key}", []);
        $apiLogo = is_string($info['logo'] ?? null) && str_starts_with($info['logo'], 'https://') ? $info['logo'] : null;

        $range = self::pick($info, 'parcel_range');
        $rawTotal = $info['total_parcel'] ?? null;
        if (is_string($rawTotal) && trim($rawTotal) !== '' && !is_numeric($rawTotal)) {
            // e.g. "50+": a range, not a count.
            $range ??= trim($rawTotal);
            $rawTotal = null;
        }

        $total = is_numeric($rawTotal) ? (int) $rawTotal : null;
        $delivered = is_numeric($info['success_parcel'] ?? null) ? (int) $info['success_parcel'] : null;
        $cancelled = is_numeric($info['cancelled_parcel'] ?? null) ? (int) $info['cancelled_parcel'] : null;
        $rate = is_numeric($info['success_ratio'] ?? null) ? (float) $info['success_ratio'] : null;
        if ($total === null && $range === null && $delivered !== null) {
            $total = $delivered + (int) $cancelled;
        }

        $hasCounts = ($total ?? 0) > 0;
        $countsRate = $hasCounts && $delivered !== null ? $delivered / $total * 100 : null;
        $snapshot = $hasCounts ? (self::date(self::pick($info, 'snapshot_date')) ?? $sharedSnapshot) : null;

        return [
            'key'         => $key,
            'name'        => $known['name'] ?? (is_string($info['name'] ?? null) ? $info['name'] : ucfirst($key)),
            'logo'        => !empty($known['logo']) ? asset('public/assets/images/courier/' . $known['logo']) : $apiLogo,
            'total'       => $total,
            'delivered'   => $delivered,
            'cancelled'   => $cancelled,
            'rate'        => $rate,
            'range'       => $range !== null ? (string) $range : null,
            'has_data'    => $hasCounts || ($rate ?? 0) > 0 || $range !== null,
            'counts_rate' => $countsRate,
            'snapshot'    => $snapshot,
            // Counts and rate disagree by more than a point: they come from different times.
            'mismatch'    => $countsRate !== null && $rate !== null && abs($countsRate - $rate) > 1,
            'source'      => self::SOURCE,
        ];
    }

    private static function overall(array $couriers, string $method): array
    {
        $rated = array_values(array_filter($couriers, fn ($c) => $c['rate'] !== null));

        if ($method === 'weighted') {
            $weighted = array_values(array_filter($rated, fn ($c) => ($c['total'] ?? 0) > 0));
            $parcels = array_sum(array_column($weighted, 'total'));
            if ($parcels > 0) {
                $rate = array_sum(array_map(fn ($c) => $c['rate'] * $c['total'], $weighted)) / $parcels;

                return [
                    'rate'    => $rate,
                    'method'  => 'weighted',
                    'formula' => '(' . implode(' + ', array_map(fn ($c) => self::pct($c['rate']) . '×' . $c['total'], $weighted)) . ') ÷ ' . $parcels . ' = ' . self::pct($rate) . '%',
                    'note'    => 'পার্সেল সংখ্যা দিয়ে ভারিত গড়: যে কুরিয়ারে পার্সেল বেশি, তার হারের গুরুত্ব বেশি।',
                    'skipped' => array_values(array_column(array_filter($rated, fn ($c) => ($c['total'] ?? 0) === 0 || $c['total'] === null), 'name')),
                ];
            }
        }

        if (!$rated) {
            return ['rate' => null, 'method' => $method, 'formula' => null, 'note' => 'কোনো কুরিয়ারে আগের তথ্য নেই, তাই সামগ্রিক হার নেই।', 'skipped' => []];
        }

        $rate = array_sum(array_column($rated, 'rate')) / count($rated);

        return [
            'rate'    => $rate,
            'method'  => 'average',
            'formula' => '(' . implode(' + ', array_map(fn ($c) => self::pct($c['rate']), $rated)) . ') ÷ ' . count($rated) . ' = ' . self::pct($rate) . '%',
            'note'    => $method === 'weighted'
                ? 'পার্সেল সংখ্যা জানা নেই, তাই ভারিত গড়ের বদলে কুরিয়ারগুলোর হারের সাধারণ গড়।'
                : 'তথ্য আছে এমন প্রতিটি কুরিয়ারের হারের সাধারণ গড় (পার্সেল সংখ্যা বিবেচনা করা হয়নি)।',
            'skipped' => [],
        ];
    }

    /** Totals from the couriers' counts; ranges ("50+") are not counts and are never added. */
    private static function counts(array $couriers): array
    {
        $counted = array_filter($couriers, fn ($c) => ($c['total'] ?? 0) > 0);
        $total = array_sum(array_column($counted, 'total'));
        $delivered = array_sum(array_map(fn ($c) => (int) $c['delivered'], $counted));
        $cancelled = array_sum(array_map(fn ($c) => (int) $c['cancelled'], $counted));

        return [
            'total'         => $total,
            'delivered'     => $delivered,
            'cancelled'     => $cancelled,
            // Percentages from the same counts, so they never contradict the numbers next to them.
            'delivered_pct' => $total > 0 ? $delivered / $total * 100 : null,
            'cancelled_pct' => $total > 0 ? $cancelled / $total * 100 : null,
            'ranges'        => array_values(array_map(fn ($c) => $c['name'] . ' ' . $c['range'], array_filter($couriers, fn ($c) => $c['range'] !== null))),
        ];
    }

    private static function risk(?float $rate, int $reportCount, array $thresholds): array
    {
        $level = match (true) {
            $rate === null                  => 'new',
            $rate >= $thresholds['safe']    => 'safe',
            $rate >= $thresholds['medium']  => 'medium',
            default                         => 'high',
        };

        // Any fraud report means at least Medium.
        $raised = $reportCount > 0 && in_array($level, ['new', 'safe'], true);
        if ($raised) {
            $level = 'medium';
        }

        return ['level' => $level, 'raised_by_reports' => $raised] + self::RISKS[$level];
    }

    /** "How this is calculated", written from the actual data and settings. */
    private static function explain(array $report, array $thresholds): array
    {
        $lines = ['প্রতিটি কুরিয়ারের হার ' . self::SOURCE . ' যেমন দিয়েছে তেমনই দেখানো হয়েছে; সংখ্যা থেকে নতুন করে হিসাব করা হয়নি।'];

        $overall = $report['overall'];
        if ($overall['formula']) {
            $lines[] = 'সামগ্রিক হার ' . $overall['formula'] . ' — ' . $overall['note'];
        } else {
            $lines[] = $overall['note'];
        }
        if ($overall['skipped']) {
            $lines[] = implode(', ', $overall['skipped']) . ': পার্সেল সংখ্যা জানা নেই, তাই ভারিত গড়ে ধরা হয়নি।';
        }
        if ($report['no_data']) {
            $lines[] = 'তথ্য নেই (' . implode(', ', $report['no_data']) . ') — এগুলো কোনো গড় বা যোগফলে ধরা হয়নি।';
        }

        foreach ($report['couriers'] as $c) {
            $counts = $c['delivered'] !== null && $c['total'] ? "{$c['total']} টির মধ্যে {$c['delivered']} টি সফল" : null;
            if ($c['snapshot'] && $counts) {
                $lines[] = "{$c['name']}: হার " . self::pct($c['rate']) . "% বর্তমান; সংখ্যা ({$counts}) " . FraudPhone::banglaDate($c['snapshot']) . ' এর পুরনো হিসাব।';
            } elseif ($c['mismatch'] && $counts) {
                $lines[] = "{$c['name']}: হার " . self::pct($c['rate']) . "%, কিন্তু সংখ্যা ({$counts}) অনুযায়ী " . self::pct($c['counts_rate']) . '% — সংখ্যা ও হার ভিন্ন সময়ের, তাই হার যেমন এসেছে তেমনই রাখা হয়েছে।';
            }
            if ($c['range'] !== null) {
                $lines[] = "{$c['name']}: মোট পার্সেল \"{$c['range']}\" — এটি নির্দিষ্ট সংখ্যা নয়, তাই মোট যোগফলে ধরা হয়নি।";
            }
        }

        $counts = $report['counts'];
        if ($counts['total'] > 0) {
            $lines[] = "সফল/বাতিলের শতাংশ সংরক্ষিত সংখ্যা থেকে: মোট {$counts['total']}, সফল {$counts['delivered']}, বাতিল {$counts['cancelled']}।";
        }
        if ($report['api_rate'] !== null && $overall['rate'] !== null && abs($report['api_rate'] - $overall['rate']) >= 0.05) {
            $lines[] = self::SOURCE . '-এর নিজের সারাংশে হার ' . self::pct($report['api_rate']) . '% (পার্সেল সংখ্যা অনুযায়ী)।';
        }

        $lines[] = 'ঝুঁকি: ' . self::pct($thresholds['safe']) . '% বা বেশি = নিরাপদ, ' . self::pct($thresholds['medium'])
            . '% বা বেশি = মাঝারি, এর নিচে = উচ্চ; কোনো ফ্রড রিপোর্ট থাকলে কমপক্ষে মাঝারি। কোনো তথ্য না থাকলে "নতুন কাস্টমার"।';
        if ($report['report_count'] > 0) {
            $lines[] = "এই নম্বরে {$report['report_count']} টি ফ্রড/মার্চেন্ট রিপোর্ট আছে।";
        }
        if ($report['api_note']) {
            $lines[] = self::SOURCE . ': ' . $report['api_note'];
        }

        return $lines;
    }

    /** First optional field found under the names listed in config/fraud_check.php. */
    private static function pick(array $source, string $field): string|int|float|null
    {
        foreach ((array) config("fraud_check.fields.{$field}", []) as $name) {
            $value = $source[$name] ?? null;
            if (is_scalar($value) && !is_bool($value) && trim((string) $value) !== '') {
                return $value;
            }
        }

        return null;
    }

    private static function date(string|int|float|null $value): ?Carbon
    {
        if ($value === null) {
            return null;
        }

        try {
            $date = is_numeric($value)
                ? Carbon::createFromTimestamp((int) ($value > 9999999999 ? $value / 1000 : $value))
                : Carbon::parse((string) $value);
        } catch (\Throwable) {
            return null;
        }

        return $date->timezone(config('app.timezone'));
    }
}
