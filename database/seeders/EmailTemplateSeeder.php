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
            // [
            //     "user_id" => 2,
            //     "subject" => "Welcome Email",
            //     "body" => "<p>Hi <strong>{{username}}</strong>! Welcome to the SonoLinq Community,</p><p>Thank you so much for signing up for SonoLinq. We are thrilled to have you join us on our journey to become the largest trusted community to provide solid, value-added services to help everyone achieve their goals and objectives with clarity and confidence.</p><p>&nbsp;</p><p>To get you started, try out these three simple tasks that would help you understand our platform.</p><p>&nbsp;</p><p>Task 1 (Login)</p><ul><li><strong>Website URL:</strong> {{url}}</li><li><strong>Email:</strong> {{email}}</li><li><strong>Password:</strong> {{password}}</li></ul><p>Task 2 (Confirm your registration details)</p><p>Task 3 (Create your unique signup link and start sharing!)</p><p>Beyond the basics:</p><p>Remember to check out our tutorials here (link) on how to use the platform.</p><p>Thank you for joining; let’s make great things happen together!</p><p>&nbsp;</p><p><strong>Sincerely</strong>,</p><p><em>SonoLinq Manager</em></p>",
            //     "type" => "welcome",
            //     "receiver" => "New User",
            // ],
            [
                "user_id" => 2,
                "subject" => "Forgot Password Mail",
                "body" => "<p>Hello <strong>{{username}}</strong>,&nbsp;</p><p>That's okay, it happens! Below is your system generated password, please change the password immediately after login.</p><p><br></p><p><strong>Password: {{password}}</strong></p><p><br></p><p><strong style=\"color: rgb(34, 34, 34);\">Sincerely</strong><span style=\"color: rgb(34, 34, 34);\">,</span></p><p><span style=\"color: rgb(34, 34, 34);\">Admin</span></p><p><span style=\"color: rgb(34, 34, 34);\">SonoLinq Manager</span></p>",
                "type" => "forgot-password",
                "receiver" => "clients",
            ],
            [
                "user_id" => 2,
                "subject" => "Verification Mail",
                "body" => "<p>Hello <strong>{{username}}</strong>,&nbsp;</p><p>You're almost ready to get started. Please click on the button below to verify your email address and enjoy exclusive labeling services with us!</p> <br /> <p>If you have any questions or need assistance, please feel free to contact us. <br /><br />Thanks, <br /> SonoLinq Manager</p>",
                "type" => "verification",
                "receiver" => "clients",
            ]
        ];

        foreach ($templates as $templateData) {
            EmailTemplate::create($templateData);
        }
    }
}