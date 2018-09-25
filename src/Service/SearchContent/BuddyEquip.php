<?php

namespace App\Service\SearchContent;

use App\Service\Helpers\ArrayHelper;
use App\Service\Helpers\SearchHelper;

class BuddyEquip
{
    use ArrayHelper;
    use SearchHelper;
    
    const FIELDS = [
        'Name_%s',

        'IconBody',
        'IconHead',
        'IconLegs',
        'Rarity',
        
        'GrandCompany.ID',
        'ModelBody',
        'ModelLegs',
        'ModelTop',
        'StartsWithVowel'
    ];
}
