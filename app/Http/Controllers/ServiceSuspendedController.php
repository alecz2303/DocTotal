<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class ServiceSuspendedController extends Controller
{
    public function __invoke(Request $request): View
    {
        $tenant = $request->user()?->tenant;

        abort_unless($tenant, 403);

        return view('service.suspended', [
            'tenant' => $tenant,
            'subscription' => $tenant->currentSubscription(),
        ]);
    }
}
