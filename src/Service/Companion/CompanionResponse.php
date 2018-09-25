<?php

namespace App\Service\Companion;

class CompanionResponse
{
    public $response = [];

    public function __construct($data, bool $cached, int $cacheExpires, int $speedms, int $queryCalls)
    {
        $this->response = [
            'Note' => 'THIS IS BETA, FOR PHOENIX (EU) SERVER ONLY, 10 MINUTE CACHE',
            'Payload' => $data,
            'QueryInformation' => [
                'SpeedMs'      => $speedms,
                'QueryCalls'   => $queryCalls,
                'Cached'       => $cached,
                'CacheExpires' => $cacheExpires
            ],
        ];
    
        $this->response = json_decode(json_encode($this->response));
    }
}
