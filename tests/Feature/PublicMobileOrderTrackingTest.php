<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderStatus;
use App\Models\Shipping;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PublicMobileOrderTrackingTest extends TestCase
{
    use DatabaseTransactions;

    public function test_valid_guest_token_returns_only_minimal_tracking_information(): void
    {
        $order = $this->makeOrder();

        $response = $this->withHeader('X-Order-Tracking-Token', $order->public_token)
            ->getJson($this->trackingUrl($order));

        $response->assertOk()
            ->assertJsonPath('data.order_reference', $order->invoice_id)
            ->assertJsonPath('data.order_status', 'Pending')
            ->assertJsonPath('data.courier_status', null);

        $this->assertSame(
            ['order_reference', 'order_status', 'courier_status', 'order_date'],
            array_keys($response->json('data'))
        );
        $this->assertStringNotContainsString('Private Recipient', $response->getContent());
        $this->assertStringNotContainsString('01799999999', $response->getContent());
        $this->assertStringNotContainsString('Private street address', $response->getContent());
        $this->assertStringNotContainsString($order->public_token, $response->getContent());
        $this->assertSame('no-store, private', $response->headers->get('Cache-Control'));
    }

    public function test_missing_invalid_or_mismatched_token_never_confirms_an_invoice(): void
    {
        $order = $this->makeOrder();
        $url = $this->trackingUrl($order);

        $missing = $this->getJson($url);
        $invalid = $this->withHeader('X-Order-Tracking-Token', str_repeat('a', 64))->getJson($url);
        $enumeration = $this->getJson('/api/v1/mobile/orders/track/54321');
        $wrongInvoice = $this->withHeader('X-Order-Tracking-Token', $order->public_token)
            ->getJson('/api/v1/mobile/orders/track/not-this-invoice');
        $caseChanged = $this->withHeader('X-Order-Tracking-Token', strtoupper($order->public_token))
            ->getJson($url);

        foreach ([$missing, $invalid, $enumeration, $wrongInvoice, $caseChanged] as $response) {
            $response->assertStatus(404)
                ->assertExactJson(['status' => 'error', 'message' => 'Tracking information unavailable.']);
        }
    }

    public function test_guest_tracking_is_limited_to_five_requests_per_minute_per_ip(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.44']);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->getJson('/api/v1/mobile/orders/track/54321')->assertStatus(404);
        }

        $this->getJson('/api/v1/mobile/orders/track/54321')->assertStatus(429);
    }

    public function test_authenticated_customer_can_retrieve_own_detail_and_share_tracking_token(): void
    {
        $owner = $this->makeCustomer();
        $order = $this->makeOrder($owner);
        Sanctum::actingAs($owner);

        $detail = $this->getJson('/api/v1/mobile/orders/'.$order->id);
        $detail->assertOk()
            ->assertJsonPath('data.id', $order->id)
            ->assertJsonPath('data.tracking_token', $order->public_token);

        $this->withHeader('X-Order-Tracking-Token', $detail->json('data.tracking_token'))
            ->getJson($this->trackingUrl($order))
            ->assertOk()
            ->assertJsonPath('data.order_reference', $order->invoice_id);
    }

    public function test_authenticated_customer_cannot_read_another_customers_order(): void
    {
        $owner = $this->makeCustomer();
        $other = $this->makeCustomer();
        $order = $this->makeOrder($owner);
        Sanctum::actingAs($other);

        $this->getJson('/api/v1/mobile/orders/'.$order->id)->assertStatus(404);
        $this->getJson('/api/v1/mobile/orders')->assertOk()
            ->assertJsonMissing(['invoice_id' => $order->invoice_id]);
    }

    public function test_new_tokens_are_unique_and_existing_manual_tokens_are_preserved(): void
    {
        $first = $this->makeOrder();
        $second = $this->makeOrder();
        $manualToken = Str::random(48);
        $manual = $this->makeOrder(null, $manualToken);

        $this->assertMatchesRegularExpression('/\A[a-f0-9]{64}\z/', $first->public_token);
        $this->assertNotSame($first->public_token, $second->public_token);
        $this->assertSame($manualToken, $manual->public_token);
        $this->assertArrayNotHasKey('public_token', $first->toArray());

        $this->withHeader('X-Order-Tracking-Token', $manualToken)
            ->getJson($this->trackingUrl($manual))
            ->assertOk()
            ->assertJsonPath('data.order_reference', $manual->invoice_id);

        DB::table('orders')->where('id', $first->id)->update(['public_token' => null]);
        $migration = require base_path('database/migrations/2026_10_26_000001_backfill_order_public_tokens.php');
        $migration->up();

        $this->assertMatchesRegularExpression('/\A[a-f0-9]{64}\z/', $first->fresh()->public_token);
        $this->assertSame($manualToken, $manual->fresh()->public_token);
    }

    private function trackingUrl(Order $order): string
    {
        return '/api/v1/mobile/orders/track/'.$order->invoice_id;
    }

    private function makeCustomer(): Customer
    {
        $suffix = Str::lower(Str::random(10));

        return Customer::create([
            'name' => 'Tracking Test Customer',
            'slug' => 'tracking-'.$suffix,
            'phone' => '017'.random_int(10000000, 99999999),
            'email' => $suffix.'@example.test',
            'password' => bcrypt('secret'),
            'status' => 'active',
        ]);
    }

    private function makeOrder(?Customer $customer = null, ?string $token = null): Order
    {
        OrderStatus::firstOrCreate(
            ['id' => 1],
            ['name' => 'Pending', 'slug' => 'pending', 'status' => '1']
        );

        $order = Order::create([
            'invoice_id' => 'TRACK-'.Str::upper(Str::random(10)),
            'customer_id' => $customer?->id,
            'amount' => 100,
            'discount' => 0,
            'shipping_charge' => 0,
            'order_status' => 1,
            'payment_status' => 'pending',
            'public_token' => $token,
            'is_manual_order' => $token !== null,
        ]);

        Shipping::create([
            'order_id' => $order->id,
            'customer_id' => $customer?->id,
            'name' => 'Private Recipient',
            'phone' => '01799999999',
            'address' => 'Private street address',
            'area' => 'Private area',
        ]);

        return $order;
    }
}
