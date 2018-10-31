<?php

namespace App\Service\Companion;

use App\Service\Redis\Cache;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\RequestOptions;

class Companion
{
    const TOKEN_CACHE   = 'companion_app_token';
    const TOKEN_LENGTH  = (60*60*24*5);
    const ENDPOINT      = 'https://companion.finalfantasyxiv.com';
    const ENDPOINT_DC   = 'https://companion-eu.finalfantasyxiv.com';
    const VERSION_PATH  = '/sight-v060/sight';
    const TIMEOUT_SEC   = 15; // 15 seconds
    const MAX_TRIES     = 15;
    const DELAY_MS      = 500000; // 0.5s
    
    /** @var Cache */
    protected $cache;
    
    public function __construct()
    {
        $this->cache = new Cache();
    }
    
    public static function getToken()
    {
        return (new Cache())->get(self::TOKEN_CACHE);
    }

    /**
     * Request data from the companion app
     */
    protected function request(CompanionRequest $request): array
    {
        $client = new Client([
            'base_uri' => $request->baseUri,
            'timeout'  => self::TIMEOUT_SEC
        ]);

        try {
            // add headers
            $options = [
                RequestOptions::HEADERS => $request->headers
            ];

            // if a json payload exists, include it
            if ($request->json) {
                $options[RequestOptions::JSON] = $request->json;
            }
            
            $start = $this->getTimestamp();
            foreach (range(0, self::MAX_TRIES) as $attempts) {
                /** @var Response $response */
                $response = $client->{$request->getMethod()}($request->apiRoute, $options);

                // if the response is 202, then we wait and try again
                if ($response->getStatusCode() == 202) {
                    usleep(self::DELAY_MS);
                    continue;
                }
    
                if ($response->getStatusCode() == 200) {
                    // get response
                    $data = json_decode((string)$response->getBody(), true);
                    $speed = $this->getTimestamp() - $start;
    
                    // return to API call
                    return [$data, $speed, $attempts];
                }
            }
            
            throw new \Exception('Could not fetch data from Companion API');
        } catch (\Exception $ex) {
            throw $ex;
        }
    }

    protected function getTimestamp()
    {
        return round(microtime(true) * 1000);
    }
}
