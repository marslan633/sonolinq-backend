<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\EmailTemplate;

class EmailTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $templates = [
            [
                "user_id" => 2,
                "subject" => "Welcome Email",
                "body" => "<h1>Hi, {{username}}!</h1><p>Welcome to our plaform you are verified successfully! Now you can access your account.Thank you so much for signing up for SonoLinq. We are thrilled to have you join us on our journey to become the largest trusted community to provide solid, value-added services to help everyone achieve their goals and objectives with clarity and confidence.</p><a href={{url}} target='_blank'>Login</a>",
                "type" => "welcome",
                "receiver" => "clients",
            ],
            [
                "user_id" => 2,
                "subject" => "Forgot Password Mail",
                "body" => "<h1>Hello, {{username}}!</h1><p>That's okay, it happens! Below is your system generated password, please change the password immediately after login.</p><p>Password: <strong>{{password}}</strong></p><a href={{url}} target='_blank'>Login</a><p>Thank you!</p>",
                "type" => "forgot-password",
                "receiver" => "clients",
            ],
            [
                "user_id" => 2,
                "subject" => "Verification Mail",
                "body" => "<h1>Hello, {{username}}!</h1><p>You're almost ready to get started. Please click on the button below to verify your email address and enjoy exclusive labeling services with us!</p><a href={{url}} target='_blank'>Verify Email</a><p>If you have any questions or need assistance, please feel free to contact us. Thanks</p>",
                "type" => "verification",
                "receiver" => "clients",
            ]
        ];

        foreach ($templates as $templateData) {
            EmailTemplate::create($templateData);
        }
    }
}