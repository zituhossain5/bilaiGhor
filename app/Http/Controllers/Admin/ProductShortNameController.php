<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Support\ShortName;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Short product names: the "Auto-generate" suggestion on the product form, and the bulk tool
 * that fills them in for products that don't have one yet.
 */
class ProductShortNameController extends Controller
{
    /** Rows shown per batch in the bulk tool. */
    private const BATCH = 100;

    public function __construct()
    {
        $this->middleware('permission:product-create|product-edit', ['only' => ['suggest']]);
        $this->middleware('permission:product-edit', ['only' => ['index', 'store']]);
    }

    /** JSON: a suggested short name for the name typed on the product form. */
    public function suggest(Request $request): JsonResponse
    {
        $request->validate(['name' => 'nullable|string|max:500']);

        return response()->json(['short_name' => ShortName::suggest($request->input('name'))]);
    }

    /** Preview table: full name → suggested short name (editable) for products without one. */
    public function index()
    {
        $query = Product::where(fn ($q) => $q->whereNull('short_name')->orWhere('short_name', ''));
        $remaining = (clone $query)->count();

        $rows = $query->orderBy('name')
            ->limit(self::BATCH)
            ->get(['id', 'name', 'product_code', 'status'])
            ->map(fn (Product $product) => [
                'id' => $product->id,
                'name' => $product->name,
                'code' => $product->product_code,
                'active' => (int) $product->status === 1,
                'suggestion' => ShortName::suggest($product->name),
            ]);

        return view('backEnd.product.short_names', [
            'rows' => $rows,
            'remaining' => $remaining,
            'max' => ShortName::max(),
        ]);
    }

    /** Saves the ticked rows. A product that got a short name in the meantime is left alone. */
    public function store(Request $request)
    {
        $max = ShortName::max();
        $validated = $request->validate([
            'apply' => 'required|array|min:1',
            'apply.*' => 'integer',
            'names' => 'required|array',
            'names.*' => 'nullable|string|max:' . $max,
        ], [
            'apply.required' => 'Tick at least one product to save.',
            'names.*.max' => "A short name can be at most {$max} characters.",
        ]);

        $names = collect($validated['names'])
            ->only(array_map('intval', $validated['apply']))
            ->map(fn ($name) => trim((string) $name))
            ->filter(fn ($name) => $name !== '');

        $saved = DB::transaction(function () use ($names) {
            $saved = 0;
            foreach ($names as $id => $name) {
                $saved += Product::whereKey($id)
                    ->where(fn ($q) => $q->whereNull('short_name')->orWhere('short_name', ''))
                    ->update(['short_name' => $name]);
            }

            return $saved;
        });

        Toastr::success("Saved short names for {$saved} product(s).", 'Success');

        return redirect()->route('products.short_names');
    }
}
