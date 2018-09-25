<?php

namespace App\Service\Search;

class SearchData
{
    // this file is generate when search is deployed
    const FILENAME = __DIR__.'/search_data.json';
    
    /** @var object */
    public static $data;
    
    public static function init()
    {
        if (!self::$data) {
            self::$data = json_decode(file_get_contents(self::FILENAME));
        }
    }
    
    /**
     * Get Search Index
     */
    public static function indexes()
    {
        self::init();
        return implode(',', self::$data->indexes);
    }
    
    /**
     * Get Search Views
     */
    public static function views($view = false)
    {
        self::init();
        return $view ? self::$data->views->{$view} : self::$data->views;
    }
    
    /**
     * Get Search Fields
     */
    public static function fields($view = false)
    {
        self::init();
        return $view ? self::$data->fields->{$view} : self::$data->fields;
    }
}
