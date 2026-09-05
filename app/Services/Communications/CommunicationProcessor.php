<?php

namespace App\Services\Communications;

use App\Contracts\Communications\CommunicationTransport;
use App\Models\Communication;
use Illuminate\Support\Facades\DB;
use Throwable;

class CommunicationProcessor
{
    public const MAX_ATTEMPTS = 3;

    private const RETRY_DELAYS_MINUTES = [1 => 5, 2 => 15];

    public function process(
        Communication $communication,
        CommunicationTransport $transport,
    ): bool {
        $claimed = $this->claim($communication);

        if ($claimed === null) {
            $communication->refresh();
            return $communication->isSent();
        }

        try {
            $transport->send($claimed);
            $claimed->markSent();
            return true;
        } catch (Throwable $exception) {
            $claimed->markFailed(
                $this->safeError($exception),
                $this->nextAttemptAt($claimed->attempt_count)
            );
            return false;
        }
    }

    private function claim(Communication $communication): ?Communication
    {
        return DB::transaction(function () use ($communication) {
            $current = Communication::query()
                ->whereKey($communication->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($current->isSent() || $current->isCancelled() || $current->isProcessing()) {
                return null;
            }

            if ($current->attempt_count >= self::MAX_ATTEMPTS) {
                return null;
            }

            if (
                $current->isFailed()
                && $current->next_attempt_at
                && $current->next_attempt_at->isFuture()
            ) {
                return null;
            }

            if (! $current->isPending() && ! $current->isFailed()) {
                return null;
            }

            $current->markProcessing();
            $current->refresh();

            return $current;
        });
    }

    private function safeError(Throwable $exception): string
    {
        $message = mb_substr($exception->getMessage(), 0, 500);

        return preg_replace(
            [
                '/\bsk_(?:test|live)_[A-Za-z0-9_-]+\b/i',
                '/\bBearer\s+[A-Za-z0-9._~+\/-]+=*\b/i',
                '/\b(api[_-]?key|token|secret|password)\s*[:=]\s*[^\s,;]+/i',
            ],
            '[REDACTED]',
            $message
        ) ?? '[REDACTED]';
    }

    private function nextAttemptAt(int $attemptCount): ?\DateTimeInterface
    {
        $delay = self::RETRY_DELAYS_MINUTES[$attemptCount] ?? null;
        return $delay === null ? null : now()->addMinutes($delay);
    }
}
