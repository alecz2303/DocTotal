<?php

namespace App\Contracts\Communications;

use App\Models\Communication;

interface CommunicationTransport
{
    public function send(Communication $communication): void;
}
