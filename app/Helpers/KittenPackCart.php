<?php

namespace App\Helpers;

use App\Http\Controllers\Frontend\ShoppingController;
use App\Models\KittenPack;
use Cart;

/**
 * A kitten pack sits in the shopping cart as its own row ("pack-{id}", pack
 * name + pack price) with options.kitten_pack_id set — there is no product
 * behind it. Everything that must treat such a row differently asks here.
 */
class KittenPackCart
{
    public const ROW_PREFIX = 'pack-';

    public static function isPackRow($item): bool
    {
        return self::packId($item) > 0;
    }

    public static function packId($item): int
    {
        return (int) ($item->options->kitten_pack_id ?? 0);
    }

    public static function hasPackRows(): bool
    {
        return Cart::instance('shopping')->content()->contains(fn ($item) => self::isPackRow($item));
    }

    /**
     * The encoded ShoppingController only knows products, so it reports a pack-only
     * cart as "all free delivery". Packs are physical and always pay delivery.
     */
    public static function hasAllFreeDelivery(): bool
    {
        return !self::hasPackRows() && ShoppingController::hasAllFreeDeliveryProducts();
    }

    /** Where a cart row links to: the pack page for packs, the product page otherwise. */
    public static function itemUrl($item): string
    {
        if (self::isPackRow($item)) {
            return route('kitten.packs');
        }

        return !empty($item->options->slug) ? route('product', $item->options->slug) : '#';
    }

    /** The pack's row already in the cart, if any. */
    public static function findRow(KittenPack $pack)
    {
        return Cart::instance('shopping')->content()
            ->first(fn ($item) => self::packId($item) === (int) $pack->id);
    }

    /**
     * Put $qty packs in the cart. $replace = Buy Now (set the quantity), otherwise
     * Add to Cart (add to what is there) — the same semantics as FrontendController::cartStore.
     */
    public static function put(KittenPack $pack, int $qty, bool $replace)
    {
        $row = self::findRow($pack);

        if ($row) {
            Cart::instance('shopping')->update($row->rowId, [
                'qty'   => $replace ? $qty : (int) $row->qty + $qty,
                'price' => (float) $pack->price,
            ]);

            return Cart::instance('shopping')->get($row->rowId);
        }

        return Cart::instance('shopping')->add([
            'id'      => self::ROW_PREFIX . $pack->id,
            'name'    => $pack->name,
            'qty'     => $qty,
            'price'   => (float) $pack->price,
            'options' => [
                'kitten_pack_id'   => (int) $pack->id,
                'color_id'         => null,
                'size_id'          => null,
                'product_size'     => null,
                'product_color'    => null,
                'variant_price_id' => null,
                'image'            => $pack->image ? 'public/' . $pack->image : null,
                'slug'             => $pack->slug,
                'purchase_price'   => $pack->purchase_price,
                'is_wholesale'     => 0,
            ],
        ]);
    }
}
