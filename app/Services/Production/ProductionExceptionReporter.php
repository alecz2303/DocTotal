<?php

namespace App\Services\Production;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProductionExceptionReporter
{
    public function report(Throwable $exception, ?Request $request = null): void
    {
        if (config('app.env') !== 'production') {
            return;
        }

        if (config('observability.enabled') !== true) {
            return;
        }

        $context = [
            'exception_class' => $exception::class,
            'exception_code' => is_int($exception->getCode())
                ? $exception->getCode()
                : 0,
            'fingerprint' => hash(
                'sha256',
                $exception::class.'|'.$exception->getFile().'|'.$exception->getLine()
            ),
        ];

        if (config('observability.include_exception_location') === true) {
            $context['source_file'] = basename($exception->getFile());
            $context['source_line'] = $exception->getLine();
        }

        if ($request) {
            $context['http_method'] = $request->method();
            $context['route_name'] = $request->route()?->getName();
        }

        Log::channel((string) config('observability.channel'))
            ->error('DocTotal production exception.', $context);
    }
}
