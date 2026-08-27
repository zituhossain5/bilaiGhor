<?php

namespace Tests\Feature;

use App\Exceptions\InsufficientStockException;
use App\Http\Controllers\Admin\ManualOrderController;
use App\Http\Middleware\PreventManualOrderDeletion;
use App\Models\InventoryMovement;
use App\Models\InventoryStock;
use App\Models\Order;
use App\Models\OrderDetails;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Shipping;
use App\Models\User;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Tests\TestCase;

class ManualOrderEditWorkflowTest extends TestCase
{
    use DatabaseTransactions;

    public function test_inventory_reconciliation_applies_only_quantity_deltas_and_is_idempotent(): void
    {
        [$product, $order] = $this->manualOrderWithStock(3, 10);

        InventoryService::reconcileOrderStock($order, [$this->stockLine($product, 5)], 1, true, 1);
        $this->assertStock($product, onHand: 10, reserved: 5, available: 5);

        InventoryService::reconcileOrderStock($order, [$this->stockLine($product, 2)], 1, true, 1);
        $this->assertStock($product, onHand: 10, reserved: 2, available: 8);

        try {
            InventoryService::reconcileOrderStock($order, [$this->stockLine($product, 11)], 1, true, 1);
            $this->fail('Expected insufficient stock validation.');
        } catch (InsufficientStockException $exception) {
            $this->assertSame(10, $exception->available);
            $this->assertSame(11, $exception->requested);
        }
        $this->assertStock($product, onHand: 10, reserved: 2, available: 8);

        InventoryService::reconcileOrderStock($order, [$this->stockLine($product, 4)], 6, true, 1);
        $this->assertStock($product, onHand: 6, reserved: 0, available: 6);

        InventoryService::reconcileOrderStock($order, [$this->stockLine($product, 2)], 6, true, 1);
        $this->assertStock($product, onHand: 8, reserved: 0, available: 8);

        InventoryService::reconcileOrderStock($order, [], 11, true, 1);
        InventoryService::reconcileOrderStock($order, [], 11, true, 1);
        $this->assertStock($product, onHand: 10, reserved: 0, available: 10);
    }

    public function test_edit_updates_customer_items_payment_status_and_preserves_invoice_identity(): void
    {
        [$product, $order] = $this->manualOrderWithStock(3, 10);
        $admin = User::create([
            'name' => 'Manual Order Test Admin',
            'email' => 'manual-order-' . Str::uuid() . '@example.test',
            'password' => bcrypt('secret'),
            'status' => 1,
        ]);
        Auth::guard('admin')->login($admin);

        Shipping::create([
            'order_id' => $order->id,
            'customer_id' => null,
            'name' => 'Old Name',
            'phone' => '01000000000',
            'address' => 'Old address',
            'area' => 'Manual Order',
        ]);
        Payment::create([
            'order_id' => $order->id,
            'customer_id' => null,
            'amount' => 0,
            'payment_method' => 'cash',
            'payment_status' => 'unpaid',
        ]);

        $invoice = $order->invoice_id;
        $invoiceNumber = $order->invoice_number;
        $publicToken = $order->public_token;

        $response = app(ManualOrderController::class)->update(
            $this->updateRequest($order, $product, paid: 500, status: 1),
            $order
        );

        $this->assertTrue($response->isRedirect(route('admin.manual_orders.show', $order)));
        $order->refresh();

        $this->assertSame($invoice, $order->invoice_id);
        $this->assertSame($invoiceNumber, $order->invoice_number);
        $this->assertSame($publicToken, $order->public_token);
        $this->assertSame('Corrected Customer', $order->manual_customer_name);
        $this->assertSame('Corrected delivery address', $order->manual_customer_address);
        $this->assertSame('partial', $order->payment_status);
        $this->assertEquals(500, $order->paid_amount);
        $this->assertEquals(40, $order->due_amount);
        $this->assertSame($admin->id, (int) $order->updated_by);
        $this->assertSame(2, $order->orderdetails()->count());
        $this->assertDatabaseHas('order_details', [
            'order_id' => $order->id,
            'product_id' => null,
            'product_name' => 'Custom packing item',
            'is_manual_item' => 1,
            'qty' => 2,
        ]);
        $this->assertDatabaseHas('shippings', [
            'order_id' => $order->id,
            'name' => 'Corrected Customer',
            'address' => 'Corrected delivery address',
        ]);
        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'amount' => 500,
            'payment_status' => 'partial',
            'trx_id' => 'BKASH-TEST-001',
        ]);
        $this->assertStock($product, onHand: 10, reserved: 4, available: 6);

        app(ManualOrderController::class)->update(
            $this->updateRequest($order, $product, paid: 540, status: 6),
            $order
        );
        $order->refresh();
        $this->assertSame('paid', $order->payment_status);
        $this->assertEquals(0, $order->due_amount);
        $this->assertSame(6, (int) $order->order_status);
        $this->assertStock($product, onHand: 6, reserved: 0, available: 6);

        app(ManualOrderController::class)->cancel($order);
        app(ManualOrderController::class)->cancel($order->refresh());
        $this->assertSame(11, (int) $order->refresh()->order_status);
        $this->assertStock($product, onHand: 10, reserved: 0, available: 10);
    }

    public function test_insufficient_edit_rolls_back_order_lines_and_stock(): void
    {
        [$product, $order] = $this->manualOrderWithStock(3, 5);
        $originalUpdatedAt = $order->updated_at;

        $request = $this->updateRequest($order, $product, paid: 0, status: 1, quantity: 6);
        $request->setLaravelSession(app('session.store'));
        $request->headers->set('referer', route('admin.manual_orders.edit', $order));

        $response = app(ManualOrderController::class)->update($request, $order);

        $this->assertTrue($response->isRedirect());
        $this->assertTrue(session('errors')->has('stock'));
        $this->assertSame(3, (int) $order->orderdetails()->first()->qty);
        $this->assertEquals($originalUpdatedAt, $order->fresh()->updated_at);
        $this->assertStock($product, onHand: 5, reserved: 3, available: 2);
    }

    public function test_legacy_delete_routes_are_blocked_for_manual_invoices_only(): void
    {
        [, $manualOrder] = $this->manualOrderWithStock(1, 2);
        $middleware = new PreventManualOrderDeletion();
        $nextCalled = false;

        $manualRequest = Request::create('/admin/order/destroy', 'POST', ['id' => $manualOrder->id]);
        $manualRequest->headers->set('Accept', 'application/json');
        $manualResponse = $middleware->handle($manualRequest, function () use (&$nextCalled) {
            $nextCalled = true;
            return response()->noContent();
        });

        $this->assertSame(422, $manualResponse->getStatusCode());
        $this->assertFalse($nextCalled);

        $websiteOrder = Order::create([
            'is_manual_order' => 0,
            'invoice_id' => 'WEB-' . Str::upper(Str::random(12)),
            'amount' => 100,
            'discount' => 0,
            'shipping_charge' => 0,
            'order_status' => 1,
        ]);
        $websiteRequest = Request::create('/admin/order/destroy', 'POST', ['id' => $websiteOrder->id]);
        $websiteResponse = $middleware->handle($websiteRequest, fn () => response()->noContent());

        $this->assertSame(204, $websiteResponse->getStatusCode());
    }

    private function manualOrderWithStock(int $quantity, int $onHand): array
    {
        $product = Product::create([
            'product_type' => 'physical',
            'name' => 'Manual Edit Test Product ' . Str::uuid(),
            'slug' => 'manual-edit-test-' . Str::uuid(),
            'category_id' => 1,
            'new_price' => 100,
            'stock' => $onHand,
            'status' => 1,
        ]);
        InventoryStock::create([
            'product_id' => $product->id,
            'on_hand' => $onHand,
            'reserved' => 0,
        ]);

        $invoice = 'TEST-' . Str::upper(Str::random(12));
        $order = Order::create([
            'is_manual_order' => 1,
            'invoice_id' => $invoice,
            'invoice_number' => $invoice,
            'amount' => $quantity * 100,
            'paid_amount' => 0,
            'due_amount' => $quantity * 100,
            'discount' => 0,
            'order_discount' => 0,
            'shipping_charge' => 0,
            'customer_id' => null,
            'manual_customer_name' => 'Old Name',
            'manual_customer_phone' => '01000000000',
            'manual_customer_address' => 'Old address',
            'order_status' => 1,
            'order_source' => 'manual',
            'payment_method' => 'cash',
            'payment_status' => 'unpaid',
            'public_token' => Str::random(48),
        ]);
        OrderDetails::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'is_manual_item' => 0,
            'purchase_price' => 50,
            'sale_price' => 100,
            'product_discount' => 0,
            'line_discount' => 0,
            'line_total' => $quantity * 100,
            'qty' => $quantity,
        ]);

        InventoryService::reserveForOrder($order, strict: true);

        return [$product, $order];
    }

    private function updateRequest(Order $order, Product $product, float $paid, int $status, int $quantity = 4): Request
    {
        return Request::create('/admin/manual-orders/' . $order->id, 'PUT', [
            'customer_id' => null,
            'customer_name' => 'Corrected Customer',
            'customer_phone' => '01900000000',
            'customer_email' => 'customer@example.test',
            'customer_address' => 'Corrected delivery address',
            'notes' => 'Updated after customer confirmation',
            'order_source' => 'facebook',
            'payment_method' => 'bkash',
            'transaction_id' => 'BKASH-TEST-001',
            'delivery_charge' => 70,
            'order_discount' => 20,
            'paid_amount' => $paid,
            'order_status' => $status,
            'items' => [
                [
                    'product_id' => $product->id,
                    'name' => '',
                    'variant' => '1kg',
                    'qty' => $quantity,
                    'unit_price' => 100,
                    'discount' => 10,
                ],
                [
                    'product_id' => null,
                    'name' => 'Custom packing item',
                    'variant' => 'Gift wrap',
                    'qty' => 2,
                    'unit_price' => 50,
                    'discount' => 0,
                ],
            ],
        ]);
    }

    private function stockLine(Product $product, int $quantity): array
    {
        return ['product_id' => $product->id, 'qty' => $quantity, 'name' => $product->name];
    }

    private function assertStock(Product $product, int $onHand, int $reserved, int $available): void
    {
        $stock = InventoryStock::where('product_id', $product->id)->firstOrFail();
        $this->assertSame($onHand, (int) $stock->on_hand);
        $this->assertSame($reserved, (int) $stock->reserved);
        $this->assertSame($available, (int) $stock->available);
        $this->assertSame($available, (int) $product->fresh()->stock);
    }
}
