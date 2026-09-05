<?php

namespace App\Services\Communications\Transports;

use App\Contracts\Communications\CommunicationTransport;
use App\Models\Communication;

class FakeCommunicationTransport implements CommunicationTransport
{
    /** @var array<int, int> */
    private array $sentCommunicationIds = [];

    public function send(Communication $communication): void
    {
        $this->sentCommunicationIds[] = (int) $communication->getKey();
    }

    /** @return array<int, int> */
    public function sentCommunicationIds(): array
    {
        return $this->sentCommunicationIds;
    }
}
