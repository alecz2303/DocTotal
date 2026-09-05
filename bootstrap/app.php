<?php

use App\Http\Middleware\EnsureInternalAdmin;
use App\Http\Middleware\EnsureOnboardingIsComplete;
use App\Http\Middleware\EnsureTenantHasServiceAccess;
use App\Http\Middleware\EnsureTrustedProductionHost;
use App\Http\Middleware\ResolveTenant;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->prepend(
            EnsureTrustedProductionHost::class
        );

        $middleware->web(append: [
            ResolveTenant::class,
        ]);

        $middleware->prependToPriorityList(
            SubstituteBindings::class,
            ResolveTenant::class
        );

        $middleware->alias([
            'onboarding' => EnsureOnboardingIsComplete::class,
            'service.access' => EnsureTenantHasServiceAccess::class,
            'internal.admin' => EnsureInternalAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) =>
                $request->is('api/*')
                || $request->expectsJson(),
        );
    })->create();
