<?php

namespace App\Service\SearchContent;

use App\Service\Helpers\ArrayHelper;
use App\Service\Helpers\SearchHelper;

class ENpcResident
{
    use ArrayHelper;
    use SearchHelper;

    const FIELDS = [
        'Name_%s',
        'Title_%s'
    ];
}
