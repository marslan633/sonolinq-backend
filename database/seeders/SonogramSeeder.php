<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Sonogram;

class SonogramSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sonograms = [
            'Adult Echo',
            'Pedi Echo',
            'Stress Echo (Bruce)',
            'Carotid Doppler',
            'Lower Extremity Arterial',
            'Upper Extremity Arterial',
            'ABI',
            'Segmental Pressures',
            'Lower Extremity Venous',
            'Upper Extremity Venous',
            'Superficial Venous Reflux',
            'Dialysis Mapping',
            'Fistula Evaluation',
            'Abdominal Vascular',
            'General',
            'Thyroid',
            'RFA',
            'Venaseal',
            'Varithena',
            'Pelvic'
        ];

        foreach ($sonograms as $name) {
            Sonogram::create([
                'name' => $name,
                'status' => true,
                'user_id' => 2
            ]);
        }
    }
}
