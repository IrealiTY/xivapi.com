<?php

namespace App\Service\Search;

use App\Service\Common\Environment;

class SearchContent
{
    const LIST_DEFAULT = [
        'Achievement', 'Title',
        'Action', 'CraftAction', 'Trait', 'PvPAction', 'PvPTrait', 'Status',
        'BNpcName', 'ENpcResident',
        'Companion', 'Mount',
        'Leve',
        'Emote',
        'InstanceContent',
        'Item', 'Recipe',
        'Fate',
        'Quest',
    ];
    
    const LIST = [
        'Achievement', 'Title',
        'Action', 'CraftAction', 'Trait', 'PvPAction', 'PvPTrait', 'Status',
        'BNpcName', 'ENpcResident',
        'Companion', 'Mount',
        'Leve',
        'Emote',
        'InstanceContent',
        'Item', 'Recipe',
        'Fate',
        'Quest',
        
        // non default
        'Balloon',
        'BuddyEquip',
        'Orchestrion',
        'PlaceName',
        'Weather',
        'World'
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
    
    public static function prefix($index)
    {
        if (is_array($index)) {
            foreach ($index as $i => $x) {
                $index[$i] = self::prefix($x);
            }
            
            return $index;
        }
        
        return sprintf('%s_%s', constant(Environment::CONSTANT), $index);
    }
}
