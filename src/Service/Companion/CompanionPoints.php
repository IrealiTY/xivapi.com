<?php

namespace App\Service\Companion;

class CompanionPoints extends Companion
{
    public function getStatus()
    {
        [$data] = $this->request(
            new CompanionRequest('GET', Companion::ENDPOINT_DC, "/points/status")
        );
        
        return $data;
    }
}
