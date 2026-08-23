<?php

namespace Tests\Feature\Consultations;

use App\Models\DiagnosisCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DiagnosisCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_diagnosis_catalog_is_global_and_can_store_diagnoses(): void
    {
        $diagnosis = DiagnosisCatalog::create([
            'code' => 'R51.9',
            'description' => 'Cefalea no especificada',
            'active' => true,
        ]);

        $this->assertSame(
            'R51.9',
            $diagnosis->code
        );

        $this->assertSame(
            'Cefalea no especificada',
            $diagnosis->description
        );

        $this->assertTrue(
            $diagnosis->active
        );
    }

    public function test_diagnosis_codes_are_unique(): void
    {
        DiagnosisCatalog::create([
            'code' => 'R51.9',
            'description' => 'Cefalea',
        ]);

        $this->expectException(
            \Illuminate\Database\QueryException::class
        );

        DiagnosisCatalog::create([
            'code' => 'R51.9',
            'description' => 'Otra descripción',
        ]);
    }
}
