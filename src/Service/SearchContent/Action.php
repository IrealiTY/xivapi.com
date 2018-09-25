<?php

namespace App\Service\SearchContent;

use App\Service\Helpers\ArrayHelper;
use App\Service\Helpers\SearchHelper;

class Action
{
    use ArrayHelper;
    use SearchHelper;

    const FIELDS = [
        'Name_%s',
        
        'CanTargetDead',
        'CanTargetFriendly',
        'CanTargetHostile',
        'CanTargetParty',
        'CanTargetSelf',

        'ClassJob.ID',
        'ClassJob.Name_%s',
        'ClassJobCategory.ID',
        'ClassJobCategory.Name_%s',
        'ClassJobLevel',

        'ActionCategory.ID',
        'ActionCategory.Name_%s',

        'IsPvP',
        'IsRoleAction',
        'PreservesCombo',
        'Range',
        'Recast100ms',
        'TargetArea',
    ];
}
