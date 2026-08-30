<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\DeliveryThanaController;
use App\Models\Customer;
use App\Models\DeliveryThana;
use App\Models\Order;
use App\Models\Shipping;
use App\Support\DeliveryLocation;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Tests\TestCase;

class DeliveryThanaSystemTest extends TestCase
{
    use DatabaseTransactions;

    public function test_thana_charge_and_district_ownership_are_authoritative(): void
    {
        $thana = DeliveryThana::active()->firstOrFail();

        $this->assertTrue(DeliveryLocation::validateDistrictThana($thana->district_id, $thana->id));
        $this->assertFalse(DeliveryLocation::validateDistrictThana($thana->district_id + 1, $thana->id));
        $this->assertSame((float) $thana->delivery_charge, DeliveryLocation::chargeForThanaId($thana->id));
    }

    public function test_shipping_keeps_encoded_controller_alias_synchronized(): void
    {
        $thana = DeliveryThana::active()->firstOrFail();
        $shipping = new Shipping([
            'order_id' => 999999999,
            'name' => 'Compatibility Test',
            'phone' => '01900000000',
            'thana_id' => $thana->id,
        ]);

        $shipping->save();

        $this->assertSame($thana->id, (int) $shipping->upazila_id);
        $this->assertSame($thana->id, (int) $shipping->fresh()->thana_id);
    }

    public function test_order_shipping_update_uses_the_selected_thana_charge(): void
    {
        $thana = DeliveryThana::active()->where('delivery_charge', '>', 0)->firstOrFail();
        $order = Order::create([
            'invoice_id' => 'THANA-' . Str::upper(Str::random(12)),
            'amount' => 100,
            'discount' => 0,
            'shipping_charge' => 0,
            'customer_id' => null,
            'order_status' => 1,
            'payment_status' => 'pending',
            'is_manual_order' => 0,
        ]);
        Shipping::create([
            'order_id' => $order->id,
            'customer_id' => null,
            'name' => 'Thana Charge Test',
            'phone' => '01900000000',
            'address' => 'Test address',
        ]);

        $request = Request::create('/admin/order/update-shipping-location', 'POST', [
            'order_id' => $order->id,
            'district_id' => $thana->district_id,
            'thana_id' => $thana->id,
            'post_code' => '1204',
        ]);

        $response = app(DeliveryThanaController::class)->updateOrderShipping($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertEquals(100 + (float) $thana->delivery_charge, (float) $order->fresh()->amount);
        $this->assertDatabaseHas('shippings', [
            'order_id' => $order->id,
            'district_id' => $thana->district_id,
            'thana_id' => $thana->id,
            'upazila_id' => $thana->id,
            'post_code' => '1204',
        ]);
    }

    public function test_admin_can_create_and_edit_a_thana_delivery_charge(): void
    {
        $districtId = DeliveryThana::query()->value('district_id');
        $name = 'Test Thana ' . Str::random(10);

        $create = Request::create('/admin/delivery/thana/save', 'POST', [
            'district_id' => $districtId,
            'name' => $name,
            'delivery_charge' => 125.50,
            'sort_order' => 99,
            'status' => 1,
        ]);
        app(DeliveryThanaController::class)->store($create);

        $thana = DeliveryThana::where('name', $name)->firstOrFail();
        $this->assertSame('125.50', $thana->delivery_charge);
        $this->assertSame(1, (int) $thana->status);

        $update = Request::create('/admin/delivery/thana/update', 'POST', [
            'id' => $thana->id,
            'district_id' => $districtId,
            'name' => $name,
            'delivery_charge' => 155,
            'sort_order' => 2,
        ]);
        app(DeliveryThanaController::class)->update($update);

        $thana->refresh();
        $this->assertSame('155.00', $thana->delivery_charge);
        $this->assertSame(0, (int) $thana->status);
        $this->assertSame(2, (int) $thana->sort_order);
    }

    public function test_customer_profile_and_address_forms_load_and_save_thanas(): void
    {
        $thanas = DeliveryThana::active()
            ->whereIn('district_id', function ($query) {
                $query->select('district_id')->from('thanas')->where('status', 1)
                    ->groupBy('district_id')->havingRaw('COUNT(*) > 1');
            })
            ->orderBy('district_id')
            ->limit(2)
            ->get();
        $this->assertCount(2, $thanas);
        $this->assertSame($thanas[0]->district_id, $thanas[1]->district_id);

        $customer = Customer::create([
            'name' => 'Thana Frontend Test',
            'slug' => 'thana-frontend-' . Str::lower(Str::random(10)),
            'phone' => '018' . random_int(10000000, 99999999),
            'password' => bcrypt('secret'),
            'verify' => 1,
            'status' => 'active',
            'address' => 'Initial address',
            'district_id' => $thanas[0]->district_id,
            'thana_id' => $thanas[0]->id,
        ]);

        $profile = $this->actingAs($customer, 'customer')
            ->withHeader('referer', url('/'))
            ->get(route('customer.profile_edit'));
        $profile->assertOk()->assertSee('Thana')->assertDontSee('Zone <span', false);
        $html = $profile->getContent();
        $this->assertTrue(strpos($html, 'jquery-3.6.3.min.js') < strpos($html, 'select2.min.js'));
        $this->assertTrue(strpos($html, 'select2.min.js') < strpos($html, 'var thanasUrl'));
        $this->assertTrue(strpos($html, 'var thanasUrl') < strpos($html, 'BilaiDistrictThana.initFields'));

        $store = $this->actingAs($customer, 'customer')
            ->withHeader('referer', route('customer.addresses'))
            ->post(route('customer.addresses.store'), [
                'adr_name' => 'Saved Address',
                'adr_phone' => '01900000000',
                'adr_email' => 'address@example.test',
                'adr_post_code' => '1204',
                'adr_district_id' => $thanas[0]->district_id,
                'adr_thana_id' => $thanas[0]->id,
                'adr_address' => 'Saved address details',
            ]);
        $store->assertRedirect();
        $address = $customer->addresses()->firstOrFail();
        $this->assertSame($thanas[0]->id, (int) $address->thana_id);

        $update = $this->actingAs($customer, 'customer')
            ->withHeader('referer', route('customer.addresses'))
            ->post(route('customer.addresses.update', $address), [
                'adr_name' => 'Edited Address',
                'adr_phone' => '01900000000',
                'adr_email' => 'address@example.test',
                'adr_post_code' => '1205',
                'adr_district_id' => $thanas[1]->district_id,
                'adr_thana_id' => $thanas[1]->id,
                'adr_address' => 'Edited address details',
            ]);
        $update->assertRedirect();
        $this->assertSame($thanas[1]->id, (int) $address->fresh()->thana_id);

        $addresses = $this->actingAs($customer, 'customer')
            ->withHeader('referer', url('/'))
            ->get(route('customer.addresses'));
        $addresses->assertOk()
            ->assertSee('data-thana="' . $thanas[1]->id . '"', false)
            ->assertSee('bilai-afm-add-open', false)
            ->assertSee('bilai-afm-edit-open', false);
    }
}
