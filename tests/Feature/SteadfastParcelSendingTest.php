<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\CourierSendController;
use App\Http\Controllers\Admin\OrderController;
use App\Models\Courierapi;
use App\Models\CourierTrackingEvent;
use App\Models\DeliveryDistrict;
use App\Models\DeliveryDivision;
use App\Models\DeliveryThana;
use App\Models\Order;
use App\Models\OrderDetails;
use App\Models\OrderStatus;
use App\Models\Shipping;
use App\Models\SteadfastPoliceStation;
use App\Models\ThanaSteadfastPoliceStation;
use App\Models\User;
use App\Services\CourierDispatchService;
use App\Services\Steadfast\AreaMatcher;
use App\Services\Steadfast\SteadfastArea;
use App\Support\CourierSettings;
use App\Support\SteadfastPickup;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Steadfast parcel sending: area in the address, typed parcel weight, total_lot and the pickup reminder.
 * Steadfast is never called for real (Http::fake + preventStrayRequests).
 */
class SteadfastParcelSendingTest extends TestCase
{
    use DatabaseTransactions;

    private DeliveryDistrict $coxsBazar;
    private DeliveryThana $sadar;
    private int $courierApiId;

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
        // courierapis is a MyISAM table (no transactions): the fake row is removed in tearDown().
        Courierapi::query()->where('api_key', 'test-key')->delete();
        $this->courierApiId = Courierapi::forceCreate([
            'type' => 'steadfast', 'status' => 1, 'api_key' => 'test-key', 'secret_key' => 'test-secret',
            'url' => 'https://portal.packzy.com/api/v1',
        ])->id;
        OrderStatus::query()->firstOrCreate(['id' => 5], ['name' => 'In Courier', 'slug' => 'in-courier', 'status' => 1]);

        $division = DeliveryDivision::forceCreate(['name' => 'চট্টগ্রাম']);
        $this->coxsBazar = DeliveryDistrict::forceCreate(['name' => 'কক্সবাজার', 'division_id' => $division->id]);
        $this->sadar = DeliveryThana::forceCreate(['name' => 'কক্সবাজার সদর', 'district_id' => $this->coxsBazar->id]);

        foreach ([[900001, "Cox's Bazar Sadar"], [900002, 'Teknaf,টেকনাফ'], [900003, 'Ramu']] as [$id, $name]) {
            SteadfastPoliceStation::forceCreate(['id' => $id, 'district_id' => 77, 'district_name' => "Cox's Bazar", 'name' => $name, 'status' => true]);
        }

        // Every switch at its default unless a test says otherwise.
        CourierSettings::set(CourierSettings::DEFAULTS);
        SteadfastArea::forget();
    }

    protected function tearDown(): void
    {
        Courierapi::query()->whereKey($this->courierApiId)->delete();

        parent::tearDown();
    }

    // ── Area mapping ─────────────────────────────────────────────

    public function test_bangla_thanas_are_matched_to_steadfast_areas_in_the_same_district(): void
    {
        $matcher = app(AreaMatcher::class);
        $candidates = $matcher->candidates($matcher->stationsByDistrict(), 'কক্সবাজার');

        [$station, $score] = $matcher->best('কক্সবাজার সদর', $candidates);
        $this->assertSame("Cox's Bazar Sadar", $station['name']);
        $this->assertGreaterThanOrEqual(AreaMatcher::EXACT, $score);

        // "Teknaf,টেকনাফ": every part of Steadfast's name counts.
        [$station, $score] = $matcher->best('টেকনাফ', $candidates);
        $this->assertSame('Teknaf,টেকনাফ', $station['name']);
        $this->assertGreaterThanOrEqual(AreaMatcher::EXACT, $score);

        // য় stored decomposed (য + ়) must compare equal to the precomposed letter.
        $this->assertSame(AreaMatcher::skeleton("পটুয\u{09BC}াখালী"), AreaMatcher::skeleton("পটু\u{09DF}াখালী"));
    }

    public function test_equal_skeletons_are_left_for_an_admin_to_confirm(): void
    {
        $candidates = [
            ['id' => 1, 'name' => 'Demra', 'keys' => [AreaMatcher::skeleton('Demra')], 'tags' => [], 'sadar' => false],
            ['id' => 2, 'name' => 'Dhamrai', 'keys' => [AreaMatcher::skeleton('Dhamrai')], 'tags' => [], 'sadar' => false],
        ];

        [$station, $score] = app(AreaMatcher::class)->best('ধামরাই', $candidates);

        $this->assertSame('Dhamrai', $station['name']);
        $this->assertLessThan(AreaMatcher::EXACT, $score);
    }

    public function test_auto_matching_never_overwrites_an_admin_choice(): void
    {
        ThanaSteadfastPoliceStation::create(['thana_id' => $this->sadar->id, 'steadfast_police_station_id' => 900003, 'match_type' => 'manual']);

        app(AreaMatcher::class)->matchAll(true);

        $row = ThanaSteadfastPoliceStation::where('thana_id', $this->sadar->id)->first();
        $this->assertSame('manual', $row->match_type);
        $this->assertSame(900003, $row->steadfast_police_station_id);
    }

    // ── Address string ──────────────────────────────────────────

    public function test_matched_thana_ends_the_address_with_the_english_steadfast_area(): void
    {
        $this->mapSadar('exact');
        $order = $this->makeOrder(['address' => 'Road 5, Baharchara']);

        $body = (new CourierDispatchService())->steadfastPayload($order, ['weight' => '1'])['body'];

        $this->assertSame("Road 5, Baharchara, Cox's Bazar Sadar, Cox's Bazar", $body['recipient_address']);
    }

    public function test_long_address_is_cut_from_the_customer_text_not_from_the_area(): void
    {
        $this->mapSadar('exact');
        $order = $this->makeOrder(['address' => str_repeat('Lane ', 50)]);

        $address = (new CourierDispatchService())->steadfastPayload($order, ['weight' => '1'])['body']['recipient_address'];

        $this->assertLessThanOrEqual(220, mb_strlen($address));
        $this->assertStringEndsWith(", Cox's Bazar Sadar, Cox's Bazar", $address);
    }

    public function test_unconfirmed_or_missing_match_keeps_the_old_address(): void
    {
        $this->mapSadar('close');   // a suggestion only
        $order = $this->makeOrder(['address' => 'Road 5, Baharchara']);

        $address = (new CourierDispatchService())->steadfastPayload($order, ['weight' => '1'])['body']['recipient_address'];

        $this->assertSame('Road 5, Baharchara, কক্সবাজার সদর, কক্সবাজার, চট্টগ্রাম', $address);
        $this->assertSame('close', SteadfastArea::problem($this->sadar->id));
    }

    // ── Weight ──────────────────────────────────────────────────

    public function test_weight_validation(): void
    {
        foreach (['', 'abc', '0', '0.00', '-1', '1.234', '31', '1,2,3'] as $bad) {
            $this->assertNotNull(CourierDispatchService::weightError($bad), "'{$bad}' should be rejected");
        }
        foreach (['0.5', '1', '1.25', '30', '১.৫', '2,5'] as $good) {
            $this->assertNull(CourierDispatchService::weightError($good), "'{$good}' should be accepted");
        }
        $this->assertSame(1.5, CourierDispatchService::parseWeight('১.৫'));

        CourierSettings::set(['weight_max_kg' => '5']);
        $this->assertNotNull(CourierDispatchService::weightError('5.5'));
    }

    public function test_required_weight_blocks_sending_without_calling_steadfast(): void
    {
        Http::fake();
        $order = $this->makeOrder();

        $result = (new CourierDispatchService())->send($order, 'steadfast');

        $this->assertFalse($result['ok']);
        $this->assertStringContainsString('ওজন', $result['message']);
        Http::assertNothingSent();
        $this->assertNull($order->fresh()->courier_tracking_id);
    }

    public function test_bulk_send_uses_each_orders_own_weight(): void
    {
        $bodies = $this->fakeSteadfast();
        $first = $this->makeOrder(['note' => 'Call before delivery']);
        $second = $this->makeOrder();

        $response = app(OrderController::class)->bulk_courier($this->bulkRequest([$first->id, $second->id], [
            'weights' => [$first->id => '1.5', $second->id => '2'],
        ]), 'steadfast');

        $data = $response->getData(true);
        $this->assertCount(2, $data['success'], json_encode($data['failed'] ?? []));
        $notes = collect($bodies())->pluck('note', 'invoice');
        $this->assertSame('Call before delivery | Wt: 1.5 kg', $notes[$first->invoice_id]);
        $this->assertSame('Wt: 2 kg', $notes[$second->invoice_id]);

        // Kept on the order and with the parcel's "sent" record.
        $this->assertEquals(1.5, (float) $first->fresh()->parcel_weight_kg);
        $event = CourierTrackingEvent::where('order_id', $first->id)->where('source', 'dispatch')->first();
        $this->assertEquals(1.5, $event->raw_payload['weight_kg']);
        $this->assertSame('Call before delivery | Wt: 1.5 kg', $event->raw_payload['sent']['note']);
    }

    public function test_one_bad_weight_fails_only_that_order(): void
    {
        $bodies = $this->fakeSteadfast();
        $good = $this->makeOrder();
        $bad = $this->makeOrder();

        $data = app(OrderController::class)->bulk_courier($this->bulkRequest([$good->id, $bad->id], [
            'weights' => [$good->id => '1', $bad->id => '0'],
        ]), 'steadfast')->getData(true);

        $this->assertSame([$good->invoice_id], $data['success']);
        $this->assertSame($bad->invoice_id, $data['failed'][0]['order_id']);
        $this->assertCount(1, $bodies());
        $this->assertNull($bad->fresh()->courier_tracking_id);
    }

    public function test_weight_in_note_stays_within_250_characters_and_can_be_switched_off(): void
    {
        $order = $this->makeOrder(['note' => str_repeat('n', 260)]);
        $note = (new CourierDispatchService())->steadfastPayload($order, ['weight' => '12.75'])['body']['note'];
        $this->assertSame(250, mb_strlen($note));
        $this->assertStringEndsWith(' | Wt: 12.75 kg', $note);

        CourierSettings::set(['weight_in_note' => '0']);
        $plain = $this->makeOrder(['note' => 'Fragile']);
        $this->assertSame('Fragile', (new CourierDispatchService())->steadfastPayload($plain, ['weight' => '1'])['body']['note']);
    }

    public function test_weight_is_never_filled_in_automatically(): void
    {
        $order = $this->makeOrder();   // two items, no weight typed

        $request = Request::create('/admin/courier-send/preview', 'GET', ['order_ids' => [$order->id]]);
        $preview = app(CourierSendController::class)->preview($request)->getData(true);

        $this->assertNull($preview['orders'][0]['weight']);
        $this->assertNull($order->fresh()->parcel_weight_kg);
        $this->assertNull((new CourierDispatchService())->steadfastPayload($order)['weight']);
    }

    public function test_create_manual_invoice_saves_only_a_typed_weight(): void
    {
        $this->actingAs($this->admin(), 'admin');

        $this->get(route('admin.manual_orders.create'))->assertOk()->assertSee('name="parcel_weight_kg"', false);

        $this->post(route('admin.manual_orders.store'), $this->manualPayload(['parcel_weight_kg' => '1.5']))->assertSessionHasNoErrors();
        $this->assertEquals(1.5, (float) Order::latest('id')->first()->parcel_weight_kg);

        $this->post(route('admin.manual_orders.store'), $this->manualPayload(['parcel_weight_kg' => '0']))->assertSessionHasErrors('parcel_weight_kg');

        $this->post(route('admin.manual_orders.store'), $this->manualPayload())->assertSessionHasNoErrors();
        $this->assertNull(Order::latest('id')->first()->parcel_weight_kg);
    }

    public function test_weight_box_saves_clears_and_rejects(): void
    {
        $order = $this->makeOrder();
        $controller = app(CourierSendController::class);
        $save = fn ($weight) => $controller->saveWeight(Request::create('/x', 'POST', ['weight' => $weight]), $order);

        $this->assertSame('2.5', $save('2.50')->getData(true)['weight']);
        $this->assertEquals(2.5, (float) $order->fresh()->parcel_weight_kg);

        $this->assertSame(422, $save('40')->getStatusCode());
        $this->assertEquals(2.5, (float) $order->fresh()->parcel_weight_kg);

        $this->assertNull($save('')->getData(true)['weight']);
        $this->assertNull($order->fresh()->parcel_weight_kg);
    }

    // ── total_lot ───────────────────────────────────────────────

    public function test_total_lot_is_item_quantity_or_number_of_packages(): void
    {
        $order = $this->makeOrder();   // 2 + 3 items
        $service = new CourierDispatchService();

        $this->assertSame(5, $service->steadfastPayload($order, ['weight' => '1'])['body']['total_lot']);

        CourierSettings::set(['steadfast_total_lot' => 'packages']);
        $this->assertSame(1, $service->steadfastPayload($order, ['weight' => '1'])['body']['total_lot']);
        $this->assertSame(3, $service->steadfastPayload($order, ['weight' => '1', 'lot' => '3'])['body']['total_lot']);
    }

    // ── Pickup reminder ─────────────────────────────────────────

    public function test_pickup_reminder_counts_todays_steadfast_parcels_until_ticked(): void
    {
        $this->assertNull(SteadfastPickup::reminder());

        $this->makeOrder()->forceFill(['courier_type' => 'steadfast', 'courier_tracking_id' => 'T1', 'courier_sent_at' => now()])->save();
        $this->makeOrder()->forceFill(['courier_type' => 'steadfast', 'courier_tracking_id' => 'T2', 'courier_sent_at' => now()->subDay()])->save();
        $this->makeOrder()->forceFill(['courier_type' => 'pathao', 'courier_tracking_id' => 'T3', 'courier_sent_at' => now()])->save();

        $reminder = SteadfastPickup::reminder();
        $this->assertSame(1, $reminder['count']);
        $this->assertFalse($reminder['done']);

        SteadfastPickup::markDone(true);
        $this->assertTrue(SteadfastPickup::reminder()['done']);

        CourierSettings::set(['steadfast_pickup_reminder' => '0']);
        $this->assertNull(SteadfastPickup::reminder());
    }

    // ── Old behaviour ───────────────────────────────────────────

    public function test_with_the_new_switches_off_the_payload_is_exactly_the_old_one(): void
    {
        CourierSettings::set(['steadfast_area_in_address' => '0', 'weight_required' => '0', 'steadfast_total_lot' => 'items']);
        $this->mapSadar('exact');
        $bodies = $this->fakeSteadfast();
        $order = $this->makeOrder(['address' => 'Road 5, Baharchara', 'note' => 'Fragile']);

        $result = (new CourierDispatchService())->send($order, 'steadfast');

        $this->assertTrue($result['ok'], $result['message']);
        $this->assertSame([
            'invoice'           => (string) $order->invoice_id,
            'recipient_name'    => 'Test Customer',
            'recipient_phone'   => '01711000000',
            'recipient_address' => 'Road 5, Baharchara, কক্সবাজার সদর, কক্সবাজার, চট্টগ্রাম',
            'cod_amount'        => 500,
            'note'              => 'Fragile',
            'item_description'  => 'Cat Food x2, Litter x3',
            'total_lot'         => 5,
        ], $bodies()[0]);
    }

    public function test_an_order_already_sent_is_never_sent_again(): void
    {
        $bodies = $this->fakeSteadfast();
        $order = $this->makeOrder();
        $order->forceFill(['courier_type' => 'steadfast', 'courier_tracking_id' => '308914966'])->save();

        $result = (new CourierDispatchService())->send($order, 'steadfast', [], ['weight' => '1']);

        $this->assertFalse($result['ok']);
        $this->assertSame([], $bodies());
    }

    // ── Helpers ─────────────────────────────────────────────────

    private function mapSadar(string $type): void
    {
        ThanaSteadfastPoliceStation::updateOrCreate(['thana_id' => $this->sadar->id], [
            'steadfast_police_station_id' => 900001, 'match_type' => $type, 'score' => 1,
        ]);
        SteadfastArea::forget();
    }

    private function makeOrder(array $o = []): Order
    {
        $order = Order::create([
            'invoice_id'      => 'SFT-' . Str::upper(Str::random(10)),
            'amount'          => 500,
            'paid_amount'     => 0,
            'due_amount'      => 500,
            'discount'        => 0,
            'shipping_charge' => 0,
            'order_status'    => 1,
            'payment_status'  => 'unpaid',
            'order_note'      => $o['note'] ?? null,
        ]);
        Shipping::forceCreate([
            'order_id'    => $order->id,
            'name'        => 'Test Customer',
            'phone'       => '01711000000',
            'address'     => $o['address'] ?? 'House 1',
            'area'        => 'কক্সবাজার সদর, কক্সবাজার, চট্টগ্রাম',
            'district_id' => $this->coxsBazar->id,
            'thana_id'    => $this->sadar->id,
        ]);
        OrderDetails::forceCreate(['order_id' => $order->id, 'product_name' => 'Cat Food', 'sale_price' => 100, 'qty' => 2]);
        OrderDetails::forceCreate(['order_id' => $order->id, 'product_name' => 'Litter', 'sale_price' => 100, 'qty' => 3]);

        return $order;
    }

    /** Fake create_order; returns a reader for the request bodies Steadfast would have received. */
    private function fakeSteadfast(): \Closure
    {
        $bodies = [];
        Http::fake(function (HttpRequest $request) use (&$bodies) {
            if (!str_ends_with($request->url(), '/create_order')) {
                return Http::response(['message' => 'unexpected'], 500);
            }
            $bodies[] = $request->data();

            return Http::response(['status' => 200, 'consignment' => [
                'consignment_id' => random_int(100000, 999999), 'tracking_code' => 'SFR' . Str::upper(Str::random(8)),
            ]]);
        });

        return function () use (&$bodies) {
            return $bodies;
        };
    }

    private function admin(): User
    {
        $admin = User::create([
            'name' => 'Steadfast Test Admin',
            'email' => 'sf-' . Str::uuid() . '@example.test',
            'password' => bcrypt('secret'),
            'status' => 1,
        ]);
        $admin->assignRole(Role::findOrCreate('Admin', 'admin'));

        return $admin;
    }

    /** A Create Manual Invoice form with one typed item (no stock involved). */
    private function manualPayload(array $extra = []): array
    {
        return [
            'customer_name'    => 'Walk In',
            'customer_phone'   => '01711122233',
            'customer_address' => 'Shop counter',
            'district_id'      => $this->coxsBazar->id,
            'thana_id'         => $this->sadar->id,
            'order_source'     => 'manual',
            'payment_method'   => 'cash',
            'delivery_charge'  => 0,
            'items'            => [['name' => 'Cat Toy', 'qty' => 1, 'unit_price' => 100, 'discount' => 0]],
        ] + $extra;
    }

    private function bulkRequest(array $ids, array $extra = []): Request
    {
        $request = Request::create('/admin/bulk-courier/steadfast', 'GET', ['order_ids' => $ids, 'status' => 5] + $extra);
        $request->headers->set('Accept', 'application/json');

        return $request;
    }
}
