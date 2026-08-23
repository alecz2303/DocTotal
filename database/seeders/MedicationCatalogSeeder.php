<?php

namespace Database\Seeders;

use App\Models\MedicationCatalog;
use Illuminate\Database\Seeder;

class MedicationCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $medications = [
            [
                'code' => 'PARA-500-TAB',
                'name' => 'Paracetamol',
                'presentation' => 'Tabletas 500 mg',
                'therapeutic_group' => 'Analgésicos',
            ],
            [
                'code' => 'PARA-160-SUS',
                'name' => 'Paracetamol',
                'presentation' => 'Suspensión 160 mg/5 mL',
                'therapeutic_group' => 'Analgésicos',
            ],
            [
                'code' => 'IBU-400-TAB',
                'name' => 'Ibuprofeno',
                'presentation' => 'Tabletas 400 mg',
                'therapeutic_group' => 'Antiinflamatorios',
            ],
            [
                'code' => 'AMOX-500-CAP',
                'name' => 'Amoxicilina',
                'presentation' => 'Cápsulas 500 mg',
                'therapeutic_group' => 'Antibióticos',
            ],
            [
                'code' => 'LORA-10-TAB',
                'name' => 'Loratadina',
                'presentation' => 'Tabletas 10 mg',
                'therapeutic_group' => 'Antihistamínicos',
            ],
        ];

        foreach ($medications as $medication) {
            MedicationCatalog::updateOrCreate(
                [
                    'code' => $medication['code'],
                ],
                [
                    ...$medication,
                    'active' => true,
                ]
            );
        }
    }
}
