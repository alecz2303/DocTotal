<?php

namespace App\Services\Production;

class ProductionReadinessChecker
{
    /**
     * @return array<int, array{key: string, message: string}>
     */
    public function failures(): array
    {
        $failures = array_merge(
            app(DataDurabilityChecker::class)->failures(),
            app(ObservabilityChecker::class)->failures(),
            app(QueueOperationsChecker::class)->failures(),
            app(EmailDeliveryChecker::class)->failures(),
        );

        $this->require(
            $failures,
            config('app.env') === 'production',
            'app.env',
            'APP_ENV debe ser production para validar un entorno productivo.'
        );

        $this->require(
            $failures,
            strtolower((string) config('app.name')) !== 'laravel',
            'app.name',
            'APP_NAME debe identificar a DocTotal y no conservar el valor genérico Laravel.'
        );

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

        $appUrl = (string) config('app.url');
        $appScheme = parse_url($appUrl, PHP_URL_SCHEME);
        $appHost = parse_url($appUrl, PHP_URL_HOST);

        $this->require(
            $failures,
            is_string($appScheme) && strtolower($appScheme) === 'https',
            'app.url',
            'APP_URL debe usar HTTPS en producción.'
        );

        $this->require(
            $failures,
            is_string($appHost) && $appHost !== '',
            'app.host',
            'APP_URL debe contener un hostname válido para proteger el Host header.'
        );

        $this->require(
            $failures,
            config('database.default') !== 'sqlite',
            'database.default',
            'DB_CONNECTION no debe usar SQLite en producción.'
        );

        $this->require(
            $failures,
            ! in_array(config('session.driver'), ['array', 'cookie'], true),
            'session.driver',
            'SESSION_DRIVER debe usar almacenamiento server-side persistente en producción.'
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
            ! in_array(config('cache.default'), ['array', 'null'], true),
            'cache.default',
            'CACHE_STORE debe soportar locks persistentes para procesos programados.'
        );

        $this->require(
            $failures,
            ! in_array(config('mail.default'), ['log', 'array'], true),
            'mail.default',
            'MAIL_MAILER debe usar un transport real en producción.'
        );

        $mailFrom = strtolower((string) config('mail.from.address'));

        $this->require(
            $failures,
            filled($mailFrom) && ! str_ends_with($mailFrom, '@example.com'),
            'mail.from.address',
            'MAIL_FROM_ADDRESS debe usar una dirección real del servicio.'
        );

        $activeLoggingChannels = $this->activeLoggingChannels();

        $this->require(
            $failures,
            $activeLoggingChannels !== [],
            'logging.channel',
            'LOG_CHANNEL debe apuntar a uno o más canales de logging válidos.'
        );

        $this->require(
            $failures,
            ! $this->activeLoggingUsesDebugLevel($activeLoggingChannels),
            'logging.level',
            'Los canales de logging activos no deben usar nivel debug en producción.'
        );

        $this->require(
            $failures,
            ! in_array(config('queue.default'), ['sync', 'null'], true),
            'queue.default',
            'QUEUE_CONNECTION debe usar un backend persistente en producción.'
        );

        $this->require(
            $failures,
            config('queue.failed.driver') !== 'null',
            'queue.failed.driver',
            'QUEUE_FAILED_DRIVER debe conservar fallos para diagnóstico operativo.'
        );

        $this->require(
            $failures,
            config('billing.payment_gateway') === 'stripe',
            'billing.payment_gateway',
            'BILLING_PAYMENT_GATEWAY debe ser stripe en producción.'
        );

        $this->require(
            $failures,
            filled(config('services.stripe.key')),
            'services.stripe.key',
            'STRIPE_KEY es obligatorio en producción.'
        );

        $this->require(
            $failures,
            filled(config('services.stripe.secret')),
            'services.stripe.secret',
            'STRIPE_SECRET es obligatorio en producción.'
        );

        $this->require(
            $failures,
            filled(config('services.stripe.webhook_secret')),
            'services.stripe.webhook_secret',
            'STRIPE_WEBHOOK_SECRET es obligatorio en producción.'
        );

        return $failures;
    }

    public function isReady(): bool
    {
        return $this->failures() === [];
    }

    /**
     * @return array<int, string>
     */
    private function activeLoggingChannels(): array
    {
        $defaultChannel = (string) config('logging.default');

        if ($defaultChannel === '') {
            return [];
        }

        return $this->resolveLoggingChannel($defaultChannel);
    }

    /**
     * @return array<int, string>
     */
    private function resolveLoggingChannel(string $channel, array $visited = []): array
    {
        if (in_array($channel, $visited, true)) {
            return [];
        }

        $configuration = config("logging.channels.{$channel}");

        if (! is_array($configuration)) {
            return [];
        }

        if (($configuration['driver'] ?? null) !== 'stack') {
            return [$channel];
        }

        $visited[] = $channel;
        $resolved = [];

        foreach (($configuration['channels'] ?? []) as $nestedChannel) {
            if (! is_string($nestedChannel) || $nestedChannel === '') {
                continue;
            }

            $resolved = array_merge(
                $resolved,
                $this->resolveLoggingChannel($nestedChannel, $visited)
            );
        }

        return array_values(array_unique($resolved));
    }

    /**
     * @param array<int, string> $channels
     */
    private function activeLoggingUsesDebugLevel(array $channels): bool
    {
        foreach ($channels as $channel) {
            $level = strtolower((string) config("logging.channels.{$channel}.level"));

            if ($level === 'debug') {
                return true;
            }
        }

        return false;
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
