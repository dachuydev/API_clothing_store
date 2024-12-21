<?php

namespace App\Services\Auth;

use GuzzleHttp\Client;
use Exception;
use Illuminate\Support\Facades\Log;

class GithubAuthService
{
    private $client;

    public function __construct()
    {
        $this->client = new Client();
    }

    public function verifyToken($token)
    {
        try {
            if (!str_starts_with($token, 'token ')) {
                $token = 'token ' . $token;
            }

            $userData = $this->getUserData($token);
            $primaryEmail = $this->getPrimaryEmail($token);

            return [
                'email' => $primaryEmail,
                'name' => $userData['name'] ?? $userData['login'],
                'avatar' => $userData['avatar_url'],
                'provider_id' => (string) $userData['id']
            ];
        } catch (Exception $e) {
            Log::error('GitHub verification failed: ' . $e->getMessage());
            throw new Exception('Token không hợp lệ');
        }
    }

    private function getUserData($token)
    {
        $response = $this->client->get('https://api.github.com/user', [
            'headers' => [
                'Authorization' => $token,
                'Accept' => 'application/json',
            ]
        ]);
        
        return json_decode($response->getBody(), true);
    }

    private function getPrimaryEmail($token)
    {
        $response = $this->client->get('https://api.github.com/user/emails', [
            'headers' => [
                'Authorization' => $token,
                'Accept' => 'application/json',
            ]
        ]);

        $emails = json_decode($response->getBody(), true);
        
        $primaryEmail = collect($emails)->first(function ($email) {
            return $email['primary'] === true;
        });

        return $primaryEmail ? $primaryEmail['email'] : null;
    }
} 