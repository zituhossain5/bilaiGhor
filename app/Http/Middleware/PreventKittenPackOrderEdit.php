<?php

namespace App\Http\Middleware;

use App\Models\Order;
use App\Models\OrderDetails;
use Brian2694\Toastr\Facades\Toastr;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The (encoded) admin order editor loads every line into its cart by product_id,
 * and a kitten pack line has none — so pack orders are kept out of it.
 */
class PreventKittenPackOrderEdit
{
    public function handle(Request $request, Closure $next): Response
    {
        $order = Order::where('invoice_id', $request->route('invoice_id'))->first(['id', 'invoice_id']);

        if ($order && OrderDetails::where('order_id', $order->id)->whereNotNull('kitten_pack_id')->exists()) {
            Toastr::warning(
                'This order contains a Kitten Pack, so its items cannot be edited here. Change its status from Process, or cancel it and place a new order.',
                'Cannot edit'
            );

            return redirect()->route('admin.order.process', $order->invoice_id);
        }

        return $next($request);
    }
}
