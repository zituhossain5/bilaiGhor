<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\InlineOrderStatusController;
use App\Http\Controllers\Admin\OrderPaymentController;
use App\Models\FundTransaction;
use App\Models\InventoryStock;
use App\Models\Order;
use App\Models\OrderDetails;
use App\Models\OrderStatus;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Tests\TestCase;

class InlineOrderStatusUpdateTest extends TestCase
{
    use DatabaseTransactions;

    public function test_inline_status_updates_share_inventory_and_fund_side_effects_without_duplicates(): void
    {
        $this->ensureStatus(1, 'Pending');
        $this->ensureStatus(6, 'Delivered');
        $this->ensureStatus(11, 'Cancelled');

        $admin = User::create([
            'name' => 'Inline Status Admin',
            'email' => 'inline-status-' . Str::uuid() . '@example.test',
            'password' => bcrypt('secret'),
            'status' => 1,
        ]);
        Auth::guard('admin')->login($admin);

        $product = Product::create([
            'product_type' => 'physical',
            'name' => 'Inline Status Product ' . Str::uuid(),
            'slug' => 'inline-status-product-' . Str::uuid(),
            'category_id' => 1,
            'new_price' => 100,
            'stock' => 10,
            'status' => 1,
        ]);
        InventoryStock::create(['product_id' => $product->id, 'on_hand' => 10, 'reserved' => 0]);

        $order = $this->makeOrder(200);
        OrderDetails::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'sale_price' => 100,
            'qty' => 2,
        ]);
        Payment::create([
            'order_id' => $order->id,
            'customer_id' => null,
            'amount' => 0,
            'payment_method' => 'cash',
            'payment_status' => 'unpaid',
        ]);
        InventoryService::reserveForOrder($order, strict: true);

        $statusController = app(InlineOrderStatusController::class);
        $statusController->update($this->statusRequest(6), $order);
        $statusController->update($this->statusRequest(6), $order->refresh());

        $stock = InventoryStock::where('product_id', $product->id)->firstOrFail();
        $this->assertSame(8, (int) $stock->on_hand);
        $this->assertSame(0, (int) $stock->reserved);

        $paymentController = app(OrderPaymentController::class);
        $paymentController->updateStatus($this->paymentRequest($order, 'paid'));
        $paymentController->updateStatus($this->paymentRequest($order, 'paid'));

        $this->assertSame('paid', $order->refresh()->payment_status);
        $this->assertEquals(200, $order->paid_amount);
        $this->assertEquals(0, $order->due_amount);
        $this->assertSame(1, FundTransaction::query()
            ->where('source', 'sale')
            ->where('source_id', $order->id)
            ->count());

        $statusController->update($this->statusRequest(11), $order);
        $statusController->update($this->statusRequest(11), $order->refresh());

        $stock->refresh();
        $this->assertSame(10, (int) $stock->on_hand);
        $this->assertSame(0, (int) $stock->reserved);
    }

    public function test_inline_payment_update_preserves_workflow_statuses_and_existing_partial_amount(): void
    {
        $this->ensureStatus(1, 'Pending');
        $order = $this->makeOrder(500);
        Payment::create([
            'order_id' => $order->id,
            'customer_id' => null,
            'amount' => 200,
            'payment_method' => 'bkash',
            'payment_status' => 'partial',
        ]);
        $order->forceFill(['paid_amount' => 200, 'due_amount' => 300, 'payment_status' => 'partial'])->save();

        $controller = app(OrderPaymentController::class);
        $response = $controller->updateStatus($this->paymentRequest($order, 'partial'));
        $this->assertSame('partial', $response->getData(true)['payment_status']);
        $this->assertEquals(200, $order->refresh()->paid_amount);

        $controller->updateStatus($this->paymentRequest($order, 'paid'));
        $this->assertSame('paid', $order->refresh()->payment_status);
        $this->assertEquals(500, $order->paid_amount);

        $controller->updateStatus($this->paymentRequest($order, 'pending'));
        $this->assertSame('pending', $order->refresh()->payment_status);
        $this->assertSame('pending', Payment::where('order_id', $order->id)->latest('id')->value('payment_status'));
        $this->assertEquals(0, $order->paid_amount);
        $this->assertEquals(500, $order->due_amount);

        $controller->updateStatus($this->paymentRequest($order, 'failed'));
        $this->assertSame('failed', $order->refresh()->payment_status);
        $this->assertSame('failed', Payment::where('order_id', $order->id)->latest('id')->value('payment_status'));
    }

    private function makeOrder(int $amount): Order
    {
        return Order::create([
            'invoice_id' => 'INLINE-' . Str::upper(Str::random(12)),
            'amount' => $amount,
            'paid_amount' => 0,
            'due_amount' => $amount,
            'discount' => 0,
            'shipping_charge' => 0,
            'customer_id' => null,
            'order_status' => 1,
            'payment_status' => 'unpaid',
        ]);
    }

    private function ensureStatus(int $id, string $name): void
    {
        OrderStatus::query()->firstOrCreate(
            ['id' => $id],
            ['name' => $name, 'slug' => Str::slug($name), 'status' => 1]
        );
    }

    private function statusRequest(int $status): Request
    {
        $request = Request::create('/admin/orders/1/inline-status', 'POST', ['order_status' => $status]);
        $request->headers->set('Accept', 'application/json');

        return $request;
    }

    private function paymentRequest(Order $order, string $status): Request
    {
        $request = Request::create('/admin/order/update-payment-status', 'POST', [
            'order_id' => $order->id,
            'payment_status' => $status,
        ]);
        $request->headers->set('Accept', 'application/json');

        return $request;
    }
}
