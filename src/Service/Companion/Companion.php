<?php

namespace App\Service\Companion;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;

class Companion
{
    const ENDPOINT      = 'https://companion.finalfantasyxiv.com';
    const ENDPOINT_DC   = 'https://companion-eu.finalfantasyxiv.com';
    const VERSION_PATH  = '/sight-v060/sight';
    const CACHE_TIME    = 600; // 10 minutes
    const TIMEOUT_SEC   = 15; // 15 seconds
    const MAX_TRIES     = 15;
    const DELAY_MS      = 250000; // 250ms
    
    public static function setToken($token)
    {
        file_put_contents(__DIR__.'/token', trim($token));
    }
    
    public static function getToken()
    {
        return trim(file_get_contents(__DIR__.'/token'));
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
                $response = $client->get($request->apiRoute, $options);

                // if the response is 202, then we wait and try again
                if ($response->getStatusCode() == 202) {
                    usleep(self::DELAY_MS);
                    continue;
                }

                // get response
                $data  = json_decode((string)$response->getBody(), true);
                $speed = $this->getTimestamp() - $start;

                // return to API call
                return [ $data, $speed, $attempts ];
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
