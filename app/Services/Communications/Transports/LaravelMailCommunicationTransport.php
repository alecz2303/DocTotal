<?php

namespace App\Services\Communications\Transports;

use App\Contracts\Communications\CommunicationTransport;
use App\Models\Communication;
use Illuminate\Support\Facades\Mail;
use InvalidArgumentException;

class LaravelMailCommunicationTransport implements CommunicationTransport
{
    public function send(Communication $communication): void
    {
        if ($communication->channel !== Communication::CHANNEL_EMAIL) {
            throw new InvalidArgumentException('LaravelMailCommunicationTransport solo admite el canal email.');
        }

        $recipient = trim((string) $communication->recipient);

        if (filter_var($recipient, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidArgumentException('La comunicación no contiene un destinatario de correo válido.');
        }

        $subject = trim((string) $communication->subject);
        $body = (string) $communication->body;

        Mail::raw($body, function ($message) use ($recipient, $subject): void {
            $message->to($recipient);

            if ($subject !== '') {
                $message->subject($subject);
            }
        });
    }
}
