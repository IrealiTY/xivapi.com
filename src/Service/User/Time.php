<?php

namespace App\Service\User;

use App\Service\Common\GoogleAnalytics;
use GuzzleHttp\Client;
use Symfony\Component\HttpFoundation\Request;

/**
 * Handle the users time, this should only be
 * called on routes that need it as it does
 * a GET query to a third-party service.
 */
class Time
{
    private static $timezone = 'Europe/London';
    
    /**
     * Set the users timezone
     */
    public static function set(Request $request): void
    {
        // todo - move dev ip to env file
        $ip  = getenv('APP_ENV') === 'dev' ? getenv('DEV_IP') : $request->getClientIp();
        $geo = "http://ip-api.com/json/{$ip}?fields=timezone&lang=en";
        
        try {
            $start      = microtime(true);
            $client     = new Client([ 'timeout' => 1.5 ]);
            $response   = $client->get($geo);
            $json       = json_decode((string)$response->getBody());
            $duration   = microtime(true) - $start;
            
            self::$timezone = $json->timezone ?: self::$timezone;
        } catch (\Exception $ex) {
            // ignore
        }
    }
    
    /**
     * Get the users timezone
     */
    public static function get(): string
    {
        return self::$timezone;
    }
}
