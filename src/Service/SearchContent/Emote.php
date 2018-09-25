<?php

namespace App\Service\SearchContent;

use App\Service\Helpers\ArrayHelper;
use App\Service\Helpers\SearchHelper;

class Emote
{
    use ArrayHelper;
    use SearchHelper;

    const FIELDS = [
        'Name_%s',
        
        'EmoteCategory.ID',
        'EmoteCategory.Name_%s',
        
        'TextCommand.ID',
        'TextCommand.Command_%s'
    ];
}
