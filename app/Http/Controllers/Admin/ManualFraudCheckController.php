<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\FraudCheck\FraudCheckLookup;
use App\Services\FraudCheck\FraudReport;
use App\Services\InventoryService;
use App\Support\FraudCheckSettings;
use App\Support\FraudPhone;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/** Admin → Fraud Protection → Manual Fraud Check: a phone's courier history from BD Courier. */
class ManualFraudCheckController extends Controller
{
    public function index()
    {
        return view('backEnd.fraud.manual_check', $this->pageData());
    }

    public function check(Request $request)
    {
        $phone = FraudPhone::normalize((string) $request->input('mobile'));
        if ($phone === null) {
            return back()->withInput()->with('error', 'সঠিক মোবাইল নম্বর দিন — যেমন 01712345678 (১১ সংখ্যা, 01 দিয়ে শুরু)।');
        }

        $adminId = (int) Auth::guard('admin')->id();
        $lookup = FraudCheckLookup::bdCourier($phone, $request->boolean('refresh'), $adminId);
        FraudCheckLookup::remember($adminId, $phone);

        $report = $lookup['ok']
            ? FraudReport::fromBdCourier($lookup['payload'], FraudCheckSettings::rateMethod(), FraudCheckSettings::riskThresholds())
            : null;

        return view('backEnd.fraud.manual_check', $this->pageData() + [
            'phone'  => $phone,
            'lookup' => $lookup,
            'report' => $report,
            'store'  => $this->storeHistory($phone),
        ]);
    }

    public function updateSettings(Request $request)
    {
        if (!FraudCheckSettings::ready()) {
            Toastr::error('courier_settings টেবিল নেই — সার্ভারে php artisan migrate চালান।', 'Error');

            return redirect()->route('manualFraud.page');
        }

        $data = $request->validate([
            'fraud_rate_method'     => ['required', Rule::in(FraudCheckSettings::RATE_METHODS)],
            'fraud_cache_minutes'   => ['required', 'integer', 'min:0', 'max:10080'],
            'fraud_risk_safe_min'   => ['required', 'numeric', 'min:1', 'max:100'],
            'fraud_risk_medium_min' => ['required', 'numeric', 'min:0', 'lt:fraud_risk_safe_min'],
        ], [
            'fraud_risk_medium_min.lt' => '"মাঝারি" সীমা "নিরাপদ" সীমার চেয়ে কম হতে হবে।',
        ]);
        $data['fraud_mask_phone'] = $request->boolean('fraud_mask_phone') ? '1' : '0';

        FraudCheckSettings::set($data);
        Toastr::success('ফ্রড চেক সেটিংস সংরক্ষণ হয়েছে', 'Success!');

        return redirect()->route('manualFraud.page');
    }

    private function pageData(): array
    {
        return [
            'recent'        => FraudCheckLookup::recent((int) Auth::guard('admin')->id()),
            'settings'      => FraudCheckSettings::all(),
            'settingsReady' => FraudCheckSettings::ready(),
        ];
    }

    /** This shop's own orders for the number. Null when there are none. */
    private function storeHistory(string $phone): ?array
    {
        $orders = Order::with('status:id,name')
            ->whereHas('shipping', fn ($q) => $q->whereIn('phone', FraudPhone::variants($phone)))
            ->latest('id')
            ->get(['id', 'invoice_id', 'amount', 'order_status', 'created_at']);

        if ($orders->isEmpty()) {
            return null;
        }

        return [
            'total'     => $orders->count(),
            'delivered' => $orders->where('order_status', InventoryService::COMPLETE_STATUS)->count(),
            'cancelled' => $orders->where('order_status', InventoryService::CANCEL_STATUS)->count(),
            'open'      => $orders->whereIn('order_status', InventoryService::RESERVE_STATUSES)->count(),
            'last'      => $orders->take(3)->map(fn ($o) => [
                'invoice' => $o->invoice_id,
                'amount'  => (float) $o->amount,
                'status'  => $o->status->name ?? (string) $o->order_status,
                'date'    => $o->created_at?->format('d M Y'),
            ])->all(),
            'url'       => route('admin.orders', ['slug' => 'all', 'keyword' => substr($phone, 1)]),
        ];
    }
}
