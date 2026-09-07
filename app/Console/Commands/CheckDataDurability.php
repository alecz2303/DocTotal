<?php

namespace App\Console\Commands;

use App\Services\Production\DataDurabilityChecker;
use Illuminate\Console\Command;

class CheckDataDurability extends Command
{
    protected $signature = 'doctotal:check-data-durability';

    protected $description =
        'Valida la estrategia declarada de backup, restauración y retención de DocTotal.';

    public function handle(DataDurabilityChecker $checker): int
    {
        $failures = $checker->failures();

        if ($failures === []) {
            $this->info('Durabilidad de datos de producción: OK.');

            return self::SUCCESS;
        }

        $this->error('La estrategia de durabilidad de datos está incompleta o es insegura.');

        foreach ($failures as $failure) {
            $this->line(sprintf(
                '- [%s] %s',
                $failure['key'],
                $failure['message']
            ));
        }

        return self::FAILURE;
    }
}
