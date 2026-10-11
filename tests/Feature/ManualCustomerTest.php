<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\DeliveryThana;
use App\Models\Order;
use App\Models\Shipping;
use App\Models\User;
use App\Support\BdPhone;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Manual (admin-added) customers, the customer search used by Create Manual Invoice, and
 * linking a manual order to a customer.
 */
class ManualCustomerTest extends TestCase
{
    use DatabaseTransactions;

    // ---------------------------------------------------------------- customer form

    public function test_a_customer_can_be_added_with_every_field_empty(): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->post(route('customers.store'), [])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('customers.index'));

        $customer = Customer::latest('id')->first();
        $this->assertSame(Customer::SOURCE_MANUAL, $customer->source);
        $this->assertNull($customer->name);
        $this->assertNull($customer->phone);
        $this->assertNull($customer->email);
        $this->assertNull($customer->getRawOriginal('password'));
        $this->assertSame('active', $customer->status);
    }

    public function test_the_customer_form_checks_only_the_format_of_what_is_entered(): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->post(route('customers.store'), ['email' => 'not-an-email', 'phone' => '01712-abc', 'post_code' => str_repeat('9', 21)])
            ->assertSessionHasErrors(['email', 'phone', 'post_code']);

        $this->actingAs($this->admin(), 'admin')
            ->post(route('customers.store'), [
                'name' => 'Karim', 'phone' => '+880 1712-345678', 'email' => 'karim@example.test',
                'address' => 'House 1', 'post_code' => '1207', 'notes' => 'Prefers evening calls',
            ])
            ->assertSessionHasNoErrors();

        $customer = Customer::latest('id')->first();
        $this->assertSame('01712345678', $customer->phone_normalized);
        $this->assertSame('Prefers evening calls', $customer->notes);
    }

    public function test_a_duplicate_mobile_warns_but_still_saves(): void
    {
        $existing = $this->manualCustomer('Existing Person', '01811111111');

        $this->actingAs($this->admin(), 'admin')
            ->post(route('customers.store'), ['name' => 'Second Person', 'phone' => '+8801811111111'])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('customer_duplicates', fn ($dup) => $dup['others'][0]['id'] === $existing->id);

        $this->assertSame(2, Customer::withPhone('01811111111')->count());
    }

    // ---------------------------------------------------------------- mobile numbers

    public function test_bangladeshi_mobile_formats_normalize_to_the_same_number(): void
    {
        foreach (['+8801712345678', '8801712345678', '01712345678', '008801712345678', '1712345678', '+880 1712-345678'] as $format) {
            $this->assertSame('01712345678', BdPhone::normalize($format), $format);
        }
        $this->assertNull(BdPhone::normalize(''));
        $this->assertTrue(BdPhone::isComplete('+8801712345678'));
        $this->assertFalse(BdPhone::isComplete('01712'));

        $customer = $this->manualCustomer('Format Test', '+8801712345670');
        $this->assertSame('01712345670', $customer->phone_normalized);
        $this->assertTrue(Customer::withPhone('8801712345670')->whereKey($customer->id)->exists());

        $customer->update(['phone' => '01999999990']);
        $this->assertSame('01999999990', $customer->fresh()->phone_normalized);
    }

    // ---------------------------------------------------------------- search endpoint

    public function test_search_finds_customers_by_name_and_by_mobile_in_any_format(): void
    {
        $customer = $this->manualCustomer('Zubaida Searchable', '01755500011');
        $admin = $this->admin();

        $byName = $this->actingAs($admin, 'admin')->getJson(route('customers.search', ['q' => 'Zubaida Search']))->assertOk()->json('results');
        $this->assertContains($customer->id, array_column($byName, 'id'));

        foreach (['01755500011', '+8801755500011', '8801755500011', '55500011'] as $typed) {
            $byPhone = $this->actingAs($admin, 'admin')->getJson(route('customers.search', ['q' => $typed]))->json('results');
            $this->assertContains($customer->id, array_column($byPhone, 'id'), $typed);
        }

        $tooShort = $this->actingAs($admin, 'admin')->getJson(route('customers.search', ['q' => 'Z']))->json('results');
        $this->assertSame([], $tooShort);
    }

    public function test_same_name_customers_come_back_distinguishable(): void
    {
        $thanas = DeliveryThana::where('status', 1)->whereNotNull('district_id')->limit(2)->get();
        $a = $this->manualCustomer('Rahim Twinname', '01700000001', $thanas[0]);
        $b = $this->manualCustomer('Rahim Twinname', '01700000002', $thanas[1] ?? $thanas[0]);

        $results = collect($this->actingAs($this->admin(), 'admin')
            ->getJson(route('customers.search', ['q' => 'Rahim Twinname']))->json('results'))
            ->whereIn('id', [$a->id, $b->id]);

        $this->assertCount(2, $results);
        $this->assertSame(['01700000001', '01700000002'], $results->pluck('phone')->sort()->values()->all());
        $this->assertTrue($results->every(fn ($r) => $r['location'] !== '' && $r['manual'] === true));
        // Only what the form needs.
        $this->assertEqualsCanonicalizing(
            ['id', 'name', 'phone', 'email', 'address', 'district_id', 'thana_id', 'post_code', 'manual', 'location', 'exact'],
            array_keys($results->first())
        );
    }

    public function test_phone_lookup_marks_a_complete_exact_match(): void
    {
        $customer = $this->manualCustomer('Exact Match', '01766600077');
        $admin = $this->admin();

        $full = $this->actingAs($admin, 'admin')->getJson(route('customers.search', ['phone' => '+8801766600077']))->json('results');
        $row = collect($full)->firstWhere('id', $customer->id);
        $this->assertTrue($row['exact']);

        $partial = $this->actingAs($admin, 'admin')->getJson(route('customers.search', ['phone' => '017666']))->json('results');
        $this->assertFalse(collect($partial)->firstWhere('id', $customer->id)['exact']);

        $this->assertSame([], $this->actingAs($admin, 'admin')->getJson(route('customers.search', ['phone' => '017']))->json('results'));
    }

    public function test_search_is_admin_only(): void
    {
        Auth::guard('admin')->logout();
        $this->get(route('customers.search', ['q' => 'anything']))->assertRedirect();
    }

    // ---------------------------------------------------------------- login

    public function test_manual_customers_cannot_log_in(): void
    {
        $manual = $this->manualCustomer('No Login', '01788800088');
        $manual->forceFill(['password' => bcrypt('secret123')])->save(); // even if a password were set

        $this->assertFalse(Auth::guard('customer')->attempt(['phone' => '01788800088', 'password' => 'secret123']));
        $this->assertFalse(Auth::guard('customer')->loginUsingId($manual->id));
    }

    public function test_a_registered_customer_still_logs_in_when_a_manual_customer_shares_the_phone(): void
    {
        $this->manualCustomer('Earlier Manual', '01799900099');
        $registered = Customer::create([
            'name' => 'Real Shopper', 'slug' => 'real-shopper-' . Str::random(5), 'phone' => '01799900099',
            'password' => bcrypt('secret123'), 'status' => 'active', 'verify' => 1,
        ]);

        $this->assertTrue(Auth::guard('customer')->attempt(['phone' => '01799900099', 'password' => 'secret123']));
        $this->assertSame($registered->id, Auth::guard('customer')->id());
    }

    // ---------------------------------------------------------------- manual invoice

    public function test_a_manual_order_saved_with_a_customer_is_linked_to_that_customer(): void
    {
        $customer = $this->manualCustomer('Linked Buyer', '01722200022');

        $this->actingAs($this->admin(), 'admin')
            ->post(route('admin.manual_orders.store'), $this->orderPayload(['customer_id' => $customer->id, 'customer_name' => 'Linked Buyer (gift)']))
            ->assertSessionHasNoErrors();

        $order = Order::latest('id')->first();
        $this->assertSame($customer->id, (int) $order->customer_id);
        $this->assertSame('Linked Buyer (gift)', $order->manual_customer_name);        // order keeps its own snapshot
        $this->assertSame($customer->id, (int) Shipping::where('order_id', $order->id)->value('customer_id'));
        $this->assertSame('Linked Buyer', $customer->fresh()->name);                     // customer record untouched
    }

    public function test_also_update_customer_pushes_the_order_details_back(): void
    {
        $customer = $this->manualCustomer('Old Name', '01733300033');

        $this->actingAs($this->admin(), 'admin')
            ->post(route('admin.manual_orders.store'), $this->orderPayload([
                'customer_id' => $customer->id, 'customer_name' => 'New Name', 'customer_address' => 'New Road 5', 'update_customer' => 1,
            ]))
            ->assertSessionHasNoErrors();

        $fresh = $customer->fresh();
        $this->assertSame('New Name', $fresh->name);
        $this->assertSame('New Road 5', $fresh->address);
    }

    public function test_save_as_new_customer_creates_a_manual_customer_for_the_order(): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->post(route('admin.manual_orders.store'), $this->orderPayload([
                'customer_name' => 'Brand New', 'customer_phone' => '+8801744400044', 'save_as_customer' => 1,
            ]))
            ->assertSessionHasNoErrors();

        $order = Order::latest('id')->first();
        $customer = Customer::find($order->customer_id);
        $this->assertNotNull($customer);
        $this->assertSame(Customer::SOURCE_MANUAL, $customer->source);
        $this->assertSame('01744400044', $customer->phone_normalized);
    }

    public function test_an_order_without_a_customer_stays_unlinked(): void
    {
        $before = Customer::count();

        $this->actingAs($this->admin(), 'admin')
            ->post(route('admin.manual_orders.store'), $this->orderPayload())
            ->assertSessionHasNoErrors();

        $this->assertNull(Order::latest('id')->first()->customer_id);
        $this->assertSame($before, Customer::count());
    }

    // ---------------------------------------------------------------- helpers

    private function admin(): User
    {
        $admin = User::create([
            'name' => 'Customer Test Admin',
            'email' => 'customers-' . Str::uuid() . '@example.test',
            'password' => bcrypt('secret'),
            'status' => 1,
        ]);
        $admin->assignRole(Role::findOrCreate('Admin', 'admin'));

        return $admin;
    }

    private function manualCustomer(?string $name, ?string $phone, ?DeliveryThana $thana = null): Customer
    {
        $customer = new Customer([
            'name' => $name,
            'phone' => $phone,
            'district_id' => $thana?->district_id,
            'thana_id' => $thana?->id,
        ]);
        $customer->source = Customer::SOURCE_MANUAL;
        $customer->status = 'active';
        $customer->save();

        return $customer;
    }

    private function orderPayload(array $overrides = []): array
    {
        $thana = DeliveryThana::where('status', 1)
            ->whereHas('district', fn ($q) => $q->where('status', 1))
            ->firstOrFail();

        return array_merge([
            'customer_name' => 'Walk In',
            'customer_phone' => '01711122233',
            'customer_address' => 'Shop counter',
            'district_id' => $thana->district_id,
            'thana_id' => $thana->id,
            'order_source' => 'manual',
            'payment_method' => 'cash',
            'delivery_charge' => 0,
            'items' => [['name' => 'Test item', 'qty' => 1, 'unit_price' => 100, 'discount' => 0]],
        ], $overrides);
    }
}
