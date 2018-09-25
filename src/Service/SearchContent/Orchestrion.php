<?php

namespace App\Service\SearchContent;

use App\Service\Helpers\ArrayHelper;
use App\Service\Helpers\SearchHelper;

class Orchestrion
{
    use ArrayHelper;
    use SearchHelper;

    const FIELDS = [
        'Name_%s',
        'Description_%s',
        'OrchestrionUiparam.ID',
        'OrchestrionUiparam.OrchestrionCategory.ID',
        'OrchestrionUiparam.OrchestrionCategory.Name_%s',
        'OrchestrionUiparam.Order',
    ];
}
