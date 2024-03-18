<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\LevelSystem;

class LevelSystemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $levels = [
            [
                'level' => 'Level 0',
                'badge' => 'New',
                'appointment' => 0,
                'days' => null,
                'rating' => null,
            ],
            [
                'level' => 'Level 1',
                'badge' => 'Business starter',
                'appointment' => 5,
                'days' => 15,
                'rating' => null,
            ],
            [
                'level' => 'Level 2',
                'badge' => 'Pro',
                'appointment' => 20,
                'days' => 90,
                'rating' => 90,
            ],
        ];

        foreach ($levels as $data) {
            LevelSystem::create($data);
        }
    }
}