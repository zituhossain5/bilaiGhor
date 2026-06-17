<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LicenseController extends Controller
{
    public function licenseInfo()
    {
        $apiUrl = 'https://www.bmitltd.com/api/verify-license';

        $domain = preg_replace('/^www\./', '', request()->getHost());
        $license_key = env('LICENSE_KEY');

        try {
            $response = Http::withoutVerifying()
                ->asJson()
                ->acceptJson()
                ->timeout(10)
                ->post($apiUrl, [
                    'domain'      => $domain,
                    'license_key' => $license_key,
                ]);

            if ($response->successful()) {
                $licenseData = $response->json();
            } else {
                $licenseData = ['status' => 'invalid', 'message' => 'মাদার সার্ভার থেকে রেসপন্স পাওয়া যায়নি।'];
            }
        } catch (\Exception $e) {
            $licenseData = ['status' => 'error', 'message' => 'সার্ভারের সাথে সংযোগ স্থাপন করা সম্ভব হয়নি।'];
        }

        return view('backEnd.license.info', compact('licenseData'));
    }
}
