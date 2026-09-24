<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KittenPack;
use App\Models\KittenPackAddon;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class KittenPackController extends Controller
{
    /** Max upload size in kilobytes. Change here to adjust the limit everywhere. */
    private const MAX_IMAGE_KB = 2048;

    /** Allowed image extensions. */
    private const ALLOWED_MIMES = 'jpg,jpeg,png,webp';

    /** Upload folder, relative to public/. */
    private const UPLOAD_DIR = 'uploads/kitten-packs';

    public function index()
    {
        $packs = KittenPack::withCount('items')->ordered()->get();

        $addons = KittenPackAddon::with('product.image')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return view('backEnd.kitten-pack.index', compact('packs', 'addons'));
    }

    public function create()
    {
        return view('backEnd.kitten-pack.create', [
            'products' => $this->productOptions(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validatePack($request, true);

        $pack = KittenPack::create([
            'name'       => $data['name'],
            'slug'       => $this->uniqueSlug($data['name']),
            'tier_label' => $data['tier_label'] ?? null,
            'badge'      => $data['badge'] ?? null,
            'theme'      => $data['theme'],
            'image'      => $request->hasFile('image') ? $this->storeImage($request->file('image')) : null,
            'price'      => $data['price'],
            'old_price'  => $data['old_price'] ?? null,
            'product_id' => $data['product_id'] ?? null,
            'status'     => $request->status ? 1 : 0,
            'sort_order' => (int) ($request->sort_order ?? 0),
        ]);

        $this->syncItems($pack, $request);

        return redirect()->route('admin.kitten-pack.index')
            ->with('success', 'Kitten pack created successfully');
    }

    public function edit($id)
    {
        return view('backEnd.kitten-pack.edit', [
            'pack'     => KittenPack::with('items')->findOrFail($id),
            'products' => $this->productOptions(),
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
            'product_id' => $data['product_id'] ?? null,
            'status'     => $request->status ? 1 : 0,
            'sort_order' => (int) ($request->sort_order ?? 0),
        ]);

        if ($pack->isDirty('name')) {
            $pack->slug = $this->uniqueSlug($data['name'], $pack->id);
        }

        $pack->save();

        $this->syncItems($pack, $request);

        return redirect()->route('admin.kitten-pack.index')
            ->with('success', 'Kitten pack updated successfully');
    }

    public function delete($id)
    {
        $pack = KittenPack::findOrFail($id);

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
        return $request->validate([
            'name'              => 'required|string|max:255',
            'tier_label'        => 'nullable|string|max:100',
            'badge'             => 'nullable|string|max:100',
            'theme'             => 'required|in:light,dark',
            'price'             => 'required|numeric|min:0',
            'old_price'         => 'nullable|numeric|min:0|gte:price',
            'product_id'        => 'nullable|integer|exists:products,id',
            'sort_order'        => 'nullable|integer|min:0',
            'image'             => ($imageRequired ? 'required' : 'nullable') . '|image|mimes:' . self::ALLOWED_MIMES . '|max:' . self::MAX_IMAGE_KB,
            'items'             => 'nullable|array',
            'items.*.name'      => 'nullable|string|max:255',
            'items.*.quantity'  => 'nullable|integer|min:1',
        ], [
            'old_price.gte' => 'The old price must be greater than or equal to the price.',
            'image.max'     => 'The image must be ' . (self::MAX_IMAGE_KB / 1024) . 'MB or smaller.',
        ]);
    }

    /** Rewrite the pack's contents list from the repeating rows on the form. */
    private function syncItems(KittenPack $pack, Request $request): void
    {
        $pack->items()->delete();

        foreach ((array) $request->items as $index => $row) {
            if (empty($row['name'])) {
                continue; // blank row left by the "add row" button
            }

            $pack->items()->create([
                'name'        => $row['name'],
                'quantity'    => max(1, (int) ($row['quantity'] ?? 1)),
                'is_included' => !empty($row['is_included']),
                'sort_order'  => $index,
            ]);
        }
    }

    private function productOptions()
    {
        return Product::where('status', 1)
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);
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
