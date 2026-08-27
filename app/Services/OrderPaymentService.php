<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;

class OrderPaymentService
{
    public static function statusFromAmounts(float $paid, float $grandTotal): string
    {
        if ($paid <= 0) {
            return 'unpaid';
        }

        if ($paid < $grandTotal) {
            return 'partial';
        }

        return 'paid';
    }

    public static function dueAmount(float $paid, float $grandTotal): float
    {
        return max($grandTotal - $paid, 0);
    }

    public static function currentPayment(Order $order): ?Payment
    {
        if ($order->relationLoaded('payment') && $order->payment) {
            return $order->payment;
        }

        return Payment::where('order_id', $order->id)->latest('id')->first();
    }

    public static function state(Order $order): array
    {
        $payment = self::currentPayment($order);
        $grandTotal = (float) $order->amount;
        $paid = (float) ($payment?->amount ?? $order->paid_amount ?? 0);

        $status = self::statusFromAmounts($paid, $grandTotal);

        return [
            'payment' => $payment,
            'grand_total' => $grandTotal,
            'paid' => $paid,
            'due' => self::dueAmount($paid, $grandTotal),
            'status' => $status,
            'method' => $payment?->payment_method ?? $order->payment_method ?? $order->payment_gateway,
            'transaction_id' => $payment?->trx_id ?? $order->transaction_id,
        ];
    }

    public static function syncSnapshot(Order $order, ?Payment $payment = null): Order
    {
        $payment ??= self::currentPayment($order);
        $grandTotal = (float) $order->amount;
        $paid = (float) ($payment?->amount ?? 0);
        $status = self::statusFromAmounts($paid, $grandTotal);

        $order->forceFill([
            'paid_amount' => $paid,
            'due_amount' => self::dueAmount($paid, $grandTotal),
            'payment_status' => $status,
            'payment_method' => $payment?->payment_method ?? $order->payment_method ?? $order->payment_gateway,
            'transaction_id' => $payment?->trx_id ?? $order->transaction_id,
        ])->save();

        return $order;
    }

    public static function upsertPayment(
        Order $order,
        ?int $customerId,
        float $paid,
        ?string $method,
        ?string $transactionId,
        ?string $senderNumber = null
    ): Payment {
        $paid = max(0, min($paid, (float) $order->amount));
        $status = self::statusFromAmounts($paid, (float) $order->amount);

        $payment = self::currentPayment($order);
        if (!$payment) {
            $payment = new Payment(['order_id' => $order->id]);
        }

        $payment->forceFill([
            'customer_id' => $customerId,
            'amount' => (int) round($paid),
            'trx_id' => $transactionId,
            'sender_number' => $senderNumber,
            'payment_method' => $method,
            'payment_status' => $status,
        ])->save();

        self::syncSnapshot($order, $payment);

        return $payment;
    }

    public static function updateFromAdminStatus(Order $order, string $requestedStatus): array
    {
        return DB::transaction(function () use ($order, $requestedStatus) {
            $lockedOrder = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            $payment = self::currentPayment($lockedOrder);

            $paid = match ($requestedStatus) {
                'paid' => (float) $lockedOrder->amount,
                'unpaid', 'pending', 'failed', 'cancelled', 'cancel' => 0.0,
                'partial' => min((float) ($payment?->amount ?? $lockedOrder->paid_amount ?? 0), (float) $lockedOrder->amount),
                default => min((float) ($payment?->amount ?? $lockedOrder->paid_amount ?? 0), (float) $lockedOrder->amount),
            };

            $payment = self::upsertPayment(
                $lockedOrder,
                $lockedOrder->customer_id,
                $paid,
                $payment?->payment_method ?? $lockedOrder->payment_method ?? $lockedOrder->payment_gateway,
                $payment?->trx_id ?? $lockedOrder->transaction_id,
                $payment?->sender_number ?? $lockedOrder->manual_customer_phone
            );

            return [
                'order' => $lockedOrder->refresh(),
                'payment' => $payment->refresh(),
                'paid' => (float) $lockedOrder->paid_amount,
                'due' => (float) $lockedOrder->due_amount,
                'status' => $lockedOrder->payment_status,
            ];
        });
    }
}
