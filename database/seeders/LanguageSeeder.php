<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Language;

class LanguageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $languages = [
            [
                "id" => 1,
                "user_id" => 2,
                "name" => "English",
                "status" => 1
            ]
        ];

        foreach ($languages as $language) {
            Language::create($language);
        }
    }
}