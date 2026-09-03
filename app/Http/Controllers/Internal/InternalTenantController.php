<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Services\Internal\InternalSaasOverviewService;
use Illuminate\View\View;

class InternalTenantController extends Controller
{
    public function index(
        InternalSaasOverviewService $service
    ): View {
        return view('internal.tenants.index', [
            'tenants' => $service->tenants(),
        ]);
    }

    public function show(
        Tenant $tenant,
        InternalSaasOverviewService $service
    ): View {
        return view('internal.tenants.show', [
            'detail' => $service->tenantDetail($tenant),
        ]);
    }
}
