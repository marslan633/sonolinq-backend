<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Client;
use App\Models\Company;

class ClientCompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $client = Client::create([
            "reg_no" => "FM1001",
            "full_name" => "Client 1",
            "email" => "client1@gmail.com",
            'password' => 'ABcd@@12',
            "phone_number" => "+923110767466",
            "referrer_id" => "798798789",
            "is_verified" => 1,
            "terms" => 1,
            "status" => "Active",
            "role" => "Sonographer",
            "gender" => "Male",
            "created_at" => "2023-12-26 09:09:04",
            "updated_at" => "2023-12-26 09:09:04",
        ]);

        Company::create([
            "client_id" => $client->id,
            "company_name" => "Company 1",
            
            "is_vat" => 1,
            "years_of_experience" => "0-1yr",
            "type_of_equipment" => "demo",
            "practice_instructions" => "demo",
            "references" => "demo",
            "languages_spoken" => "English,Urdu,Hindi,French",
            "level" => "Verified",
            "created_at" => "2023-12-26 09:09:04",
            "updated_at" => "2023-12-26 09:09:04",
        ]);


        $client = Client::create([
            "reg_no" => "FM1002",
            "full_name" => "Client 2",
            "email" => "client2@gmail.com",
            'password' => 'ABcd@@12',
            "phone_number" => "+923110767466",
            "referrer_id" => "798798789",
            "is_verified" => 1,
            "terms" => 1,
            "status" => "Active",
            "role" => "Sonographer",
            "gender" => "Male",
            "created_at" => "2023-12-26 09:09:04",
            "updated_at" => "2023-12-26 09:09:04",
        ]);

        Company::create([
            "client_id" => $client->id,
            "company_name" => "Company 2",
            
            "is_vat" => 1,
            "years_of_experience" => "1-5yr",
            "type_of_equipment" => "demo",
            "practice_instructions" => "demo",
            "references" => "demo",
            "languages_spoken" => "English,Urdu",
            "level" => "UnVerified",
            "created_at" => "2023-12-26 09:09:04",
            "updated_at" => "2023-12-26 09:09:04",
        ]);


        $client = Client::create([
            "reg_no" => "FM1003",
            "full_name" => "Client 3",
            "email" => "client3@gmail.com",
            'password' => 'ABcd@@12',
            "phone_number" => "+923110767466",
            "referrer_id" => "798798789",
            "is_verified" => 1,
            "terms" => 1,
            "status" => "Active",
            "role" => "Sonographer",
            "gender" => "Male",
            "created_at" => "2023-12-26 09:09:04",
            "updated_at" => "2023-12-26 09:09:04",
        ]);

        Company::create([
            "client_id" => $client->id,
            "company_name" => "Company 3",
            
            "is_vat" => 1,
            "years_of_experience" => "0-1yr",
            "type_of_equipment" => "demo",
            "practice_instructions" => "demo",
            "references" => "demo",
            "languages_spoken" => "Hindi,French",
            "level" => "Verified",
            "created_at" => "2023-12-26 09:09:04",
            "updated_at" => "2023-12-26 09:09:04",
        ]);


        $client = Client::create([
            "reg_no" => "FM1004",
            "full_name" => "Client 4",
            "email" => "client4@gmail.com",
            'password' => 'ABcd@@12',
            "phone_number" => "+923110767466",
            "referrer_id" => "798798789",
            "is_verified" => 1,
            "terms" => 1,
            "status" => "Active",
            "role" => "Doctor/Facility",
            "gender" => "Male",
            "created_at" => "2023-12-26 09:09:04",
            "updated_at" => "2023-12-26 09:09:04",
        ]);

        Company::create([
            "client_id" => $client->id,
            "company_name" => "Company 4",
            
            "is_vat" => 1,
            "years_of_experience" => "0-1yr",
            "type_of_equipment" => "demo",
            "practice_instructions" => "demo",
            "references" => "demo",
            "languages_spoken" => "English,Urdu,Hindi,French",
            "level" => "Verified",
            "created_at" => "2023-12-26 09:09:04",
            "updated_at" => "2023-12-26 09:09:04",
        ]);

        $client = Client::create([
            "reg_no" => "FM1005",
            "full_name" => "Client 5",
            "email" => "client5@gmail.com",
            'password' => 'ABcd@@12',
            "phone_number" => "+923110767466",
            "referrer_id" => "798798789",
            "is_verified" => 1,
            "terms" => 1,
            "status" => "Active",
            "role" => "Sonographer",
            "gender" => "Female",
            "created_at" => "2023-12-26 09:09:04",
            "updated_at" => "2023-12-26 09:09:04",
        ]);

        Company::create([
            "client_id" => $client->id,
            "company_name" => "Company 5",
            
            "is_vat" => 1,
            "years_of_experience" => "5-10yr",
            "type_of_equipment" => "demo",
            "practice_instructions" => "demo",
            "references" => "demo",
            "languages_spoken" => "English,Urdu,Hindi,French",
            "level" => "Verified+",
            "created_at" => "2023-12-26 09:09:04",
            "updated_at" => "2023-12-26 09:09:04",
        ]);


        $client = Client::create([
            "reg_no" => "FM1006",
            "full_name" => "Client 6",
            "email" => "client6@gmail.com",
            'password' => 'ABcd@@12',
            "phone_number" => "+923110767466",
            "referrer_id" => "798798789",
            "is_verified" => 1,
            "terms" => 1,
            "status" => "Active",
            "role" => "Sonographer",
            "gender" => "Female",
            "created_at" => "2023-12-26 09:09:04",
            "updated_at" => "2023-12-26 09:09:04",
        ]);

        Company::create([
            "client_id" => $client->id,
            "company_name" => "Company 6",
            
            "is_vat" => 1,
            "years_of_experience" => "5-10yr",
            "type_of_equipment" => "demo",
            "practice_instructions" => "demo",
            "references" => "demo",
            "languages_spoken" => "English,Urdu,Hindi,French",
            "level" => "Verified+",
            "created_at" => "2023-12-26 09:09:04",
            "updated_at" => "2023-12-26 09:09:04",
        ]);



        $client = Client::create([
            "reg_no" => "FM1007",
            "full_name" => "Client 7",
            "email" => "client7@gmail.com",
            'password' => 'ABcd@@12',
            "phone_number" => "+923110767466",
            "referrer_id" => "798798789",
            "is_verified" => 1,
            "terms" => 1,
            "status" => "Active",
            "role" => "Sonographer",
            "gender" => "Male",
            "created_at" => "2023-12-26 09:09:04",
            "updated_at" => "2023-12-26 09:09:04",
        ]);

        Company::create([
            "client_id" => $client->id,
            "company_name" => "Company 7",
            
            "is_vat" => 1,
            "years_of_experience" => "10+ yr",
            "type_of_equipment" => "demo",
            "practice_instructions" => "demo",
            "references" => "demo",
            "languages_spoken" => "French",
            "level" => "Verified+",
            "created_at" => "2023-12-26 09:09:04",
            "updated_at" => "2023-12-26 09:09:04",
        ]);

        $client = Client::create([
            "reg_no" => "FM1008",
            "full_name" => "Client 8",
            "email" => "client8@gmail.com",
            'password' => 'ABcd@@12',
            "phone_number" => "+923110767466",
            "referrer_id" => "798798789",
            "is_verified" => 1,
            "terms" => 1,
            "status" => "Active",
            "role" => "Sonographer",
            "gender" => "Male",
            "created_at" => "2023-12-26 09:09:04",
            "updated_at" => "2023-12-26 09:09:04",
        ]);

        Company::create([
            "client_id" => $client->id,
            "company_name" => "Company 8",
            
            "is_vat" => 1,
            "years_of_experience" => "10+ yr",
            "type_of_equipment" => "demo",
            "practice_instructions" => "demo",
            "references" => "demo",
            "languages_spoken" => "English,French",
            "level" => "Verified+",
            "created_at" => "2023-12-26 09:09:04",
            "updated_at" => "2023-12-26 09:09:04",
        ]);


        $client = Client::create([
            "reg_no" => "FM1009",
            "full_name" => "Client 9",
            "email" => "client9@gmail.com",
            'password' => 'ABcd@@12',
            "phone_number" => "+923110767466",
            "referrer_id" => "798798789",
            "is_verified" => 1,
            "terms" => 1,
            "status" => "Active",
            "role" => "Sonographer",
            "gender" => "Male",
            "created_at" => "2023-12-26 09:09:04",
            "updated_at" => "2023-12-26 09:09:04",
        ]);

        Company::create([
            "client_id" => $client->id,
            "company_name" => "Company 9",
            
            "is_vat" => 1,
            "years_of_experience" => "1-5yr",
            "type_of_equipment" => "demo",
            "practice_instructions" => "demo",
            "references" => "demo",
            "languages_spoken" => "English,Urdu,Hindi",
            "level" => "UnVerified",
            "created_at" => "2023-12-26 09:09:04",
            "updated_at" => "2023-12-26 09:09:04",
        ]);
    }
}