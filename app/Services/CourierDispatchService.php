<?php

namespace App\Services;

use App\Models\Courierapi;
use App\Models\DeliveryThana;
use App\Models\Order;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Books an order with Steadfast, Pathao or RedX and records the consignment on the order
 * (courier_type, consignment_id / courier_tracking_id, courier_tracking_code, courier_sent_at) —
 * the columns the status cron, the courier webhooks, the invoice and the customer's tracking page read.
 *
 * The cash to collect is what the customer still owes (order total minus anything already paid).
 */
class CourierDispatchService
{
    public const COURIERS = ['steadfast' => 'Steadfast', 'pathao' => 'Pathao', 'redx' => 'RedX'];

    public static function label(?string $courier): string
    {
        return self::COURIERS[$courier] ?? ucfirst((string) $courier);
    }

    /** Active settings for a courier, or null when it is switched off or missing credentials. */
    public static function config(string $courier): ?Courierapi
    {
        $config = Courierapi::where(['type' => $courier, 'status' => 1])->first();
        if (!$config) {
            return null;
        }

        $ready = match ($courier) {
            'steadfast'      => filled($config->api_key) && filled($config->secret_key),
            'pathao', 'redx' => filled($config->token),
            default          => false,
        };

        return $ready ? $config : null;
    }

    /**
     * @param  array  $pathao  store_id, city_id, zone_id, area_id chosen in the Pathao dialog
     * @return array{ok: bool, message: string, status_code?: int}
     */
    public function send(Order $order, string $courier, array $pathao = []): array
    {
        // Two clicks (or two admins) must not book the same parcel twice.
        $lock = Cache::lock('courier-send:' . $order->id, 60);
        if (!$lock->get()) {
            return ['ok' => false, 'message' => 'এই অর্ডারটি এখন পাঠানো হচ্ছে — একটু পরে দেখুন'];
        }

        try {
            $order = Order::with(['shipping', 'orderdetails', 'payment'])->find($order->id);

            if ($reason = $this->cannotSend($order)) {
                return ['ok' => false, 'message' => $reason];
            }

            $parcel = $this->parcel($order);

            $result = match ($courier) {
                'steadfast' => $this->steadfast($parcel),
                'pathao'    => $this->pathao($parcel, $pathao),
                'redx'      => $this->redx($order, $parcel),
                default     => ['ok' => false, 'message' => 'অজানা কুরিয়ার'],
            };

            if ($result['ok']) {
                $order->forceFill([
                    'courier_type'          => $courier,
                    'consignment_id'        => $result['consignment_id'],
                    'courier_tracking_id'   => $result['consignment_id'],
                    'courier_tracking_code' => $result['tracking_code'] ?? null,
                    'courier_sent_at'       => now(),
                ])->save();
            }

            return $result;
        } finally {
            $lock->release();
        }
    }

    private function cannotSend(?Order $order): ?string
    {
        if (!$order) {
            return 'অর্ডার পাওয়া যায়নি';
        }
        if (filled($order->courier_tracking_id)) {
            return 'আগেই ' . self::label($order->courier_type) . ' এ পাঠানো হয়েছে (ID: ' . $order->courier_tracking_id . ')';
        }
        if (in_array((int) $order->order_status, [InventoryService::COMPLETE_STATUS, InventoryService::CANCEL_STATUS], true)) {
            return 'ডেলিভারড বা বাতিল অর্ডার কুরিয়ারে পাঠানো যায় না';
        }
        if (!$order->shipping || blank($order->shipping->phone)) {
            return 'শিপিং নাম/ফোন নেই';
        }
        if (strlen(self::phone($order->shipping->phone)) !== 11) {
            return 'ফোন নম্বর সঠিক নয় (১১ ডিজিট লাগবে): ' . $order->shipping->phone;
        }

        return null;
    }

    private function parcel(Order $order): array
    {
        $shipping = $order->shipping;
        // Street address + "thana, district, division"; drop repeats (a district often shares its division's name).
        $address = collect(explode(',', $shipping->address . ',' . $shipping->area))
            ->map(fn ($part) => trim($part))
            ->filter()
            ->unique(fn ($part) => mb_strtolower($part))
            ->implode(', ');

        $items = $order->orderdetails
            ->map(fn ($line) => trim((string) $line->product_name) . ' x' . (int) $line->qty)
            ->implode(', ');

        return [
            'invoice' => (string) $order->invoice_id,
            'name'    => Str::limit(trim((string) $shipping->name) ?: 'Customer', 95, ''),
            'phone'   => self::phone($shipping->phone),
            'address' => mb_substr($address, 0, 220),
            'cod'     => (int) round(OrderPaymentService::state($order)['due']),
            'value'   => (int) round((float) $order->amount),
            'note'    => mb_substr(trim((string) $order->order_note), 0, 250),
            'qty'     => max(1, (int) $order->orderdetails->sum('qty')),
            'items'   => mb_substr($items, 0, 250),
        ];
    }

    private function steadfast(array $p): array
    {
        $config = self::config('steadfast');
        if (!$config) {
            return ['ok' => false, 'message' => 'Steadfast API সেটিংস চালু/পূরণ করা নেই'];
        }

        $base = rtrim(trim((string) $config->url), '/') ?: 'https://portal.packzy.com/api/v1';
        $base = rtrim(preg_replace('#/create_order/?$#i', '', $base), '/');

        try {
            $response = Http::withHeaders([
                'Api-Key'    => $config->api_key,
                'Secret-Key' => $config->secret_key,
            ])->acceptJson()->asJson()->connectTimeout(5)->timeout(30)->post($base . '/create_order', [
                'invoice'           => $p['invoice'],
                'recipient_name'    => $p['name'],
                'recipient_phone'   => $p['phone'],
                'recipient_address' => $p['address'],
                'cod_amount'        => $p['cod'],
                'note'              => $p['note'],
                'item_description'  => $p['items'],
                'total_lot'         => $p['qty'],
            ]);
        } catch (\Throwable $e) {
            Log::warning('Steadfast create order failed', ['invoice' => $p['invoice'], 'error' => $e->getMessage()]);

            return ['ok' => false, 'message' => 'Steadfast সার্ভারে সংযোগ করা যায়নি'];
        }

        $json = $response->json() ?? [];
        $consignment = $json['consignment'] ?? [];

        if ($response->successful() && (int) ($json['status'] ?? 0) === 200 && !empty($consignment['consignment_id'])) {
            return [
                'ok'             => true,
                'message'        => 'Steadfast এ পাঠানো হয়েছে',
                'consignment_id' => (string) $consignment['consignment_id'],
                'tracking_code'  => $consignment['tracking_code'] ?? null,
            ];
        }

        Log::warning('Steadfast create order rejected', ['invoice' => $p['invoice'], 'status' => $response->status(), 'response' => $json ?: $response->body()]);

        return [
            'ok'          => false,
            'message'     => self::errorText($json, 'Steadfast অর্ডার তৈরি হয়নি (HTTP ' . $response->status() . ')'),
            'status_code' => $response->status(),
        ];
    }

    private function pathao(array $p, array $place): array
    {
        $service = new PathaoService();
        if (!$service->isConfigured()) {
            return ['ok' => false, 'message' => 'Pathao API সেটিংস চালু/পূরণ করা নেই'];
        }

        return $service->createOrder([
            'store_id'            => (int) ($place['store_id'] ?? 0),
            'merchant_order_id'   => $p['invoice'],
            'recipient_name'      => $p['name'],
            'recipient_phone'     => $p['phone'],
            'recipient_address'   => $p['address'],
            'recipient_city'      => (int) ($place['city_id'] ?? 0),
            'recipient_zone'      => (int) ($place['zone_id'] ?? 0),
            'recipient_area'      => (int) ($place['area_id'] ?? 0),
            'delivery_type'       => 48, // normal delivery
            'item_type'           => 2,  // parcel
            'special_instruction' => $p['note'],
            'item_quantity'       => $p['qty'],
            'item_weight'         => 0.5,
            'amount_to_collect'   => $p['cod'],
            'item_description'    => $p['items'],
        ]);
    }

    private function redx(Order $order, array $p): array
    {
        $service = new RedXService();
        if (!self::config('redx') || !$service->isConfigured()) {
            return ['ok' => false, 'message' => 'RedX API সেটিংস চালু/পূরণ করা নেই'];
        }

        $area = $this->redxArea($service, $order);
        if (!$area) {
            return ['ok' => false, 'message' => 'RedX এরিয়া মেলানো যায়নি — থানার নাম ইংরেজিতে রাখুন অথবা Steadfast/Pathao ব্যবহার করুন'];
        }

        $result = $service->createParcel([
            'customer_name'          => $p['name'],
            'customer_phone'         => $p['phone'],
            'delivery_area'          => $area['name'],
            'delivery_area_id'       => (int) $area['id'],
            'customer_address'       => $p['address'],
            'merchant_invoice_id'    => $p['invoice'],
            'cash_collection_amount' => (string) $p['cod'],
            'parcel_weight'          => '500',
            'instruction'            => $p['note'],
            'value'                  => (string) $p['value'],
        ]);

        if (!empty($result['tracking_id'])) {
            return ['ok' => true, 'message' => 'RedX এ পাঠানো হয়েছে', 'consignment_id' => (string) $result['tracking_id']];
        }

        return [
            'ok'          => false,
            'message'     => (string) ($result['message'] ?? $result['error'] ?? 'RedX পার্সেল তৈরি হয়নি'),
            'status_code' => $result['status'] ?? null,
        ];
    }

    /** RedX needs its own area id: match by post code first, then by thana name. */
    private function redxArea(RedXService $service, Order $order): ?array
    {
        $areas = Cache::get('redx_areas_all');
        if (!is_array($areas)) {
            $areas = $service->getAreas()['areas'] ?? null;
            if (!is_array($areas) || !$areas) {
                return null;
            }
            Cache::put('redx_areas_all', $areas, now()->addDay());
        }

        $shipping = $order->shipping;
        $thana = $shipping->thana_id ? DeliveryThana::find($shipping->thana_id) : null;

        $postCode = trim((string) ($shipping->post_code ?: $thana?->post_code));
        if ($postCode !== '') {
            $match = collect($areas)->first(fn ($a) => (string) ($a['post_code'] ?? '') === $postCode);
            if ($match) {
                return $match;
            }
        }

        $name = self::areaKey((string) $thana?->name);
        if ($name === '') {
            return null;
        }

        return collect($areas)->first(fn ($a) => self::areaKey((string) ($a['name'] ?? '')) === $name);
    }

    private static function areaKey(string $name): string
    {
        return preg_replace('/[^a-z0-9]/', '', strtolower($name));
    }

    /** 01XXXXXXXXX — what all three couriers expect. */
    public static function phone(?string $phone): string
    {
        $digits = preg_replace('/\D/', '', (string) $phone);
        if (str_starts_with($digits, '880')) {
            $digits = substr($digits, 2);
        }
        if (strlen($digits) === 10 && str_starts_with($digits, '1')) {
            $digits = '0' . $digits;
        }

        return $digits;
    }

    /** First readable message from a courier's error body ("message" and/or per-field "errors"). */
    public static function errorText(array $json, string $fallback): string
    {
        $parts = [];
        if (!empty($json['message']) && is_string($json['message'])) {
            $parts[] = $json['message'];
        }
        foreach ((array) ($json['errors'] ?? []) as $field => $messages) {
            $parts[] = (is_string($field) ? $field . ': ' : '') . implode(' ', Arr::flatten((array) $messages));
        }

        return $parts ? implode(' — ', $parts) : $fallback;
    }
}
