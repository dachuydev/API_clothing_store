<?php

namespace App\Services\Auth;

use Google_Client;

class GoogleAuthService
{
    private $client;

    public function __construct()
    {
        $this->client = new Google_Client(['client_id' => config('services.google.client_id')]);
    }

    public function verifyToken($token)
    {
        $payload = $this->client->verifyIdToken($token);
        
        if (!$payload) return null;

        return [
            'email' => $payload['email'],
            'name' => $payload['name'],
            'avatar' => $payload['picture'] ?? null,
            'provider_id' => $payload['sub']
        ];
    }
} 