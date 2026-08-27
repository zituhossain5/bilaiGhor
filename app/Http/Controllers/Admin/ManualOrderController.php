<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\InsufficientStockException;
use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Customer;
use App\Models\GeneralSetting;
use App\Models\Order;
use App\Models\OrderDetails;
use App\Models\OrderStatus;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Shipping;
use App\Services\InventoryService;
use Brian2694\Toastr\Facades\Toastr;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ManualOrderController extends Controller
{
    private array $sources = ['facebook', 'whatsapp', 'phone', 'walk-in', 'manual', 'other'];
    private array $methods = ['bkash', 'nagad', 'rocket', 'cash', 'bank_transfer', 'other'];

    public function index(Request $request)
    {
        $orders = Order::with(['payment', 'shipping', 'status', 'creator'])
            ->where('is_manual_order', 1)
            ->when($request->filled('source'), fn ($q) => $q->where('order_source', $request->source))
            ->when($request->filled('payment_method'), fn ($q) => $q->where('payment_method', $request->payment_method))
            ->when($request->filled('payment_status'), fn ($q) => $q->where('payment_status', $request->payment_status))
            ->when($request->filled('order_status'), fn ($q) => $q->where('order_status', $request->order_status))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('created_at', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('created_at', '<=', $request->date_to))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $statuses = OrderStatus::orderBy('id')->get();

        return view('backEnd.manual_orders.index', [
            'orders' => $orders,
            'statuses' => $statuses,
            'sources' => $this->sources,
            'methods' => $this->methods,
        ]);
    }

    public function create()
    {
        $products = Product::with('weight')
            ->where('status', 1)
            ->orderBy('name')
            ->get(['id', 'name', 'new_price', 'old_price', 'stock', 'purchase_price', 'weight_id']);

        $customers = Customer::orderBy('name')->limit(300)->get(['id', 'name', 'phone', 'email', 'address']);

        return view('backEnd.manual_orders.create', [
            'products' => $products,
            'customers' => $customers,
            'sources' => $this->sources,
            'methods' => $this->methods,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'customer_name' => ['required', 'string', 'max:155'],
            'customer_phone' => ['required', 'string', 'max:55'],
            'customer_email' => ['nullable', 'email', 'max:155'],
            'customer_address' => ['required', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'order_source' => ['required', 'in:' . implode(',', $this->sources)],
            'payment_method' => ['required', 'in:' . implode(',', $this->methods)],
            'transaction_id' => ['nullable', 'string', 'max:100'],
            'delivery_charge' => ['nullable', 'numeric', 'min:0'],
            'order_discount' => ['nullable', 'numeric', 'min:0'],
            'paid_amount' => ['nullable', 'numeric', 'min:0'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['nullable', 'integer', 'exists:products,id'],
            'items.*.name' => ['required_without:items.*.product_id', 'nullable', 'string', 'max:255'],
            'items.*.variant' => ['nullable', 'string', 'max:155'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.discount' => ['nullable', 'numeric', 'min:0'],
        ]);

        try {
            $order = DB::transaction(function () use ($validated) {
                $items = $this->normalizeItems($validated['items']);
                $subtotal = collect($items)->sum('gross');
                $itemDiscount = collect($items)->sum('discount');
                $orderDiscount = min((float) ($validated['order_discount'] ?? 0), max(0, $subtotal - $itemDiscount));
                $delivery = (float) ($validated['delivery_charge'] ?? 0);
                $grandTotal = max(0, $subtotal - $itemDiscount - $orderDiscount + $delivery);
                $paid = min((float) ($validated['paid_amount'] ?? 0), $grandTotal);
                $due = max(0, $grandTotal - $paid);
                $paymentStatus = $this->paymentStatus($paid, $grandTotal);

                $stockLines = collect($items)
                    ->filter(fn ($item) => !empty($item['product_id']))
                    ->map(fn ($item) => [
                        'product_id' => (int) $item['product_id'],
                        'qty' => (int) $item['qty'],
                        'name' => $item['name'],
                    ]);

                InventoryService::assertAvailable($stockLines);

                $invoice = $this->nextInvoiceNumber();
                $customerId = $validated['customer_id'] ?? null;

                $order = Order::create([
                    'is_manual_order' => 1,
                    'invoice_id' => $invoice,
                    'invoice_number' => $invoice,
                    'amount' => (int) round($grandTotal),
                    'discount' => (int) round($itemDiscount + $orderDiscount),
                    'order_discount' => $orderDiscount,
                    'shipping_charge' => (int) round($delivery),
                    'customer_id' => $customerId,
                    'manual_customer_name' => $validated['customer_name'],
                    'manual_customer_phone' => $validated['customer_phone'],
                    'manual_customer_email' => $validated['customer_email'] ?? null,
                    'manual_customer_address' => $validated['customer_address'],
                    'order_status' => 1,
                    'order_source' => $validated['order_source'],
                    'payment_method' => $validated['payment_method'],
                    'transaction_id' => $validated['transaction_id'] ?? null,
                    'payment_status' => $paymentStatus,
                    'paid_amount' => $paid,
                    'due_amount' => $due,
                    'note' => $validated['notes'] ?? null,
                    'order_note' => $validated['notes'] ?? null,
                    'created_by' => Auth::guard('admin')->id(),
                    'public_token' => $this->uniquePublicToken(),
                ]);

                foreach ($items as $item) {
                    OrderDetails::create([
                        'order_id' => $order->id,
                        'product_id' => $item['product_id'],
                        'product_name' => $item['name'],
                        'manual_variant' => $item['variant'],
                        'is_manual_item' => empty($item['product_id']),
                        'purchase_price' => $item['purchase_price'],
                        'sale_price' => (int) round($item['unit_price']),
                        'product_discount' => (int) round($item['discount']),
                        'line_discount' => $item['discount'],
                        'line_total' => $item['line_total'],
                        'qty' => $item['qty'],
                    ]);
                }

                Shipping::create([
                    'order_id' => $order->id,
                    'customer_id' => $customerId,
                    'name' => $validated['customer_name'],
                    'phone' => $validated['customer_phone'],
                    'address' => $validated['customer_address'],
                    'area' => 'Manual Order',
                ]);

                Payment::create([
                    'order_id' => $order->id,
                    'customer_id' => $customerId,
                    'amount' => (int) round($paid),
                    'trx_id' => $validated['transaction_id'] ?? null,
                    'sender_number' => $validated['customer_phone'],
                    'payment_method' => $validated['payment_method'],
                    'payment_status' => $paymentStatus,
                ]);

                InventoryService::reserveForOrder($order, strict: true);

                return $order;
            });
        } catch (InsufficientStockException $e) {
            return back()
                ->withInput()
                ->withErrors(['stock' => $e->validationMessage()]);
        }

        Toastr::success('Manual order created successfully.', 'Success');
        return redirect()->route('admin.manual_orders.show', $order);
    }

    public function show(Order $order)
    {
        $this->ensureManual($order);

        return view('backEnd.manual_orders.invoice', $this->invoiceData($order, false));
    }

    public function print(Order $order)
    {
        $this->ensureManual($order);

        return view('backEnd.manual_orders.print', $this->invoiceData($order, true));
    }

    public function download(Order $order)
    {
        $this->ensureManual($order);

        $data = $this->invoiceData($order, false);
        $data['logoUrl'] = $this->localImageDataUri($data['generalsetting']?->dark_logo) ?? $data['logoUrl'];
        $data['qrUrl'] = $this->remoteImageDataUri($data['qrUrl']) ?? $data['qrUrl'];
        $data['facebookIconUrl'] = $this->localImageDataUri('public/frontEnd/images/facebook-f-brands.png') ?? $data['facebookIconUrl'];
        $data['whatsappIconUrl'] = $this->localImageDataUri('public/frontEnd/images/whatsapp-brands.png') ?? $data['whatsappIconUrl'];
        $data['receiptFontUrl'] = $this->localImageDataUri('public/frontEnd/fonts/Potro-Sans-Bangla-Regular.ttf') ?? $data['receiptFontUrl'];
        $data['receiptBoldFontUrl'] = $this->localImageDataUri('public/frontEnd/fonts/Potro-Sans-Bangla-Bold.ttf') ?? $data['receiptBoldFontUrl'];
        File::ensureDirectoryExists(storage_path('fonts'));

        $pdf = Pdf::loadView('backEnd.manual_orders.pdf', $data)
            ->setPaper('a4', 'portrait')
            ->setOption('isRemoteEnabled', true)
            ->setOption('defaultFont', 'DejaVu Sans');

        $invoice = $order->invoice_number ?: $order->invoice_id;

        return response()->streamDownload(
            fn () => print($pdf->output()),
            'Receipt-' . $invoice . '.pdf',
            ['Content-Type' => 'application/pdf']
        );
    }

    public function cancel(Order $order)
    {
        $this->ensureManual($order);

        if ((int) $order->order_status === 11) {
            Toastr::info('Manual order is already cancelled.', 'Info');
            return back();
        }

        $order->order_status = 11;
        $order->save();

        Toastr::success('Manual order cancelled and stock released safely.', 'Success');
        return back();
    }

    public function verify(string $token)
    {
        $order = Order::where('public_token', $token)
            ->where('is_manual_order', 1)
            ->firstOrFail();

        $generalsetting = GeneralSetting::where('status', 1)->first();

        return view('frontEnd.layouts.invoice_verify', compact('order', 'generalsetting'));
    }

    private function normalizeItems(array $rows): array
    {
        $items = [];

        foreach ($rows as $row) {
            $product = !empty($row['product_id'])
                ? Product::lockForUpdate()->find((int) $row['product_id'])
                : null;

            $qty = (int) $row['qty'];
            $unitPrice = (float) $row['unit_price'];
            $discount = min((float) ($row['discount'] ?? 0), $qty * $unitPrice);

            $items[] = [
                'product_id' => $product?->id,
                'name' => $product?->name ?: trim((string) $row['name']),
                'variant' => trim((string) ($row['variant'] ?? '')) ?: ($product?->weight?->name),
                'qty' => $qty,
                'unit_price' => $unitPrice,
                'purchase_price' => (int) round($product?->purchase_price ?? 0),
                'discount' => $discount,
                'gross' => $qty * $unitPrice,
                'line_total' => max(0, ($qty * $unitPrice) - $discount),
            ];
        }

        return $items;
    }

    private function paymentStatus(float $paid, float $grandTotal): string
    {
        if ($paid <= 0) {
            return 'unpaid';
        }

        if ($paid < $grandTotal) {
            return 'partial';
        }

        return 'paid';
    }

    private function nextInvoiceNumber(): string
    {
        $year = now()->format('Y');
        $prefix = "BG-{$year}-";
        $latest = Order::where('invoice_id', 'like', $prefix . '%')
            ->lockForUpdate()
            ->orderByDesc('id')
            ->value('invoice_id');

        $next = $latest && preg_match('/^BG-' . $year . '-(\d+)$/', $latest, $m)
            ? ((int) $m[1]) + 1
            : 1;

        return $prefix . str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }

    private function uniquePublicToken(): string
    {
        do {
            $token = Str::random(48);
        } while (Order::where('public_token', $token)->exists());

        return $token;
    }

    private function ensureManual(Order $order): void
    {
        abort_unless((bool) $order->is_manual_order, 404);
    }

    private function invoiceData(Order $order, bool $printMode): array
    {
        $order->load(['orderdetails.product', 'shipping', 'payment', 'status', 'creator']);
        $verifyUrl = route('manual.invoice.verify', $order->public_token);
        $generalsetting = GeneralSetting::where('status', 1)->first();

        return [
            'order' => $order,
            'generalsetting' => $generalsetting,
            'contact' => Contact::where('status', 1)->first(),
            'printMode' => $printMode,
            'verifyUrl' => $verifyUrl,
            'logoUrl' => $generalsetting?->dark_logo ? asset($generalsetting->dark_logo) : null,
            'qrUrl' => 'https://api.qrserver.com/v1/create-qr-code/?size=220x220&margin=2&data=' . urlencode($verifyUrl),
            'facebookIconUrl' => $this->localImageDataUri('public/frontEnd/images/facebook-f-brands.png')
                ?? asset('public/frontEnd/images/facebook-f-brands.png'),
            'whatsappIconUrl' => $this->localImageDataUri('public/frontEnd/images/whatsapp-brands.png')
                ?? asset('public/frontEnd/images/whatsapp-brands.png'),
            'receiptFontUrl' => asset('frontEnd/fonts/Potro-Sans-Bangla-Regular.ttf'),
            'receiptBoldFontUrl' => asset('frontEnd/fonts/Potro-Sans-Bangla-Bold.ttf'),
        ];
    }

    private function localImageDataUri(?string $path): ?string
    {
        if (!$path || filter_var($path, FILTER_VALIDATE_URL)) {
            return null;
        }

        $normalized = ltrim(str_replace('\\', '/', $path), '/');
        $publicRelative = preg_replace('#^public/#', '', $normalized);
        $candidates = array_unique([
            base_path($normalized),
            public_path($publicRelative),
        ]);

        foreach ($candidates as $candidate) {
            if (is_file($candidate) && is_readable($candidate)) {
                $mime = mime_content_type($candidate) ?: 'image/png';

                return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($candidate));
            }
        }

        return null;
    }

    private function remoteImageDataUri(string $url): ?string
    {
        try {
            $response = Http::timeout(10)->get($url);

            if (!$response->successful() || $response->body() === '') {
                return null;
            }

            $mime = $response->header('Content-Type') ?: 'image/png';

            return 'data:' . strtok($mime, ';') . ';base64,' . base64_encode($response->body());
        } catch (\Throwable) {
            return null;
        }
    }
}
