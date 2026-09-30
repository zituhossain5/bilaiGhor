<?php

namespace App\Services;

use App\Models\KittenPack;
use App\Models\Product;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Storefront product search.
 *
 * Matching runs in PHP over a small cached index of the catalog's searchable text, so
 * it works on shared hosting with no search server. It tolerates what a plain LIKE
 * cannot: spacing/punctuation differences ("paw paw" = "paw-paw" = "PawPaw"), words in
 * any order, partial words ("cann" → "Canned") and small typos ("chiken").
 *
 * Only text is cached. Stock, price and status are always read fresh from the database,
 * so inventory changes (which bypass model events) can never make results stale.
 */
class ProductSearchService
{
    public const CACHE_KEY = 'product_search_index_v1';
    private const CACHE_TTL = 600;

    /** Words that carry no meaning on their own in a product search. */
    private const STOP_WORDS = ['a', 'an', 'the', 'for', 'in', 'of', 'with', 'and', 'to', 'on', 'by', 'or', 'per'];

    /** Relevance tiers — lower is better. Out-of-stock only ranks lower within a tier. */
    private const TIER_EXACT = 1;      // whole name or SKU equals the query
    private const TIER_PHRASE = 2;     // the query appears inside the name (spacing ignored)
    private const TIER_NAME = 3;       // every word matched in the name (any order, partial or typo)
    private const TIER_OTHER = 4;      // every word matched, some only in category/brand/tags/SKU/description
    private const TIER_RELAXED = 5;    // most words matched — only used when nothing matched them all

    /**
     * Lowercase, "&" → "and", every run of punctuation/whitespace → one space.
     * Letters and combining marks of any script are kept, so Bangla text survives intact.
     */
    public static function normalize(?string $text): string
    {
        $text = html_entity_decode(strip_tags((string) $text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = mb_strtolower($text, 'UTF-8');
        $text = str_replace('&', ' and ', $text);
        $text = preg_replace('/[^\p{L}\p{M}\p{N}]+/u', ' ', $text);

        return trim($text);
    }

    /** Normalized text with every space removed: "Paw-Paw" and "PawPaw" both become "pawpaw". */
    public static function compact(?string $text): string
    {
        return str_replace(' ', '', self::normalize($text));
    }

    /** Drop the cached index — called whenever searchable catalog text changes. */
    public static function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Ranked product ids (best first) and matching kitten packs for a query.
     *
     * @return array{product_ids: int[], packs: \Illuminate\Support\Collection}
     */
    public function search(string $query, ?int $categoryId = null, ?int $limit = null): array
    {
        $tokens = $this->tokens($query);
        if (empty($tokens)) {
            return ['product_ids' => [], 'packs' => collect()];
        }

        $index = $this->index();
        $compactQuery = $tokens['compact'];

        $products = array_filter(
            $index['products'],
            fn ($entry) => !$categoryId || (int) $entry['category_id'] === $categoryId
        );

        $productHits = $this->rank($products, $tokens['significant'], $compactQuery);

        // Packs are sold from their own page, so they never compete with products for a slot.
        // They are shown first, so only exact/partial matches count — no typo or "most words" guesses.
        $packHits = $categoryId ? [] : $this->rank($index['packs'], $tokens['significant'], $compactQuery, false);

        // A query that names a pack ("nawabi pack") should not also pull in loose product guesses.
        if ($packHits && !array_filter($productHits, fn ($hit) => $hit['tier'] < self::TIER_RELAXED)) {
            $productHits = [];
        }

        $productIds = $this->orderProducts($productHits);
        $packIds = array_keys($packHits);
        $packs = empty($packIds)
            ? collect()
            : KittenPack::active()->whereIn('id', $packIds)->get()
                ->sortBy(fn ($pack) => array_search($pack->id, $packIds))->values();

        if ($limit) {
            $productIds = array_slice($productIds, 0, $limit);
        }

        return ['product_ids' => $productIds, 'packs' => $packs];
    }

    /**
     * "Did you mean …" for a query that found nothing: each word that is not a catalog word
     * is replaced by the closest one. Returns null when no better query exists.
     */
    public function suggest(string $query): ?string
    {
        $tokens = $this->tokens($query);
        if (empty($tokens)) {
            return null;
        }

        $vocabulary = $this->index()['vocabulary'];
        $changed = false;
        $words = [];

        foreach ($tokens['all'] as $token) {
            if (isset($vocabulary[$token]) || !$this->isAscii($token) || mb_strlen($token) < 3) {
                $words[] = $token;
                continue;
            }

            $best = null;
            $bestDistance = PHP_INT_MAX;
            // At least ~70% alike, so "wiskaas" → "whiskas" but "purina" is not turned into "print".
            $maxDistance = max(1, (int) floor(strlen($token) * 0.3));
            foreach ($vocabulary as $word => $_) {
                if (abs(strlen($word) - strlen($token)) > $maxDistance) {
                    continue;
                }
                $distance = levenshtein($token, $word);
                if ($distance < $bestDistance) {
                    [$best, $bestDistance] = [$word, $distance];
                }
            }

            if ($best !== null && $bestDistance <= $maxDistance) {
                $words[] = $best;
                $changed = true;
            } else {
                $words[] = $token;
            }
        }

        if (!$changed) {
            return null;
        }

        $suggestion = implode(' ', $words);
        $result = $this->search($suggestion);

        return (!empty($result['product_ids']) || $result['packs']->isNotEmpty()) ? $suggestion : null;
    }

    /**
     * compact: the whole query with spaces removed, repeats kept ("paw paw" → "pawpaw").
     * all / significant: its distinct words, without and with stop words removed.
     *
     * @return array{compact: string, all: string[], significant: string[]}|array{}
     */
    private function tokens(string $query): array
    {
        $normalized = self::normalize(mb_substr($query, 0, 191));
        if ($normalized === '') {
            return [];
        }
        $words = array_values(array_unique(explode(' ', $normalized)));

        $significant = array_values(array_filter(
            $words,
            fn ($word) => !in_array($word, self::STOP_WORDS, true)
                && (mb_strlen($word) > 1 || ctype_digit($word))
        ));

        return [
            'compact'     => str_replace(' ', '', $normalized),
            'all'         => $words,
            'significant' => $significant ?: $words,
        ];
    }

    /**
     * Score every entry; keep those matching all words (or, failing that, most of them).
     * $loose = false turns off typo matching and the "most words" fallback.
     *
     * @return array<int, array{tier: int, score: int}> keyed by id
     */
    private function rank(array $entries, array $tokens, string $compactQuery, bool $loose = true): array
    {
        $strict = [];
        $relaxed = [];
        $needed = (int) ceil(count($tokens) / 2);

        foreach ($entries as $id => $entry) {
            $score = 0;
            $matched = 0;
            $allInName = true;

            foreach ($tokens as $token) {
                $inName = $this->matchText($token, $entry['name_compact'], $entry['name_words'], $loose);
                if ($inName) {
                    $score += $inName * 3;
                    $matched++;
                    continue;
                }
                $allInName = false;

                $inMeta = ($entry['code'] !== '' && strlen($token) >= 3 && str_contains($entry['code'], $token)) ? 3
                    : $this->matchText($token, $entry['meta_compact'], $entry['meta_words'], $loose);
                if ($inMeta) {
                    $score += $inMeta;
                    $matched++;
                    continue;
                }

                if (mb_strlen($token) >= 3 && $this->prefixOfAny($token, $entry['desc_words'])) {
                    $score += 1;
                    $matched++;
                }
            }

            $phrase = $this->phraseTier($entry, $compactQuery, $loose && count($tokens) > 1);

            if ($matched === count($tokens) || $phrase) {
                $tier = $phrase ?: ($allInName ? self::TIER_NAME : self::TIER_OTHER);
                if ($phrase === self::TIER_PHRASE && str_starts_with($entry['name_compact'], $compactQuery)) {
                    $score += 50;
                }
                $strict[$id] = ['tier' => $tier, 'score' => $score];
            } elseif ($loose && $matched >= $needed && $matched > 0) {
                $relaxed[$id] = ['tier' => self::TIER_RELAXED, 'score' => $score];
            }
        }

        $hits = $strict ?: $relaxed;
        uasort($hits, fn ($a, $b) => [$a['tier'], -$a['score']] <=> [$b['tier'], -$b['score']]);

        return $hits;
    }

    /** Whole-query match against the name/SKU, ignoring spaces and punctuation. */
    private function phraseTier(array $entry, string $compactQuery, bool $fuzzyPhrase): ?int
    {
        if ($compactQuery === '') {
            return null;
        }
        if ($entry['name_compact'] === $compactQuery || ($entry['code'] !== '' && $entry['code'] === $compactQuery)) {
            return self::TIER_EXACT;
        }
        if (mb_strlen($compactQuery) >= 3 && str_contains($entry['name_compact'], $compactQuery)) {
            return self::TIER_PHRASE;
        }
        // "paw pew" → "pawpew" is one typo away from the joined name words "pawpaw".
        if ($fuzzyPhrase && $this->fuzzyDistance($compactQuery, $entry['name_words']) !== null) {
            return self::TIER_NAME;
        }

        return null;
    }

    /**
     * How well one query word matches a field: 10 = starts a word, 7 = inside the text
     * (spacing ignored, so "pawpaw" hits "paw paw"), 4 / 2 = one / two typos away, 0 = no match.
     */
    private function matchText(string $token, string $compact, array $words, bool $fuzzy): int
    {
        if ($compact === '') {
            return 0;
        }
        if ($this->prefixOfAny($token, $words)) {
            return 10;
        }
        if (str_contains($compact, $token)) {
            return 7;
        }
        if ($fuzzy && ($distance = $this->fuzzyDistance($token, $words)) !== null) {
            return $distance === 1 ? 4 : 2;
        }

        return 0;
    }

    private function prefixOfAny(string $token, array $words): bool
    {
        foreach ($words as $word) {
            if (str_starts_with($word, $token)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Typo tolerance: compare against each word, each pair of neighbouring words joined
     * ("paw paw" → "pawpaw"), and the start of longer words (so a half-typed, misspelt
     * word of 5+ letters still matches). 1 typo allowed up to 7 letters, 2 from 8. Returns the closest
     * distance, or null. English only — PHP's levenshtein() counts bytes, which would
     * over-count Bangla characters.
     */
    private function fuzzyDistance(string $token, array $words): ?int
    {
        $length = strlen($token);
        if ($length < 4 || !$this->isAscii($token)) {
            return null;
        }
        $maxDistance = $length >= 8 ? 2 : 1;
        $best = null;

        $candidates = $words;
        for ($i = 0, $n = count($words) - 1; $i < $n; $i++) {
            $candidates[] = $words[$i] . $words[$i + 1];
        }

        foreach ($candidates as $candidate) {
            // The whole word ("chiken" → "chicken") and its start ("chik" → "chic…").
            $forms = $length >= 5 ? array_unique([$candidate, substr($candidate, 0, $length)]) : [$candidate];
            foreach ($forms as $form) {
                if (abs(strlen($form) - $length) > $maxDistance) {
                    continue;
                }
                $distance = levenshtein($token, $form);
                if ($distance <= $maxDistance && ($best === null || $distance < $best)) {
                    $best = $distance;
                }
            }
        }

        return $best;
    }

    /** Relevance tier first, then in-stock before sold-out, then score, then best-selling. */
    private function orderProducts(array $hits): array
    {
        if (empty($hits)) {
            return [];
        }

        $live = Product::whereIn('id', array_keys($hits))
            ->where('status', 1)
            ->where('approval_status', 'approved')
            ->get(['id', 'stock', 'sold', 'is_digital'])
            ->keyBy('id');

        $ids = array_keys(array_filter($hits, fn ($id) => $live->has($id), ARRAY_FILTER_USE_KEY));

        usort($ids, function ($a, $b) use ($hits, $live) {
            $soldOutA = !$live[$a]->is_digital && (int) $live[$a]->stock <= 0;
            $soldOutB = !$live[$b]->is_digital && (int) $live[$b]->stock <= 0;

            return [$hits[$a]['tier'], $soldOutA, -$hits[$a]['score'], -(int) $live[$a]->sold]
                <=> [$hits[$b]['tier'], $soldOutB, -$hits[$b]['score'], -(int) $live[$b]->sold];
        });

        return $ids;
    }

    private function isAscii(string $text): bool
    {
        return !preg_match('/[^\x00-\x7F]/', $text);
    }

    /** Searchable text for every active product and pack, cached until the catalog changes. */
    private function index(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, fn () => $this->buildIndex());
    }

    private function buildIndex(): array
    {
        $vocabulary = [];

        $rows = DB::table('products')
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->leftJoin('subcategories', 'subcategories.id', '=', 'products.subcategory_id')
            ->leftJoin('childcategories', 'childcategories.id', '=', 'products.childcategory_id')
            ->leftJoin('brands', 'brands.id', '=', 'products.brand_id')
            ->where('products.status', 1)
            ->where('products.approval_status', 'approved')
            ->select([
                'products.id', 'products.name', 'products.category_id', 'products.product_code',
                'products.meta_keywords', 'products.description',
                'categories.name as category_name', 'subcategories.subcategoryName as subcategory_name',
                'childcategories.childcategoryName as childcategory_name', 'brands.name as brand_name',
            ])
            ->get();

        $products = [];
        foreach ($rows as $row) {
            $meta = implode(' ', [
                $row->category_name, $row->subcategory_name, $row->childcategory_name,
                $row->brand_name, $row->meta_keywords,
            ]);
            $products[$row->id] = $this->entry($row->name, $meta, $row->description, $row->product_code, $vocabulary)
                + ['category_id' => $row->category_id];
        }

        // Packs match on their own name/tier/badge only — matching the products inside them
        // would put a pack on top of nearly every search.
        $packs = [];
        foreach (KittenPack::active()->get(['id', 'name', 'tier_label', 'badge']) as $pack) {
            $packs[$pack->id] = $this->entry(
                $pack->name . ' kitten pack',
                $pack->tier_label . ' ' . $pack->badge,
                '',
                '',
                $vocabulary
            );
        }

        return ['products' => $products, 'packs' => $packs, 'vocabulary' => $vocabulary];
    }

    private function entry(?string $name, ?string $meta, ?string $description, ?string $code, array &$vocabulary): array
    {
        $nameWords = $this->words($name);
        $metaWords = $this->words($meta);
        foreach (array_merge($nameWords, $metaWords) as $word) {
            if (mb_strlen($word) >= 3 && !ctype_digit($word)) {
                $vocabulary[$word] = true;
            }
        }

        return [
            'name_compact' => implode('', $nameWords),
            'name_words'   => $nameWords,
            'meta_compact' => implode('', $metaWords),
            'meta_words'   => $metaWords,
            'code'         => self::compact($code),
            'desc_words'   => array_slice(array_values(array_unique($this->words($description))), 0, 400),
        ];
    }

    private function words(?string $text): array
    {
        $normalized = self::normalize($text);

        return $normalized === '' ? [] : explode(' ', $normalized);
    }
}
