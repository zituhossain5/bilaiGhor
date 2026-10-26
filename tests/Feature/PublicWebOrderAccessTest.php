<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderStatus;
use App\Models\ResellerLandingPage;
use App\Models\Shipping;
use App\Models\User;
use App\Support\PublicOrderAccess;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

class PublicWebOrderAccessTest extends TestCase
{
    use DatabaseTransactions;

    public function test_guest_success_requires_token_and_shows_minimal_confirmation(): void
    {
        $order = $this->makeOrder();
        $path = '/customer/order-success/'.$order->id;
        $this->assertStringContainsString('token='.$order->public_token, PublicOrderAccess::successUrl($order));

        $this->get($path)->assertNotFound();
        $this->get($path.'?token='.str_repeat('a', 64))->assertNotFound();
        $this->get('/customer/order-success/987654?token='.$order->public_token)->assertNotFound();

        $response = $this->get($path.'?token='.$order->public_token);
        $response->assertOk()->assertSee($order->invoice_id)
            ->assertDontSee('Private Recipient')
            ->assertDontSee('01799999999')
            ->assertDontSee('Private street address')
            ->assertDontSee('100.00');
        $this->assertSame('no-store, private', $response->headers->get('Cache-Control'));
        $this->assertSame('no-referrer', $response->headers->get('Referrer-Policy'));
    }

    public function test_public_website_tracking_requires_matching_token_and_reference(): void
    {
        $order = $this->makeOrder();
        $path = '/customer/order-track/result';

        $this->post($path, ['invoice_id' => $order->invoice_id])->assertNotFound();
        $this->post($path, ['invoice_id' => $order->invoice_id, 'token' => str_repeat('a', 64)])->assertNotFound();
        $this->post($path, ['invoice_id' => '54321', 'token' => $order->public_token])->assertNotFound();
        $this->post($path, ['invoice_id' => $order->invoice_id, 'phone' => '01799999999'])->assertNotFound();

        $this->post($path, ['invoice_id' => $order->invoice_id, 'token' => $order->public_token])
            ->assertOk()->assertSee($order->invoice_id)->assertDontSee('Private street address');
    }

    public function test_reseller_success_and_tracking_require_token_and_correct_landing(): void
    {
        config()->set('business.reseller_enabled', true);
        $user = User::create(['name' => 'Store owner', 'email' => Str::random(12).'@example.test',
            'password' => bcrypt('secret'), 'status' => 1]);
        $other = User::create(['name' => 'Other owner', 'email' => Str::random(12).'@example.test',
            'password' => bcrypt('secret'), 'status' => 1]);
        $landing = ResellerLandingPage::create(['user_id' => $user->id, 'slug' => 'store-'.Str::lower(Str::random(8)), 'is_active' => 1]);
        $otherLanding = ResellerLandingPage::create(['user_id' => $other->id, 'slug' => 'store-'.Str::lower(Str::random(8)), 'is_active' => 1]);
        $order = $this->makeOrder(null, null, $user->id);

        $success = route('reseller.landing.order.success', ['slug' => $landing->slug, 'orderId' => $order->id]);
        $this->get($success)->assertNotFound();
        $this->get($success.'?token='.$order->public_token)->assertOk()
            ->assertSee($order->invoice_id)->assertDontSee('01799999999');
        $this->get(route('reseller.landing.order.success', [
            'slug' => $otherLanding->slug, 'orderId' => $order->id, 'token' => $order->public_token,
        ]))->assertNotFound();

        $track = route('reseller.landing.order-track.result', $landing->slug);
        $this->post($track, ['invoice_id' => $order->invoice_id])->assertNotFound();
        $this->post($track, ['invoice_id' => $order->invoice_id, 'token' => $order->public_token])
            ->assertOk()->assertDontSee('Private street address');

        session(['landing_order_id' => $order->id, 'landing_order_slug' => $landing->slug,
            'public_order_success_id' => $order->id,
            'public_order_success_token' => $order->public_token]);
        $this->assertStringContainsString('/r/'.$landing->slug.'/order/success/', PublicOrderAccess::checkoutSuccessUrl($order));
    }

    public function test_manual_invoice_token_remains_usable_without_financial_or_customer_details(): void
    {
        $token = Str::random(48);
        $order = $this->makeOrder(null, $token);
        $path = '/invoice/verify/';

        $this->get($path.str_repeat('a', 48))->assertNotFound();
        $this->get($path.$token)->assertOk()->assertSee($order->invoice_id)
            ->assertDontSee('Private Recipient')->assertDontSee('01799999999')
            ->assertDontSee('Private street address')->assertDontSee('100.00');
    }

    public function test_another_customer_cannot_use_numeric_id_to_see_success_page(): void
    {
        $owner = $this->makeCustomer();
        $other = $this->makeCustomer();
        $order = $this->makeOrder($owner);

        $this->actingAs($other, 'customer')->get('/customer/order-success/'.$order->id)->assertNotFound();
        $this->assertStringNotContainsString('token=', PublicOrderAccess::successUrl($this->makeOrder($other)));
        $this->actingAs($owner, 'customer')->get('/customer/order-success/'.$order->id)->assertOk();
        $this->get('/customer/order-success/'.$order->id.'?token='.$order->public_token)
            ->assertRedirect('/customer/order-success/'.$order->id);
    }

    public function test_public_tracking_is_rate_limited(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.75']);
        for ($i = 0; $i < 5; $i++) {
            $this->post('/customer/order-track/result', ['invoice_id' => '54321'])->assertNotFound();
        }

        $this->post('/customer/order-track/result', ['invoice_id' => '54321'])->assertStatus(429);
    }

    public function test_public_confirmation_lookups_are_rate_limited(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.76']);
        for ($i = 0; $i < 10; $i++) {
            $this->get('/customer/order-success/987654')->assertNotFound();
        }

        $this->get('/customer/order-success/987654')->assertStatus(429);
    }

    public function test_public_payment_callbacks_cannot_exchange_numeric_id_for_tracking_token(): void
    {
        $order = $this->makeOrder();

        $bkash = $this->get('/bkash/checkout-url/callback?orderId='.$order->id.'&status=failure');
        $bkash->assertRedirect(route('customer.order_track'));
        $this->assertStringNotContainsString($order->public_token, $bkash->headers->get('Location'));

        $aamarpay = $this->get('/aamarpay/fail?opt_a='.$order->id);
        $aamarpay->assertRedirect(route('customer.order_track'));
        $this->assertStringNotContainsString($order->public_token, $aamarpay->headers->get('Location'));

        session(['public_order_success_id' => $order->id,
            'public_order_success_token' => str_repeat('a', 64)]);
        $this->assertSame(route('customer.order_track'), PublicOrderAccess::checkoutSuccessUrl($order));

        session(['public_order_success_id' => $order->id,
            'public_order_success_token' => $order->public_token]);
        $this->assertStringContainsString('token='.$order->public_token, PublicOrderAccess::checkoutSuccessUrl($order));
        $this->assertSame(route('customer.order_track'), PublicOrderAccess::checkoutSuccessUrl($this->makeOrder()));
    }

    private function makeCustomer(): Customer
    {
        $suffix = Str::lower(Str::random(10));

        return Customer::create(['name' => 'Order owner', 'slug' => 'owner-'.$suffix,
            'phone' => '017'.random_int(10000000, 99999999), 'email' => $suffix.'@example.test',
            'password' => bcrypt('secret'), 'status' => 'active']);
    }

    private function makeOrder(?Customer $customer = null, ?string $token = null, ?int $userId = null): Order
    {
        OrderStatus::firstOrCreate(['id' => 1], ['name' => 'Pending', 'slug' => 'pending', 'status' => '1']);
        $order = Order::create(['invoice_id' => 'TRACK-'.Str::upper(Str::random(10)),
            'customer_id' => $customer?->id, 'user_id' => $userId, 'amount' => 100,
            'discount' => 0, 'shipping_charge' => 0, 'order_status' => 1,
            'payment_status' => 'pending', 'public_token' => $token, 'is_manual_order' => $token !== null]);
        Shipping::create(['order_id' => $order->id, 'customer_id' => $customer?->id,
            'name' => 'Private Recipient', 'phone' => '01799999999',
            'address' => 'Private street address', 'area' => 'Private area']);

        return $order;
    }
}
