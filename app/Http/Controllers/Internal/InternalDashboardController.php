<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Services\Internal\InternalSaasOverviewService;
use Illuminate\View\View;

class InternalDashboardController extends Controller
{
    public function __invoke(
        InternalSaasOverviewService $service
    ): View {
        return view('internal.dashboard', [
            'overview' => $service->overview(),
        ]);
    }
}
