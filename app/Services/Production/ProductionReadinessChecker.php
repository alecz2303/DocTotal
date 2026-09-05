<?php

namespace App\Services\Production;

class ProductionReadinessChecker
{
    /**
     * @return array<int, array{key: string, message: string}>
     */
    public function failures(): array
    {
        $failures = [];

        $this->require(
            $failures,
            ! config('app.debug'),
            'app.debug',
            'APP_DEBUG debe estar desactivado en producción.'
        );

        $this->require(
            $failures,
            filled(config('app.key')),
            'app.key',
            'APP_KEY debe estar configurado.'
        );

        $this->require(
            $failures,
            str_starts_with((string) config('app.url'), 'https://'),
            'app.url',
            'APP_URL debe usar HTTPS en producción.'
        );

        $this->require(
            $failures,
            config('session.secure') === true,
            'session.secure',
            'SESSION_SECURE_COOKIE debe estar habilitado en producción.'
        );

        $this->require(
            $failures,
            config('session.http_only') === true,
            'session.http_only',
            'SESSION_HTTP_ONLY debe permanecer habilitado.'
        );

        $this->require(
            $failures,
            config('session.serialization') === 'json',
            'session.serialization',
            'La serialización de sesión debe permanecer en JSON.'
        );

        $this->require(
            $failures,
            ! in_array(config('mail.default'), ['log', 'array'], true),
            'mail.default',
            'MAIL_MAILER debe usar un transport real en producción.'
        );

        $this->require(
            $failures,
            strtolower((string) config('logging.channels.single.level')) !== 'debug',
            'logging.level',
            'LOG_LEVEL no debe ser debug en producción.'
        );

        $this->require(
            $failures,
            ! in_array(config('queue.default'), ['sync', 'null'], true),
            'queue.default',
            'QUEUE_CONNECTION debe usar un backend persistente en producción.'
        );

        if (config('billing.automatic_charging_enabled')) {
            $this->require(
                $failures,
                config('billing.payment_gateway') === 'stripe',
                'billing.payment_gateway',
                'Los cobros automáticos requieren BILLING_PAYMENT_GATEWAY=stripe.'
            );

            $this->require(
                $failures,
                filled(config('services.stripe.secret')),
                'services.stripe.secret',
                'STRIPE_SECRET es obligatorio cuando los cobros automáticos están habilitados.'
            );

            $this->require(
                $failures,
                filled(config('services.stripe.webhook_secret')),
                'services.stripe.webhook_secret',
                'STRIPE_WEBHOOK_SECRET es obligatorio cuando los cobros automáticos están habilitados.'
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
    private function require(
        array &$failures,
        bool $condition,
        string $key,
        string $message
    ): void {
        if ($condition) {
            return;
        }

        $failures[] = [
            'key' => $key,
            'message' => $message,
        ];
    }
}
