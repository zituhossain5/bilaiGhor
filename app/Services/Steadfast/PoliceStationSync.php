<?php

namespace App\Services\Steadfast;

use App\Models\SteadfastPoliceStation;
use App\Services\CourierDispatchService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Copies Steadfast's area list (GET /police_stations, read-only) into steadfast_police_stations,
 * then auto-matches our thanas. Stations that disappeared are switched off, never deleted.
 */
class PoliceStationSync
{
    /**
     * @return array{ok: bool, message: string, stations?: int, districts?: int, deactivated?: int, match?: array}
     */
    public function sync(bool $rematch = false): array
    {
        $config = CourierDispatchService::config('steadfast');
        if (!$config) {
            return ['ok' => false, 'message' => 'Steadfast API সেটিংস চালু/পূরণ করা নেই'];
        }

        try {
            $response = Http::withHeaders([
                'Api-Key'    => $config->api_key,
                'Secret-Key' => $config->secret_key,
            ])->acceptJson()->connectTimeout(5)->timeout(30)
                ->get(CourierDispatchService::steadfastBase($config) . '/police_stations');
        } catch (\Throwable $e) {
            Log::warning('Steadfast police_stations failed', ['error' => $e->getMessage()]);

            return ['ok' => false, 'message' => 'Steadfast সার্ভারে সংযোগ করা যায়নি'];
        }

        $districts = $response->json('data');
        if (!$response->successful() || !is_array($districts) || !$districts) {
            Log::warning('Steadfast police_stations rejected', ['status' => $response->status(), 'body' => mb_substr($response->body(), 0, 500)]);

            return ['ok' => false, 'message' => 'Steadfast এরিয়া লিস্ট পাওয়া যায়নি (HTTP ' . $response->status() . ')'];
        }

        $now = now();
        $rows = [];
        foreach ($districts as $district) {
            foreach ((array) ($district['policestations'] ?? []) as $ps) {
                if (empty($ps['id']) || blank($ps['name'] ?? null)) {
                    continue;
                }
                $rows[(int) $ps['id']] = [
                    'id'            => (int) $ps['id'],
                    'district_id'   => (int) ($district['id'] ?? $ps['district_id'] ?? 0),
                    'district_name' => mb_substr(trim((string) ($district['name'] ?? '')), 0, 100),
                    'name'          => mb_substr(trim((string) $ps['name']), 0, 150),
                    'hub_id'        => isset($ps['hub_id']) ? (int) $ps['hub_id'] : null,
                    'ps_type'       => isset($ps['ps_type']) ? mb_substr((string) $ps['ps_type'], 0, 50) : null,
                    'search_tags'   => filled($ps['search_tags'] ?? null) ? (string) $ps['search_tags'] : null,
                    'status'        => !isset($ps['status']) || (int) $ps['status'] === 1,
                    'synced_at'     => $now,
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ];
            }
        }

        if (!$rows) {
            return ['ok' => false, 'message' => 'Steadfast এরিয়া লিস্ট খালি'];
        }

        $deactivated = DB::transaction(function () use ($rows) {
            foreach (array_chunk(array_values($rows), 200) as $chunk) {
                SteadfastPoliceStation::upsert($chunk, ['id'], [
                    'district_id', 'district_name', 'name', 'hub_id', 'ps_type', 'search_tags', 'status', 'synced_at', 'updated_at',
                ]);
            }

            return SteadfastPoliceStation::whereNotIn('id', array_keys($rows))->where('status', true)->update(['status' => false]);
        });

        $match = app(AreaMatcher::class)->matchAll($rematch);
        SteadfastArea::forget();

        return [
            'ok'          => true,
            'message'     => count($rows) . ' টি Steadfast এরিয়া সিঙ্ক হয়েছে',
            'stations'    => count($rows),
            'districts'   => count(array_unique(array_column($rows, 'district_name'))),
            'deactivated' => $deactivated,
            'match'       => $match,
        ];
    }
}
