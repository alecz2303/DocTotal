<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;

class VerifyEmailNotification extends VerifyEmail
{
    use Queueable;

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Verifica tu correo electrónico de DocTotal')
            ->view('emails.auth.verify-email', [
                'url' => $this->verificationUrl($notifiable),
                'user' => $notifiable,
            ]);
    }
}
