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
            ],
            [
                "user_id" => 2,
                "subject" => "Appointment Request Email",
                "body" => "<h1>Hello, {{username}}!</h1><p>You have received a appointment request from SonoLinq. Thank you for your cooperation, and we look forward to a successful appointment.</p><p>Thank you</p>",
                "type" => "booking-request",
                "receiver" => "sonographer",
            ],
            [
                "user_id" => 2,
                "subject" => "Appointment Accepted Email",
                "body" => "<h1>Hello, {{username}}!</h1><p>Your appointment request has been accepted by SonoLinq. We are pleased to confirm the appointment.</p><p>Thank you for your cooperation, and we look forward to a successful appointment.</p><p>Thank you</p>",
                "type" => "booking-accept",
                "receiver" => "doctor",
            ],
            [
                "user_id" => 2,
                "subject" => "Appointment Delivered Email",
                "body" => "<h1>Hello, {{username}}!</h1><p>Your booking has been marked as delivered by SonoLinq. We are pleased to confirm the completion of the booking.</p><p>Thank you for your cooperation, and we hope everything met your expectations.</p><p>Thank you</p>",
                "type" => "booking-deliver",
                "receiver" => "doctor",
            ],
            [
                "user_id" => 2,
                "subject" => "Appointment Completed Email",
                "body" => "<h1>Hello, {{username}}!</h1><p>The appointment you conducted has been marked as completed. We appreciate your dedication and professionalism throughout the appointment.</p><p>Thank you for your cooperation in providing excellent service, and we look forward to working with you again in the future.</p><p>Thank you</p>",
                "type" => "booking-complete",
                "receiver" => "sonographer",
            ],
            [
                "user_id" => 2,
                "subject" => "Appointment Cancellation Email",
                "body" => "<h1>Hello, {{username}}!</h1><p>We regret to inform you that your appointment request with SonoLinq has been rejected/cancelled.</p><p>This decision was made due to [reason for rejection/cancellation, e.g., scheduling conflicts, unforeseen circumstances, etc.]. We apologize for any inconvenience this may cause.</p><p>If you have any questions or concerns, please feel free to contact us. Thank you for your understanding.</p><p>Best regards,</p><p>SonoLinq Manager</p>",
                "type" => "booking-cancel",
                "receiver" => "doctor",
            ],
            [
                "user_id" => 2,
                "subject" => "Congratulations! You've Reached {{level}}",
                "body" => "<h1>Congratulations, {{username}}!</h1><p>We are excited to inform you that you have successfully advanced to {{level}} in our system!</p><p>Thank you for your continued engagement and participation. We appreciate your commitment and look forward to serving you as you progress further.</p><p>If you have any questions or need assistance, please don't hesitate to contact us. Keep up the great work!</p><p>Best regards,</p><p>SonoLinq Manager</p>",
                "type" => "level-upgrade",
                "receiver" => "clients",
            ],
            [
                "user_id" => 2,
                "subject" => "Important: Your Account Downgraded to {{level}}",
                "body" => "<h1>Important: Downgrade Notification</h1><p>Dear {{username}},</p><p>We regret to inform you that your account has been downgraded from {{previous_level}} to {{latest_level}}.</p><p>This action was taken due to [reason for downgrade, e.g., inactivity, violation of terms, etc.]. We understand this may be disappointing, and we encourage you to reach out if you have any questions or concerns regarding this change.</p><p>Thank you for your understanding.</p><p>Best regards,</p><p>SonoLinq Manager</p>",
                "type" => "level-downgrade",
                "receiver" => "clients",
            ],
            [
                "user_id" => 2,
                "subject" => "Inactive Client Email",
                "body" => "<h1>Hi, {{username}}!</h1><p>{{reason}}</p>",
                "type" => "inactive-client-email",
                "receiver" => "clients",
            ],
        ];

        foreach ($templates as $templateData) {
            EmailTemplate::create($templateData);
        }
    }
}