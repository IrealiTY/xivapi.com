<?php

namespace App\Service\SearchContent;

use App\Service\Helpers\ArrayHelper;
use App\Service\Helpers\SearchHelper;

class Status
{
    use ArrayHelper;
    use SearchHelper;

    const FIELDS = [
        'Name_%s',
        'Description_%s',
        
        'MaxStacks',
        'Transfiguration',
        'LockMovement',
        'LockControl',
        'LockActions',
        'IsPermanent',
        'IsFcBuff',
        'Invisibility',
        'InflictedByActor',
        'CanDispel',
        'Category'
    ];
}
