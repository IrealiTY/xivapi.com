<?php

namespace App\Service\SearchContent;

use App\Service\Helpers\ArrayHelper;
use App\Service\Helpers\SearchHelper;

class Companion
{
    use ArrayHelper;
    use SearchHelper;

    const FIELDS = [
        'Name_%s',
        
        'Behavior.ID',
        'Behavior.Name_%s',
        'MinionRace.ID',
        'MinionRace.Name_%s',
        
        'SkillAngle',
        'SkillCost',
        'Cost',
        'HP',
    ];
}
