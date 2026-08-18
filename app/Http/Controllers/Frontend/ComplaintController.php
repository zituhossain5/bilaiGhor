<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\File;

class ComplaintController extends Controller
{
    public function create()
    {
        return view('frontEnd.layouts.pages.complaint', [
            'contact' => Contact::where('status', 1)->first() ?? Contact::first(),
            'customer' => Auth::guard('customer')->user(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:155'],
            'phone' => ['required', 'regex:/^01[3-9][0-9]{8}$/'],
            'email' => ['nullable', 'email:rfc', 'max:255'],
            'order_reference' => ['nullable', 'string', 'max:55'],
            'description' => ['required', 'string', 'min:10', 'max:5000'],
            'image' => ['nullable', File::image()->types(['jpg', 'jpeg', 'png', 'webp'])->max(5 * 1024)],
        ], [
            'phone.regex' => 'Please enter a valid 11-digit Bangladeshi mobile number.',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('complaints', 'private');
        }

        try {
            $complaint = Complaint::create([
                'ticket_number' => $this->generateTicketNumber(),
                'customer_id' => Auth::guard('customer')->id(),
                'name' => $validated['name'],
                'phone' => $validated['phone'],
                'email' => $validated['email'] ?? null,
                'order_reference' => $validated['order_reference'] ?? null,
                'description' => $validated['description'],
                'image' => $imagePath,
                'status' => 'pending',
            ]);
        } catch (\Throwable $exception) {
            if ($imagePath) {
                Storage::disk('private')->delete($imagePath);
            }

            throw $exception;
        }

        return back()->with([
            'success' => 'Your support ticket was submitted successfully.',
            'ticket_number' => $complaint->ticket_number,
        ]);
    }

    private function generateTicketNumber(): string
    {
        do {
            $ticketNumber = sprintf('BG-%s-%04d', now()->format('ymd'), random_int(0, 9999));
        } while (Complaint::where('ticket_number', $ticketNumber)->exists());

        return $ticketNumber;
    }
}
