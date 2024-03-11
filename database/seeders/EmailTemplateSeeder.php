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
                "id" => 1,
                "user_id" => 2,
                "subject" => "Welcome Email",
                "body" => "<p>Hi <strong>{{username}}</strong>! Welcome to the SonoLinq Community,</p><p>Thank you so much for signing up for Nexus8. We are thrilled to have you join us on our journey to become the largest trusted community to provide solid, value-added services to help everyone achieve their goals and objectives with clarity and confidence.</p><p>&nbsp;</p><p>To get you started, try out these three simple tasks that would help you understand our platform.</p><p>&nbsp;</p><p>Task 1 (Login)</p><ul><li><strong>Website URL:</strong> {{url}}</li><li><strong>Email:</strong> {{email}}</li><li><strong>Password:</strong> {{password}}</li></ul><p>Task 2 (Confirm your registration details)</p><p>Task 3 (Create your unique signup link and start sharing!)</p><p>Beyond the basics:</p><p>Remember to check out our tutorials here (link) on how to use the platform.</p><p>Thank you for joining; let’s make great things happen together!</p><p>&nbsp;</p><p><strong>Sincerely</strong>,</p><p><em>{{accountManagerName}}</em></p><p><em>Nexus8</em></p><p><em>{{phoneNumber}}</em></p><p><em>{{managerEmail}}</em></p>",
                "type" => "Welcome",
                "receiver" => "New User",
            ],
        ];

        foreach ($templates as $templateData) {
            EmailTemplate::create($templateData);
        }
    }
}