<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Services\Internal\InternalCommunicationOverviewService;
use Illuminate\View\View;

class InternalCommunicationController extends Controller
{
    public function index(
        InternalCommunicationOverviewService $service
    ): View {
        return view('internal.communications.index', [
            'summary' => $service->summary(),
            'failedCommunications' => $service->failedCommunications(),
            'pendingCommunications' => $service->pendingCommunications(),
            'cancelledCommunications' => $service->cancelledCommunications(),
        ]);
    }
}
