<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderPaymentController extends Controller
{
    public function updateStatus(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_id' => ['required', 'integer', 'exists:orders,id'],
            'payment_status' => ['required', 'string', 'max:55'],
        ]);

        $status = strtolower(trim($validated['payment_status']));
        $allowed = ['pending', 'paid', 'unpaid', 'partial', 'failed', 'cancelled', 'cancel'];

        if (!in_array($status, $allowed, true)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid payment status.',
            ], 422);
        }

        $result = OrderPaymentService::updateFromAdminStatus(
            Order::findOrFail($validated['order_id']),
            $status
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Payment status updated successfully.',
            'payment_status' => $result['status'],
            'paid_amount' => $result['paid'],
            'due_amount' => $result['due'],
        ]);
    }
}
