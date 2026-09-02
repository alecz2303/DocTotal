<?php

namespace Tests\Feature;

use App\Contracts\Communications\CommunicationTransport;
use App\Models\Communication;
use App\Services\Communications\CommunicationTransportManager;
use LogicException;
use Tests\TestCase;

class CommunicationTransportManagerTest extends TestCase
{
    public function test_unconfigured_channel_returns_null(): void
    {
        config()->set(
            'communications.transports.whatsapp',
            null
        );

        $manager = app(
            CommunicationTransportManager::class
        );

        $this->assertNull(
            $manager->resolve(
                Communication::CHANNEL_WHATSAPP
            )
        );
    }

    public function test_configured_transport_is_resolved(): void
    {
        config()->set(
            'communications.transports.whatsapp',
            TestWhatsAppCommunicationTransport::class
        );

        $manager = app(
            CommunicationTransportManager::class
        );

        $transport = $manager->resolve(
            Communication::CHANNEL_WHATSAPP
        );

        $this->assertInstanceOf(
            CommunicationTransport::class,
            $transport
        );

        $this->assertInstanceOf(
            TestWhatsAppCommunicationTransport::class,
            $transport
        );
    }

    public function test_is_configured_returns_false_when_transport_is_missing(): void
    {
        config()->set(
            'communications.transports.sms',
            null
        );

        $manager = app(
            CommunicationTransportManager::class
        );

        $this->assertFalse(
            $manager->isConfigured(
                Communication::CHANNEL_SMS
            )
        );
    }

    public function test_is_configured_returns_true_when_transport_exists(): void
    {
        config()->set(
            'communications.transports.email',
            TestEmailCommunicationTransport::class
        );

        $manager = app(
            CommunicationTransportManager::class
        );

        $this->assertTrue(
            $manager->isConfigured(
                Communication::CHANNEL_EMAIL
            )
        );
    }

    public function test_invalid_transport_configuration_throws_exception(): void
    {
        config()->set(
            'communications.transports.whatsapp',
            InvalidCommunicationTransport::class
        );

        $manager = app(
            CommunicationTransportManager::class
        );

        $this->expectException(
            LogicException::class
        );

        $manager->resolve(
            Communication::CHANNEL_WHATSAPP
        );
    }
}

class TestWhatsAppCommunicationTransport implements CommunicationTransport
{
    public function send(
        Communication $communication
    ): void {}
}

class TestEmailCommunicationTransport implements CommunicationTransport
{
    public function send(
        Communication $communication
    ): void {}
}

class InvalidCommunicationTransport {}
