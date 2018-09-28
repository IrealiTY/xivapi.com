<?php

namespace App\Service\Google;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Ramsey\Uuid\Uuid;

/**
 * Interact with Google Analytics
 * Guide: https://developers.google.com/analytics/devguides/collection/protocol/v1/devguide
 *
 * Route tracking needs to be done manually to avoid capturing PID URLs or
 * any URL that contains any form of ID (eg Dev Apps)
 */
class GoogleAnalytics
{
    const ENDPOINT = 'http://www.google-analytics.com';
    const TIMEOUT  = 3;
    const OPTIONS  = [
        'v'   => 1,
        'tid' => 'UA-125096878-1',
    ];

    public static function getClient()
    {
        return new Client([
            'base_uri' => self::ENDPOINT,
            'timeout'  => self::TIMEOUT
        ]);
    }
    
    /**
     * Post a hit to Google Analytics
     */
    public static function hit(array $routes): void
    {
        $options = self::OPTIONS;
        $options['t'] = 'pageview';
        $options['dp'] = "/". implode('/', $routes);
        
        self::post($options);
    }
    
    /**
     * Post an event to Google Analytics
     */
    public static function event(string $category, string $action, string $label = null, $value = null): void
    {
        $options = self::OPTIONS;
        $options['t'] = 'event';
        $options['ec'] = $category; // eg: video
        $options['ea'] = $action;   // eg: play
        
        if ($label) {
            $options['el'] = $label; // eg: dungeon guide
        }
        
        if ($value) {
            $options['ev'] = $value; // eg: 500 (seconds)
        }

        self::post($options);
    }
    
    /**
     * Process a post request
     */
    private static function post($options): void
    {
        $options['cid'] = Uuid::uuid4()->toString();
        $options['z'] = mt_rand(0,999999);
        
        try {
            self::getClient()->post('/collect', [
                RequestOptions::QUERY => $options
            ]);
        } catch (\Exception $ex) {
            // ignore
        }
    }
}
