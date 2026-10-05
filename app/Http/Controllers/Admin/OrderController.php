<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\InsufficientStockException;
use App\Http\Controllers\Controller;
use App\Models\Color;
use App\Models\Contact;
use App\Models\DeliveryBoy;
use App\Models\DeliveryDistrict;
use App\Models\Order;
use App\Models\OrderDetails;
use App\Models\OrderStatus;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariantPrice;
use App\Models\Shipping;
use App\Models\Size;
use App\Models\User;
use App\Services\BdCourierService;
use App\Services\CouponService;
use App\Services\CourierDispatchService;
use App\Services\InventoryService;
use App\Services\PathaoService;
use App\Services\RedXService;
use App\Services\RewardPointService;
use App\Support\TrafficSourceDetector;
use Brian2694\Toastr\Facades\Toastr;
use Gloudemans\Shoppingcart\Facades\Cart;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;

/**
 * Admin order management: lists, quick view, process, status, notes, invoice, print/label,
 * POS sales and order edits (both via the session "pos_shopping" cart), delete and bulk actions,
 * courier booking (CourierDispatchService), fraud and duplicate-order checks.
 *
 * Rewritten in plain PHP to replace the vendor's ionCube-encoded controller. Inventory,
 * reward points and fund crediting react to status changes through the Order model's
 * `updated` hook, so this controller only has to change orders the normal Eloquent way.
 */
class OrderController extends Controller
{
    private const POS_CART = 'pos_shopping';
    private const PER_PAGE = 50;

    // =========================================================
    // Order lists
    // =========================================================

    public function index(Request $request, $slug)
    {
        return view('backEnd.order.index', $this->listData($request, $slug));
    }

    /** Same list, for callers that load it asynchronously. */
    public function ajaxIndex(Request $request, $slug)
    {
        return $this->index($request, $slug);
    }

    private function listData(Request $request, string $slug): array
    {
        if ($slug === 'all') {
            $order_status = new OrderStatus(['name' => 'All', 'slug' => 'all']);
            $order_status->orders_count = Order::count();
        } else {
            $order_status = OrderStatus::where('slug', $slug)->withCount('orders')->firstOrFail();
        }

        $keyword = trim((string) $request->keyword);
        $source = trim((string) $request->traffic_source);

        $show_data = Order::query()
            ->with(['shipping', 'status', 'payment'])
            ->when($slug !== 'all', fn ($q) => $q->where('order_status', $order_status->id))
            ->when($keyword !== '', function ($q) use ($keyword) {
                $like = '%' . addcslashes($keyword, '%_\\') . '%';
                $q->where(function ($q) use ($like) {
                    $q->where('invoice_id', 'like', $like)
                        ->orWhereHas('shipping', fn ($s) => $s->where('phone', 'like', $like)->orWhere('name', 'like', $like));
                });
            })
            ->when($source !== '', fn ($q) => $q->where('traffic_source', $source))
            ->latest('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        $pathao = new PathaoService();

        return [
            'order_status'           => $order_status,
            'show_data'              => $show_data,
            'orderstatus'            => $this->statuses(),
            'users'                  => $this->staffUsers(),
            'traffic_source_options' => $this->trafficSourceOptions(),
            // A courier's button shows only when its API settings are switched on and filled in.
            'steadfast'              => CourierDispatchService::config('steadfast'),
            'pathao_info'            => $pathao->isConfigured(),
            'redx_info'              => CourierDispatchService::config('redx'),
            'pathaostore'            => $pathao->isConfigured() ? $pathao->stores() : null,
            'pathaocities'           => $pathao->isConfigured() ? $pathao->cities() : null,
        ];
    }

    public function orderQuickView($id): JsonResponse
    {
        $order = Order::with([
            'orderdetails.product.image', 'orderdetails.vendor', 'orderdetails.image',
            'shipping', 'customer', 'payment', 'status',
        ])->find($id);

        if (!$order) {
            return response()->json(['status' => 'error', 'message' => 'Order not found'], 404);
        }

        $html = view('backEnd.order.partials.order_quick_view_body', [
            'order'                  => $order,
            'blockedIps'             => DB::table('ip_blocks')->pluck('ip_no')->all(),
            'traffic_source_options' => $this->trafficSourceOptions(),
            'steadfast'              => CourierDispatchService::config('steadfast'),
            'pathao_info'            => CourierDispatchService::config('pathao'),
            'redx_info'              => CourierDispatchService::config('redx'),
        ])->render();

        return response()->json(['status' => 'success', 'html' => $html, 'invoice_id' => $order->invoice_id]);
    }

    // =========================================================
    // Process page, status and notes
    // =========================================================

    public function process($invoice_id)
    {
        $data = $this->findByInvoice($invoice_id, [
            'orderdetails.product.image', 'orderdetails.image', 'shipping', 'payment', 'status', 'customer',
        ]);

        return view('backEnd.order.process', [
            'data'         => $data,
            'orderstatus'  => $this->statuses(),
            'districts'    => $this->districts(),
            'deliveryBoys' => DeliveryBoy::active()->orderBy('name')->get(),
        ]);
    }

    /**
     * Process form: customer name/phone/address, rider and status. District/thana/post code
     * (and the delivery charge that follows them) are saved just before this by
     * DeliveryThanaController::updateOrderShipping, so they are not touched here.
     */
    public function order_process(Request $request)
    {
        $validated = $request->validate([
            'id'              => 'required|integer|exists:orders,id',
            'name'            => 'required|string|max:155',
            'phone'           => 'required|string|max:55',
            'address'         => 'required|string|max:256',
            'status'          => ['required', 'integer', Rule::exists('order_statuses', 'id')],
            'delivery_boy_id' => 'nullable|integer|exists:delivery_boys,id',
        ]);

        $order = Order::findOrFail($validated['id']);

        try {
            DB::transaction(function () use ($order, $validated) {
                Shipping::where('order_id', $order->id)->update([
                    'name'    => $validated['name'],
                    'phone'   => $validated['phone'],
                    'address' => $validated['address'],
                ]);

                $riderId = $validated['delivery_boy_id'] ?? null;
                if ((int) $order->delivery_boy_id !== (int) $riderId) {
                    $order->forceFill([
                        'delivery_boy_id'      => $riderId,
                        'delivery_assigned_at' => $riderId ? now() : null,
                    ])->save();
                }

                $this->changeStatus($order, (int) $validated['status']);
            });
        } catch (InsufficientStockException $e) {
            Toastr::error($e->validationMessage(), 'স্টক নেই');

            return redirect()->back();
        }

        Toastr::success('অর্ডার আপডেট হয়েছে।', 'সফল');

        return redirect()->route('admin.order.process', $order->invoice_id);
    }

    /** Invoice page status dropdown (JSON). */
    public function updateSingleStatus(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_id'     => 'required|integer|exists:orders,id',
            'order_status' => ['required', 'integer', Rule::exists('order_statuses', 'id')],
        ]);

        try {
            $this->changeStatus(Order::findOrFail($validated['order_id']), (int) $validated['order_status']);
        } catch (InsufficientStockException $e) {
            return response()->json(['status' => 'error', 'message' => $e->validationMessage()], 422);
        }

        return response()->json(['status' => 'success', 'message' => 'অর্ডার স্ট্যাটাস আপডেট হয়েছে।']);
    }

    public function updateNote(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_id'  => 'required|integer|exists:orders,id',
            'note_type' => 'required|in:admin,order',
            'note'      => 'nullable|string|max:5000',
        ]);

        $column = $validated['note_type'] === 'admin' ? 'admin_note' : 'order_note';
        Order::whereKey($validated['order_id'])->update([$column => $validated['note'] ?? null]);

        return response()->json(['status' => 'success', 'message' => 'Note updated']);
    }

    // =========================================================
    // Invoice / print / label
    // =========================================================

    public function invoice($invoice_id)
    {
        $order = $this->findByInvoice($invoice_id, [
            'orderdetails.product.image', 'orderdetails.image', 'shipping', 'payment', 'status', 'customer',
        ]);

        return view('backEnd.order.invoice', [
            'order'       => $order,
            'orderstatus' => $this->statuses(),
            'contact'     => Contact::first(),
        ]);
    }

    /** Bulk print (or shipping labels with type=label) — the page opens the returned HTML. */
    public function order_print(Request $request): JsonResponse
    {
        $ids = $this->orderIds($request);
        if (empty($ids)) {
            return response()->json(['status' => 'error', 'message' => 'Please select at least one order.']);
        }

        $orders = Order::with(['orderdetails.product.image', 'orderdetails.image', 'shipping', 'payment', 'status', 'customer'])
            ->whereIn('id', $ids)
            ->latest('id')
            ->get();

        $view = $request->type === 'label' ? 'backEnd.order.label' : 'backEnd.order.print';
        $html = view($view, ['orders' => $orders, 'contact' => Contact::first()])->render();

        return response()->json(['status' => 'success', 'view' => $html]);
    }

    // =========================================================
    // Bulk actions
    // =========================================================

    public function order_assign(Request $request): JsonResponse
    {
        $ids = $this->orderIds($request);
        $userId = (int) $request->user_id;

        if (empty($ids)) {
            return response()->json(['status' => 'error', 'message' => 'Please select an order first.']);
        }
        if (!$userId || !User::whereKey($userId)->exists()) {
            return response()->json(['status' => 'error', 'message' => 'Please select a user.']);
        }

        Order::whereIn('id', $ids)->update(['user_id' => $userId]);

        return response()->json(['status' => 'success', 'message' => count($ids) . ' টি অর্ডার অ্যাসাইন হয়েছে।']);
    }

    public function order_status(Request $request): JsonResponse
    {
        $request->validate([
            'order_status' => ['required', 'integer', Rule::exists('order_statuses', 'id')],
            'order_ids'    => 'required|array|min:1',
            'order_ids.*'  => 'integer',
        ]);

        $target = (int) $request->order_status;
        $updated = 0;
        $failed = [];

        foreach (Order::whereIn('id', $this->orderIds($request))->get() as $order) {
            try {
                $this->changeStatus($order, $target);
                $updated++;
            } catch (InsufficientStockException $e) {
                $failed[] = '#' . $order->invoice_id . ': ' . $e->validationMessage();
            }
        }

        if ($updated === 0 && $failed) {
            return response()->json(['status' => 'error', 'message' => implode(' | ', $failed)]);
        }

        $message = $updated . ' টি অর্ডারের স্ট্যাটাস আপডেট হয়েছে।';
        if ($failed) {
            $message .= ' ব্যর্থ: ' . implode(' | ', $failed);
        }

        return response()->json(['status' => 'success', 'message' => $message]);
    }

    public function destroy(Request $request)
    {
        $order = Order::find((int) $request->id);

        if (!$order) {
            Toastr::error('অর্ডার পাওয়া যায়নি।', 'ত্রুটি');

            return redirect()->back();
        }
        if ($order->is_manual_order) {
            Toastr::error('Manual invoices cannot be deleted. Cancel them instead.', 'ত্রুটি');

            return redirect()->back();
        }

        $this->deleteOrder($order);
        Toastr::success('অর্ডার ডিলিট হয়েছে।', 'সফল');

        return redirect()->back();
    }

    public function bulk_destroy(Request $request): JsonResponse
    {
        $ids = $this->orderIds($request);
        if (empty($ids)) {
            return response()->json(['status' => 'error', 'message' => 'Please select an order first.']);
        }

        $deleted = 0;
        foreach (Order::whereIn('id', $ids)->where('is_manual_order', 0)->get() as $order) {
            $this->deleteOrder($order);
            $deleted++;
        }

        $skipped = count($ids) - $deleted;
        $message = $deleted . ' টি অর্ডার ডিলিট হয়েছে।' . ($skipped > 0 ? " ($skipped manual invoice skipped)" : '');

        return response()->json(['status' => 'success', 'message' => $message]);
    }

    // =========================================================
    // Edit order (items live in the "pos_shopping" session cart while editing)
    // =========================================================

    public function order_edit($invoice_id)
    {
        $order = $this->findByInvoice($invoice_id, ['orderdetails.product.image', 'shipping', 'payment', 'status']);

        // Always start from what is saved, so a cart left over from another order never leaks in.
        $this->loadOrderIntoCart($order);

        return view('backEnd.order.edit', [
            'order'        => $order,
            'shippinginfo' => $order->shipping ?? new Shipping(),
            'products'     => Product::where('status', 1)->orderBy('name')->get(['id', 'name']),
            'cartinfo'     => Cart::instance(self::POS_CART)->content(),
            'districts'    => $this->districts(),
        ]);
    }

    public function order_update(Request $request)
    {
        $validated = $request->validate([
            'order_id'        => 'required|integer|exists:orders,id',
            'name'            => 'required|string|max:155',
            'phone'           => 'required|string|max:55',
            'address'         => 'required|string|max:256',
            'line_discount'   => 'nullable|array',
            'line_discount.*' => 'nullable|numeric|min:0',
        ]);

        $cart = Cart::instance(self::POS_CART)->content();
        if ($cart->isEmpty()) {
            Toastr::error('অর্ডারে অন্তত একটি পণ্য থাকতে হবে।', 'ত্রুটি');

            return redirect()->back();
        }

        // The discount boxes post their latest values; they win over the cart's copy.
        $postedDiscounts = $validated['line_discount'] ?? [];
        $lines = $cart->values()->map(function ($item) use ($postedDiscounts) {
            $key = $item->options->details_id ?? ('row_' . $item->rowId);
            $discount = array_key_exists($key, $postedDiscounts)
                ? (float) $postedDiscounts[$key]
                : (float) ($item->options->product_discount ?? 0);

            return [
                'product_id'       => (int) $item->id,
                'name'             => $item->name,
                'qty'              => (int) $item->qty,
                'price'            => (float) $item->price,
                'discount'         => max(0, min($discount, (float) $item->price)),
                'purchase_price'   => $item->options->purchase_price,
                'color_id'         => $item->options->color_id ?: null,
                'size_id'          => $item->options->size_id ?: null,
                'variant_price_id' => $item->options->variant_price_id ?: null,
            ];
        });

        try {
            $order = DB::transaction(function () use ($validated, $lines) {
                $order = Order::whereKey($validated['order_id'])->lockForUpdate()->firstOrFail();

                InventoryService::reconcileOrderStock(
                    $order,
                    $lines->map(fn ($l) => ['product_id' => $l['product_id'], 'qty' => $l['qty'], 'name' => $l['name']]),
                    (int) $order->order_status,
                    strict: true,
                    adminId: Auth::guard('admin')->id()
                );

                $vendors = Product::whereIn('id', $lines->pluck('product_id'))->pluck('vendor_id', 'id');

                OrderDetails::where('order_id', $order->id)->delete();
                foreach ($lines as $line) {
                    OrderDetails::create([
                        'order_id'         => $order->id,
                        'product_id'       => $line['product_id'],
                        'vendor_id'        => $vendors[$line['product_id']] ?? null,
                        'product_name'     => $line['name'],
                        'purchase_price'   => $line['purchase_price'],
                        'sale_price'       => $line['price'],
                        'product_discount' => $line['discount'],
                        'line_total'       => ($line['price'] - $line['discount']) * $line['qty'],
                        'qty'              => $line['qty'],
                        'product_color'    => $line['color_id'],
                        'product_size'     => $line['size_id'],
                        'variant_price_id' => $line['variant_price_id'],
                    ]);
                }

                $subtotal = $lines->sum(fn ($l) => $l['price'] * $l['qty']);
                $lineDiscount = $lines->sum(fn ($l) => $l['discount'] * $l['qty']);
                $couponDiscount = (float) Session::get('pos_discount', 0);
                $shipping = (float) $order->shipping_charge; // kept current by updateOrderShipping
                $amount = max(0, $subtotal + $shipping - $couponDiscount - $lineDiscount - (float) $order->reward_discount_amount);

                $order->forceFill([
                    'amount'     => (int) round($amount),
                    'discount'   => (int) round($couponDiscount + $lineDiscount),
                    'updated_by' => Auth::guard('admin')->id(),
                ])->save();

                Shipping::where('order_id', $order->id)->update([
                    'name'    => $validated['name'],
                    'phone'   => $validated['phone'],
                    'address' => $validated['address'],
                ]);

                // An unpaid payment row carries the amount due — keep it equal to the new total.
                Payment::where('order_id', $order->id)
                    ->where('payment_status', '!=', 'paid')
                    ->update(['amount' => $order->amount]);

                return $order;
            });
        } catch (InsufficientStockException $e) {
            Toastr::error($e->validationMessage(), 'স্টক নেই');

            return redirect()->back();
        }

        $this->clearPosCart();
        Toastr::success('অর্ডার আপডেট হয়েছে।', 'সফল');

        return redirect()->route('admin.order.process', $order->invoice_id);
    }

    // ---- session cart used by the edit page (and the POS page in phase 3) ----

    public function cart_add(Request $request): JsonResponse
    {
        $product = Product::with('image')->where('status', 1)->find((int) $request->id);
        if (!$product) {
            return response()->json(['status' => 'error', 'message' => 'Product not found'], 404);
        }

        Cart::instance(self::POS_CART)->add([
            'id'      => $product->id,
            'name'    => $product->name,
            'qty'     => 1,
            'price'   => $product->resolveSalePrice(1) ?: (float) $product->new_price,
            'options' => $this->cartOptions($product),
        ]);

        $this->refreshPosCoupon();

        return response()->json(['status' => 'success']);
    }

    public function cart_content(Request $request)
    {
        // Rows only: both pages bind their own click handlers once, so the response must not re-bind them.
        $view = $request->layout === 'edit' ? 'backEnd.order.cart_table_rows_edit' : 'backEnd.order.cart_table_rows';

        return view($view, ['cartinfo' => Cart::instance(self::POS_CART)->content()]);
    }

    public function cart_details(Request $request)
    {
        return view($request->layout === 'edit' ? 'backEnd.order.cart_details_edit' : 'backEnd.order.cart_details');
    }

    public function cart_increment(Request $request): JsonResponse
    {
        if ($item = $this->posRow($request->id)) {
            Cart::instance(self::POS_CART)->update($item->rowId, (int) $item->qty + 1);
            $this->repricePosRow($item->rowId);
        }

        $this->refreshPosCoupon();

        return response()->json(['status' => 'success']);
    }

    public function cart_decrement(Request $request): JsonResponse
    {
        if (($item = $this->posRow($request->id)) && (int) $item->qty > 1) {
            Cart::instance(self::POS_CART)->update($item->rowId, (int) $item->qty - 1);
            $this->repricePosRow($item->rowId);
        }

        $this->refreshPosCoupon();

        return response()->json(['status' => 'success']);
    }

    public function cart_remove(Request $request): JsonResponse
    {
        if ($item = $this->posRow($request->id)) {
            Cart::instance(self::POS_CART)->remove($item->rowId);
        }

        $this->refreshPosCoupon();

        return response()->json(['status' => 'success']);
    }

    /** Per-unit discount on one line. */
    public function product_discount(Request $request): JsonResponse
    {
        if ($item = $this->posRow($request->id)) {
            $discount = max(0, min((float) $request->discount, (float) $item->price));
            $options = $item->options->toArray();
            $options['product_discount'] = $discount;
            Cart::instance(self::POS_CART)->update($item->rowId, ['options' => $options]);
        }

        $this->refreshPosCoupon();

        return response()->json(['status' => 'success']);
    }

    /** Change a line's colour/size variant (price follows the variant). */
    public function cart_update(Request $request): JsonResponse
    {
        $item = $this->posRow($request->id);
        $product = $item ? Product::find($item->id) : null;
        if (!$item || !$product) {
            return response()->json(['status' => 'error', 'message' => 'Item not found'], 404);
        }

        $options = $item->options->toArray();
        if ($request->has('size_id')) {
            $options['size_id'] = $request->filled('size_id') ? (int) $request->size_id : null;
        }
        if ($request->has('color_id')) {
            $options['color_id'] = $request->filled('color_id') ? (int) $request->color_id : null;
        }
        $options = array_merge($options, $this->variantOptions($product, $options['color_id'] ?? null, $options['size_id'] ?? null));

        Cart::instance(self::POS_CART)->update($item->rowId, [
            'options' => $options,
            'price'   => $product->resolveSalePrice((int) $item->qty, $options['color_id'] ?? null, $options['size_id'] ?? null),
        ]);

        $this->refreshPosCoupon();

        return response()->json(['status' => 'success']);
    }

    /** Edit page "clear cart": discard unsaved changes (the page reloads the saved items). */
    public function cart_clear()
    {
        $this->clearPosCart();
        Toastr::success('অসংরক্ষিত পরিবর্তন বাতিল হয়েছে।', 'কার্ট');

        return redirect()->back();
    }

    // =========================================================
    // POS: create an order from the admin panel
    // =========================================================

    public function order_create()
    {
        // Items left over from the order editor must not turn up in a new sale.
        // options->get(): the cart's options object has no __isset, so empty()/isset() always say "missing".
        if (Cart::instance(self::POS_CART)->content()->contains(fn ($item) => (bool) $item->options->get('details_id'))) {
            $this->clearPosCart();
        }

        return view('backEnd.order.create', [
            'products'  => Product::with('image')
                ->where('status', 1)
                ->where('approval_status', 'approved')
                ->orderBy('name')
                ->get(['id', 'name', 'new_price', 'old_price', 'stock']),
            'divisions' => \App\Models\DeliveryDivision::active()->ordered()->get(['id', 'name']),
            'cartinfo'  => Cart::instance(self::POS_CART)->content(),
        ]);
    }

    public function order_store(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:155',
            'phone'       => 'required|string|max:55',
            'address'     => 'required|string|max:256',
            'division_id' => 'nullable|integer',
            'district_id' => 'required|integer|exists:districts,id',
            'thana_id'    => 'required|integer|exists:thanas,id',
        ]);

        if (!\App\Support\DeliveryLocation::validateDistrictThana((int) $validated['district_id'], (int) $validated['thana_id'])) {
            return back()->withInput()->withErrors(['thana_id' => 'The selected thana does not belong to this district.']);
        }

        $cart = Cart::instance(self::POS_CART)->content();
        if ($cart->isEmpty()) {
            Toastr::error('কার্টে কোনো পণ্য নেই।', 'ত্রুটি');

            return back()->withInput();
        }

        $this->refreshPosCoupon();
        $couponCode = Session::get('pos_coupon_code');
        $couponDiscount = $couponCode ? (float) Session::get('pos_discount', 0) : 0.0;
        $shipping = \App\Support\DeliveryLocation::chargeForThanaId((int) $validated['thana_id']);
        $subtotal = $cart->sum(fn ($item) => (float) $item->price * (int) $item->qty);
        $lineDiscount = $cart->sum(fn ($item) => (float) ($item->options->product_discount ?? 0) * (int) $item->qty);
        $amount = max(0, $subtotal + $shipping - $couponDiscount - $lineDiscount);
        $adminId = Auth::guard('admin')->id();

        try {
            $order = DB::transaction(function () use ($validated, $cart, $couponCode, $couponDiscount, $lineDiscount, $shipping, $amount, $adminId) {
                $customer = $this->posCustomer($validated['name'], $validated['phone']);

                $order = Order::create([
                    'invoice_id'      => $this->newInvoiceId(),
                    'amount'          => (int) round($amount),
                    'discount'        => (int) round($couponDiscount + $lineDiscount),
                    'shipping_charge' => (int) round($shipping),
                    'customer_id'     => $customer->id,
                    'order_status'    => 1,
                    'payment_status'  => 'pending',
                    'coupon_code'     => $couponCode,
                    'order_source'    => 'pos',
                    'traffic_source'  => 'direct',
                    'created_by'      => $adminId,
                    'user_id'         => $adminId,
                ]);

                $vendors = Product::whereIn('id', $cart->pluck('id'))->pluck('vendor_id', 'id');
                foreach ($cart as $item) {
                    $discount = (float) ($item->options->product_discount ?? 0);
                    OrderDetails::create([
                        'order_id'         => $order->id,
                        'product_id'       => (int) $item->id,
                        'vendor_id'        => $vendors[$item->id] ?? null,
                        'product_name'     => $item->name,
                        'purchase_price'   => $item->options->purchase_price,
                        'sale_price'       => (float) $item->price,
                        'product_discount' => $discount,
                        'line_total'       => ((float) $item->price - $discount) * (int) $item->qty,
                        'qty'              => (int) $item->qty,
                        'product_color'    => $item->options->color_id ?: null,
                        'product_size'     => $item->options->size_id ?: null,
                        'variant_price_id' => $item->options->variant_price_id ?: null,
                    ]);
                }

                Shipping::create([
                    'order_id'    => $order->id,
                    'customer_id' => $customer->id,
                    'name'        => $validated['name'],
                    'phone'       => $validated['phone'],
                    'address'     => $validated['address'],
                    'division_id' => \App\Support\DeliveryLocation::divisionIdForDistrict((int) $validated['district_id']),
                    'district_id' => (int) $validated['district_id'],
                    'thana_id'    => (int) $validated['thana_id'],
                    'area'        => \App\Support\DeliveryLocation::shippingLabel((int) $validated['district_id'], (int) $validated['thana_id']),
                ]);

                Payment::create([
                    'order_id'       => $order->id,
                    'customer_id'    => $customer->id,
                    'payment_method' => 'Cash On Delivery',
                    'amount'         => $order->amount,
                    'payment_status' => 'pending',
                ]);

                // Same rule as checkout: hold the stock now, fail the whole sale if it is not there.
                InventoryService::reserveForOrder($order, strict: true);

                return $order;
            });
        } catch (InsufficientStockException $e) {
            Toastr::error($e->validationMessage(), 'স্টক নেই');

            return back()->withInput();
        }

        $this->clearPosCart();
        Toastr::success('অর্ডার তৈরি হয়েছে — #' . $order->invoice_id, 'সফল');

        return redirect()->route('admin.order.invoice', $order->invoice_id);
    }

    /** Older POS screens set the delivery charge by thana here (the current one uses cart_thana_shipping). */
    public function cart_shipping(Request $request): JsonResponse
    {
        $thanaId = (int) ($request->thana_id ?? $request->id);
        $charge = $thanaId ? \App\Support\DeliveryLocation::chargeForThanaId($thanaId) : 0;
        Session::put('pos_shipping', $charge);

        return response()->json(['status' => 'success', 'shipping_charge' => $charge]);
    }

    public function posApplyCoupon(Request $request): JsonResponse
    {
        $result = CouponService::evaluate((string) $request->coupon_code, $this->posSubtotal());

        if ($result['error']) {
            return response()->json(['success' => false, 'message' => $result['error']]);
        }

        Session::put('pos_coupon_code', $result['coupon']->code);
        Session::put('pos_discount', $result['discount']);

        return response()->json([
            'success'  => true,
            'message'  => 'কুপন প্রয়োগ হয়েছে — ৳' . number_format($result['discount'], 2) . ' ছাড়',
            'discount' => $result['discount'],
        ]);
    }

    public function posRemoveCoupon(): JsonResponse
    {
        Session::forget(['pos_coupon_code', 'pos_discount']);

        return response()->json(['success' => true]);
    }

    // =========================================================
    // Courier booking (Steadfast, Pathao, RedX)
    // =========================================================

    /** Steadfast / RedX: send the ticked orders (GET ?order_ids[]=…&status=5). */
    public function bulk_courier(Request $request, $slug = null): JsonResponse
    {
        if (!in_array($slug, ['steadfast', 'redx'], true)) {
            return response()->json(['status' => 'error', 'message' => 'অজানা কুরিয়ার'], 404);
        }
        if (!CourierDispatchService::config($slug)) {
            return response()->json(['status' => 'error', 'message' => CourierDispatchService::label($slug) . ' API সেটিংস চালু/পূরণ করা নেই']);
        }

        return $this->dispatchToCourier($request, $this->orderIds($request), $slug);
    }

    /** Pathao: the dialog posts comma-separated order_ids plus store/city/zone/area. */
    public function order_pathao(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_ids'   => 'required|string',
            'pathaostore' => 'required|integer',
            'pathaocity'  => 'required|integer',
            'pathaozone'  => 'required|integer',
            'pathaoarea'  => 'required|integer',
        ]);

        if (!CourierDispatchService::config('pathao')) {
            return response()->json(['status' => 'error', 'message' => 'Pathao API সেটিংস চালু/পূরণ করা নেই']);
        }

        $ids = collect(explode(',', $validated['order_ids']))->map(fn ($id) => (int) trim($id))->filter()->unique()->values()->all();

        $response = $this->dispatchToCourier($request, $ids, 'pathao', [
            'store_id' => $validated['pathaostore'],
            'city_id'  => $validated['pathaocity'],
            'zone_id'  => $validated['pathaozone'],
            'area_id'  => $validated['pathaoarea'],
        ]);

        // The Pathao dialog reads the per-order lists from "result".
        $data = $response->getData(true);
        $data['result'] = ['success' => $data['success'] ?? [], 'failed' => $data['failed'] ?? []];

        return response()->json($data, $response->getStatusCode());
    }

    /** Zones of a Pathao city (the dialog's second dropdown). */
    public function pathaocity(Request $request): JsonResponse
    {
        $zones = (new PathaoService())->zones((int) $request->city_id);

        return $zones
            ? response()->json($zones)
            : response()->json(['message' => 'Pathao থেকে জোন লোড করা যায়নি'], 502);
    }

    /** Areas of a Pathao zone (the dialog's third dropdown). */
    public function pathaozone(Request $request): JsonResponse
    {
        $areas = (new PathaoService())->areas((int) $request->zone_id);

        return $areas
            ? response()->json($areas)
            : response()->json(['message' => 'Pathao থেকে এরিয়া লোড করা যায়নি'], 502);
    }

    public function redxAreas(Request $request): JsonResponse
    {
        $service = new RedXService();
        if (!$service->isConfigured()) {
            return response()->json(['message' => 'RedX API সেটিংস চালু/পূরণ করা নেই'], 422);
        }

        $areas = $service->getAreas($request->integer('post_code') ?: null, $request->district_name ?: null);

        return $areas ? response()->json($areas) : response()->json(['message' => 'RedX থেকে এরিয়া লোড করা যায়নি'], 502);
    }

    public function redxPickupStores(): JsonResponse
    {
        $service = new RedXService();
        if (!$service->isConfigured()) {
            return response()->json(['message' => 'RedX API সেটিংস চালু/পূরণ করা নেই'], 422);
        }

        $stores = $service->getPickupStores();

        return $stores ? response()->json($stores) : response()->json(['message' => 'RedX থেকে পিকআপ স্টোর লোড করা যায়নি'], 502);
    }

    /**
     * Book each order, then move the booked ones to the requested status (In Courier by default).
     * Answers with the lists the order page shows: success[] and failed[{order_id, message, status_code}].
     */
    private function dispatchToCourier(Request $request, array $ids, string $courier, array $pathao = []): JsonResponse
    {
        if (!$ids) {
            return response()->json(['status' => 'error', 'message' => 'Please Select An Order First !']);
        }

        $targetStatus = (int) $request->input('status', 5);
        if (!OrderStatus::whereKey($targetStatus)->exists()) {
            $targetStatus = 5;
        }

        $service = new CourierDispatchService();
        $success = [];
        $failed = [];

        foreach (Order::whereIn('id', $ids)->get() as $order) {
            $result = $service->send($order, $courier, $pathao);

            if (!$result['ok']) {
                $failed[] = [
                    'order_id'    => $order->invoice_id,
                    'message'     => $result['message'],
                    'status_code' => $result['status_code'] ?? null,
                ];
                continue;
            }

            try {
                $this->changeStatus($order, $targetStatus);
            } catch (\Throwable $e) {
                // The parcel is booked either way; only the status move failed.
                Log::warning('Courier booked but status change failed', ['order_id' => $order->id, 'error' => $e->getMessage()]);
            }

            $success[] = $order->invoice_id;
        }

        $name = CourierDispatchService::label($courier);

        if (!$success) {
            $first = $failed[0]['message'] ?? 'পাঠানো যায়নি';

            return response()->json([
                'status'  => 'error',
                'message' => count($failed) > 1 ? "{$name}: কোনো অর্ডার পাঠানো যায়নি — {$first}" : "{$name}: {$first}",
                'success' => [],
                'failed'  => $failed,
            ]);
        }

        return response()->json([
            'status'  => 'success',
            'message' => count($success) . " টি অর্ডার {$name} এ পাঠানো হয়েছে" . ($failed ? ', ' . count($failed) . ' টি ব্যর্থ' : ''),
            'success' => $success,
            'failed'  => $failed,
        ]);
    }

    // =========================================================
    // Fraud and duplicate-order checks
    // =========================================================

    /** Order-list "ফ্রড চেক" button: BD Courier history for a phone, saved onto that phone's orders. */
    public function fraudCheck(Request $request): JsonResponse
    {
        $mobile = trim((string) $request->mobile);
        if ($mobile === '') {
            return response()->json(['status' => 'error', 'message' => 'মোবাইল নম্বর নেই'], 422);
        }

        $check = BdCourierService::fetchCourierCheck($mobile, true);

        return $check['success']
            ? response()->json(['status' => 'success', 'data' => $check['payload']])
            : response()->json(['status' => 'error', 'message' => $check['message']]);
    }

    public function manualFraudCheckPage()
    {
        return view('backEnd.fraud.manual_check');
    }

    public function manualFraudCheck(Request $request)
    {
        $mobile = trim((string) $request->mobile);
        if ($mobile === '') {
            return back()->with('error', 'দয়া করে একটি মোবাইল নাম্বার লিখুন');
        }

        $check = BdCourierService::fetchCourierCheck($mobile, true);
        if (!$check['success']) {
            return back()->withInput()->with('error', $check['message']);
        }

        return view('backEnd.fraud.manual_check', [
            'mobile'  => $mobile,
            'data'    => $check['payload']['data'] ?? [],
            'reports' => $check['payload']['reports'] ?? [],
        ]);
    }

    /** JSON: open orders already placed with this phone (also flagged on those orders). */
    public function duplicateOrderCheck(Request $request): JsonResponse
    {
        $mobile = trim((string) $request->mobile);
        if (strlen(CourierDispatchService::phone($mobile)) < 10) {
            return response()->json(['status' => 'error', 'message' => 'সঠিক মোবাইল নম্বর দিন'], 422);
        }

        return response()->json(['status' => 'success', 'data' => $this->duplicateSummary($mobile, true)]);
    }

    public function manualDuplicateOrderCheckPage()
    {
        return view('backEnd.duplicate_order.manual_check');
    }

    public function manualDuplicateOrderCheck(Request $request)
    {
        $mobile = trim((string) $request->mobile);
        if (strlen(CourierDispatchService::phone($mobile)) < 10) {
            return back()->with('error', 'সঠিক মোবাইল নম্বর দিন');
        }

        return view('backEnd.duplicate_order.manual_check', [
            'mobile' => $mobile,
            'data'   => $this->duplicateSummary($mobile, true),
        ]);
    }

    /**
     * A "duplicate" is a second order from the same phone that is still open (pending, processing,
     * shipped, in courier or unpaid) — the usual sign of a double submit or a repeat fake order.
     */
    private function duplicateSummary(string $mobile, bool $flagOrders): array
    {
        $digits = CourierDispatchService::phone($mobile);
        $variants = array_values(array_unique([$mobile, $digits, '88' . $digits, '+88' . $digits]));

        $orders = Order::with('status')
            ->whereHas('shipping', fn ($q) => $q->whereIn('phone', $variants))
            ->latest('id')
            ->get(['id', 'invoice_id', 'amount', 'order_status', 'created_at']);

        $open = $orders->filter(fn ($o) => in_array((int) $o->order_status, InventoryService::RESERVE_STATUSES, true));
        $duplicates = max($open->count() - 1, 0);
        $rate = $orders->count() ? round($duplicates / $orders->count() * 100, 2) : 0;
        $last = $duplicates ? $open->first()->created_at : null;

        if ($flagOrders && $open->isNotEmpty()) {
            Order::whereIn('id', $open->pluck('id'))->update([
                'is_duplicate_order'        => $duplicates > 0 ? 1 : 0,
                'duplicate_order_count'     => $duplicates,
                'duplicate_order_rate'      => $rate,
                'last_duplicate_order_date' => $last,
            ]);
        }

        return [
            'is_duplicate'        => $duplicates > 0,
            'duplicate_count'     => $duplicates,
            'duplicate_rate'      => $rate,
            'last_duplicate_date' => $last?->format('d M Y, h:i A'),
            'details'             => [
                'total_orders' => $orders->count(),
                'open_orders'  => $open->map(fn ($o) => [
                    'invoice' => $o->invoice_id,
                    'amount'  => (float) $o->amount,
                    'status'  => $o->status->name ?? (string) $o->order_status,
                    'date'    => $o->created_at?->format('d M Y, h:i A'),
                ])->values()->all(),
            ],
        ];
    }

    // =========================================================
    // Old report links — the reports live in ReportController now
    // =========================================================

    public function stock_report(Request $request)
    {
        return redirect()->route('admin.reports.stock', $request->query());
    }

    public function order_report(Request $request)
    {
        return redirect()->route('admin.reports.orders', $request->query());
    }

    // =========================================================
    // Internals
    // =========================================================

    /**
     * Same transition as InlineOrderStatusController: inventory moves inside the transaction
     * (so a stock error rolls the change back), then the Order model hook handles reward
     * points and fund crediting.
     *
     * @throws InsufficientStockException
     */
    private function changeStatus(Order $order, int $targetStatus): void
    {
        DB::transaction(function () use ($order, $targetStatus) {
            $locked = Order::query()->lockForUpdate()->findOrFail($order->id);
            if ((int) $locked->order_status === $targetStatus) {
                return;
            }

            InventoryService::syncOrderStatus($locked, $targetStatus);

            $locked->forceFill([
                'order_status' => $targetStatus,
                'updated_by'   => Auth::guard('admin')->id(),
            ])->save();
        });
    }

    /** Delete an order and everything hanging off it, giving back held stock and spent points. */
    private function deleteOrder(Order $order): void
    {
        DB::transaction(function () use ($order) {
            InventoryService::releaseReservation($order);

            try {
                RewardPointService::handleCancellation($order);
            } catch (\Throwable $e) {
                Log::error('Reward point restore failed while deleting order ' . $order->id . ': ' . $e->getMessage());
            }

            OrderDetails::where('order_id', $order->id)->delete();
            Shipping::where('order_id', $order->id)->delete();
            Payment::where('order_id', $order->id)->delete();
            $order->delete();
        });
    }

    private function loadOrderIntoCart(Order $order): void
    {
        $this->clearPosCart();
        $cart = Cart::instance(self::POS_CART);
        $lineDiscountTotal = 0;

        foreach ($order->orderdetails as $detail) {
            if (!$detail->product_id) {
                continue; // kitten pack lines are kept out of the editor by PreventKittenPackOrderEdit
            }

            $product = $detail->product;
            $colorId = $detail->product_color ? (int) $detail->product_color : null;
            $sizeId = $detail->product_size ? (int) $detail->product_size : null;
            $discount = (float) ($detail->product_discount ?? 0);
            $lineDiscountTotal += $discount * (int) $detail->qty;

            $options = array_merge(
                $product ? $this->cartOptions($product) : ['image' => null, 'purchase_price' => $detail->purchase_price],
                $product ? $this->variantOptions($product, $colorId, $sizeId) : [],
                [
                    'details_id'       => $detail->id,
                    'product_discount' => $discount,
                    'purchase_price'   => $detail->purchase_price,
                    'color_id'         => $colorId,
                    'size_id'          => $sizeId,
                    'variant_price_id' => $detail->variant_price_id,
                ]
            );

            $cart->add([
                'id'      => $detail->product_id,
                'name'    => $detail->product_name,
                'qty'     => (int) $detail->qty,
                'price'   => (float) $detail->sale_price,
                'options' => $options,
            ]);
        }

        Session::put('pos_shipping', (float) $order->shipping_charge);
        // order.discount holds the coupon discount plus any per-line discounts; keep only the coupon part here.
        Session::put('pos_discount', max(0, (float) $order->discount - $lineDiscountTotal));
    }

    private function cartOptions(Product $product): array
    {
        return [
            'image'            => optional($product->image)->image,
            'purchase_price'   => $product->purchase_price,
            'product_discount' => 0,
            'details_id'       => null,
            'color_id'         => null,
            'size_id'          => null,
            'variant_price_id' => null,
        ];
    }

    private function variantOptions(Product $product, ?int $colorId, ?int $sizeId): array
    {
        $variant = null;
        if ($colorId || $sizeId) {
            $query = ProductVariantPrice::where('product_id', $product->id);
            if ($colorId && $sizeId) {
                $variant = (clone $query)->where('color_id', $colorId)->where('size_id', $sizeId)->first();
            } elseif ($colorId) {
                $variant = (clone $query)->where('color_id', $colorId)->whereNull('size_id')->first();
            } else {
                $variant = (clone $query)->where('size_id', $sizeId)->whereNull('color_id')->first();
            }
        }

        $color = $colorId ? Color::find($colorId) : null;
        $size = $sizeId ? Size::find($sizeId) : null;

        return [
            'variant_price_id'   => $variant->id ?? null,
            'product_color_name' => $color ? ($color->colorName ?? $color->color_name ?? null) : null,
            'product_size_name'  => $size ? ($size->sizeName ?? $size->size_name ?? null) : null,
        ];
    }

    /** Wholesale tiers depend on quantity — re-price after a quantity change. */
    private function repricePosRow(string $rowId): void
    {
        $item = Cart::instance(self::POS_CART)->get($rowId);
        $product = $item ? Product::find($item->id) : null;
        if (!$product || !$product->is_wholesale) {
            return;
        }

        $price = $product->resolveSalePrice((int) $item->qty, $item->options->color_id ?: null, $item->options->size_id ?: null);
        if ($price > 0) {
            Cart::instance(self::POS_CART)->update($rowId, ['price' => $price]);
        }
    }

    private function posRow($rowId)
    {
        if (!$rowId) {
            return null;
        }

        return Cart::instance(self::POS_CART)->content()->first(fn ($item) => $item->rowId === $rowId);
    }

    private function clearPosCart(): void
    {
        Cart::instance(self::POS_CART)->destroy();
        Session::forget(['pos_shipping', 'pos_discount', 'product_discount', 'pos_coupon_code']);
    }

    /** A POS coupon follows the cart: re-priced on every change, dropped if no longer valid. */
    private function refreshPosCoupon(): void
    {
        $code = Session::get('pos_coupon_code');
        if (!$code) {
            return;
        }

        $result = CouponService::evaluate((string) $code, $this->posSubtotal());
        if ($result['error']) {
            Session::forget(['pos_coupon_code', 'pos_discount']);
        } else {
            Session::put('pos_discount', $result['discount']);
        }
    }

    private function posSubtotal(): float
    {
        return (float) Cart::instance(self::POS_CART)->subtotal(2, '.', '');
    }

    /** Reuse the customer account for this phone number, or open one (same as checkout and incomplete orders). */
    private function posCustomer(string $name, string $phone): \App\Models\Customer
    {
        return \App\Models\Customer::firstOrCreate(
            ['phone' => $phone],
            [
                'name'     => $name,
                'slug'     => \Illuminate\Support\Str::slug($name) . '-' . random_int(1000, 9999),
                'password' => bcrypt(\Illuminate\Support\Str::random(16)),
                'verify'   => 1,
                'status'   => 'active',
            ]
        );
    }

    /** Same numbering as checkout (5 digits), but never a number that is already taken. */
    private function newInvoiceId(): string
    {
        do {
            $id = (string) random_int(11111, 99999);
        } while (Order::where('invoice_id', $id)->exists());

        return $id;
    }

    private function findByInvoice($invoiceId, array $with): Order
    {
        return Order::with($with)->where('invoice_id', $invoiceId)->firstOrFail();
    }

    /** @return int[] */
    private function orderIds(Request $request): array
    {
        return collect((array) $request->input('order_ids', []))
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function statuses()
    {
        return OrderStatus::orderBy('id')->get();
    }

    private function districts()
    {
        return DeliveryDistrict::active()->ordered()->get(['id', 'name', 'division_id', 'delivery_charge']);
    }

    /**
     * Staff who can be given orders. Admin accounts keep the column default role "customer"
     * (shop customers live in the separate customers table), so only vendors/resellers are excluded.
     */
    private function staffUsers()
    {
        return User::where('status', 1)
            ->where(fn ($q) => $q->whereNull('role')->orWhereNotIn('role', ['vendor', 'reseller']))
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    private function trafficSourceOptions(): array
    {
        $options = ['' => 'সব ট্র্যাফিক'];
        foreach (TrafficSourceDetector::ALLOWED as $key) {
            $options[$key] = ucfirst($key);
        }

        return $options;
    }
}
