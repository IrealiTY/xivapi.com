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

    public static function indexes()
    {
        return array_map('strtolower', self::LIST);
    }
}
