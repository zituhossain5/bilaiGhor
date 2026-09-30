<?php

namespace App\Models;

use App\Services\ProductSearchService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/** A storefront search that found nothing — see ProductSearchService. */
class SearchMiss extends Model
{
    protected $fillable = ['query', 'normalized_query', 'search_count', 'last_searched_at'];

    protected $casts = [
        'search_count'     => 'integer',
        'last_searched_at' => 'datetime',
    ];

    /** Count one more miss for this query. Never lets a logging failure break the search page. */
    public static function record(string $query): void
    {
        $query = mb_substr(trim($query), 0, 191);
        $normalized = mb_substr(ProductSearchService::normalize($query), 0, 191);
        if ($normalized === '') {
            return;
        }

        try {
            $now = now();
            static::query()->upsert(
                [[
                    'query'            => $query,
                    'normalized_query' => $normalized,
                    'search_count'     => 1,
                    'last_searched_at' => $now,
                    'created_at'       => $now,
                    'updated_at'       => $now,
                ]],
                ['normalized_query'],
                [
                    'query'            => $query,
                    'search_count'     => DB::raw('search_count + 1'),
                    'last_searched_at' => $now,
                    'updated_at'       => $now,
                ]
            );
        } catch (\Throwable $e) {
            Log::warning('Search miss logging failed: ' . $e->getMessage());
        }
    }
}
