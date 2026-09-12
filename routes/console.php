<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(
        Inspiring::quote()
    );
})->purpose(
    'Display an inspiring quote'
);

Artisan::command('doctotal:test-alert', function () {
    if (! config('observability.enabled', false)) {
        $this->error('DocTotal observability is disabled.');

        return 1;
    }

    $channel = (string) config('observability.channel', 'stack');

    Log::channel($channel)->error(
        'DocTotal controlled production alert test.',
        [
            'source' => 'artisan',
            'environment' => app()->environment(),
            'timestamp' => now()->toIso8601String(),
        ]
    );

    $this->info("Controlled alert emitted to logging channel [{$channel}].");

    return 0;
})->purpose(
    'Emit a controlled observability alert without modifying application data'
);

/*
|--------------------------------------------------------------------------
| DocTotal scheduled processes
|--------------------------------------------------------------------------
|
| El cron del servidor solamente ejecutará:
|
|     php artisan schedule:run
|
| Laravel será responsable de decidir qué procesos corresponden en cada
| minuto.
|
*/

/*
 * Las cancelaciones programadas no realizan cargos y pueden procesarse
 * independientemente del proveedor de pagos.
 */
Schedule::command(
    'billing:process-cancellations'
)
    ->everyMinute()
    ->withoutOverlapping();

/*
 * Los grace periods vencidos tampoco realizan cargos.
 *
 * Solamente cambian el estado operativo del Tenant cuando el proceso de
 * recuperación ya terminó.
 */
Schedule::command(
    'billing:process-expired-grace-periods'
)
    ->everyMinute()
    ->withoutOverlapping();

Schedule::command(
    'billing:cleanup-abandoned-checkouts'
)
    ->hourly()
    ->withoutOverlapping();

/*
 * Estos procesos SÍ realizan intentos de cobro.
 *
 * No deben activarse hasta que exista un PaymentGateway real configurado
 * para producción.
 */
if (
    config(
        'billing.automatic_charging_enabled',
        false
    )
) {
    Schedule::command(
        'billing:process-renewals'
    )
        ->everyMinute()
        ->withoutOverlapping();

    Schedule::command(
        'billing:process-retries'
    )
        ->everyMinute()
        ->withoutOverlapping();
}


/*
|--------------------------------------------------------------------------
| Transactional communications
|--------------------------------------------------------------------------
|
| Genera las comunicaciones pendientes correspondientes a recordatorios
| de citas próximas.
|
| Este proceso únicamente genera registros de Communication.
| El envío efectivo se procesa de forma independiente mediante el
| transport correspondiente.
|
| La idempotencia evita generar recordatorios duplicados cuando el
| scheduler ejecuta nuevamente este comando.
|
*/

Schedule::command(
    'communications:generate-appointment-reminders',
    [
        '--channel' => 'whatsapp',
        '--days' => 7,
    ]
)
    ->hourly()
    ->withoutOverlapping();


/*
 * Procesa las comunicaciones cuyo momento de envío
 * o reintento ya llegó.
 *
 * El transport concreto determina cómo se realiza el envío.
 * Actualmente puede operar con el transport de infraestructura
 * sin depender todavía de un proveedor externo.
 */
Schedule::command(
    'communications:process-due'
)
    ->everyMinute()
    ->withoutOverlapping();
