<?php

namespace App\Services\Communications;

use App\Contracts\Communications\CommunicationTransport;
use App\Models\Communication;
use Throwable;

class CommunicationProcessor
{
    public const MAX_ATTEMPTS = 3;

    private const RETRY_DELAYS_MINUTES = [
        1 => 5,
        2 => 15,
    ];

    public function process(
        Communication $communication,
        CommunicationTransport $transport,
    ): bool {
        /*
         * Una comunicación enviada nunca debe volver a enviarse.
         */
        if ($communication->isSent()) {
            return true;
        }

        /*
         * Una comunicación fallida que agotó sus intentos
         * queda en estado FAILED sin next_attempt_at.
         */
        if (
            $communication->isFailed()
            && $communication->attempt_count >= self::MAX_ATTEMPTS
        ) {
            return false;
        }

        /*
         * Si existe un reintento programado y todavía no llegó
         * su momento, no intentamos enviarla antes de tiempo.
         */
        if (
            $communication->isFailed()
            && $communication->next_attempt_at
            && $communication->next_attempt_at->isFuture()
        ) {
            return false;
        }

        /*
         * Registramos el intento antes de contactar al transport.
         */
        $communication->registerAttempt();
        $communication->refresh();

        try {
            $transport->send($communication);

            $communication->markSent();

            return true;
        } catch (Throwable $exception) {
            $communication->markFailed(
                $exception->getMessage(),
                $this->nextAttemptAt(
                    $communication->attempt_count
                )
            );

            return false;
        }
    }

    private function nextAttemptAt(
        int $attemptCount
    ): ?\DateTimeInterface {
        $delay = self::RETRY_DELAYS_MINUTES[$attemptCount] ?? null;

        if ($delay === null) {
            return null;
        }

        return now()->addMinutes($delay);
    }
}
