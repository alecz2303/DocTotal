<?php

namespace App\Services\Production;

use App\Services\Communications\Transports\LaravelMailCommunicationTransport;

class EmailDeliveryChecker
{
    /**
     * @return array<int, array{key: string, message: string}>
     */
    public function failures(): array
    {
        $failures = [];
        $mailer = strtolower((string) config('mail.default'));
        $from = strtolower(trim((string) config('mail.from.address')));
        $transport = config('communications.transports.email');
        $runbook = trim((string) config('communications.email.runbook'));

        $this->require(
            $failures,
            ! in_array($mailer, ['', 'log', 'array'], true),
            'email.mailer',
            'MAIL_MAILER debe usar un transport real para correo productivo.'
        );

        $this->require(
            $failures,
            filter_var($from, FILTER_VALIDATE_EMAIL) !== false
                && ! str_ends_with($from, '@example.com'),
            'email.from',
            'MAIL_FROM_ADDRESS debe ser una dirección válida y no placeholder.'
        );

        $this->require(
            $failures,
            $transport === LaravelMailCommunicationTransport::class,
            'email.communications_transport',
            'DOCTOTAL_EMAIL_COMMUNICATIONS_ENABLED debe habilitar el transport real de email.'
        );

        $this->require(
            $failures,
            $runbook !== '' && is_file(base_path($runbook)),
            'email.runbook',
            'DOCTOTAL_EMAIL_RUNBOOK debe apuntar al runbook operacional versionado.'
        );

        if ($mailer === 'smtp') {
            $smtp = config('mail.mailers.smtp', []);
            $host = strtolower(trim((string) ($smtp['host'] ?? '')));
            $port = (int) ($smtp['port'] ?? 0);

            $this->require(
                $failures,
                $host !== '' && ! in_array($host, ['127.0.0.1', 'localhost'], true),
                'email.smtp.host',
                'MAIL_HOST debe apuntar al servicio SMTP real y no a localhost.'
            );

            $this->require(
                $failures,
                $port > 0 && $port <= 65535,
                'email.smtp.port',
                'MAIL_PORT debe ser un puerto SMTP válido.'
            );
        }

        return $failures;
    }

    public function isReady(): bool
    {
        return $this->failures() === [];
    }

    /**
     * @param array<int, array{key: string, message: string}> $failures
     */
    private function require(array &$failures, bool $condition, string $key, string $message): void
    {
        if (! $condition) {
            $failures[] = ['key' => $key, 'message' => $message];
        }
    }
}
