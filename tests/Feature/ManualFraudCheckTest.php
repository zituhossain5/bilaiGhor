<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Shipping;
use App\Models\User;
use App\Services\FraudCheck\FraudReport;
use App\Support\FraudCheckSettings;
use App\Support\FraudPhone;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Manual Fraud Check: rates shown as BD Courier gives them, snapshot/range labels, overall-rate
 * methods, risk badge, cache + Refresh, fallback and phone input. BD Courier is always faked.
 */
class ManualFraudCheckTest extends TestCase
{
    use DatabaseTransactions;

    private const PHONE = '01977340165';

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertStringEndsWith('_testing', DB::connection()->getDatabaseName(), 'Tests must use the separate testing database.');

        Http::preventStrayRequests();
        $this->admin = User::create([
            'name' => 'Fraud Test Admin', 'email' => 'fraud-' . Str::uuid() . '@example.test',
            'password' => bcrypt('secret'), 'status' => 1,
        ]);
        $this->admin->assignRole(Role::findOrCreate('Admin', 'admin'));
        $this->settings([]);
    }

    // ================================================================ phone input

    public function test_phone_input_accepts_local_international_spaced_and_bangla_forms(): void
    {
        foreach (['01977340165', '+8801977340165', '8801977340165', '+880 1977-340165', '00880 1977 340 165',
            '1977340165', '০১৯৭৭৩৪০১৬৫', '+৮৮০ ১৯৭৭-৩৪০১৬৫', ' 01977-340-165 '] as $input) {
            $this->assertSame(self::PHONE, FraudPhone::normalize($input), $input);
        }

        foreach (['', '0197734016', '019773401655', '02977340165', '01277340165', 'abc01977340165', '01977340165x'] as $input) {
            $this->assertNull(FraudPhone::normalize($input), $input);
        }
        $this->assertSame('019****0165', FraudPhone::mask(self::PHONE));
    }

    public function test_an_invalid_number_is_rejected_before_any_api_call(): void
    {
        Http::fake();

        $this->actingAs($this->admin, 'admin')
            ->from(route('manualFraud.page'))
            ->post(route('manualFraud.check'), ['mobile' => '01234'])
            ->assertRedirect(route('manualFraud.page'))
            ->assertSessionHas('error');

        Http::assertNothingSent();
    }

    public function test_a_bangla_or_plus_880_number_is_sent_to_bd_courier_as_01_number(): void
    {
        $this->fakeBdCourier($this->samplePayload());

        $this->check('+৮৮০ ১৯৭৭-৩৪০১৬৫')->assertOk()->assertSee(self::PHONE);

        Http::assertSent(fn (HttpRequest $r) => $r['phone'] === self::PHONE);
    }

    // ================================================================ numbers and labels

    public function test_each_courier_rate_is_shown_as_given_with_counts_marked_as_a_dated_snapshot(): void
    {
        $this->fakeBdCourier($this->samplePayload());

        $response = $this->check(self::PHONE)->assertOk();
        $steadfast = $this->courier($response, 'steadfast');

        // 45 of 45 delivered would be 100%; the API's current rate is 98% and that is what shows.
        $this->assertSame(98.0, $steadfast['rate']);
        $this->assertSame(45, $steadfast['delivered']);
        $this->assertSame('2026-01-05', $steadfast['snapshot']->toDateString());
        $response->assertSeeInOrder(['data-courier="steadfast"', 'data-rate>98%', 'পুরনো হিসাব · ৫ জানুয়ারি ২০২৬'], false);
        $response->assertDontSee('data-mismatch', false);
    }

    public function test_counts_that_disagree_with_the_rate_without_a_date_get_an_info_note(): void
    {
        $payload = $this->samplePayload();
        unset($payload['data']['steadfast']['snapshot_date']);
        $this->fakeBdCourier($payload);

        $response = $this->check(self::PHONE)->assertOk();

        $this->assertTrue($this->courier($response, 'steadfast')['mismatch']);
        $this->assertNull($this->courier($response, 'steadfast')['snapshot']);
        $response->assertSee('data-mismatch', false)
            ->assertSee('Counts and rate come from different times')
            ->assertDontSee('পুরনো হিসাব ·');
    }

    public function test_a_parcel_range_is_shown_as_given_and_never_added_to_totals(): void
    {
        // Second answer: SteadFast's only total is a range.
        $rangeOnly = $this->samplePayload();
        $rangeOnly['data']['steadfast'] = ['name' => 'Steadfast', 'total_parcel' => '50+', 'success_parcel' => null, 'cancelled_parcel' => null, 'success_ratio' => 98];
        Http::fake(['api.bdcourier.com/*' => Http::sequence()->push($this->samplePayload())->push($rangeOnly)]);

        $response = $this->check(self::PHONE)->assertOk();
        $report = $response->viewData('report');

        $this->assertSame('50+', $this->courier($response, 'steadfast')['range']);
        $this->assertSame(['text' => '50+', 'is_range' => true], $report['volume']);
        $this->assertSame(12 + 45 + 87, $report['counts']['total']);
        $response->assertSee('<span class="fc-range" data-range>50+</span>', false)
            ->assertSee('<div class="fc-tile-value" data-volume>50+</div>', false);

        // A courier whose only total is a range adds nothing to the totals.
        $report = $this->check(self::PHONE, refresh: true)->viewData('report');
        $this->assertSame(12 + 87, $report['counts']['total']);
        $this->assertSame('50+', $report['volume']['text']);
    }

    public function test_couriers_without_history_are_grouped_as_no_data_never_a_red_zero(): void
    {
        $this->fakeBdCourier($this->samplePayload());

        $response = $this->check(self::PHONE)->assertOk();

        $this->assertSame(['RedX', 'PaperFly', 'ParcelDex'], $response->viewData('report')['no_data']);
        $response->assertSee('কোনো তথ্য নেই: RedX, PaperFly, ParcelDex')
            ->assertDontSee('data-courier="redx"', false)
            ->assertDontSee('data-rate>0%', false);
    }

    public function test_overall_rate_is_the_average_of_courier_rates_by_default(): void
    {
        $this->fakeBdCourier($this->samplePayload());

        $response = $this->check(self::PHONE)->assertOk();
        $overall = $response->viewData('report')['overall'];

        $this->assertSame('average', $overall['method']);
        $this->assertSame('99.3', FraudReport::pct($overall['rate']));
        $this->assertSame('(100 + 98 + 100) ÷ 3 = 99.3%', $overall['formula']);
        $response->assertSee('data-overall>99.3%', false)->assertSee('(100 + 98 + 100) ÷ 3 = 99.3%');
    }

    public function test_overall_rate_can_be_weighted_by_parcels(): void
    {
        $this->settings(['fraud_rate_method' => 'weighted']);
        $this->fakeBdCourier($this->samplePayload());

        $overall = $this->check(self::PHONE)->assertOk()->viewData('report')['overall'];

        // (100×12 + 98×45 + 100×87) ÷ 144 = 99.375
        $this->assertSame('weighted', $overall['method']);
        $this->assertEqualsWithDelta(99.375, $overall['rate'], 0.0001);
        $this->assertSame('(100×12 + 98×45 + 100×87) ÷ 144 = 99.4%', $overall['formula']);
    }

    public function test_cancelled_percentage_never_contradicts_the_cancelled_count(): void
    {
        $this->fakeBdCourier($this->samplePayload());

        $response = $this->check(self::PHONE)->assertOk();
        $counts = $response->viewData('report')['counts'];

        // Rates average 99.3%, but no parcel was cancelled: the tile says 0% with 0, not 0.7%.
        $this->assertSame(0, $counts['cancelled']);
        $this->assertSame(0.0, (float) $counts['cancelled_pct']);
        $response->assertSee('<div class="fc-tile-value" data-cancelled>0% <small>0 টি</small></div>', false)
            ->assertDontSee('0.7%')
            ->assertSee('সংরক্ষিত হিসাব থেকে');
    }

    public function test_explanation_is_built_from_the_data(): void
    {
        $this->fakeBdCourier($this->samplePayload());

        $lines = $this->check(self::PHONE)->assertOk()->viewData('report')['explanation'];
        $text = implode("\n", $lines);

        $this->assertStringContainsString('(100 + 98 + 100) ÷ 3 = 99.3%', $text);
        $this->assertStringContainsString('SteadFast: হার 98% বর্তমান; সংখ্যা (45 টির মধ্যে 45 টি সফল) ৫ জানুয়ারি ২০২৬ এর পুরনো হিসাব।', $text);
        $this->assertStringContainsString('"50+"', $text);
        $this->assertStringContainsString('RedX, PaperFly, ParcelDex', $text);
    }

    // ================================================================ risk

    public function test_risk_badge_follows_the_thresholds(): void
    {
        $this->fakeBdCourier($this->samplePayload());
        $response = $this->check(self::PHONE)->assertOk();

        $this->assertSame('safe', $response->viewData('report')['risk']['level']);
        $this->assertSame(0, $response->viewData('report')['report_count']);
        $response->assertSee('data-risk="safe"', false)->assertSee('data-reports="0"', false);

        $this->settings(['fraud_risk_safe_min' => '99.5', 'fraud_risk_medium_min' => '99']);
        $this->assertSame('medium', $this->check(self::PHONE)->viewData('report')['risk']['level']);

        $this->settings(['fraud_risk_safe_min' => '100', 'fraud_risk_medium_min' => '99.9']);
        $this->assertSame('high', $this->check(self::PHONE)->viewData('report')['risk']['level']);
    }

    public function test_any_fraud_report_makes_the_risk_at_least_medium_and_lists_the_reports(): void
    {
        $payload = $this->samplePayload();
        $payload['reports'] = [['courierName' => 'Steadfast', 'name' => 'Some Merchant', 'details' => 'Refused the parcel twice', 'created_at' => '2026-03-02T10:00:00Z']];
        $this->fakeBdCourier($payload);

        $response = $this->check(self::PHONE)->assertOk();
        $report = $response->viewData('report');

        $this->assertSame(1, $report['report_count']);
        $this->assertSame('medium', $report['risk']['level']);
        $this->assertTrue($report['risk']['raised_by_reports']);
        $response->assertSee('fc-tile fc-tile-bad', false)
            ->assertSee('data-reports="1"', false)
            ->assertSee('id="fc-reports"', false)
            ->assertSee('Refused the parcel twice');
    }

    public function test_no_history_anywhere_is_a_new_customer_with_a_grey_no_data_gauge(): void
    {
        $payload = $this->samplePayload();
        foreach (['pathao', 'steadfast', 'carrybee'] as $key) {
            $payload['data'][$key] = ['name' => ucfirst($key), 'total_parcel' => 0, 'success_parcel' => 0, 'cancelled_parcel' => 0, 'success_ratio' => 0];
        }
        $payload['data']['summary'] = ['total_parcel' => 0, 'success_parcel' => 0, 'cancelled_parcel' => 0, 'success_ratio' => 0];
        $this->fakeBdCourier($payload);

        $response = $this->check(self::PHONE)->assertOk();
        $report = $response->viewData('report');

        $this->assertNull($report['overall']['rate']);
        $this->assertSame('new', $report['risk']['level']);
        $this->assertSame([], $report['couriers']);
        $response->assertSee('fc-gauge fc-tone-none', false)->assertDontSee('data-rate>0%', false);
    }

    // ================================================================ cache, refresh, fallback, limits

    public function test_repeat_checks_use_the_cache_and_refresh_bypasses_it(): void
    {
        $this->fakeBdCourier($this->samplePayload());

        $this->check(self::PHONE)->assertOk();
        $cached = $this->check(self::PHONE)->assertOk();
        Http::assertSentCount(1);
        $this->assertTrue($cached->viewData('lookup')['from_cache']);
        $cached->assertSee('উৎস: <strong>BD Courier</strong>', false)->assertSee('নতুন সার্চ খরচ হয়নি');

        $fresh = $this->check(self::PHONE, refresh: true)->assertOk();
        Http::assertSentCount(2);
        $this->assertFalse($fresh->viewData('lookup')['from_cache']);
    }

    public function test_when_bd_courier_fails_the_last_saved_answer_is_shown_with_a_warning(): void
    {
        Http::fake(['api.bdcourier.com/*' => Http::sequence()
            ->push($this->samplePayload())
            ->push(['status' => 'error', 'message' => 'Both paid and free search limits have been reached.'], 429)]);

        $this->check(self::PHONE)->assertOk();
        $response = $this->check(self::PHONE, refresh: true)->assertOk();

        $this->assertTrue($response->viewData('lookup')['stale']);
        $response->assertSee('BD Courier থেকে নতুন তথ্য আনা যায়নি')
            ->assertSee('Both paid and free search limits have been reached.')
            ->assertSee('data-overall>99.3%', false);
    }

    public function test_a_failure_with_nothing_saved_shows_a_clear_error(): void
    {
        Http::fake(['api.bdcourier.com/*' => Http::response(['status' => 'error', 'message' => 'Both paid and free search limits have been reached.'], 429)]);

        $response = $this->check(self::PHONE)->assertOk();

        $this->assertFalse($response->viewData('lookup')['ok']);
        $response->assertSee('<strong>BD Courier:</strong> Both paid and free search limits have been reached.', false)
            ->assertDontSee('data-overall', false);
    }

    public function test_fresh_searches_are_limited_per_admin(): void
    {
        config(['fraud_check.fresh_checks_per_minute' => 2]);
        $this->settings(['fraud_cache_minutes' => '0']);
        $this->fakeBdCourier($this->samplePayload());

        $this->check(self::PHONE)->assertOk();
        $this->check(self::PHONE)->assertOk();
        $third = $this->check(self::PHONE)->assertOk();

        Http::assertSentCount(2);
        $this->assertTrue($third->viewData('lookup')['stale']);
        $third->assertSee('এক মিনিটে সর্বোচ্চ 2 বার নতুন চেক করা যায়');
    }

    // ================================================================ order list button and checkout share the cache

    public function test_order_list_button_reuses_the_cached_answer_and_updates_orders_saved_with_plus_880(): void
    {
        $this->fakeBdCourier($this->samplePayload());
        $order = $this->makeOrder('+8801977340165');

        $this->check(self::PHONE)->assertOk();
        $this->actingAs($this->admin, 'admin')
            ->postJson(route('admin.fraud.check'), ['mobile' => '+8801977340165'])
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.data.steadfast.success_ratio', 98)
            ->assertJsonMissingPath('warning');

        Http::assertSentCount(1);
        $this->assertEquals(98, $order->fresh()->steadfast_rate);
        $this->assertEquals(100, $order->fresh()->pathao_rate);
    }

    public function test_order_list_button_shows_the_saved_answer_with_a_warning_when_bd_courier_fails(): void
    {
        Http::fake(['api.bdcourier.com/*' => Http::sequence()
            ->push($this->samplePayload())
            ->push(['status' => 'error', 'message' => 'Both paid and free search limits have been reached.'], 429)]);
        $this->settings(['fraud_cache_minutes' => '0']);

        $this->check(self::PHONE)->assertOk();
        $this->actingAs($this->admin, 'admin')
            ->postJson(route('admin.fraud.check'), ['mobile' => self::PHONE])
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.data.steadfast.success_ratio', 98)
            ->assertJsonPath('warning', fn ($w) => str_contains($w, 'Both paid and free search limits have been reached.'));
    }

    public function test_order_list_button_rejects_an_invalid_number_without_a_search(): void
    {
        Http::fake();

        $this->actingAs($this->admin, 'admin')
            ->postJson(route('admin.fraud.check'), ['mobile' => '12345'])
            ->assertStatus(422)
            ->assertJsonPath('status', 'error');

        Http::assertNothingSent();
    }

    public function test_checkout_lookups_reuse_the_cache_and_are_not_held_to_the_admin_limit(): void
    {
        config(['fraud_check.fresh_checks_per_minute' => 1]);
        $this->fakeBdCourier($this->samplePayload());

        \App\Services\FraudCheck\FraudCheckLookup::bdCourier(self::PHONE, false, null);
        \App\Services\FraudCheck\FraudCheckLookup::bdCourier(self::PHONE, false, null);
        Http::assertSentCount(1);

        $this->assertTrue(\App\Services\FraudCheck\FraudCheckLookup::bdCourier('01711111111', false, null)['ok']);
        $this->assertTrue(\App\Services\FraudCheck\FraudCheckLookup::bdCourier('01822222222', false, null)['ok']);
        Http::assertSentCount(3);
    }

    // ================================================================ page

    public function test_recent_searches_are_kept_per_admin_and_numbers_can_be_masked(): void
    {
        $this->fakeBdCourier($this->samplePayload());
        foreach (['01711111111', '01822222222', '01933333333', '01544444444', '01655555555', self::PHONE] as $phone) {
            $this->check($phone)->assertOk();
        }

        $recent = $this->actingAs($this->admin, 'admin')->get(route('manualFraud.page'))->assertOk()->viewData('recent');
        $this->assertSame([self::PHONE, '01655555555', '01544444444', '01933333333', '01822222222'], $recent);

        $this->settings(['fraud_mask_phone' => '1']);
        $this->check(self::PHONE)->assertOk()
            ->assertSee('<span class="fc-phone">019****0165</span>', false)
            ->assertSee('<button type="submit" class="fc-chip">019****0165</button>', false);
    }

    public function test_settings_are_saved_when_the_settings_table_exists(): void
    {
        if (!FraudCheckSettings::ready()) {
            $this->markTestSkipped('courier_settings table is not migrated in this testing database.');
        }
        $this->forgetSettings();

        $this->actingAs($this->admin, 'admin')->post(route('manualFraud.settings'), [
            'fraud_rate_method' => 'weighted', 'fraud_mask_phone' => '1', 'fraud_cache_minutes' => '60',
            'fraud_risk_safe_min' => '85', 'fraud_risk_medium_min' => '60',
        ])->assertRedirect(route('manualFraud.page'));

        $this->assertSame('weighted', FraudCheckSettings::rateMethod());
        $this->assertTrue(FraudCheckSettings::maskPhone());
        $this->assertSame(['safe' => 85.0, 'medium' => 60.0], FraudCheckSettings::riskThresholds());

        $this->actingAs($this->admin, 'admin')->post(route('manualFraud.settings'), [
            'fraud_rate_method' => 'average', 'fraud_cache_minutes' => '60',
            'fraud_risk_safe_min' => '60', 'fraud_risk_medium_min' => '80',
        ])->assertSessionHasErrors('fraud_risk_medium_min');
    }

    public function test_guests_cannot_use_the_page(): void
    {
        Http::fake();

        $this->get(route('manualFraud.page'))->assertRedirect();
        $this->post(route('manualFraud.check'), ['mobile' => self::PHONE])->assertRedirect();
        Http::assertNothingSent();
    }

    // ================================================================ helpers

    private function check(string $mobile, bool $refresh = false)
    {
        return $this->actingAs($this->admin, 'admin')->post(route('manualFraud.check'), array_filter([
            'mobile' => $mobile, 'refresh' => $refresh ? '1' : null,
        ]));
    }

    private function courier($response, string $key): array
    {
        return collect($response->viewData('report')['couriers'])->firstWhere('key', $key);
    }

    private function makeOrder(string $phone): Order
    {
        $order = new Order();
        $order->forceFill([
            'invoice_id' => 'FRAUD-TEST-' . Str::random(6), 'amount' => 500, 'discount' => 0,
            'shipping_charge' => 60, 'order_status' => 1, 'steadfast_rate' => 0, 'pathao_rate' => 0,
        ])->save();

        $shipping = new Shipping();
        $shipping->forceFill(['order_id' => $order->id, 'name' => 'Fraud Test', 'phone' => $phone, 'address' => 'Test'])->save();

        return $order;
    }

    private function fakeBdCourier(array $payload): void
    {
        Http::fake(['api.bdcourier.com/*' => Http::response($payload)]);
    }

    /** Page settings for this request only (no database rows). */
    private function settings(array $values): void
    {
        app()->instance('fraud_check_settings.all', array_merge(FraudCheckSettings::DEFAULTS, $values));
    }

    private function forgetSettings(): void
    {
        app()->forgetInstance('fraud_check_settings.all');
    }

    /**
     * BD Courier's documented v3 shape, as described for 01977340165: Pathao 100%, SteadFast 98% now
     * with counts 45/45 saved on 5 Jan 2026 and a "50+" volume, CarryBee 100%, the rest without history.
     */
    private function samplePayload(): array
    {
        $none = fn (string $name) => ['name' => $name, 'logo' => 'https://api.bdcourier.com/logo.png', 'total_parcel' => 0, 'success_parcel' => 0, 'cancelled_parcel' => 0, 'success_ratio' => 0];

        return [
            'status' => 'success',
            'data'   => [
                'pathao'    => ['name' => 'Pathao', 'total_parcel' => 12, 'success_parcel' => 12, 'cancelled_parcel' => 0, 'success_ratio' => 100],
                'steadfast' => ['name' => 'Steadfast', 'total_parcel' => 45, 'success_parcel' => 45, 'cancelled_parcel' => 0, 'success_ratio' => 98,
                    'parcel_range' => '50+', 'snapshot_date' => '2026-01-05'],
                'redx'      => $none('RedX'),
                'paperfly'  => $none('Paperfly'),
                'parceldex' => $none('Parceldex'),
                'carrybee'  => ['name' => 'CarryBee', 'total_parcel' => 87, 'success_parcel' => 87, 'cancelled_parcel' => 0, 'success_ratio' => 100],
                'summary'   => ['total_parcel' => 144, 'success_parcel' => 144, 'cancelled_parcel' => 0, 'success_ratio' => 100],
            ],
            'reports'      => [],
            'risk_verdict' => ['level' => 'safe', 'label' => 'Safe', 'action' => 'OK to ship', 'color' => 'green', 'reasons' => ['Strong delivery success rate']],
        ];
    }
}
