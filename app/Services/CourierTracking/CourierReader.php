<?php

namespace App\Services\CourierTracking;

use App\Models\CourierParcel;
use App\Services\CourierDispatchService;
use App\Services\PathaoService;
use App\Services\RedXService;
use Illuminate\Support\Facades\Http;

/**
 * Reads a parcel's current state from the courier's official merchant API.
 *  - Steadfast: current delivery_status only.
 *  - Pathao:    current order_status_slug (+ whatever details the info endpoint returns).
 *  - RedX:      full tracking history + parcel info.
 *
 * @return array{ok: bool, error?: string, raw_status?: ?string, event_time?: mixed, history?: list<array>, details?: array}
 */
class CourierReader
{
    public function read(CourierParcel $parcel): array
    {
        try {
            return match ($parcel->courier) {
                'steadfast' => $this->steadfast($parcel),
                'pathao'    => $this->pathao($parcel),
                'redx'      => $this->redx($parcel),
                default     => ['ok' => false, 'error' => 'Unsupported courier'],
            };
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    private function steadfast(CourierParcel $parcel): array
    {
        $config = CourierDispatchService::config('steadfast');
        if (!$config) {
            return ['ok' => false, 'error' => 'Steadfast API settings are off or incomplete'];
        }

        $base = rtrim(trim((string) $config->url), '/') ?: 'https://portal.packzy.com/api/v1';
        $base = rtrim(preg_replace('#/create_order/?$#i', '', $base), '/');

        // Same lookup order as courier:check-status.
        $cid = trim((string) $parcel->consignment_id);
        $code = trim((string) $parcel->tracking_code);
        $tries = [];
        if ($cid !== '' && ctype_digit($cid)) {
            $tries[] = '/status_by_cid/' . rawurlencode($cid);
        }
        if ($code !== '') {
            $tries[] = '/status_by_trackingcode/' . rawurlencode($code);
        }
        if ($cid !== '' && !ctype_digit($cid)) {
            $tries[] = '/status_by_trackingcode/' . rawurlencode($cid);
        }
        if (filled($parcel->invoice)) {
            $tries[] = '/status_by_invoice/' . rawurlencode($parcel->invoice);
        }

        $lastError = 'No lookup id';
        foreach ($tries as $path) {
            $response = Http::withHeaders(['Api-Key' => $config->api_key, 'Secret-Key' => $config->secret_key])
                ->acceptJson()->connectTimeout(4)->timeout(config('courier_tracking.http_timeout', 12))
                ->get($base . $path);

            $json = $response->json();
            if ($response->successful() && is_array($json) && isset($json['delivery_status'])) {
                return ['ok' => true, 'raw_status' => (string) $json['delivery_status'], 'history' => [], 'details' => []];
            }
            $lastError = 'HTTP ' . $response->status();
        }

        return ['ok' => false, 'error' => $lastError];
    }

    private function pathao(CourierParcel $parcel): array
    {
        $service = new PathaoService();
        if (!$service->isConfigured()) {
            return ['ok' => false, 'error' => 'Pathao API settings are off or incomplete'];
        }

        $response = $service->orderInfo((string) $parcel->consignment_id);
        if (!$response) {
            return ['ok' => false, 'error' => 'Connection failed'];
        }

        $data = $response->json('data');
        if (!$response->successful() || !is_array($data) || blank($data['order_status_slug'] ?? $data['order_status'] ?? null)) {
            return ['ok' => false, 'error' => 'HTTP ' . $response->status()];
        }

        return [
            'ok'         => true,
            'raw_status' => (string) ($data['order_status_slug'] ?? $data['order_status']),
            'event_time' => $data['updated_at'] ?? null,
            'history'    => [],
            'details'    => array_filter([
                'invoice'         => $data['merchant_order_id'] ?? null,
                'recipient_name'  => $data['recipient_name'] ?? null,
                'recipient_phone' => $data['recipient_phone'] ?? null,
                'recipient_address' => $data['recipient_address'] ?? null,
                'courier_cod'     => $data['amount_to_collect'] ?? $data['collectable_amount'] ?? null,
                'delivery_charge' => $data['delivery_fee'] ?? null,
                'weight'          => $data['item_weight'] ?? null,
            ], fn ($v) => $v !== null && $v !== ''),
        ];
    }

    private function redx(CourierParcel $parcel): array
    {
        $service = new RedXService();
        if (!$service->isConfigured()) {
            return ['ok' => false, 'error' => 'RedX API settings are off or incomplete'];
        }

        $id = (string) $parcel->consignment_id;
        $info = $service->getParcelDetails($id);
        $track = $service->trackParcel($id);
        if (!$info && !$track) {
            return ['ok' => false, 'error' => 'RedX did not answer'];
        }

        $p = is_array($info['parcel'] ?? null) ? $info['parcel'] : [];

        $history = [];
        foreach ((array) ($track['tracking'] ?? []) as $row) {
            if (!is_array($row)) {
                continue;
            }
            $history[] = [
                'message'    => $row['message_en'] ?? $row['messageEn'] ?? $row['message_bn'] ?? $row['message'] ?? null,
                'event_time' => $row['time'] ?? $row['created_at'] ?? null,
                'payload'    => $row,
            ];
        }

        return [
            'ok'         => true,
            'raw_status' => $p['status'] ?? null,
            'history'    => $history,
            'details'    => array_filter([
                'invoice'           => $p['merchant_invoice_id'] ?? null,
                'recipient_name'    => $p['customer_name'] ?? null,
                'recipient_phone'   => $p['customer_phone'] ?? null,
                'recipient_address' => $p['customer_address'] ?? null,
                'recipient_area'    => $p['delivery_area'] ?? null,
                'courier_cod'       => $p['cash_collection_amount'] ?? null,
                'delivery_charge'   => $p['charge'] ?? null,
                'weight'            => $p['parcel_weight'] ?? null,
                'note'              => $p['instruction'] ?? null,
            ], fn ($v) => $v !== null && $v !== ''),
        ];
    }
}
