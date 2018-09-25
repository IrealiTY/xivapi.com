<?php

namespace App\Service\SearchContent;

use App\Service\Helpers\ArrayHelper;
use App\Service\Helpers\SearchHelper;

class InstanceContent
{
    use ArrayHelper;
    use SearchHelper;

    const FIELDS = [
        'Name_%s',

        // Currency
        'BossCurrencyA0',
        'BossCurrencyA1',
        'BossCurrencyA2',
        'BossCurrencyA3',
        'BossCurrencyA4',
        'BossCurrencyB0',
        'BossCurrencyB1',
        'BossCurrencyB2',
        'BossCurrencyB3',
        'BossCurrencyB4',
        'BossCurrencyC0',
        'BossCurrencyC1',
        'BossCurrencyC2',
        'BossCurrencyC3',
        'BossCurrencyC4',
        'FinalBossCurrencyA',
        'FinalBossCurrencyB',
        'FinalBossCurrencyC',
        
        'ContentFinderCondition.ClassJobLevelRequired',
        'ContentFinderCondition.ClassJobLevelSync',
        'ContentFinderCondition.ItemLevelRequired',
        'ContentFinderCondition.ItemLevelSync',
        
        'ContentType.ID',
        'ContentType.Name_%s',
        
        'ContentMemberType.HealersPerParty',
        'ContentMemberType.MeleesPerParty',
        'ContentMemberType.RangedPerParty',
        'ContentMemberType.TanksPerParty',
        
        'InstanceContentType.ID',
        'NewPlayerBonusA',
        'NewPlayerBonusB',
        'PartyCondition',
        'SortKey',
        'TimeLimitMin',
        'WeekRestriction',
        
        'TerritoryType.ID',
        'TerritoryType.PlaceName.ID',
        'TerritoryType.PlaceName.Name_%s',
        'TerritoryType.PlaceNameRegion.ID',
        'TerritoryType.PlaceNameRegion.Name_%s',
        'TerritoryType.PlaceNameZone.ID',
        'TerritoryType.PlaceNameZone.Name_%s',
    ];
}
