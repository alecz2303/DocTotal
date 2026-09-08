<?php

namespace Tests\Feature\Production;

use App\Services\Communications\Transports\LaravelMailCommunicationTransport;
use App\Services\Production\EmailDeliveryChecker;
use Tests\TestCase;

class EmailDeliveryCheckerTest extends TestCase
{
    public function test_real_smtp_configuration_is_ready(): void
    {
        config([
            'mail.default' => 'smtp',
            'mail.from.address' => 'notificaciones@doctotal.mx',
            'mail.mailers.smtp.host' => 'smtp.mail-provider.test',
            'mail.mailers.smtp.port' => 587,
            'communications.transports.email' => LaravelMailCommunicationTransport::class,
            'communications.email.runbook' => 'docs/OPERATIONS_EMAIL_DELIVERY.md',
        ]);

        $this->assertSame([], app(EmailDeliveryChecker::class)->failures());
    }

    public function test_development_mailer_and_disabled_transport_are_rejected(): void
    {
        config([
            'mail.default' => 'log',
            'mail.from.address' => 'hello@example.com',
            'communications.transports.email' => null,
        ]);

        $keys = collect(app(EmailDeliveryChecker::class)->failures())->pluck('key');

        $this->assertTrue($keys->contains('email.mailer'));
        $this->assertTrue($keys->contains('email.from'));
        $this->assertTrue($keys->contains('email.communications_transport'));
    }

    public function test_smtp_localhost_is_rejected(): void
    {
        config([
            'mail.default' => 'smtp',
            'mail.from.address' => 'notificaciones@doctotal.mx',
            'mail.mailers.smtp.host' => '127.0.0.1',
            'mail.mailers.smtp.port' => 2525,
            'communications.transports.email' => LaravelMailCommunicationTransport::class,
        ]);

        $keys = collect(app(EmailDeliveryChecker::class)->failures())->pluck('key');

        $this->assertTrue($keys->contains('email.smtp.host'));
    }

    public function test_missing_runbook_is_rejected(): void
    {
        config([
            'mail.default' => 'smtp',
            'mail.from.address' => 'notificaciones@doctotal.mx',
            'mail.mailers.smtp.host' => 'smtp.mail-provider.test',
            'mail.mailers.smtp.port' => 587,
            'communications.transports.email' => LaravelMailCommunicationTransport::class,
            'communications.email.runbook' => 'docs/MISSING_EMAIL_RUNBOOK.md',
        ]);

        $keys = collect(app(EmailDeliveryChecker::class)->failures())->pluck('key');

        $this->assertTrue($keys->contains('email.runbook'));
    }
}
