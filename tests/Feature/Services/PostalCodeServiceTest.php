<?php

namespace Tests\Feature\Services;

use App\Services\PostalCodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class PostalCodeServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.sepomex.base_url' => 'https://sepomex.test/api/v1',
        ]);

        Cache::flush();
    }

    public function test_it_returns_normalized_postal_code_data(): void
    {
        Http::fake([
            'https://sepomex.test/api/v1/zip_codes*' => Http::response([
                [
                    'd_codigo' => '29025',
                    'd_asenta' => 'Centenario Tuchtlán',
                    'd_tipo_asenta' => 'Colonia',
                    'D_mnpio' => 'Tuxtla Gutiérrez',
                    'd_estado' => 'Chiapas',
                    'd_ciudad' => 'Tuxtla Gutiérrez',
                ],
                [
                    'd_codigo' => '29025',
                    'd_asenta' => 'Las Palmas',
                    'd_tipo_asenta' => 'Colonia',
                    'D_mnpio' => 'Tuxtla Gutiérrez',
                    'd_estado' => 'Chiapas',
                    'd_ciudad' => 'Tuxtla Gutiérrez',
                ],
            ], 200),
        ]);

        $result = app(PostalCodeService::class)
            ->lookup('29025');

        $this->assertSame('29025', $result['postal_code']);
        $this->assertSame('Chiapas', $result['state']);
        $this->assertSame(
            'Tuxtla Gutiérrez',
            $result['city']
        );

        $this->assertSame([
            'Centenario Tuchtlán',
            'Las Palmas',
        ], $result['neighborhoods']);
    }

    public function test_it_uses_municipality_as_city_when_city_is_missing(): void
    {
        Http::fake([
            'https://sepomex.test/api/v1/zip_codes*' => Http::response([
                [
                    'd_codigo' => '29025',
                    'd_asenta' => 'Colonia Test',
                    'D_mnpio' => 'Tuxtla Gutiérrez',
                    'd_estado' => 'Chiapas',
                    'd_ciudad' => null,
                ],
            ], 200),
        ]);

        $result = app(PostalCodeService::class)
            ->lookup('29025');

        $this->assertSame(
            'Tuxtla Gutiérrez',
            $result['city']
        );
    }

    public function test_it_returns_unique_sorted_neighborhoods(): void
    {
        Http::fake([
            'https://sepomex.test/api/v1/zip_codes*' => Http::response([
                [
                    'd_codigo' => '29025',
                    'd_asenta' => 'Las Palmas',
                    'D_mnpio' => 'Tuxtla Gutiérrez',
                    'd_estado' => 'Chiapas',
                ],
                [
                    'd_codigo' => '29025',
                    'd_asenta' => 'Centenario Tuchtlán',
                    'D_mnpio' => 'Tuxtla Gutiérrez',
                    'd_estado' => 'Chiapas',
                ],
                [
                    'd_codigo' => '29025',
                    'd_asenta' => 'Las Palmas',
                    'D_mnpio' => 'Tuxtla Gutiérrez',
                    'd_estado' => 'Chiapas',
                ],
            ], 200),
        ]);

        $result = app(PostalCodeService::class)
            ->lookup('29025');

        $this->assertSame([
            'Centenario Tuchtlán',
            'Las Palmas',
        ], $result['neighborhoods']);
    }

    public function test_it_caches_postal_code_requests(): void
    {
        Http::fake([
            'https://sepomex.test/api/v1/zip_codes*' => Http::response([
                [
                    'd_codigo' => '29025',
                    'd_asenta' => 'Centenario Tuchtlán',
                    'D_mnpio' => 'Tuxtla Gutiérrez',
                    'd_estado' => 'Chiapas',
                    'd_ciudad' => 'Tuxtla Gutiérrez',
                ],
            ], 200),
        ]);

        $service = app(PostalCodeService::class);

        $service->lookup('29025');
        $service->lookup('29025');

        Http::assertSentCount(1);
    }

    public function test_it_rejects_invalid_postal_code(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'El código postal debe contener 5 dígitos.'
        );

        app(PostalCodeService::class)
            ->lookup('ABC');
    }

    public function test_it_throws_exception_when_provider_fails(): void
    {
        Http::fake([
            'https://sepomex.test/api/v1/zip_codes*' => Http::response(
                [],
                500
            ),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'No fue posible consultar el código postal.'
        );

        app(PostalCodeService::class)
            ->lookup('29025');
    }

    public function test_it_throws_exception_when_postal_code_has_no_results(): void
    {
        Http::fake([
            'https://sepomex.test/api/v1/zip_codes*' => Http::response(
                [],
                200
            ),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'No encontramos información para este código postal.'
        );

        app(PostalCodeService::class)
            ->lookup('99999');
    }
}
