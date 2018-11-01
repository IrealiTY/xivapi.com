<?php

namespace App\Service\Companion;

class CompanionAuth extends Companion
{
    /**
     * Refresh the app token
     */
    public function refreshToken()
    {
        $payload = [
            'platform'  => 1,
            'uid'       => getenv('COMPANION_APP_UID')
        ];
        
        [$data] = $this->request(
            new CompanionRequest('POST', Companion::ENDPOINT, "/login/token", $payload, false)
        );
    
        $data['created'] = time();
        
        $this->cache->set(Companion::TOKEN_CACHE, $data, Companion::TOKEN_LENGTH);
    }
    
    /**
     * Not sure what this does? Validate a token?
     */
    public function fcmToken()
    {
        $payload = [
            'fcmToken' => getenv('COMPANION_APP_FCM')
        ];

        $this->request(
            new CompanionRequest('POST', Companion::ENDPOINT_DC, "/login/fcm-token", $payload)
        );
    }
    
    /**
     * Login to the current active character
     */
    public function loginCharacter()
    {
        $token = $this->cache->get(Companion::TOKEN_CACHE);
        
        [$data] = $this->request(
            new CompanionRequest('GET', Companion::ENDPOINT_DC, "/login/character")
        );
        
        // append character onto token info
        $token->character = $data;
    
        // cache token
        $this->cache->set(Companion::TOKEN_CACHE, $token, Companion::TOKEN_LENGTH);
    }
}
