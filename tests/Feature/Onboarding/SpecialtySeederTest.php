<?php

namespace Tests\Feature\Onboarding;

use App\Models\Specialty;
use Database\Seeders\SpecialtySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpecialtySeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_specialty_seeder_creates_available_specialties(): void
    {
        $this->seed(SpecialtySeeder::class);

        $this->assertGreaterThan(0, Specialty::count());

        $this->assertDatabaseHas('specialties', [
            'name' => 'Medicina General',
            'slug' => 'medicina-general',
            'active' => true,
        ]);
    }

    public function test_specialty_seeder_can_run_more_than_once(): void
    {
        $this->seed(SpecialtySeeder::class);

        $count = Specialty::count();

        $this->seed(SpecialtySeeder::class);

        $this->assertSame($count, Specialty::count());
    }
}
