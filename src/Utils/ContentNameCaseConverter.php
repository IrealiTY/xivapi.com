<?php

namespace App\Utils;

use App\Service\Redis\Cache;

class ContentNameCaseConverter
{
    public static function toUpperCase($string)
    {
        // force it lower, incase upper is provided anyway
        $string = strtolower($string);
        
        foreach ((new Cache())->get('content') as $name) {
            if ($string === strtolower($name)) {
                return $name;
            }
        }
        
        return false;
    }
}
