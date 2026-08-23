<?php

namespace Database\Seeders;

use App\Models\Specialty;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SpecialtySeeder extends Seeder
{
    public function run(): void
    {
        $specialties = [
            'Medicina General',
            'Medicina Interna',
            'Pediatría',
            'Ginecología y Obstetricia',
            'Cardiología',
            'Dermatología',
            'Endocrinología',
            'Gastroenterología',
            'Neurología',
            'Oftalmología',
            'Otorrinolaringología',
            'Psiquiatría',
            'Traumatología y Ortopedia',
            'Urología',
            'Cirugía General',
            'Medicina Familiar',
            'Neumología',
            'Nefrología',
            'Reumatología',
            'Oncología',
            'Geriatría',
            'Alergología e Inmunología',
            'Medicina del Deporte',
            'Medicina del Trabajo',
        ];

        foreach ($specialties as $name) {
            Specialty::updateOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'name' => $name,
                    'active' => true,
                ]
            );
        }
    }
}
