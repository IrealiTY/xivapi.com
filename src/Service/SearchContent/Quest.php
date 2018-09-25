<?php

namespace App\Service\SearchContent;

use App\Service\Helpers\ArrayHelper;
use App\Service\Helpers\SearchHelper;

class Quest
{
    use ArrayHelper;
    use SearchHelper;

    const FIELDS = [
        'Name_%s',
        'ExperiencePoints',
        'GilReward',
        'IconSpecial',
        
        'ClassJobCategory0.Name_%s',
        'ClassJobCategory1.Name_%s',
        'ClassJobLevel0',
        'ClassJobLevel1',
        
        // Linked Content
        'EmoteReward.ID',
        'PlaceName.ID',
        
        // Journal
        'JournalGenre.Name_%s',
        'JournalGenre.JournalCategory.Name_%s',
        
        // Tomestones
        'TomestoneCountReward',
        'TomestoneReward.ID',
    ];
}
