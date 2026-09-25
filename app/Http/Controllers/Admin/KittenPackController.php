<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KittenPack;
use App\Models\KittenPackAddon;
use App\Models\OrderDetails;
use App\Models\Product;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class KittenPackController extends Controller
{
    /** Max upload size in kilobytes. Change here to adjust the limit everywhere. */
    private const MAX_IMAGE_KB = 2048;

    /** Allowed image extensions. */
    private const ALLOWED_MIMES = 'jpg,jpeg,png,webp';

    /** Upload folder, relative to public/. */
    private const UPLOAD_DIR = 'uploads/kitten-packs';

    /** Most units of one product a single pack may hold. */
    private const MAX_ITEM_QTY = 1000;

    public function index()
    {
        $packs = KittenPack::with('items')->ordered()->get();

        $addons = KittenPackAddon::with('product.image')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return view('backEnd.kitten-pack.index', compact('packs', 'addons'));
    }

    public function create()
    {
        return view('backEnd.kitten-pack.create', [
            'itemProducts' => $this->itemProductOptions(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validatePack($request, true);

        $image = $request->hasFile('image') ? $this->storeImage($request->file('image')) : null;

        DB::transaction(function () use ($request, $data, $image) {
            $pack = KittenPack::create([
                'name'       => $data['name'],
                'slug'       => $this->uniqueSlug($data['name']),
                'tier_label' => $data['tier_label'] ?? null,
                'badge'      => $data['badge'] ?? null,
                'theme'      => $data['theme'],
                'image'      => $image,
                'price'      => $data['price'],
                'old_price'  => $data['old_price'] ?? null,
                'status'     => $request->status ? 1 : 0,
                'sort_order' => (int) ($request->sort_order ?? 0),
            ]);

            $this->syncItems($pack, $request);
        });

        return redirect()->route('admin.kitten-pack.index')
            ->with('success', 'Kitten pack created successfully');
    }

    public function edit($id)
    {
        return view('backEnd.kitten-pack.edit', [
            'pack'         => KittenPack::with('items.product')->findOrFail($id),
            'itemProducts' => $this->itemProductOptions(),
        ]);
    }

    public function update(Request $request, $id)
    {
        $pack = KittenPack::findOrFail($id);
        $data = $this->validatePack($request, false);

        if ($request->hasFile('image')) {
            $this->deleteImage($pack->image);
            $pack->image = $this->storeImage($request->file('image'));
        }

        $pack->fill([
            'name'       => $data['name'],
            'tier_label' => $data['tier_label'] ?? null,
            'badge'      => $data['badge'] ?? null,
            'theme'      => $data['theme'],
            'price'      => $data['price'],
            'old_price'  => $data['old_price'] ?? null,
            'status'     => $request->status ? 1 : 0,
            'sort_order' => (int) ($request->sort_order ?? 0),
        ]);

        if ($pack->isDirty('name')) {
            $pack->slug = $this->uniqueSlug($data['name'], $pack->id);
        }

        DB::transaction(function () use ($pack, $request) {
            $pack->save();

            $this->syncItems($pack, $request);
        });

        return redirect()->route('admin.kitten-pack.index')
            ->with('success', 'Kitten pack updated successfully');
    }

    public function delete($id)
    {
        $pack = KittenPack::findOrFail($id);

        // Open orders still need this pack's items to complete (deduct) or cancel (restore).
        $hasOpenOrders = OrderDetails::where('kitten_pack_id', $pack->id)
            ->whereHas('order', fn ($q) => $q->whereIn('order_status', InventoryService::RESERVE_STATUSES))
            ->exists();

        if ($hasOpenOrders) {
            return back()->with('error', 'This pack has open orders. Deactivate it instead, and delete it once those orders are delivered or cancelled.');
        }

        $this->deleteImage($pack->image);
        $pack->delete(); // items cascade at the database level

        return back()->with('success', 'Kitten pack deleted successfully');
    }

    /** Replace the curated "Add to Your Kit" product list. */
    public function storeAddons(Request $request)
    {
        $request->validate([
            'product_ids'   => 'nullable|array',
            'product_ids.*' => 'integer|exists:products,id',
        ]);

        KittenPackAddon::query()->delete();

        foreach ((array) $request->product_ids as $index => $productId) {
            KittenPackAddon::create([
                'product_id' => $productId,
                'sort_order' => $index,
            ]);
        }

        return back()->with('success', 'Add-on products updated successfully');
    }

    private function validatePack(Request $request, bool $imageRequired): array
    {
        $data = $request->validate([
            'name'               => 'required|string|max:255',
            'tier_label'         => 'nullable|string|max:100',
            'badge'              => 'nullable|string|max:100',
            'theme'              => 'required|in:light,dark',
            'price'              => 'required|numeric|min:0',
            'old_price'          => 'nullable|numeric|min:0|gte:price',
            'sort_order'         => 'nullable|integer|min:0',
            'image'              => ($imageRequired ? 'required' : 'nullable') . '|image|mimes:' . self::ALLOWED_MIMES . '|max:' . self::MAX_IMAGE_KB,
            'items'              => 'nullable|array',
            'items.*.product_id' => 'nullable|integer|exists:products,id',
            // Only rows carried over from the old free-text list have a name and no product.
            'items.*.name'       => 'nullable|string|max:255|required_without:items.*.product_id',
            'items.*.quantity'   => 'required|integer|min:1|max:' . self::MAX_ITEM_QTY,
        ], [
            'old_price.gte'                  => 'The old price must be greater than or equal to the price.',
            'image.max'                      => 'The image must be ' . (self::MAX_IMAGE_KB / 1024) . 'MB or smaller.',
            'items.*.name.required_without'  => 'Every pack item needs a product.',
            'items.*.quantity.required'      => 'Every pack item needs a quantity.',
            'items.*.quantity.min'           => 'Item quantities must be at least 1.',
            'items.*.product_id.exists'      => 'One of the selected products no longer exists.',
        ]);

        // One row per product — its quantity is edited in place instead.
        $productIds = collect($request->items)->pluck('product_id')->filter();
        if ($productIds->count() !== $productIds->unique()->count()) {
            throw ValidationException::withMessages([
                'items' => 'Each product can only be added to a pack once — change its quantity instead.',
            ]);
        }

        return $data;
    }

    /**
     * Replace the pack's item list with what the form submitted. Replace (not diff):
     * the list is small, nothing references item rows, and stock history lives in the
     * inventory ledger. The product name is stored as a snapshot so the row stays
     * readable if its product is ever deleted.
     */
    private function syncItems(KittenPack $pack, Request $request): void
    {
        $pack->items()->delete();

        $names = Product::whereIn('id', collect($request->items)->pluck('product_id')->filter())->pluck('name', 'id');

        foreach (array_values((array) $request->items) as $index => $row) {
            $productId = !empty($row['product_id']) ? (int) $row['product_id'] : null;
            $name      = $productId ? ($names[$productId] ?? null) : ($row['name'] ?? null);

            if (!$productId && !$name) {
                continue;
            }

            $pack->items()->create([
                'product_id'  => $productId,
                'name'        => $name,
                'quantity'    => max(1, (int) ($row['quantity'] ?? 1)),
                'is_included' => !empty($row['is_included']),
                'sort_order'  => $index,
            ]);
        }
    }

    /** Every product, with its live available stock, for the Pack Items picker. */
    private function itemProductOptions()
    {
        $products  = Product::orderBy('name')->get(['id', 'name', 'status']);
        $available = InventoryService::availableMany($products->pluck('id')->all());

        return $products->each(fn ($product) => $product->setAttribute('available', $available[$product->id] ?? 0));
    }

    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'kitten-pack';
        $slug = $base;
        $i    = 2;

        while (KittenPack::where('slug', $slug)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists()) {
            $slug = $base . '-' . $i++;
        }

        return $slug;
    }

    private function storeImage($image): string
    {
        $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
        $uploadDir = public_path(self::UPLOAD_DIR);

        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $image->move($uploadDir, $imageName);

        return self::UPLOAD_DIR . '/' . $imageName;
    }

    private function deleteImage(?string $path): void
    {
        // Seeded packs point at the shared frontEnd artwork, which must survive.
        if ($path && Str::startsWith($path, self::UPLOAD_DIR) && file_exists(public_path($path))) {
            unlink(public_path($path));
        }
    }
}
