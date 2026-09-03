<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantHasServiceAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        $tenant = $user->tenant;

        if (! $tenant) {
            abort(403, 'El usuario no tiene un consultorio asignado.');
        }

        if ($tenant->hasAccessToService()) {
            return $next($request);
        }

        if ($request->routeIs('service.suspended')) {
            return $next($request);
        }

        return redirect()->route('service.suspended');
    }
}
