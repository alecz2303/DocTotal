<?php

namespace App\Http\Middleware;

use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenantContext = app(TenantContext::class);

        $tenantContext->clear();

        $user = $request->user();

        if ($user?->tenant_id) {
            $tenantContext->set($user->tenant);
        }

        return $next($request);
    }
}