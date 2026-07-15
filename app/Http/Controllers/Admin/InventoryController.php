<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\InventoryMovement;
use App\Models\InventoryStock;
use App\Models\Product;
use App\Models\PurchaseItem;
use App\Services\InventoryService;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    /** Movement types offered in filters / adjustment reasons. */
    public const ADJUST_REASONS = ['Opening Stock', 'Damaged', 'Lost', 'Correction', 'Returned', 'Other'];

    public function dashboard()
    {
        $totals = InventoryStock::selectRaw('
                COUNT(*)                            as products,
                COALESCE(SUM(on_hand), 0)           as on_hand,
                COALESCE(SUM(reserved), 0)          as reserved,
                COALESCE(SUM(on_hand - reserved), 0) as available
            ')->first();

        $lowStockCount = InventoryStock::whereRaw('(on_hand - reserved) > 0')
            ->whereRaw('(on_hand - reserved) <= low_stock_threshold')->count();
        $outOfStockCount = InventoryStock::whereRaw('(on_hand - reserved) <= 0')->count();

        $inventoryValue = (float) InventoryStock::join('products', 'products.id', '=', 'inventory_stocks.product_id')
            ->selectRaw('COALESCE(SUM(inventory_stocks.on_hand * COALESCE(products.purchase_price, 0)), 0) as v')
            ->value('v');

        $recentRestocks = InventoryStock::with('product:id,name,slug')
            ->whereNotNull('last_restocked_at')
            ->orderByDesc('last_restocked_at')
            ->take(5)->get();

        $recentMovements = InventoryMovement::with('product:id,name')
            ->latest('id')->take(10)->get();

        return view('backEnd.inventory.dashboard', compact(
            'totals', 'lowStockCount', 'outOfStockCount', 'inventoryValue', 'recentRestocks', 'recentMovements'
        ));
    }

    public function stock(Request $request)
    {
        $query = InventoryStock::with(['product' => fn ($q) => $q->with(['category:id,name', 'brand:id,name', 'weight:id,name'])])
            ->join('products', 'products.id', '=', 'inventory_stocks.product_id')
            ->select('inventory_stocks.*');

        if ($request->filled('keyword')) {
            $query->where('products.name', 'like', '%' . $request->keyword . '%');
        }
        if ($request->filled('category_id')) {
            $query->where('products.category_id', $request->category_id);
        }
        if ($request->filled('brand_id')) {
            $query->where('products.brand_id', $request->brand_id);
        }
        if ($request->status === 'low') {
            $query->whereRaw('(inventory_stocks.on_hand - inventory_stocks.reserved) > 0')
                  ->whereRaw('(inventory_stocks.on_hand - inventory_stocks.reserved) <= inventory_stocks.low_stock_threshold');
        } elseif ($request->status === 'out') {
            $query->whereRaw('(inventory_stocks.on_hand - inventory_stocks.reserved) <= 0');
        } elseif ($request->status === 'in') {
            $query->whereRaw('(inventory_stocks.on_hand - inventory_stocks.reserved) > inventory_stocks.low_stock_threshold');
        }

        $stocks = $query->orderBy('products.name')->paginate(20)->withQueryString();

        $categories = Category::orderBy('name')->get(['id', 'name']);
        $brands     = Brand::orderBy('name')->get(['id', 'name']);

        return view('backEnd.inventory.stock', compact('stocks', 'categories', 'brands'));
    }

    public function movements(Request $request)
    {
        $query = InventoryMovement::with(['product:id,name', 'creator:id,name'])->latest('id');

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }
        if ($request->filled('movement_type')) {
            $query->where('movement_type', $request->movement_type);
        }
        if ($request->filled('order_id')) {
            $query->where('order_id', $request->order_id);
        }
        if ($request->filled('purchase_id')) {
            $query->where('purchase_id', $request->purchase_id);
        }
        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        $movements = $query->paginate(30)->withQueryString();

        $products = Product::orderBy('name')->get(['id', 'name']);
        $types    = InventoryMovement::select('movement_type')->distinct()->pluck('movement_type');

        return view('backEnd.inventory.movements', compact('movements', 'products', 'types'));
    }

    public function lowStock()
    {
        $rows = InventoryStock::with('product:id,name,slug,purchase_price')
            ->whereRaw('(on_hand - reserved) <= low_stock_threshold')
            ->orderByRaw('(on_hand - reserved) asc')
            ->paginate(20);

        // Last purchase (date + supplier) per listed product, one query.
        $productIds = $rows->pluck('product_id')->all();
        $lastPurchases = PurchaseItem::with('purchase.supplier:id,name')
            ->whereIn('product_id', $productIds ?: [0])
            ->orderByDesc('id')->get()
            ->unique('product_id')->keyBy('product_id');

        return view('backEnd.inventory.low_stock', compact('rows', 'lastPurchases'));
    }

    public function adjust(Request $request)
    {
        $products = Product::orderBy('name')->get(['id', 'name']);
        $selected = null;
        if ($request->filled('product_id')) {
            $product = Product::find($request->product_id);
            if ($product) {
                InventoryService::seedProduct($product); // idempotent — guarantees the row exists
            }
            $selected = InventoryStock::with('product:id,name')
                ->where('product_id', $request->product_id)->first();
        }

        return view('backEnd.inventory.adjust', [
            'products' => $products,
            'selected' => $selected,
            'reasons'  => self::ADJUST_REASONS,
        ]);
    }

    public function adjustStore(Request $request)
    {
        $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'type'       => 'required|in:increase,decrease',
            'quantity'   => 'required|integer|min:1|max:1000000',
            'reason'     => 'required|in:' . implode(',', self::ADJUST_REASONS),
            'notes'      => 'nullable|string|max:1000',
        ]);

        try {
            $row = InventoryService::adjustStock(
                (int) $request->product_id,
                $request->type,
                (int) $request->quantity,
                $request->reason,
                $request->notes,
                Auth::guard('admin')->id()
            );
            Toastr::success("Stock adjusted. On hand is now {$row->on_hand} (available {$row->available}).", 'Success');
            return redirect()->route('admin.inventory.stock');
        } catch (\InvalidArgumentException $e) {
            Toastr::error($e->getMessage(), 'Failed');
            return back()->withInput();
        }
    }

    public function threshold(Request $request, $productId)
    {
        $request->validate(['low_stock_threshold' => 'required|integer|min:0|max:1000000']);
        InventoryService::setThreshold((int) $productId, (int) $request->low_stock_threshold);
        Toastr::success('Low stock threshold updated.', 'Success');
        return back();
    }

    public function reports(Request $request)
    {
        $from = $request->filled('from_date') ? $request->from_date : now()->startOfMonth()->toDateString();
        $to   = $request->filled('to_date') ? $request->to_date : now()->toDateString();

        $period = fn ($q) => $q->whereDate('created_at', '>=', $from)->whereDate('created_at', '<=', $to);

        $stockIn = (int) $period(InventoryMovement::query())
            ->whereIn('movement_type', [InventoryMovement::TYPE_PURCHASE_RECEIVED, InventoryMovement::TYPE_OPENING_STOCK, InventoryMovement::TYPE_RETURN_RESTOCK])
            ->where('on_hand_change', '>', 0)->sum('on_hand_change');

        $soldOut = (int) abs($period(InventoryMovement::query())
            ->where('movement_type', InventoryMovement::TYPE_SALE_COMPLETED)->sum('on_hand_change'));

        $adjustments = (int) $period(InventoryMovement::query())
            ->whereIn('movement_type', [
                InventoryMovement::TYPE_MANUAL_ADJUSTMENT, InventoryMovement::TYPE_CORRECTION,
                InventoryMovement::TYPE_DAMAGE, InventoryMovement::TYPE_LOST,
            ])->count();

        $byType = $period(InventoryMovement::query())
            ->selectRaw('movement_type, COUNT(*) as movements, SUM(on_hand_change) as on_hand_delta, SUM(reserved_change) as reserved_delta')
            ->groupBy('movement_type')->orderBy('movement_type')->get();

        // Valuation (current, not period-bound): on_hand × latest purchase cost.
        $valuation = InventoryStock::with('product:id,name,purchase_price,new_price')
            ->join('products', 'products.id', '=', 'inventory_stocks.product_id')
            ->select('inventory_stocks.*')
            ->orderByRaw('(inventory_stocks.on_hand * COALESCE(products.purchase_price,0)) desc')
            ->paginate(20)->withQueryString();

        $valuationTotal = (float) InventoryStock::join('products', 'products.id', '=', 'inventory_stocks.product_id')
            ->selectRaw('COALESCE(SUM(inventory_stocks.on_hand * COALESCE(products.purchase_price,0)),0) as v')->value('v');

        return view('backEnd.inventory.reports', compact(
            'from', 'to', 'stockIn', 'soldOut', 'adjustments', 'byType', 'valuation', 'valuationTotal'
        ));
    }
}
