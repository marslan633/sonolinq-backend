<?php

namespace App\Traits;

use Illuminate\Support\Facades\Http;

trait NotificationTrait
{
    /**
     * Send Notification using Firebase v1 API (No Google Client dependency)
     *
     * @param array  $tokens  List of device tokens
     * @param string $title   Notification title
     * @param string $body    Notification body
     * @param int    $count   Additional count data
     * @return array|bool
     */
    public function sendNotification($tokens, string $title, string $body, int $count = 0)
    {
        // Load Service Account
        $serviceAccountPath = storage_path('app/firebase/firebase_service_account.json');
        $serviceAccount = json_decode(file_get_contents($serviceAccountPath), true);

        $projectId   = $serviceAccount['project_id'];
        $privateKey  = $serviceAccount['private_key'];
        $clientEmail = $serviceAccount['client_email'];

        // Create JWT
        $now = time();
        $jwtHeader = rtrim(strtr(base64_encode(json_encode(['alg' => 'RS256', 'typ' => 'JWT'])), '+/', '-_'), '=');
        $jwtClaimSet = rtrim(strtr(base64_encode(json_encode([
            'iss'   => $clientEmail,
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud'   => 'https://oauth2.googleapis.com/token',
            'exp'   => $now + 3600,
            'iat'   => $now
        ])), '+/', '-_'), '=');

        $signatureInput = $jwtHeader . '.' . $jwtClaimSet;
        openssl_sign($signatureInput, $signature, $privateKey, 'sha256');
        $jwt = $signatureInput . '.' . rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');

        // Exchange JWT for Access Token
        $tokenResponse = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion'  => $jwt,
        ]);

        if ($tokenResponse->failed()) {
            \Log::error('Token Request Failed', ['error' => $tokenResponse->body()]);
            return false;
        }

        $accessToken = $tokenResponse->json()['access_token'];

        // Firebase v1 API endpoint
        $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";

        $allResponses = [];

        foreach ($tokens as $token) {
            $payload = [
                'message' => [
                    'token' => $token,
                    'notification' => [
                        'title' => $title,
                        'body'  => $body,
                        'image'  => "https://sonolinq.com/assets/img/favicon.png"
                    ],
                    'data' => [
                        'count' => (string) $count,
                    ],
                ]
            ];

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type'  => 'application/json',
            ])->post($url, $payload);

            $allResponses[] = [
                'token'    => $token,
                'success'  => $response->successful(),
                'response' => $response->json(),
            ];

            if ($response->failed()) {
                \Log::error('FCM Send Failed', [
                    'token' => $token,
                    'error' => $response->body()
                ]);
            }
        }

        return $allResponses;
    }
}