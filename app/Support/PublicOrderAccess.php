<?php

namespace App\Support;

use App\Models\Order;
use App\Models\ResellerLandingPage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

final class PublicOrderAccess
{
    public static function find(Request $request, string $reference, string $column = 'invoice_id'): ?Order
    {
        $token = $request->input('token');

        $order = self::findToken($token);
        if (! $order || ! hash_equals((string) $order->{$column}, $reference)) {
            return null;
        }

        return $order;
    }

    public static function findToken(mixed $token): ?Order
    {

        if (! is_string($token) || ! preg_match('/\A(?:[A-Za-z0-9]{48}|[a-f0-9]{64})\z/D', $token)) {
            return null;
        }

        // The unique token index avoids invoice enumeration and ambiguous legacy
        // invoice references. Compare bytes because MySQL may use a CI collation.
        $order = Order::where('public_token', $token)->first();

        if (! $order || ! hash_equals((string) $order->public_token, $token)) {
            return null;
        }

        return $order;
    }

    public static function successUrl(Order $order): string
    {
        return route('customer.order_success', self::successParameters($order));
    }

    public static function checkoutSuccessUrl(Order $order): string
    {
        if (Auth::guard('customer')->id() && (int) Auth::guard('customer')->id() === (int) $order->customer_id) {
            return self::successUrl($order);
        }

        if ($order->reseller_profit && Auth::guard('admin')->check()) {
            return route('reseller.order.success', ['id' => $order->id]);
        }

        $sessionToken = session('public_order_success_token');
        if ((int) session('public_order_success_id') !== (int) $order->id
            || ! is_string($sessionToken)
            || ! hash_equals((string) $order->public_token, $sessionToken)) {
            // Callback order IDs are public input, never proof that this browser
            // placed the order. Do not turn a forged callback into a token oracle.
            return route('customer.order_track');
        }

        $slug = session('landing_order_slug');
        if (is_string($slug) && (int) session('landing_order_id') === (int) $order->id) {
            $landing = ResellerLandingPage::where('slug', $slug)->where('user_id', $order->user_id)->first();
            if ($landing) {
                return route('reseller.landing.order.success', [
                    'slug' => $slug, 'orderId' => $order->id, 'token' => $order->public_token,
                ]);
            }
        }

        return self::successUrl($order);
    }

    private static function successParameters(Order $order): array
    {
        // An authenticated owner needs no bearer token in a URL.
        if (Auth::guard('customer')->id()
            && (int) Auth::guard('customer')->id() === (int) $order->customer_id) {
            return ['id' => $order->id];
        }

        return ['id' => $order->id, 'token' => $order->public_token];
    }

    public static function noStore($response)
    {
        return $response->header('Cache-Control', 'no-store, private')
            ->header('Referrer-Policy', 'no-referrer')
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }
}
