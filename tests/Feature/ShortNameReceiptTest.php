<?php

namespace Tests\Feature;

use App\Models\DeliveryThana;
use App\Models\GeneralSetting;
use App\Models\InventoryStock;
use App\Models\Order;
use App\Models\OrderDetails;
use App\Models\Product;
use App\Models\User;
use App\Services\ReceiptLayout;
use App\Support\ShortName;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Short product names, the name each order line keeps, and the compact receipt layout.
 */
class ShortNameReceiptTest extends TestCase
{
    use DatabaseTransactions;

    // ---------------------------------------------------------------- short-name suggestions

    public function test_the_suggester_turns_the_spec_examples_into_short_names(): void
    {
        $this->assertSame('Nekko Kitten Tuna Mousse', ShortName::suggest('Nekko Kitten Pouch Tuna Mousse Wet Cat Food - 70g'));
        $this->assertSame('Whiskas Ocean Fish', ShortName::suggest('Whiskas Adult Pouch Ocean Fish Flavor Wet Cat Food – 80g'));
    }

    public function test_the_suggester_drops_weights_ages_and_pack_sizes(): void
    {
        $this->assertSame('Whiskas Tuna', ShortName::suggest('Whiskas Dry Cat Food Adult 1+ Years Tuna 3kg'));
        $this->assertSame('Wanpy Creamy Treats Tuna', ShortName::suggest('Wanpy Creamy Treats for Cats Tuna – (5x14g)'));
        $this->assertSame('Dr. Clump Clumping Cat Litter Lemon', ShortName::suggest('Dr. Clump Clumping Cat Litter Lemon Flavor 10 Ltr'));
        $this->assertSame('Jungle Kitten Chicken', ShortName::suggest('Jungle Kitten Dry Food Chicken 1.5kg'));
        $this->assertSame('Taipet Premium Chicken & Tuna', ShortName::suggest('Taipet Premium Dry Cat Food for Adult Chicken & Tuna 1kg'));
    }

    public function test_pouch_is_kept_when_it_is_needed_to_tell_the_product_apart(): void
    {
        $this->assertSame('Whiskas Pouch Tuna', ShortName::suggest('Whiskas Pouch Adult Tuna Cat Food 80g'));
    }

    public function test_suggestions_never_exceed_the_limit_and_end_on_a_whole_word(): void
    {
        $long = 'Bellotta Cat Canned Food Real Tuna Topping Tuna Roe in Jelly Wet Cat Food for Adult Cats  400g';
        $short = ShortName::suggest($long);

        $this->assertLessThanOrEqual(40, mb_strlen($short));
        $this->assertSame('Bellotta Real Tuna Topping Tuna Roe', $short);
        $this->assertSame('', ShortName::suggest('   '));
    }

    // ---------------------------------------------------------------- fallback

    public function test_the_fallback_cuts_long_names_at_a_word_boundary_with_an_ellipsis(): void
    {
        $fallback = ShortName::fallback('Whiskas Adult Pouch Ocean Fish Flavor Wet Cat Food – 80g');

        $this->assertSame('Whiskas Adult Pouch Ocean Fish Flavor…', $fallback);
        $this->assertLessThanOrEqual(40, mb_strlen($fallback));
        $this->assertSame('Pet Hair Remover Grooming Glove', ShortName::fallback('Pet Hair Remover Grooming Glove'));
        $this->assertSame(40, mb_strlen(ShortName::fallback(str_repeat('a', 60))));
    }

    public function test_a_line_uses_the_short_name_then_the_shortened_full_name(): void
    {
        $product = new Product(['name' => 'Whiskas Adult Pouch Ocean Fish Flavor Wet Cat Food – 80g', 'short_name' => 'Whiskas Ocean Fish']);
        $this->assertSame('Whiskas Ocean Fish', ShortName::forLine($product, $product->name));

        $product->short_name = null;
        $this->assertSame('Whiskas Adult Pouch Ocean Fish Flavor…', ShortName::forLine($product, $product->name));
        $this->assertSame('Custom gift wrap', ShortName::forLine(null, 'Custom gift wrap'));
    }

    // ---------------------------------------------------------------- name kept on the order line

    public function test_order_lines_keep_their_receipt_name_after_the_product_is_renamed(): void
    {
        $product = $this->product('Nekko Kitten Pouch Tuna Mousse Wet Cat Food - 70g', 'Nekko Kitten Tuna Mousse');
        $order = $this->order();
        $line = OrderDetails::create($this->line($order, $product));

        $this->assertSame('Nekko Kitten Tuna Mousse', $line->fresh()->invoice_name);

        $product->update(['name' => 'Nekko Renamed Product', 'short_name' => 'Nekko Renamed']);

        $this->assertSame('Nekko Kitten Tuna Mousse', $line->fresh()->display_name);
        $this->assertSame('Nekko Kitten Tuna Mousse', ReceiptLayout::mergeLines($order->fresh()->orderdetails)->first()['name']);
    }

    public function test_lines_saved_before_the_change_fall_back_to_the_products_current_names(): void
    {
        $product = $this->product('Whiskas Adult Pouch Ocean Fish Flavor Wet Cat Food – 80g', null);
        $line = OrderDetails::create($this->line($this->order(), $product));
        $line->forceFill(['invoice_name' => null])->saveQuietly(); // an old line

        $this->assertSame('Whiskas Adult Pouch Ocean Fish Flavor…', $line->fresh()->display_name);

        $product->update(['short_name' => 'Whiskas Ocean Fish']);
        $this->assertSame('Whiskas Ocean Fish', $line->fresh()->display_name);
    }

    public function test_create_manual_invoice_saves_the_per_line_receipt_name(): void
    {
        $product = $this->product('Whiskas Adult Pouch Ocean Fish Flavor Wet Cat Food – 80g', 'Whiskas Ocean Fish');

        $this->actingAs($this->admin(), 'admin')
            ->post(route('admin.manual_orders.store'), $this->orderPayload([
                ['product_id' => $product->id, 'qty' => 2, 'unit_price' => 85, 'discount' => 0, 'invoice_name' => 'Whiskas Ocean Fish (gift)'],
                ['product_id' => $product->id, 'qty' => 1, 'unit_price' => 85, 'discount' => 0],
            ]))
            ->assertSessionHasNoErrors();

        $names = Order::latest('id')->first()->orderdetails()->orderBy('id')->pluck('invoice_name')->all();
        $this->assertSame(['Whiskas Ocean Fish (gift)', 'Whiskas Ocean Fish'], $names);
    }

    public function test_a_receipt_name_longer_than_the_limit_is_rejected(): void
    {
        $product = $this->product('Long Name Product', null);

        $this->actingAs($this->admin(), 'admin')
            ->post(route('admin.manual_orders.store'), $this->orderPayload([
                ['product_id' => $product->id, 'qty' => 1, 'unit_price' => 10, 'invoice_name' => str_repeat('x', 41)],
            ]))
            ->assertSessionHasErrors('items.0.invoice_name');
    }

    // ---------------------------------------------------------------- product form + bulk tool

    public function test_the_product_short_name_is_validated_to_40_characters(): void
    {
        $product = $this->product('Validation Product', null);
        $admin = $this->admin();

        $this->actingAs($admin, 'admin')
            ->post(route('products.update'), ['id' => $product->id, 'hidden_id' => $product->id, 'name' => $product->name, 'short_name' => str_repeat('y', 41)])
            ->assertSessionHasErrors('short_name');
    }

    public function test_the_suggest_endpoint_returns_a_suggestion_for_admins_only(): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->getJson(route('products.short_names.suggest', ['name' => 'Whiskas Adult Pouch Ocean Fish Flavor Wet Cat Food – 80g']))
            ->assertOk()
            ->assertExactJson(['short_name' => 'Whiskas Ocean Fish']);

        auth('admin')->logout();
        $this->get(route('products.short_names.suggest', ['name' => 'x']))->assertRedirect();
    }

    public function test_the_bulk_tool_previews_and_saves_only_the_ticked_rows(): void
    {
        $a = $this->product('Nekko Kitten Pouch Tuna Mousse Wet Cat Food - 70g', null);
        $b = $this->product('Whiskas Adult Pouch Ocean Fish Flavor Wet Cat Food – 80g', null);
        $done = $this->product('Already Named Product', 'Already Named');
        $admin = $this->admin();

        $this->actingAs($admin, 'admin')
            ->get(route('products.short_names'))
            ->assertOk()
            ->assertSee('Nekko Kitten Tuna Mousse')
            ->assertSee('Whiskas Ocean Fish')
            ->assertDontSee('Already Named Product');

        $this->actingAs($admin, 'admin')
            ->post(route('products.short_names.store'), [
                'apply' => [$a->id, $done->id],
                'names' => [$a->id => 'Nekko Tuna Mousse', $b->id => 'Whiskas Ocean Fish', $done->id => 'Overwritten'],
            ])
            ->assertRedirect(route('products.short_names'));

        $this->assertSame('Nekko Tuna Mousse', $a->fresh()->short_name);
        $this->assertNull($b->fresh()->short_name);                 // not ticked
        $this->assertSame('Already Named', $done->fresh()->short_name); // already had one
    }

    // ---------------------------------------------------------------- receipt layout

    public function test_the_same_product_variant_and_price_merge_into_one_row(): void
    {
        $product = $this->product('Whiskas Adult Pouch Ocean Fish Flavor Wet Cat Food – 80g', 'Whiskas Ocean Fish');
        $order = $this->order();
        OrderDetails::create($this->line($order, $product, qty: 2, price: 85, variant: '80 gm'));
        OrderDetails::create($this->line($order, $product, qty: 3, price: 85, variant: '80 gm'));
        OrderDetails::create($this->line($order, $product, qty: 1, price: 90, variant: '80 gm')); // different price
        OrderDetails::create($this->line($order, $product, qty: 1, price: 85, variant: '400 gm')); // different variant

        $rows = ReceiptLayout::mergeLines($order->fresh()->orderdetails);

        $this->assertCount(3, $rows);
        $this->assertSame(['name' => 'Whiskas Ocean Fish', 'variant' => '80 gm', 'qty' => 5, 'price' => 85.0, 'amount' => 425.0], $rows[0]);
        // Same-named rows get their weight added so they can be told apart.
        $this->assertSame('Whiskas Ocean Fish 80 gm ×5, Whiskas Ocean Fish 80 gm ×1, Whiskas Ocean Fish 400 gm ×1', ReceiptLayout::commaText($rows));
    }

    public function test_long_orders_go_dense_and_split_pages_with_totals_on_the_last_page(): void
    {
        $rows = array_fill(0, 53, ['name' => 'Item', 'variant' => null, 'qty' => 1, 'price' => 1.0, 'amount' => 1.0]);

        $this->assertCount(1, ReceiptLayout::paginate(array_slice($rows, 0, 52), 'dense'));
        $pages = ReceiptLayout::paginate($rows, 'dense');
        $this->assertSame([52, 1], array_map('count', $pages));

        $this->assertCount(1, ReceiptLayout::paginate(array_slice($rows, 0, 38), 'normal'));
        $this->assertSame([38, 1], array_map('count', ReceiptLayout::paginate(array_slice($rows, 0, 39), 'normal')));

        $this->assertCount(1, ReceiptLayout::paginate([], 'normal')); // an order without items still prints its totals
    }

    public function test_the_receipt_hides_empty_totals_and_shows_how_it_was_paid(): void
    {
        $product = $this->product('Whiskas Adult Pouch Ocean Fish Flavor Wet Cat Food – 80g', 'Whiskas Ocean Fish');
        $order = $this->order();
        OrderDetails::create($this->line($order, $product, qty: 2, price: 85));

        $html = $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.manual_orders.print', $order))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Whiskas Ocean Fish', $html);
        $this->assertStringContainsString('Due ৳170 · Cash', $html);
        $this->assertStringContainsString('Scan to verify', $html);
        $this->assertStringNotContainsString('Discount', $html);
        $this->assertStringNotContainsString('---', $html);
        $this->assertStringNotContainsString('Delivery</td>', $html);
    }

    /** Saving General Settings also rewrites APP_NAME in .env, so the form and the layout are tested here. */
    public function test_receipt_settings_appear_in_general_settings_and_drive_the_layout(): void
    {
        $settings = GeneralSetting::first();

        $this->actingAs($this->admin(), 'admin')
            ->get(route('settings.edit', $settings->id))
            ->assertOk()
            ->assertSee('name="receipt_dense_after"', false)
            ->assertSee('name="receipt_comma_list"', false)
            ->assertSee('no per-item prices', false);

        $order = $this->order();
        $product = $this->product('Comma Product', 'Comma Short');
        foreach (range(1, 3) as $price) {
            OrderDetails::create($this->line($order, $product, qty: 4, price: $price));
        }

        $settings->forceFill(['receipt_dense_after' => 2, 'receipt_comma_list' => 0]);
        $layout = ReceiptLayout::build($order->fresh(), $settings);
        $this->assertTrue($layout['dense']);
        $this->assertFalse($layout['commaList']);

        $settings->forceFill(['receipt_dense_after' => 12, 'receipt_comma_list' => 1]);
        $layout = ReceiptLayout::build($order->fresh(), $settings);
        $this->assertFalse($layout['dense']);
        $this->assertTrue($layout['commaList']);
        $this->assertSame('Comma Short ×4, Comma Short ×4, Comma Short ×4', $layout['commaText']);
    }

    // ---------------------------------------------------------------- helpers

    private function admin(): User
    {
        $admin = User::create([
            'name' => 'Receipt Test Admin',
            'email' => 'receipt-' . Str::uuid() . '@example.test',
            'password' => bcrypt('secret'),
            'status' => 1,
        ]);
        $admin->assignRole(Role::findOrCreate('Admin', 'admin'));

        return $admin;
    }

    private function product(string $name, ?string $shortName): Product
    {
        $product = Product::create([
            'product_type' => 'physical',
            'name' => $name,
            'short_name' => $shortName,
            'slug' => 'receipt-test-' . Str::uuid(),
            'category_id' => 1,
            'new_price' => 85,
            'stock' => 50,
            'status' => 1,
        ]);
        InventoryStock::create(['product_id' => $product->id, 'on_hand' => 50, 'reserved' => 0]);

        return $product;
    }

    private function order(): Order
    {
        $invoice = 'TEST-' . Str::upper(Str::random(12));

        return Order::create([
            'is_manual_order' => 1,
            'invoice_id' => $invoice,
            'invoice_number' => $invoice,
            'amount' => 170,
            'paid_amount' => 0,
            'due_amount' => 170,
            'discount' => 0,
            'order_discount' => 0,
            'shipping_charge' => 0,
            'manual_customer_name' => 'Receipt Buyer',
            'manual_customer_phone' => '01700000000',
            'manual_customer_address' => 'House 1, Dhaka',
            'order_status' => 1,
            'order_source' => 'manual',
            'payment_method' => 'cash',
            'payment_status' => 'unpaid',
            'public_token' => Str::random(48),
        ]);
    }

    private function line(Order $order, Product $product, int $qty = 1, int $price = 85, ?string $variant = null): array
    {
        return [
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'manual_variant' => $variant,
            'is_manual_item' => 0,
            'purchase_price' => 0,
            'sale_price' => $price,
            'product_discount' => 0,
            'line_discount' => 0,
            'line_total' => $qty * $price,
            'qty' => $qty,
        ];
    }

    private function orderPayload(array $items): array
    {
        $thana = DeliveryThana::where('status', 1)
            ->whereHas('district', fn ($q) => $q->where('status', 1))
            ->firstOrFail();

        return [
            'customer_name' => 'Walk In',
            'customer_phone' => '01711122233',
            'customer_address' => 'Shop counter',
            'district_id' => $thana->district_id,
            'thana_id' => $thana->id,
            'order_source' => 'manual',
            'payment_method' => 'cash',
            'delivery_charge' => 0,
            'items' => $items,
        ];
    }
}
