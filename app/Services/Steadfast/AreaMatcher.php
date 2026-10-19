<?php

namespace App\Services\Steadfast;

use App\Models\DeliveryDistrict;
use App\Models\DeliveryThana;
use App\Models\SteadfastPoliceStation;
use App\Models\ThanaSteadfastPoliceStation;
use Illuminate\Support\Facades\Cache;

/**
 * Matches our (Bangla) thanas to Steadfast police stations inside the same district.
 * Names are transliterated to Latin and compared as consonant skeletons ("কক্সবাজার সদর" ≈ "Cox's Bazar Sadar").
 * Admin choices (match_type "manual") are never overwritten.
 */
class AreaMatcher
{
    public const MAP_CACHE_KEY = 'steadfast_area_map';

    /** Similarity at or above this is an exact (auto-used) match; from CLOSE it is a suggestion to confirm. */
    public const EXACT = 0.999;
    public const CLOSE = 0.75;

    /** Our district names → Steadfast district names. Dhaka is split into two Steadfast districts. */
    public const DISTRICTS = [
        'বরগুনা' => ['Barguna'], 'বরিশাল' => ['Barishal'], 'ভোলা' => ['Bhola'], 'ঝালকাঠি' => ['Jhalokati'],
        'পটুয়াখালী' => ['Patuakhali'], 'পিরোজপুর' => ['Pirojpur'], 'বান্দরবান' => ['Bandarban'],
        'ব্রাহ্মণবাড়িয়া' => ['Brahmanbaria'], 'চাঁদপুর' => ['Chandpur'], 'চট্টগ্রাম' => ['Chittagong'],
        'কুমিল্লা' => ['Cumilla'], 'কক্সবাজার' => ["Cox's Bazar"], 'ফেনী' => ['Feni'], 'খাগড়াছড়ি' => ['Khagrachori'],
        'লক্ষ্মীপুর' => ['Laxmipur'], 'নোয়াখালী' => ['Noakhali'], 'রাঙ্গামাটি' => ['Rangamati'],
        'ঢাকা' => ['Dhaka City', 'Dhaka Sub-Urban'], 'Dhaka' => ['Dhaka City', 'Dhaka Sub-Urban'],
        'ফরিদপুর' => ['Faridpur'], 'গাজীপুর' => ['Gazipur'], 'গোপালগঞ্জ' => ['Gopalganj'], 'কিশোরগঞ্জ' => ['Kishoreganj'],
        'মাদারীপুর' => ['Madaripur'], 'মানিকগঞ্জ' => ['Manikganj'], 'মুন্সিগঞ্জ' => ['Munshiganj'],
        'নারায়ণগঞ্জ' => ['Narayanganj'], 'নরসিংদী' => ['Narshindi'], 'রাজবাড়ী' => ['Rajbari'], 'শরীয়তপুর' => ['Shariatpur'],
        'টাঙ্গাইল' => ['Tangail'], 'বাগেরহাট' => ['Bagerhat'], 'চুয়াডাঙ্গা' => ['Chuadanga'], 'যশোর' => ['Jashore'],
        'ঝিনাইদহ' => ['Jhenaidah'], 'খুলনা' => ['Khulna'], 'কুষ্টিয়া' => ['Kustia'], 'মাগুরা' => ['Magura'],
        'মেহেরপুর' => ['Meherpur'], 'নড়াইল' => ['Narail'], 'সাতক্ষীরা' => ['Shatkhira'], 'জামালপুর' => ['Jamalpur'],
        'ময়মনসিংহ' => ['Mymensingh'], 'নেত্রকোণা' => ['Netrokona'], 'শেরপুর' => ['Sherpur'], 'বগুড়া' => ['Bogra'],
        'জয়পুরহাট' => ['Joypurhat'], 'নওগাঁ' => ['Naogaon'], 'নাটোর' => ['Natore'], 'চাঁপাইনবাবগঞ্জ' => ['Chapainawabganj'],
        'পাবনা' => ['Pabna'], 'রাজশাহী' => ['Rajshahi'], 'সিরাজগঞ্জ' => ['Sirajganj'], 'দিনাজপুর' => ['Dinajpur'],
        'গাইবান্ধা' => ['Gaibandha'], 'কুড়িগ্রাম' => ['Kurigram'], 'লালমনিরহাট' => ['Lalmonirhat'],
        'নীলফামারী' => ['Nilphamari'], 'পঞ্চগড়' => ['Panchgarh'], 'রংপুর' => ['Rangpur'], 'ঠাকুরগাঁও' => ['Thakurgaon'],
        'হবিগঞ্জ' => ['Habiganj'], 'মৌলভীবাজার' => ['Moulvibazar'], 'সুনামগঞ্জ' => ['Sunamganj'], 'সিলেট' => ['Sylhet'],
    ];

    /** Bangla words that Steadfast writes in English (translated before transliteration). */
    private const WORDS = [
        'উত্তর' => 'north', 'দক্ষিণ' => 'south', 'পূর্ব' => 'east', 'পশ্চিম' => 'west',
        'সিটি' => 'city', 'কর্পোরেশন' => 'corporation', 'পৌরসভা' => 'pourashava',
        'কোতয়ালী' => 'kotwali', 'কোতোয়ালী' => 'kotwali', 'কোতোয়ালি' => 'kotwali', 'কোতয়ালি' => 'kotwali',
        'বন্দর' => 'bandar', 'মডেল' => 'model', 'থানা' => 'thana', 'উপজেলা' => 'upazila',
    ];

    /**
     * Auto-match thanas that an admin has not decided on.
     *
     * @param  bool  $rematch  also recompute existing exact/close/none rows (manual rows are always kept)
     * @return array{exact:int, close:int, none:int, manual:int, no_district:int}
     */
    public function matchAll(bool $rematch = false): array
    {
        $byDistrict = $this->stationsByDistrict();
        $districts = DeliveryDistrict::pluck('name', 'id')->map(fn ($n) => self::nfc($n));
        $existing = ThanaSteadfastPoliceStation::get()->keyBy('thana_id');
        $counts = ['exact' => 0, 'close' => 0, 'none' => 0, 'manual' => 0, 'no_district' => 0];

        foreach (DeliveryThana::get(['id', 'district_id', 'name']) as $thana) {
            $row = $existing->get($thana->id);
            if ($row && $row->match_type === 'manual') {
                $counts['manual']++;
                continue;
            }
            if ($row && !$rematch && $row->match_type !== 'none') {
                $counts[$row->match_type] = ($counts[$row->match_type] ?? 0) + 1;
                continue;
            }

            $candidates = $this->candidates($byDistrict, (string) ($districts[$thana->district_id] ?? ''));
            if (!$candidates) {
                $counts['no_district']++;
            }
            [$station, $score] = $this->best((string) $thana->name, $candidates);
            $type = $score >= self::EXACT ? 'exact' : ($score >= self::CLOSE ? 'close' : 'none');
            if ($candidates) {
                $counts[$type]++;
            }

            ThanaSteadfastPoliceStation::updateOrCreate(['thana_id' => $thana->id], [
                'steadfast_police_station_id' => $type === 'none' ? null : $station['id'],
                'match_type'                  => $type,
                'score'                       => $station ? round($score, 3) : null,
                'matched_by'                  => null,
            ]);
        }

        self::forgetCache();

        return $counts;
    }

    /** Steadfast district names for one of our districts (by name, or by spelling when not in the list). */
    public function steadfastDistricts(string $ourDistrict): array
    {
        $ourDistrict = self::nfc($ourDistrict);
        foreach (self::DISTRICTS as $bn => $names) {
            if (self::nfc($bn) === $ourDistrict) {
                return $names;
            }
        }

        $key = self::skeleton($ourDistrict);
        $all = SteadfastPoliceStation::where('status', true)->distinct()->pluck('district_name');

        return $key === '' ? [] : $all->filter(fn ($name) => self::skeleton($name) === $key)->values()->all();
    }

    /**
     * Best Steadfast station for a thana name among candidates.
     *
     * @param  array<int, array{id:int, name:string, key:string, keys:array, sadar:bool}>  $candidates
     * @return array{0: ?array, 1: float}
     */
    public function best(string $thanaName, array $candidates): array
    {
        $keys = self::skeletons($thanaName);
        $sadar = self::isSadar($thanaName);
        $sadarCandidates = array_values(array_filter($candidates, fn ($c) => $c['sadar']));

        $best = null;
        $bestScore = 0.0;
        $scores = [];
        foreach ($candidates as $c) {
            $score = 0.0;
            foreach ($keys as $key) {
                foreach ($c['keys'] as $k) {
                    $score = max($score, self::similarity($key, $k));
                }
                // Search tags list nearby places too: a tag match is only a suggestion.
                foreach ($c['tags'] as $k) {
                    $score = max($score, min(0.9, self::similarity($key, $k)));
                }
            }
            if ($sadar !== $c['sadar']) {
                $score = min($score, 0.9);   // "Sadar" on one side only is never exact
            } elseif ($sadar && count($sadarCandidates) === 1) {
                $score = max($score, 0.9);   // the district's only "Sadar" station: suggest it
            }
            $scores[] = [$c, $score];
            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $c;
            }
        }

        // Two areas with the same skeleton (ধামরাই: "Dhamrai" and "Demra" are both "dmr"):
        // pick the closer spelling with vowels, and leave it for an admin to confirm.
        $ties = array_filter($scores, fn ($pair) => $pair[1] >= self::EXACT && $pair[1] >= $bestScore - 1e-9);
        if (count($ties) > 1) {
            $loose = self::loose($thanaName);
            usort($ties, fn ($a, $b) => self::similarity($loose, self::loose($b[0]['name'])) <=> self::similarity($loose, self::loose($a[0]['name'])));
            $best = $ties[0][0];
            $bestScore = 0.95;
        }

        return [$best, $bestScore];
    }

    /** Candidates of one of our districts, prepared for best(). */
    public function candidates(array $byDistrict, string $ourDistrict): array
    {
        return collect($this->steadfastDistricts($ourDistrict))
            ->flatMap(fn ($name) => $byDistrict[$name] ?? [])
            ->values()
            ->all();
    }

    /** Active Steadfast stations grouped by Steadfast district, with comparison keys. */
    public function stationsByDistrict(): array
    {
        $out = [];
        foreach (SteadfastPoliceStation::where('status', true)->get() as $s) {
            if (preg_match('/\btest\b/i', $s->name)) {
                continue;
            }
            // "Teknaf,টেকনাফ", "Fulbari // ফুলবাড়ি": every part of the name is a key.
            $keys = self::partKeys($s->name);
            $out[$s->district_name][] = [
                'id'    => (int) $s->id,
                'name'  => $s->name,
                'keys'  => $keys,
                'tags'  => array_values(array_diff(self::partKeys((string) $s->search_tags), $keys)),
                'sadar' => self::isSadar($s->name),
            ];
        }

        return $out;
    }

    private static function partKeys(string $text): array
    {
        $keys = [];
        foreach (preg_split('/[,;|\/]+/u', $text) as $part) {
            if (trim($part) !== '') {
                array_push($keys, ...self::skeletons($part));
            }
        }

        return array_values(array_unique(array_filter($keys)));
    }

    /**
     * Skeletons of a name with and without its Bangla words translated
     * ("মতলব দক্ষিণ" ≈ "Matlab South" and ≈ "Motlab dokkhin").
     */
    public static function skeletons(string $s): array
    {
        $words = preg_split('/\s+/u', trim(self::nfc($s)));
        $translated = implode(' ', array_map(fn ($w) => self::words()[$w] ?? $w, $words));

        return array_values(array_unique(array_filter([self::skeleton($translated), self::skeleton($s)])));
    }

    private static function words(): array
    {
        static $words = null;

        return $words ??= array_combine(array_map([self::class, 'nfc'], array_keys(self::WORDS)), self::WORDS);
    }

    /** Consonant skeleton: transliterate, lower-case, unify sounds spelled many ways, drop vowels. */
    public static function skeleton(string $s): string
    {
        $s = self::nfc($s);
        $s = (string) preg_replace('/\(.*?\)/u', ' ', $s);
        $bangla = (bool) preg_match('/\p{Bengali}/u', $s);
        if ($bangla) {
            // ং is written "ng" (Gangni, Baniachong) and ঁ as "n" (Chandpur, Santhia) in English names.
            $s = strtr($s, ['ং' => 'ng', 'ঁ' => 'n']);
            $s = (string) transliterator_transliterate('Any-Latin; Latin-ASCII', $s);
        }
        $s = strtolower($s);
        if (!$bangla) {
            $s = (string) preg_replace('/c(?!h)/', 'k', $s);   // English "Cumilla", "Cox's" = ক
        }
        $s = (string) preg_replace('/\b(sadara?|sador|sodor|thana|upazila|model|ps)\b/', ' ', $s);
        $s = (string) preg_replace('/[^a-z]/', '', $s);
        $s = strtr($s, ['ph' => 'f', 'bh' => 'b', 'gh' => 'g', 'kh' => 'k', 'th' => 't', 'dh' => 'd', 'ch' => 'c', 'jh' => 'j',
            'sh' => 's', 'z' => 'j', 'v' => 'b', 'w' => '', 'q' => 'k', 'x' => 'k', 'y' => '', 'h' => '']);
        $s = (string) preg_replace('/[aeiou]/', '', $s);
        $s = (string) preg_replace('/(.)\1+/', '$1', $s);

        // ক্ষ is "ks" in transliteration but "kh"/"x" in English (Laxmipur, Khetlal).
        return str_replace('ks', 'k', $s);
    }

    /** Latin letters with vowels kept ("ধামরাই" → "dhamarai"), for telling apart equal skeletons. */
    private static function loose(string $s): string
    {
        $s = (string) preg_replace('/,.*$/u', '', self::nfc($s));
        if (preg_match('/\p{Bengali}/u', $s)) {
            $s = (string) transliterator_transliterate('Any-Latin; Latin-ASCII', $s);
        }

        return (string) preg_replace('/[^a-z]/', '', strtolower($s));
    }

    public static function isSadar(string $s): bool
    {
        return (bool) preg_match('/sadar|sador|sodor|সদর/iu', self::nfc($s));
    }

    public static function similarity(string $a, string $b): float
    {
        if ($a === '' || $b === '') {
            return 0.0;
        }

        return 1 - levenshtein($a, $b) / max(strlen($a), strlen($b));
    }

    /** Bangla letters such as য় / ড় are stored both precomposed and decomposed; compare in one form. */
    public static function nfc(?string $s): string
    {
        $s = (string) $s;

        return class_exists(\Normalizer::class) ? (string) \Normalizer::normalize($s, \Normalizer::FORM_C) : $s;
    }

    public static function forgetCache(): void
    {
        Cache::forget(self::MAP_CACHE_KEY);
    }
}
