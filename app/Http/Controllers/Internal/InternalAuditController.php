<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Services\Internal\InternalAuditOverviewService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InternalAuditController extends Controller
{
    public function index(
        Request $request,
        InternalAuditOverviewService $service
    ): View {
        $filters = [
            'action' => trim((string) $request->query('action', '')),
            'tenant_id' => $request->integer('tenant_id') ?: null,
            'user_id' => $request->integer('user_id') ?: null,
        ];

        return view('internal.audit.index', [
            'summary' => $service->summary(),
            'events' => $service->events($filters),
            'actions' => $service->actions(),
            'tenants' => $service->tenants(),
            'filters' => $filters,
        ]);
    }
}
