<?php

namespace App\Service\ThirdParty;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Request;

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
        't'   => 'pageview',
    ];

    public static function getClient()
    {
        return new Client([
            'base_uri' => self::ENDPOINT,
            'timeout'  => self::TIMEOUT
        ]);
    }

    public static function record(Request $request)
    {
        self::hit($request->getPathInfo());
    }
    
    /**
     * Post a hit to Google Analytics
     */
    public static function hit(string $route): void
    {
        $options = self::OPTIONS;
        $options['dp']  = $route;
        $options['cid'] = Uuid::uuid4()->toString();
        $options['z']   = mt_rand(0,999999);

        try {
            self::getClient()->post('/collect', [
                RequestOptions::QUERY => $options
            ]);
        } catch (\Exception $ex) {
            // ignore
        }
    }
}
