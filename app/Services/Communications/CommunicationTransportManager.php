<?php

namespace App\Services\Communications;

use App\Contracts\Communications\CommunicationTransport;
use Illuminate\Contracts\Container\Container;
use LogicException;

class CommunicationTransportManager
{
    public function __construct(
        private readonly Container $container,
    ) {}

    public function resolve(
        string $channel
    ): ?CommunicationTransport {
        $transportClass = config(
            "communications.transports.{$channel}"
        );

        if (! $transportClass) {
            return null;
        }

        $transport = $this->container->make(
            $transportClass
        );

        if (! $transport instanceof CommunicationTransport) {
            throw new LogicException(
                sprintf(
                    'El transport configurado para el canal "%s" debe implementar %s.',
                    $channel,
                    CommunicationTransport::class
                )
            );
        }

        return $transport;
    }

    public function isConfigured(
        string $channel
    ): bool {
        return $this->resolve($channel) !== null;
    }
}
