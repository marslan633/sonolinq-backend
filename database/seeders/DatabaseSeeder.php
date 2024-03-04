<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        \App\Models\User::factory(1)->create();

        $this->call([
            ClientCompanySeeder::class,
            FaqSeeder::class,
        ]);
        
        \App\Models\User::factory()->create([
            'full_name' => 'Michael Bertrand',
            'email' => 'mbertrand.0618@gmail.com',
            'email_verified_at' => now(),
            'password' => 'ABcd@@12',
            'role' => 'Admin', // Adjust the role as needed
            'status' => true,
            'remember_token' => Str::random(10),
        ]);
    }
}