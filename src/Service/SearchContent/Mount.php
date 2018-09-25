<?php

namespace App\Service\SearchContent;

use App\Service\Helpers\ArrayHelper;
use App\Service\Helpers\SearchHelper;

class Mount
{
    use ArrayHelper;
    use SearchHelper;

    const FIELDS = [
        'Name_%s',
        
        'FlyingCondition',
        'IsFlying',
    ];
}
