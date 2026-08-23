<?php

namespace App\Console\Commands;

use App\Models\MedicationCatalog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ImportMedicationCatalog extends Command
{
    protected $signature = 'medications:import
                            {file : Ruta del archivo CSV}';

    protected $description = 'Importa el catálogo institucional de medicamentos';

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

        $headers = fgetcsv(
            $handle,
            null,
            ',',
            '"',
            '\\'
        );

        if (! $headers) {
            fclose($handle);

            $this->error('El CSV no tiene encabezados.');

            return self::FAILURE;
        }

        $headers = array_map(
            fn($header) => Str::of((string) $header)
                ->replace("\xEF\xBB\xBF", '')
                ->trim()
                ->lower()
                ->ascii()
                ->replace([' ', '-', '/'], '_')
                ->toString(),
            $headers
        );

        $requiredHeaders = [
            'clave',
            'descripcion',
            'unidad_presentacion',
            'grupo_terapeutico',
        ];

        foreach ($requiredHeaders as $requiredHeader) {
            if (! in_array($requiredHeader, $headers, true)) {
                fclose($handle);

                $this->error(
                    "Falta la columna requerida: {$requiredHeader}"
                );

                return self::FAILURE;
            }
        }

        $codeIndex = array_search(
            'clave',
            $headers,
            true
        );

        $descriptionIndex = array_search(
            'descripcion',
            $headers,
            true
        );

        $presentationIndex = array_search(
            'unidad_presentacion',
            $headers,
            true
        );

        $groupIndex = array_search(
            'grupo_terapeutico',
            $headers,
            true
        );

        $processed = 0;
        $imported = 0;
        $skipped = 0;

        DB::beginTransaction();

        try {
            while (($row = fgetcsv(
                $handle,
                null,
                ',',
                '"',
                '\\'
            )) !== false) {
                $processed++;

                $code = trim(
                    (string) ($row[$codeIndex] ?? '')
                );

                $description = trim(
                    (string) ($row[$descriptionIndex] ?? '')
                );

                $presentation = trim(
                    (string) ($row[$presentationIndex] ?? '')
                );

                $therapeuticGroup = trim(
                    (string) ($row[$groupIndex] ?? '')
                );

                if ($description === '') {
                    $skipped++;

                    continue;
                }

                if ($code === '') {
                    $code = 'AUTO-' . strtoupper(
                        substr(
                            sha1(
                                $description . '|' . $presentation
                            ),
                            0,
                            16
                        )
                    );
                }

                $sourceHash = sha1(
                    $code
                        . '|'
                        . $description
                        . '|'
                        . $presentation
                );

                MedicationCatalog::updateOrCreate(
                    [
                        'source_hash' => $sourceHash,
                    ],
                    [
                        'code' => $code,
                        'name' => $description,
                        'presentation' =>
                        $presentation !== ''
                            ? $presentation
                            : null,
                        'therapeutic_group' =>
                        $therapeuticGroup !== ''
                            ? $therapeuticGroup
                            : null,
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

        $this->info("Procesados: {$processed}");
        $this->info("Importados/actualizados: {$imported}");
        $this->info("Omitidos: {$skipped}");

        return self::SUCCESS;
    }
}
