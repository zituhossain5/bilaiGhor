<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Mail\ContactMail;
use Illuminate\Http\Request;
use App\Models\ContactMessage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ContactMessageController extends Controller
{
    private const SUCCESS = 'ধন্যবাদ! আপনার মেসেজটি পেয়েছি, আমরা দ্রুত যোগাযোগ করব।';

    public function store(Request $request)
    {
        // Honeypot: real visitors never see or fill "website". Bots get the normal
        // success message, but nothing is saved or emailed.
        if ($request->filled('website')) {
            return back()->with('success', self::SUCCESS);
        }

        // ✅ Correct validation (phone/address বাদ)
        $request->validate([
            'full_name' => 'required|string|max:255',
            'mobile'    => 'required|string|max:20',
            'email'     => 'nullable|email',
            'subject'   => 'nullable|string|max:255',
            'details'   => 'required|string',
        ]);

        // ✅ Save data
        $message = ContactMessage::create([
            'full_name' => $request->full_name,
            'mobile'    => $request->mobile,
            'email'     => $request->email,
            'subject'   => $request->subject,
            'details'   => $request->details,
            'status'    => 0,
        ]);

        // Notify the shop inbox. The message is already saved (admin → Contact Messages),
        // so a mail failure is logged and the visitor still gets the success message.
        try {
            Mail::to(config('mail.contact_to'))->send(new ContactMail($message));
        } catch (\Throwable $e) {
            Log::error('Contact form email failed', [
                'contact_message_id' => $message->id,
                'error'              => $e->getMessage(),
            ]);
        }

        return back()->with('success', self::SUCCESS);
    }
}
