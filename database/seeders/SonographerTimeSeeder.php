<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\SonographerTime;

class SonographerTimeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $times = [
            [
                "id" => 1,
                "user_id" => 2,
                "name" => "8m-5p",
                "price" => 0,
                "status" => 1
            ],
            [
                "id" => 2,
                "user_id" => 2,
                "name" => "8m-12pm",
                "price" => 55,
                "status" => 1
            ],
            [
                "id" => 3,
                "user_id" => 2,
                "name" => "1p-5p",
                "price" => 30,
                "status" => 1
            ]
        ];

        foreach ($times as $time) {
            SonographerTime::create($time);
        }
    }
}