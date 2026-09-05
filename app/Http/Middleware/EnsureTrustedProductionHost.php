<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTrustedProductionHost
{
    public function handle(Request $request, Closure $next): Response
    {
        if (config('app.env') !== 'production') {
            return $next($request);
        }

        $expectedHost = parse_url(
            (string) config('app.url'),
            PHP_URL_HOST
        );

        if (
            ! is_string($expectedHost)
            || $expectedHost === ''
            || ! hash_equals(
                strtolower($expectedHost),
                strtolower($request->getHost())
            )
        ) {
            abort(400, 'Invalid Host header.');
        }

        return $next($request);
    }
}
