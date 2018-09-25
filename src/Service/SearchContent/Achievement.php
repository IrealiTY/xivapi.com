<?php

namespace App\Service\SearchContent;

use App\Service\Helpers\ArrayHelper;
use App\Service\Helpers\SearchHelper;

class Achievement
{
    use ArrayHelper;
    use SearchHelper;

    const FIELDS = [
        'Description_%s',
        'Name_%s',
        'Order',
        'Points',
        'Type',

        'AchievementCategory.ID',
        'AchievementCategory.Name_%s',
        'AchievementCategory.AchievementKind.ID',
        'AchievementCategory.AchievementKind.Name_%s',
    ];
}
