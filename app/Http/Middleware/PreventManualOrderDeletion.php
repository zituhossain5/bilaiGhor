<?php

namespace App\Http\Middleware;

use App\Models\Order;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PreventManualOrderDeletion
{
    public function handle(Request $request, Closure $next): Response
    {
        $ids = collect((array) $request->input('order_ids', []));

        if ($request->filled('id')) {
            $ids->push($request->input('id'));
        }

        $ids = $ids->filter(fn ($id) => is_numeric($id))->map(fn ($id) => (int) $id)->unique();

        if ($ids->isNotEmpty() && Order::whereIn('id', $ids)->where('is_manual_order', 1)->exists()) {
            $message = 'Manual invoices cannot be permanently deleted. Cancel the order instead.';

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['status' => 'error', 'message' => $message], 422);
            }

            return back()->withErrors(['manual_order' => $message]);
        }

        return $next($request);
    }
}
