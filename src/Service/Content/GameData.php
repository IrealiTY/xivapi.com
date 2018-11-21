<?php

namespace App\Service\Content;

use App\Service\Redis\Cache;

class GameData
{
    /** @var Cache */
    private static $cache = null;
    
    /**
     * Initialize cache
     */
    public static function init()
    {
        if (self::$cache === null) {
            self::$cache = new Cache();
        }
    }
    
    /**
     * get a single piece of content from the cache
     */
    public static function one($contentName, $contentId)
    {
        self::init();
        
        $content   = self::$cache->get("xiv_{$contentName}_{$contentId}");
        $secondary = self::$cache->get("xiv2_{$contentName}_{$contentId}") ?: [];
    
        if (!$content) {
            throw new \Exception("Game Data does not exist: {$contentName} {$contentId}");
        }
        
        // merge main and secondary content
        return (Object)array_merge(
            (array)$content,
            (array)$secondary
        );
    }
}
