<?php

namespace App\Service\SearchContent;

use App\Service\Helpers\ArrayHelper;
use App\Service\Helpers\SearchHelper;

class Fate
{
    use ArrayHelper;
    use SearchHelper;

    const FIELDS = [
        'Name_%s',
        'Description_%s',
        
        'ClassJobLevel',
        'ClassJobLevelMax',
    ];
}
