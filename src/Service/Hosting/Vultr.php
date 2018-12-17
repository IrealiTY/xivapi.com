<?php

namespace App\Service\Hosting;

use App\Service\Common\Arrays;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\RequestOptions;

class Vultr
{
    private static function client(): Client
    {
        return new Client([
            RequestOptions::HEADERS => [
                'API-Key' => getenv('VULTR_API_KEY')
            ]
        ]);
    }
    
    public static function costs()
    {
        /** @var Response $response */
        $response = self::client()->get('https://api.vultr.com/v1/server/list');
        $servers  = json_decode($response->getBody());
        
        $list  = [];
        $total = 0;
        
        foreach ($servers as $server) {
            $total += (float)$server->cost_per_month;
            
            $list[] = [
                'os'         => $server->os,
                'ram'        => $server->ram,
                'location'   => $server->location,
                'cost'       => $server->cost_per_month,
                'name'       => $server->label,
            ];
        }
        
        Arrays::sortBySubKey($list, 'name', true);

        return [
            'servers' => $list,
            'total'   => $total,
        ];
    }
}
