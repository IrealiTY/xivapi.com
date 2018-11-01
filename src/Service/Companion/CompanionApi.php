<?php

namespace App\Service\Companion;

class CompanionApi
{
    public function auth()
    {
        return new CompanionAuth();
    }
    
    public function market()
    {
        return new CompanionMarket();
    }
    
    public function points()
    {
        return new CompanionPoints();
    }
}
