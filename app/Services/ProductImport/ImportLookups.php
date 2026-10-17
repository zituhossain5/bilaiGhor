<?php

namespace App\Services\ProductImport;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Childcategory;
use App\Models\Color;
use App\Models\ProductFlavor;
use App\Models\ProductLifeStage;
use App\Models\ProductWeight;
use App\Models\Size;
use App\Models\Subcategory;
use Illuminate\Support\Str;

/**
 * The shop's categories and option lists, matched by name (case-insensitive), as the product
 * form offers them (active items only).
 */
class ImportLookups
{
    /** Lists that may be auto-created when missing: type => [model, name column, label]. */
    public const CREATABLE = [
        'brand' => [Brand::class, 'name', 'Brand'],
        'weight' => [ProductWeight::class, 'name', 'Weight'],
        'life_stage' => [ProductLifeStage::class, 'name', 'Life Stage'],
        'flavor' => [ProductFlavor::class, 'name', 'Flavor'],
        'color' => [Color::class, 'colorName', 'Color'],
        'size' => [Size::class, 'sizeName', 'Size'],
    ];

    /** @var array<string, array{id:int, name:string}> */
    public array $categories = [];

    /** @var array<int, array{id:int, name:string, category_id:int}> */
    public array $subcategories = [];

    /** @var array<int, array{id:int, name:string, subcategory_id:int}> */
    public array $childcategories = [];

    /** @var array<string, array<string, array{id:int, name:string}>> type => lower name => item */
    public array $lists = [];

    public static function load(): self
    {
        $lookups = new self();

        foreach (Category::where('status', 1)->orderBy('name')->get(['id', 'name']) as $category) {
            $lookups->categories[self::key($category->name)] ??= ['id' => $category->id, 'name' => $category->name];
        }
        $lookups->subcategories = Subcategory::orderBy('subcategoryName')->get(['id', 'subcategoryName', 'category_id'])
            ->map(fn ($s) => ['id' => $s->id, 'name' => $s->subcategoryName, 'category_id' => (int) $s->category_id])->all();
        $lookups->childcategories = Childcategory::orderBy('childcategoryName')->get(['id', 'childcategoryName', 'subcategory_id'])
            ->map(fn ($c) => ['id' => $c->id, 'name' => $c->childcategoryName, 'subcategory_id' => (int) $c->subcategory_id])->all();

        foreach (self::CREATABLE as $type => [$model, $column]) {
            $lookups->lists[$type] = [];
            $query = $model::query()->where('status', 1);
            if (in_array($type, ['weight', 'life_stage', 'flavor'], true)) {
                $query->orderBy('sort_order');
            }
            foreach ($query->orderBy($column)->get(['id', $column]) as $item) {
                $lookups->lists[$type][self::key($item->{$column})] ??= ['id' => $item->id, 'name' => $item->{$column}];
            }
        }

        return $lookups;
    }

    public static function key(?string $name): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/u', ' ', (string) $name)));
    }

    public function category(?string $name): ?array
    {
        return $this->categories[self::key($name)] ?? null;
    }

    public function subcategory(?string $name, ?int $categoryId): ?array
    {
        foreach ($this->subcategories as $sub) {
            if (self::key($sub['name']) === self::key($name) && $sub['category_id'] === $categoryId) {
                return $sub;
            }
        }

        return null;
    }

    public function childcategory(?string $name, ?int $subcategoryId): ?array
    {
        foreach ($this->childcategories as $child) {
            if (self::key($child['name']) === self::key($name) && $child['subcategory_id'] === $subcategoryId) {
                return $child;
            }
        }

        return null;
    }

    public function find(string $type, ?string $name): ?array
    {
        return $this->lists[$type][self::key($name)] ?? null;
    }

    /** Id of a list item, creating it (active) when missing. Used only when auto-create is allowed. */
    public static function findOrCreate(string $type, string $name): int
    {
        [$model, $column] = self::CREATABLE[$type];
        $name = trim(preg_replace('/\s+/u', ' ', $name));

        $existing = $model::query()->whereRaw("LOWER({$column}) = ?", [mb_strtolower($name)])->orderByDesc('status')->first();
        if ($existing) {
            return $existing->id;
        }

        $attributes = [$column => $name, 'status' => 1];
        if ($type === 'brand') {
            $slug = Str::slug($name) ?: 'brand';
            $attributes['slug'] = Brand::where('slug', $slug)->exists() ? $slug . '-' . Str::lower(Str::random(4)) : $slug;
        }
        if ($type === 'color') {
            $attributes['color'] = '#cccccc';
        }

        return $model::create($attributes)->id;
    }

    /** Values for the template's Lists sheet. */
    public function forTemplate(): array
    {
        $categoryNames = collect($this->categories)->pluck('name', 'id');
        $subNames = collect($this->subcategories)->pluck('name', 'id');

        return [
            'categories' => collect($this->categories)->pluck('name')->values()->all(),
            'subcategories' => collect($this->subcategories)->map(fn ($s) => ['name' => $s['name'], 'parent' => $categoryNames[$s['category_id']] ?? ''])->values()->all(),
            'childcategories' => collect($this->childcategories)->map(fn ($c) => ['name' => $c['name'], 'parent' => $subNames[$c['subcategory_id']] ?? ''])->values()->all(),
            'brands' => collect($this->lists['brand'])->pluck('name')->values()->all(),
            'weights' => collect($this->lists['weight'])->pluck('name')->values()->all(),
            'life_stages' => collect($this->lists['life_stage'])->pluck('name')->values()->all(),
            'flavors' => collect($this->lists['flavor'])->pluck('name')->values()->all(),
            'colors' => collect($this->lists['color'])->pluck('name')->values()->all(),
            'sizes' => collect($this->lists['size'])->pluck('name')->values()->all(),
        ];
    }
}
