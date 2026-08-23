<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;

class PostalCodeService
{
    public function lookup(string $postalCode): array
    {
        $postalCode = trim($postalCode);

        if (! preg_match('/^\d{5}$/', $postalCode)) {
            throw new RuntimeException(
                'El código postal debe contener 5 dígitos.'
            );
        }

        return Cache::remember(
            "postal-code:mx:{$postalCode}",
            now()->addDays(30),
            fn() => $this->fetch($postalCode)
        );
    }

    private function fetch(string $postalCode): array
    {
        $baseUrl = rtrim(
            config('services.sepomex.base_url'),
            '/'
        );

        if (! $baseUrl) {
            throw new RuntimeException(
                'El servicio de códigos postales no está configurado.'
            );
        }

        try {
            $response = Http::timeout(8)
                ->retry(2, 250)
                ->get("{$baseUrl}/zip_codes", [
                    'zip_code' => $postalCode,
                ]);
        } catch (RequestException | ConnectionException $e) {
            throw new RuntimeException(
                'No fue posible consultar el código postal.',
                previous: $e
            );
        }

        if (! $response->successful()) {
            throw new RuntimeException(
                'No fue posible consultar el código postal.'
            );
        }

        $rows = $response->json();

        // La API puede envolver los resultados.
        $rows = $rows['zip_codes']
            ?? $rows['data']
            ?? $rows;

        if (! is_array($rows) || empty($rows)) {
            throw new RuntimeException(
                'No encontramos información para este código postal.'
            );
        }

        $first = $rows[0];

        $neighborhoods = collect($rows)
            ->pluck('d_asenta')
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();

        return [
            'postal_code' => $postalCode,
            'state' => $first['d_estado'] ?? null,
            'municipality' => $first['d_mnpio']
                ?? $first['D_mnpio']
                ?? null,
            'city' => $first['d_ciudad']
                ?? $first['d_mnpio']
                ?? $first['D_mnpio']
                ?? null,
            'neighborhoods' => $neighborhoods,
        ];
    }
}
