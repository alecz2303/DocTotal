<?php

namespace App\Console\Commands;

use App\Models\DiagnosisCatalog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportDiagnosisCatalog extends Command
{
    protected $signature = 'diagnoses:import
                            {file : Ruta del archivo CSV}';

    protected $description = 'Importa el catálogo CIE-10 de diagnósticos';

    public function handle(): int
    {
        $path = $this->argument('file');

        if (! file_exists($path)) {
            $this->error("No existe el archivo: {$path}");

            return self::FAILURE;
        }

        $handle = fopen($path, 'r');

        if (! $handle) {
            $this->error('No fue posible abrir el archivo.');

            return self::FAILURE;
        }

        /*
         * Permitimos BOM de UTF-8.
         */
        $headers = fgetcsv($handle);

        if (! $headers) {
            fclose($handle);

            $this->error('El CSV no tiene encabezados.');

            return self::FAILURE;
        }

        $headers = array_map(
            fn($header) => trim(
                preg_replace('/^\xEF\xBB\xBF/', '', (string) $header)
            ),
            $headers
        );

        $codeIndex = array_search(
            'CATALOG_KEY',
            $headers,
            true
        );

        $descriptionIndex = array_search(
            'NOMBRE',
            $headers,
            true
        );

        if ($codeIndex === false || $descriptionIndex === false) {
            fclose($handle);

            $this->error(
                'El CSV debe contener las columnas CATALOG_KEY y NOMBRE.'
            );

            return self::FAILURE;
        }

        $processed = 0;
        $imported = 0;

        DB::beginTransaction();

        try {
            while (($row = fgetcsv($handle)) !== false) {
                $processed++;

                $code = trim(
                    (string) ($row[$codeIndex] ?? '')
                );

                $description = trim(
                    (string) ($row[$descriptionIndex] ?? '')
                );

                if ($code === '' || $description === '') {
                    continue;
                }

                DiagnosisCatalog::updateOrCreate(
                    [
                        'code' => $code,
                    ],
                    [
                        'description' => $description,
                        'active' => true,
                    ]
                );

                $imported++;
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            fclose($handle);

            $this->error($e->getMessage());

            return self::FAILURE;
        }

        fclose($handle);

        $this->info(
            "Procesados: {$processed}. Importados/actualizados: {$imported}."
        );

        return self::SUCCESS;
    }
}
