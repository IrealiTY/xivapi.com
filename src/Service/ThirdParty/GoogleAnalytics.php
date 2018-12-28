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
    const ENDPOINT      = 'http://www.google-analytics.com';
    const TRACKKING_ID  = 'UA-125096878-1';
    const VERSION       = 1;
    const TIMEOUT       = 5;

    public static function getClient()
    {
        return new Client([
            'base_uri' => self::ENDPOINT,
            'timeout'  => self::TIMEOUT
        ]);
    }

    /**
     * @param array $options
     */
    public static function query(array $options)
    {
        try {
            self::getClient()->post('/collect', [
                RequestOptions::QUERY => $options
            ]);
        } catch (\Exception $ex) {
            // ignore
        }
    }
    
    /**
     * Post a hit to Google Analytics
     */
    public static function hit(string $url): void
    {
        self::query([
            't'   => 'pageview',
            'tid' => self::TRACKKING_ID,
            'v'   => self::VERSION,
            'cid' => Uuid::uuid4()->toString(),
            'dp'  => $url,
            'z'   => mt_rand(0, 999999),
        ]);
    }

    /**
     * Record an event
     */
    public static function event(): void
    {
        self::query([
            't'   => 'event',
            'tid' => self::TRACKKING_ID,
            'v'   => self::VERSION,
            'cid' => Uuid::uuid4()->toString(),
            'ec'  => 'Test_Category',
            'ea'  => 'Test_Action',
            'el'  => 'Test_Label',
            'ev'  => '10'
        ]);
    }
}
