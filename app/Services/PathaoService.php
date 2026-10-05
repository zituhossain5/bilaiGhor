<?php

namespace App\Services;

use App\Models\Courierapi;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Pathao merchant ("Aladdin") API: stores, city/zone/area lists and order creation.
 *
 * Credentials come from Admin → Courier API (courierapis row "pathao"). An expired token is
 * renewed once with the saved username/password, the same way courier:check-status does it.
 */
class PathaoService
{
    private const LIST_TTL_HOURS = 12;
    private const FAILED_TTL_MINUTES = 10;

    private ?Courierapi $config;
    private string $baseUrl;

    public function __construct(?Courierapi $config = null)
    {
        $this->config = $config ?? CourierDispatchService::config('pathao');

        $url = rtrim(trim((string) ($this->config->url ?? '')), '/') ?: 'https://api-hermes.pathao.com';
        $this->baseUrl = preg_replace('#/aladdin/?$#', '', $url);
    }

    public function isConfigured(): bool
    {
        return $this->config !== null;
    }

    /** Raw Pathao responses — the order page reads ['data']['data'] from them. */
    public function stores(): ?array
    {
        return $this->cachedList('/aladdin/api/v1/stores');
    }

    public function cities(): ?array
    {
        return $this->cachedList('/aladdin/api/v1/city-list');
    }

    public function zones(int $cityId): ?array
    {
        return $this->cachedList("/aladdin/api/v1/cities/{$cityId}/zone-list");
    }

    public function areas(int $zoneId): ?array
    {
        return $this->cachedList("/aladdin/api/v1/zones/{$zoneId}/area-list");
    }

    /**
     * @return array{ok: bool, message: string, consignment_id?: string, status_code?: int}
     */
    public function createOrder(array $payload): array
    {
        $response = $this->request('post', '/aladdin/api/v1/orders', $payload, 30);

        if ($response === null) {
            return ['ok' => false, 'message' => 'Pathao সার্ভারে সংযোগ করা যায়নি'];
        }

        $json = $response->json() ?? [];
        $consignmentId = $json['data']['consignment_id'] ?? null;

        if ($response->successful() && $consignmentId) {
            return ['ok' => true, 'message' => $json['message'] ?? 'Pathao তে পাঠানো হয়েছে', 'consignment_id' => (string) $consignmentId];
        }

        Log::warning('Pathao create order failed', ['status' => $response->status(), 'response' => $json ?: $response->body()]);

        return [
            'ok'          => false,
            'message'     => CourierDispatchService::errorText($json, 'Pathao অর্ডার তৈরি হয়নি (HTTP ' . $response->status() . ')'),
            'status_code' => $response->status(),
        ];
    }

    /** Lists change rarely: keep good answers for hours, and a failure briefly so a down API can't slow every page. */
    private function cachedList(string $path): ?array
    {
        if (!$this->isConfigured()) {
            return null;
        }

        $key = 'pathao_api:' . md5($this->baseUrl . $path);
        $hit = Cache::get($key);
        if (is_array($hit)) {
            return $hit['ok'] ? $hit['data'] : null;
        }

        $response = $this->request('get', $path, [], 8);
        $data = $response?->successful() ? $response->json() : null;
        $ok = is_array($data) && isset($data['data']);

        Cache::put($key, ['ok' => $ok, 'data' => $ok ? $data : null],
            $ok ? now()->addHours(self::LIST_TTL_HOURS) : now()->addMinutes(self::FAILED_TTL_MINUTES));

        return $ok ? $data : null;
    }

    private function request(string $method, string $path, array $data, int $timeout): ?\Illuminate\Http\Client\Response
    {
        if (!$this->isConfigured()) {
            return null;
        }

        try {
            $response = $this->client($timeout)->{$method}($this->baseUrl . $path, $data);

            if ($response->status() === 401 && $this->refreshToken()) {
                $response = $this->client($timeout)->{$method}($this->baseUrl . $path, $data);
            }

            return $response;
        } catch (\Throwable $e) {
            Log::warning('Pathao request failed', ['path' => $path, 'error' => $e->getMessage()]);

            return null;
        }
    }

    private function client(int $timeout)
    {
        return Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->config->token,
            'Accept'        => 'application/json',
            'Content-Type'  => 'application/json',
        ])->connectTimeout(4)->timeout($timeout);
    }

    private function refreshToken(): bool
    {
        $c = $this->config;
        if (!$c->client_id || !$c->client_secret || !$c->username || !$c->password) {
            return false;
        }

        try {
            $response = Http::acceptJson()->connectTimeout(4)->timeout(15)
                ->post($this->baseUrl . '/aladdin/api/v1/issue-token', [
                    'client_id'     => $c->client_id,
                    'client_secret' => $c->client_secret,
                    'grant_type'    => 'password',
                    'username'      => $c->username,
                    'password'      => $c->password,
                ]);

            $token = $response->json('access_token');
            if (!$response->successful() || !$token) {
                return false;
            }

            $c->forceFill(['token' => $token])->save();

            return true;
        } catch (\Throwable $e) {
            Log::warning('Pathao token refresh failed', ['error' => $e->getMessage()]);

            return false;
        }
    }
}
