<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Services\Internal\InternalBillingIncidentService;
use Illuminate\View\View;

class InternalBillingController extends Controller
{
    public function index(
        InternalBillingIncidentService $service
    ): View {
        return view('internal.billing.index', [
            'summary' => $service->summary(),
            'failedPayments' => $service->failedPayments(),
            'pastDueSubscriptions' => $service->pastDueSubscriptions(),
        ]);
    }
}
