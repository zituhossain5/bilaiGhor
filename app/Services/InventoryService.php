<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Models\InventoryMovement;
use App\Models\InventoryStock;
use App\Models\Order;
use App\Models\OrderDetails;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Central inventory business logic — every stock change goes through here,
 * inside a transaction, with the inventory row locked, and leaves a movement.
 *
 * Model: on_hand (physical units) / reserved (committed to active orders).
 * Available = on_hand - reserved, and products.stock is kept in sync as a
 * read-only cache of available (clamped at 0) because the whole legacy
 * frontend/cart/API reads products.stock for availability.
 */
class InventoryService
{
    /** Order statuses that hold a reservation: Pending, Processing, On The Way, In Courier, Unpaid. */
    public const RESERVE_STATUSES = [1, 2, 3, 5, 8];
    /** Final fulfilment status — converts the reservation into a physical deduction. */
    public const COMPLETE_STATUS = 6;
    /** Cancellation — releases the reservation (or restocks after a completed sale). */
    public const CANCEL_STATUS = 11;

    public const DEFAULT_LOW_STOCK_THRESHOLD = 5;

    // =========================================================
    // Row access
    // =========================================================

    /**
     * Fetch (and lazily seed) the inventory row for a product.
     * Call inside a transaction; $lock adds FOR UPDATE.
     *
     * Seeding self-heals legacy data: products.stock has "deduct at placement"
     * semantics (i.e. it is the AVAILABLE quantity), so
     *   on_hand = products.stock + qty reserved by active orders.
     */
    public static function stockRow(int $productId, bool $lock = true, ?int $excludeOrderId = null): ?InventoryStock
    {
        $query = InventoryStock::where('product_id', $productId);
        $row   = ($lock ? $query->lockForUpdate() : $query)->first();
        if ($row) {
            return $row;
        }

        $product = Product::find($productId);
        if (!$product) {
            return null;
        }

        return self::seedProduct($product, $excludeOrderId);
    }

    /** Create the inventory row from legacy products.stock + active order reservations. */
    public static function seedProduct(Product $product, ?int $excludeOrderId = null, string $reason = 'Inventory system migration'): InventoryStock
    {
        return DB::transaction(function () use ($product, $excludeOrderId, $reason) {
            $existing = InventoryStock::where('product_id', $product->id)->lockForUpdate()->first();
            if ($existing) {
                return $existing;
            }

            $reservedLines = self::activeOrderLines($product->id, $excludeOrderId);
            $reservedQty   = (int) $reservedLines->sum('qty');
            $onHand        = max(0, (int) $product->stock) + $reservedQty;

            $row = InventoryStock::create([
                'product_id'          => $product->id,
                'on_hand'             => $onHand,
                'reserved'            => $reservedQty,
                'low_stock_threshold' => self::DEFAULT_LOW_STOCK_THRESHOLD,
            ]);

            if ($onHand > 0) {
                self::recordMovement($row, InventoryMovement::TYPE_OPENING_STOCK, $onHand, [
                    'on_hand_change'    => $onHand,
                    'previous_on_hand'  => 0,
                    'previous_reserved' => 0,
                    'reason'            => $reason,
                ]);
            }

            // Backfill one reservation movement per active order line so future
            // status changes (cancel / complete) settle those orders correctly.
            foreach ($reservedLines as $line) {
                if ((int) $line->qty <= 0) {
                    continue;
                }
                InventoryMovement::create([
                    'product_id'        => $product->id,
                    'movement_type'     => InventoryMovement::TYPE_ORDER_RESERVED,
                    'quantity'          => (int) $line->qty,
                    'reserved_change'   => (int) $line->qty,
                    'order_id'          => (int) $line->order_id,
                    'reference_type'    => 'order',
                    'reference_id'      => (int) $line->order_id,
                    'reason'            => 'Inventory system migration (active order backfill)',
                ]);
            }

            self::syncProductCache($row);

            return $row;
        });
    }

    /** Active (reservation-holding) order lines for a product. */
    private static function activeOrderLines(int $productId, ?int $excludeOrderId = null)
    {
        return DB::table('order_details')
            ->join('orders', 'orders.id', '=', 'order_details.order_id')
            ->whereIn('orders.order_status', self::RESERVE_STATUSES)
            ->where('order_details.product_id', $productId)
            ->when($excludeOrderId, fn ($q) => $q->where('orders.id', '!=', $excludeOrderId))
            ->selectRaw('order_details.order_id, SUM(order_details.qty) as qty')
            ->groupBy('order_details.order_id')
            ->get();
    }

    public static function available(int $productId): int
    {
        $row = InventoryStock::where('product_id', $productId)->first();
        if ($row) {
            return $row->available;
        }
        // Untracked product: legacy field still is the availability.
        return (int) (Product::find($productId)->stock ?? 0);
    }

    // =========================================================
    // Checkout / order operations
    // =========================================================

    /**
     * Authoritative availability check at order placement.
     * Locks the inventory rows — keep inside the same transaction as the
     * subsequent reserveForOrder() so no other request can take the units.
     *
     * @param iterable $lines each: ['product_id' => int, 'qty' => int, 'name' => string]
     * @throws InsufficientStockException
     */
    public static function assertAvailable(iterable $lines): void
    {
        $wanted = [];
        $names  = [];
        foreach ($lines as $line) {
            $pid = (int) $line['product_id'];
            $wanted[$pid] = ($wanted[$pid] ?? 0) + (int) $line['qty'];
            $names[$pid]  = $line['name'] ?? ('#' . $pid);
        }

        foreach ($wanted as $pid => $qty) {
            $row = self::stockRow($pid, lock: true);
            $available = $row ? $row->available : 0;
            if ($qty > $available) {
                throw new InsufficientStockException($pid, $names[$pid], $available, $qty);
            }
        }
    }

    /**
     * Reserve stock for every line of an order. Idempotent: lines already
     * reserved (or already sold) are skipped, so repeated processing of the
     * same order can never double-reserve.
     */
    public static function reserveForOrder(Order $order, bool $strict = false): void
    {
        DB::transaction(function () use ($order, $strict) {
            $details = OrderDetails::where('order_id', $order->id)->get();

            foreach ($details as $detail) {
                if (!$detail->product_id || (int) $detail->qty <= 0) {
                    continue;
                }

                $row = self::stockRow((int) $detail->product_id, lock: true, excludeOrderId: (int) $order->id);
                if (!$row) {
                    continue; // product deleted
                }

                [$netReserved, $netSold] = self::orderLineNet((int) $order->id, (int) $detail->product_id);
                $need = (int) $detail->qty - $netReserved - $netSold;
                if ($need <= 0) {
                    continue; // already reserved / sold — idempotent
                }

                if ($strict && $need > $row->available) {
                    throw new InsufficientStockException(
                        (int) $detail->product_id,
                        $detail->product_name ?? ('#' . $detail->product_id),
                        $row->available,
                        $need
                    );
                }

                self::apply($row, InventoryMovement::TYPE_ORDER_RESERVED, $need, onHandChange: 0, reservedChange: $need, context: [
                    'order_id'       => $order->id,
                    'reference_type' => 'order',
                    'reference_id'   => $order->id,
                ]);
            }
        });
    }

    /**
     * Release whatever this order still holds. For active orders that means the
     * reservation; for completed sales it restocks the sold units. Idempotent.
     */
    public static function releaseReservation(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $details = OrderDetails::where('order_id', $order->id)->get();

            foreach ($details as $detail) {
                if (!$detail->product_id) {
                    continue;
                }
                $row = self::stockRow((int) $detail->product_id, lock: true, excludeOrderId: (int) $order->id);
                if (!$row) {
                    continue;
                }

                [$netReserved, $netSold] = self::orderLineNet((int) $order->id, (int) $detail->product_id);

                if ($netReserved > 0) {
                    self::apply($row, InventoryMovement::TYPE_RESERVATION_RELEASED, $netReserved, 0, -$netReserved, [
                        'order_id'       => $order->id,
                        'reference_type' => 'order',
                        'reference_id'   => $order->id,
                    ]);
                }

                if ($netSold > 0) {
                    // Sale had already been finalised — cancelling brings goods back.
                    self::apply($row, InventoryMovement::TYPE_RETURN_RESTOCK, $netSold, $netSold, 0, [
                        'order_id'       => $order->id,
                        'reference_type' => 'order',
                        'reference_id'   => $order->id,
                        'reason'         => 'Order cancelled after completion',
                    ]);
                }
            }
        });
    }

    /**
     * Final fulfilment: convert the reservation into a physical deduction.
     * Idempotent — a line whose units are already sold is never deducted again.
     */
    public static function completeSale(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $details = OrderDetails::where('order_id', $order->id)->get();

            foreach ($details as $detail) {
                if (!$detail->product_id || (int) $detail->qty <= 0) {
                    continue;
                }
                $row = self::stockRow((int) $detail->product_id, lock: true, excludeOrderId: (int) $order->id);
                if (!$row) {
                    continue;
                }

                [$netReserved, $netSold] = self::orderLineNet((int) $order->id, (int) $detail->product_id);
                $remaining = (int) $detail->qty - $netSold;
                if ($remaining <= 0) {
                    continue; // already deducted — idempotent
                }

                $reserveToConvert = min($netReserved, $remaining);

                self::apply($row, InventoryMovement::TYPE_SALE_COMPLETED, $remaining, -$remaining, -$reserveToConvert, [
                    'order_id'       => $order->id,
                    'reference_type' => 'order',
                    'reference_id'   => $order->id,
                ]);
            }
        });
    }

    /**
     * Single entry point for order-status transitions (admin panel, courier
     * webhooks, manual updates). Unknown statuses are a safe no-op.
     */
    public static function syncOrderStatus(Order $order, int $newStatus): void
    {
        if ($newStatus === self::COMPLETE_STATUS) {
            self::completeSale($order);
        } elseif ($newStatus === self::CANCEL_STATUS) {
            self::releaseReservation($order);
        } elseif (in_array($newStatus, self::RESERVE_STATUSES, true)) {
            // e.g. a cancelled order re-opened — take the reservation again.
            self::reserveForOrder($order, strict: false);
        }
        // Any other/new status: leave inventory untouched.
    }

    /**
     * Net effect this order currently holds on a product, derived from the
     * ledger: [reserved units, sold-and-not-restocked units].
     */
    private static function orderLineNet(int $orderId, int $productId): array
    {
        $rows = InventoryMovement::where('order_id', $orderId)
            ->where('product_id', $productId)
            ->get(['movement_type', 'on_hand_change', 'reserved_change']);

        $netReserved = (int) $rows->sum('reserved_change');

        $netSold = -1 * (int) $rows
            ->whereIn('movement_type', [InventoryMovement::TYPE_SALE_COMPLETED, InventoryMovement::TYPE_RETURN_RESTOCK])
            ->sum('on_hand_change');

        return [max(0, $netReserved), max(0, $netSold)];
    }

    // =========================================================
    // Purchases
    // =========================================================

    /** Stock-in for a purchase. Idempotent per (purchase, product). */
    public static function receivePurchase(Purchase $purchase): void
    {
        DB::transaction(function () use ($purchase) {
            foreach ($purchase->items()->get() as $item) {
                if (!$item->product_id || (int) $item->qty <= 0) {
                    continue;
                }

                $row = self::stockRow((int) $item->product_id, lock: true);
                if (!$row) {
                    continue;
                }

                $already = InventoryMovement::where('purchase_id', $purchase->id)
                    ->where('product_id', $item->product_id)
                    ->where('movement_type', InventoryMovement::TYPE_PURCHASE_RECEIVED)
                    ->exists();
                if ($already) {
                    continue; // received before — never double-in
                }

                self::apply($row, InventoryMovement::TYPE_PURCHASE_RECEIVED, (int) $item->qty, (int) $item->qty, 0, [
                    'purchase_id'    => $purchase->id,
                    'reference_type' => 'purchase',
                    'reference_id'   => $purchase->id,
                    'reason'         => 'Purchase #' . ($purchase->invoice_no ?? $purchase->id) . ' received',
                ]);

                $row->last_restocked_at = now();
                $row->save();
            }
        });
    }

    /** Stock-out for a purchase return (goods sent back to the supplier). */
    public static function returnPurchaseItem(PurchaseItem $item, int $qty, ?int $adminId = null): void
    {
        if ($qty <= 0) {
            return;
        }
        DB::transaction(function () use ($item, $qty, $adminId) {
            $row = self::stockRow((int) $item->product_id, lock: true);
            if (!$row) {
                return;
            }
            $out = min($qty, max(0, $row->on_hand)); // physical floor: cannot return what is not on the shelf
            if ($out <= 0) {
                return;
            }
            self::apply($row, InventoryMovement::TYPE_PURCHASE_RETURNED, $out, -$out, 0, [
                'purchase_id'    => $item->purchase_id,
                'reference_type' => 'purchase_item',
                'reference_id'   => $item->id,
                'reason'         => 'Purchase return to supplier',
                'created_by'     => $adminId,
                'notes'          => $out < $qty ? "Requested {$qty}, only {$out} on hand" : null,
            ]);
        });
    }

    /** Reverse the stock-in of a purchase that is being deleted. */
    public static function reversePurchase(Purchase $purchase, ?int $adminId = null): void
    {
        DB::transaction(function () use ($purchase, $adminId) {
            foreach ($purchase->items()->get() as $item) {
                $net = (int) $item->qty - (int) ($item->returned_qty ?? 0);
                if (!$item->product_id || $net <= 0) {
                    continue;
                }
                $row = self::stockRow((int) $item->product_id, lock: true);
                if (!$row) {
                    continue;
                }
                $out = min($net, max(0, $row->on_hand));
                if ($out <= 0) {
                    continue;
                }
                self::apply($row, InventoryMovement::TYPE_CORRECTION, $out, -$out, 0, [
                    'purchase_id'    => $purchase->id,
                    'reference_type' => 'purchase',
                    'reference_id'   => $purchase->id,
                    'reason'         => 'Purchase #' . ($purchase->invoice_no ?? $purchase->id) . ' deleted',
                    'created_by'     => $adminId,
                ]);
            }
        });
    }

    // =========================================================
    // Manual adjustment / product edit
    // =========================================================

    /**
     * Manual stock adjustment from the admin panel.
     *
     * @param string $type   'increase' | 'decrease'
     * @param string $reason one of the admin reasons (Damaged, Lost, Correction, ...)
     * @throws \InvalidArgumentException
     */
    public static function adjustStock(int $productId, string $type, int $qty, string $reason, ?string $notes, ?int $adminId): InventoryStock
    {
        if ($qty <= 0) {
            throw new \InvalidArgumentException('Adjustment quantity must be greater than zero.');
        }
        if (!in_array($type, ['increase', 'decrease'], true)) {
            throw new \InvalidArgumentException('Invalid adjustment type.');
        }

        return DB::transaction(function () use ($productId, $type, $qty, $reason, $notes, $adminId) {
            $row = self::stockRow($productId, lock: true);
            if (!$row) {
                throw new \InvalidArgumentException('Product not found.');
            }

            $delta = $type === 'increase' ? $qty : -$qty;
            if ($row->on_hand + $delta < 0) {
                throw new \InvalidArgumentException(
                    "Cannot decrease by {$qty}: only {$row->on_hand} on hand."
                );
            }

            $movementType = match (strtolower($reason)) {
                'damaged', 'damage'       => InventoryMovement::TYPE_DAMAGE,
                'lost'                    => InventoryMovement::TYPE_LOST,
                'correction'              => InventoryMovement::TYPE_CORRECTION,
                'opening stock'           => InventoryMovement::TYPE_OPENING_STOCK,
                'returned'                => InventoryMovement::TYPE_RETURN_RESTOCK,
                default                   => InventoryMovement::TYPE_MANUAL_ADJUSTMENT,
            };

            self::apply($row, $movementType, $qty, $delta, 0, [
                'reason'     => $reason,
                'notes'      => $notes,
                'created_by' => $adminId,
                'reference_type' => 'manual',
            ]);

            if ($delta > 0) {
                $row->last_restocked_at = now();
                $row->save();
            }

            return $row->refresh();
        });
    }

    /**
     * Admin edited the product's stock field directly. The input means
     * "available" (that is what the field always showed), so on_hand is moved
     * to reserved + target, and the change is logged as a correction.
     */
    public static function applyProductStockEdit(Product $product, int $targetAvailable, ?int $adminId = null): void
    {
        DB::transaction(function () use ($product, $targetAvailable, $adminId) {
            $row = self::stockRow($product->id, lock: true);
            if (!$row) {
                return;
            }
            $targetOnHand = max(0, $row->reserved + max(0, $targetAvailable));
            $delta        = $targetOnHand - $row->on_hand;
            if ($delta === 0) {
                self::syncProductCache($row);
                return;
            }
            self::apply($row, InventoryMovement::TYPE_CORRECTION, abs($delta), $delta, 0, [
                'reason'     => 'Product edit: stock set to ' . $targetAvailable,
                'created_by' => $adminId,
                'reference_type' => 'product_edit',
                'reference_id'   => $product->id,
            ]);
        });
    }

    public static function setThreshold(int $productId, int $threshold): void
    {
        $row = self::stockRow($productId, lock: false);
        if ($row) {
            $row->low_stock_threshold = max(0, $threshold);
            $row->save();
        }
    }

    // =========================================================
    // Internals
    // =========================================================

    /** Apply a change to a locked row + write the movement + sync the legacy cache. */
    private static function apply(InventoryStock $row, string $type, int $quantity, int $onHandChange, int $reservedChange, array $context = []): InventoryMovement
    {
        $prevOnHand   = (int) $row->on_hand;
        $prevReserved = (int) $row->reserved;

        $row->on_hand  = $prevOnHand + $onHandChange;
        $row->reserved = max(0, $prevReserved + $reservedChange);
        $row->save();

        $movement = self::recordMovement($row, $type, $quantity, array_merge($context, [
            'on_hand_change'    => $onHandChange,
            'reserved_change'   => $reservedChange,
            'previous_on_hand'  => $prevOnHand,
            'previous_reserved' => $prevReserved,
        ]));

        self::syncProductCache($row);

        return $movement;
    }

    private static function recordMovement(InventoryStock $row, string $type, int $quantity, array $attrs = []): InventoryMovement
    {
        return InventoryMovement::create(array_merge([
            'product_id'    => $row->product_id,
            'movement_type' => $type,
            'quantity'      => $quantity,
            'new_on_hand'   => (int) $row->on_hand,
            'new_reserved'  => (int) $row->reserved,
        ], $attrs));
    }

    /**
     * products.stock is the legacy availability field the whole storefront
     * reads — keep it equal to available (never negative).
     */
    private static function syncProductCache(InventoryStock $row): void
    {
        DB::table('products')->where('id', $row->product_id)
            ->update(['stock' => max(0, $row->on_hand - $row->reserved)]);
    }
}
