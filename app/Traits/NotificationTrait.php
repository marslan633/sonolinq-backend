<?php

namespace App\Traits;

trait NotificationTrait
{
    /**
     * Send Notification Function.
     */
    public function sendNotification($tokens, $title, $body, $count = 0)
    {
        $url = 'https://fcm.googleapis.com/fcm/send';
        $serverKey = env('FCM_SERVER_KEY'); // ADD SERVER KEY HERE PROVIDED BY FCM

        $data = [
            "registration_ids" => $tokens,
            "notification" => [
                "title" => $title,
                "body" => $body,
                "icon" => "https://sonolinq.com/assets/img/favicon.png"
            ],
            "data" => [
                "count" => $count
            ]
        ];
        $encodedData = json_encode($data);

        $headers = [
            'Authorization:key=' . $serverKey,
            'Content-Type: application/json',
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        // Disabling SSL Certificate support temporarly
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $encodedData);
        // Execute post
        $result = curl_exec($ch);
        if ($result === FALSE) {
            // Error occurred while sending notification
            curl_close($ch);
            return null;
        } 
        curl_close($ch);
        return $count;
    }
}