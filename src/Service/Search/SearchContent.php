<?php

namespace App\Service\Search;

class SearchContent
{
    const LIST = [
        'Achievement',
        'Action',
        'BNpcName',
        'BuddyEquip',
        'Companion',
        'Emote',
        'ENpcResident',
        'Fate',
        'InstanceContent',
        'Item',
        'Leve',
        'Mount',
        'Orchestrion',
        'PlaceName',
        'Quest',
        'Recipe',
        'Status',
        'Title',
        'Weather',
    ];
    
    /**
     * Validate a list of ElasticSearch indeces
     */
    public static function validate($list)
    {
        $valid = array_map('strtolower', self::LIST);
        
        foreach ($list as $i => $index) {
            if (!in_array($index, $valid)) {
                unset($list[$i]);
            }
        }
        
        return $list;
    }
}
