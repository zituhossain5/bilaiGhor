<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class CustomerResetPasswordNotification extends Notification
{
    use Queueable;

    public function __construct(public string $token)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        // Same mail template the admin reset already uses; only the URL differs.
        $resetUrl = route('customer.password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);

        return (new MailMessage)
            ->subject('Reset Your Password — '.config('app.name'))
            ->view('emails.reset_password', [
                'resetUrl'  => $resetUrl,
                'userName'  => $notifiable->name ?? 'Customer',
                'userEmail' => $notifiable->getEmailForPasswordReset(),
            ]);
    }
}
