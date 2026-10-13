<?php

namespace App\Mail;

use App\Models\ContactMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Notification to the shop inbox for a storefront "Contact Us" submission.
 * From is always config('mail.from'); the customer's address is only used as Reply-To.
 */
class ContactMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Normalised message fields for the view:
     * id, full_name, mobile, email, subject, details, submitted_at (Asia/Dhaka).
     *
     * @var array<string, mixed>
     */
    public array $data;

    /**
     * @param  ContactMessage|array<string, mixed>  $message  the saved message, or an array with
     *                                                        full_name, mobile, email, subject, details
     */
    public function __construct(ContactMessage|array $message)
    {
        $fields = $message instanceof ContactMessage ? $message->toArray() : $message;
        $createdAt = $message instanceof ContactMessage ? $message->created_at : null;

        $this->data = [
            'id'           => $fields['id'] ?? null,
            'full_name'    => trim((string) ($fields['full_name'] ?? '')),
            'mobile'       => trim((string) ($fields['mobile'] ?? '')),
            'email'        => trim((string) ($fields['email'] ?? '')),
            'subject'      => trim((string) ($fields['subject'] ?? '')),
            'details'      => (string) ($fields['details'] ?? ''),
            'submitted_at' => Carbon::parse($createdAt ?? now())->timezone('Asia/Dhaka'),
        ];
    }

    public function envelope(): Envelope
    {
        // Single line only: user input must never add header lines.
        $subject = $this->data['subject'] !== ''
            ? 'New contact message: ' . Str::squish($this->data['subject'])
            : 'New contact message from ' . Str::squish($this->data['full_name']);

        $replyTo = filter_var($this->data['email'], FILTER_VALIDATE_EMAIL)
            ? [new Address($this->data['email'], Str::squish($this->data['full_name']) ?: null)]
            : [];

        return new Envelope(
            subject: Str::limit($subject, 150),
            replyTo: $replyTo,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.contact',
            with: ['data' => $this->data],
        );
    }
}
