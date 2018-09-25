<?php

namespace App\Service\SearchContent;

use App\Service\Helpers\ArrayHelper;
use App\Service\Helpers\SearchHelper;

class Leve
{
    use ArrayHelper;
    use SearchHelper;

    const FIELDS = [
        'Name_%s',
        'Description_%s',
        'ClassJobCategory.Name_%s',
        
        'AllowanceCost',
        'ClassJobLevel',
        'ExpReward',
        'GilReward',

        'JournalGenre.ID',
        'JournalGenre.Name_%s',
        'JournalGenre.JournalCategory.ID',
        'JournalGenre.JournalCategory.Name_%s',
        
        'LeveAssignmentType.ID',
        'LeveClient.ID',
        'PlaceNameIssued.ID',
        'PlaceNameStart.ID',
        'PlaceNameStartZone.ID',
    ];
}
