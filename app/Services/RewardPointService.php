<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Order;
use App\Models\RewardPointTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Reward points business logic — the ONLY place reward maths lives.
 *
 * Ledger (reward_point_transactions) is the source of truth; balance is the
 * SUM of the signed `points` column. UNIQUE(order_id, type) makes earn/spend/
 * restore idempotent per order at the database level, and every mutation locks
 * the customer row so concurrent requests serialize (no double spending).
 *
 * Rules: floor(eligible ৳ / 100) points earned; 1 point = ৳1 at redemption.
 * Eligible ৳ = order amount minus shipping charge (i.e. product subtotal
 * after coupon AND after reward discount — points never earn on delivery
 * charge or on the part paid with points).
 */
class RewardPointService
{
    /** Current available balance (never negative by construction). */
    public static function balance(int $customerId): int
    {
        return max(0, (int) RewardPointTransaction::where('customer_id', $customerId)->sum('points'));
    }

    public static function earnedPointsFor(float $eligibleAmount): int
    {
        $per = max(1, (int) config('rewards.earn_amount_per_point', 100));

        return max(0, (int) floor($eligibleAmount / $per));
    }

    /** Max points redeemable right now against a given eligible subtotal. */
    public static function maxRedeemable(int $customerId, float $eligibleSubtotal): int
    {
        $value = max(1, (int) config('rewards.point_value', 1));

        return max(0, min(static::balance($customerId), (int) floor($eligibleSubtotal / $value)));
    }

    /**
     * Spend points on an order. MUST be called inside the same DB transaction
     * that creates the order, AFTER the caller has re-derived $points under
     * lockCustomer() — this method re-checks the balance defensively anyway.
     */
    public static function redeem(int $customerId, Order $order, int $points): void
    {
        if ($points <= 0) {
            return;
        }

        $balance = static::balance($customerId);
        if ($points > $balance) {
            throw new \RuntimeException("Reward redeem of {$points} exceeds balance {$balance} for customer {$customerId}");
        }

        RewardPointTransaction::create([
            'customer_id'   => $customerId,
            'order_id'      => $order->id,
            'type'          => RewardPointTransaction::TYPE_SPENT,
            'points'        => -$points,
            'balance_after' => $balance - $points,
            'description'   => "Used {$points} points on Order #{$order->invoice_id}",
        ]);
    }

    /** Serialize all reward mutations for one customer. Call inside DB::transaction. */
    public static function lockCustomer(int $customerId): ?Customer
    {
        return Customer::whereKey($customerId)->lockForUpdate()->first();
    }

    /**
     * Credit earned points when the order reaches the award status (Completed).
     * Idempotent: repeated status flips can never award twice.
     */
    public static function awardOrderPoints(Order $order): void
    {
        if (!$order->customer_id) {
            return;
        }

        DB::transaction(function () use ($order) {
            static::lockCustomer($order->customer_id);

            $already = RewardPointTransaction::where('order_id', $order->id)
                ->where('type', RewardPointTransaction::TYPE_EARNED)
                ->exists();
            if ($already) {
                return;
            }

            // amount = subtotal - coupon - reward + shipping  ⇒  eligible = amount - shipping
            $eligible = max(0.0, (float) $order->amount - (float) $order->shipping_charge);
            $points   = static::earnedPointsFor($eligible);
            if ($points <= 0) {
                return;
            }

            $balance = static::balance($order->customer_id);

            RewardPointTransaction::create([
                'customer_id'   => $order->customer_id,
                'order_id'      => $order->id,
                'type'          => RewardPointTransaction::TYPE_EARNED,
                'points'        => $points,
                'balance_after' => $balance + $points,
                'description'   => "Earned {$points} points from Order #{$order->invoice_id}",
            ]);

            $order->newQuery()->whereKey($order->id)->update([
                'reward_points_earned'     => $points,
                'reward_points_awarded_at' => now(),
            ]);
        });
    }

    /**
     * Order cancelled: give back what was spent on it, take back what it earned.
     * Both idempotent; safe to call on repeated status changes.
     */
    public static function handleCancellation(Order $order): void
    {
        if (!$order->customer_id) {
            return;
        }

        DB::transaction(function () use ($order) {
            static::lockCustomer($order->customer_id);
            static::restoreSpentPoints($order);
            static::reverseEarnedPoints($order);
        });
    }

    /** Return points the customer spent on this order — once. */
    protected static function restoreSpentPoints(Order $order): void
    {
        $spent = RewardPointTransaction::where('order_id', $order->id)
            ->where('type', RewardPointTransaction::TYPE_SPENT)
            ->first();
        if (!$spent) {
            return;
        }

        $alreadyRestored = RewardPointTransaction::where('order_id', $order->id)
            ->where('type', RewardPointTransaction::TYPE_REFUNDED)
            ->exists();
        if ($alreadyRestored) {
            return;
        }

        $points  = abs($spent->points);
        $balance = static::balance($order->customer_id);

        RewardPointTransaction::create([
            'customer_id'   => $order->customer_id,
            'order_id'      => $order->id,
            'type'          => RewardPointTransaction::TYPE_REFUNDED,
            'points'        => $points,
            'balance_after' => $balance + $points,
            'description'   => "Returned {$points} points after Order #{$order->invoice_id} was cancelled",
        ]);
    }

    /**
     * Take back points this order earned — once, and never below a zero balance
     * (if the customer already spent them elsewhere, only claw back what's left).
     */
    protected static function reverseEarnedPoints(Order $order): void
    {
        $earned = RewardPointTransaction::where('order_id', $order->id)
            ->where('type', RewardPointTransaction::TYPE_EARNED)
            ->first();
        if (!$earned) {
            return;
        }

        $alreadyReversed = RewardPointTransaction::where('order_id', $order->id)
            ->where('type', RewardPointTransaction::TYPE_REVERSED)
            ->exists();
        if ($alreadyReversed) {
            return;
        }

        $balance = static::balance($order->customer_id);
        $points  = min(abs($earned->points), $balance); // clamp: balance must never go negative
        if ($points <= 0) {
            Log::info("Reward reversal skipped for order {$order->id}: no balance left to claw back.");
            return;
        }

        RewardPointTransaction::create([
            'customer_id'   => $order->customer_id,
            'order_id'      => $order->id,
            'type'          => RewardPointTransaction::TYPE_REVERSED,
            'points'        => -$points,
            'balance_after' => $balance - $points,
            'description'   => "Reversed {$points} earned points from cancelled Order #{$order->invoice_id}",
        ]);
    }
}
