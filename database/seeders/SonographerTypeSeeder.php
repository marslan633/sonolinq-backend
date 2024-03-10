<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\SonographerType;

class SonographerTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            [
                "id" => 1,
                "user_id" => 2,
                "name" => "Sonographer w/Machine & PACS",
                "price" => 700,
                "status" => 1
            ],
            [
                "id" => 2,
                "user_id" => 2,
                "name" => "Sonographer w/Machine",
                "price" => 600,
                "status" => 1
            ],
            [
                "id" => 3,
                "user_id" => 2,
                "name" => "Sonographer Only",
                "price" => 500,
                "status" => 1
            ]
        ];

        foreach ($types as $type) {
            SonographerType::create($type);
        }
    }
}