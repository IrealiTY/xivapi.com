<?php

namespace App\Service\SearchContent;

use App\Service\Helpers\ArrayHelper;
use App\Service\Helpers\SearchHelper;

class Item
{
    use ArrayHelper;
    use SearchHelper;
    
    const FIELDS = [
        // Base Attributes
        'BaseParamValue0',
        'BaseParamValue1',
        'BaseParamValue2',
        'BaseParamValue3',
        'BaseParamValue4',
        'BaseParamValue5',
        'BaseParam0.ID',
        'BaseParam1.ID',
        'BaseParam2.ID',
        'BaseParam3.ID',
        'BaseParam4.ID',
        'BaseParam5.ID',

        // Base Special Attributes
        'BaseParamValueSpecial0',
        'BaseParamValueSpecial1',
        'BaseParamValueSpecial2',
        'BaseParamValueSpecial3',
        'BaseParamValueSpecial4',
        'BaseParamValueSpecial5',
        'BaseParamSpecial0.ID',
        'BaseParamSpecial1.ID',
        'BaseParamSpecial2.ID',
        'BaseParamSpecial3.ID',
        'BaseParamSpecial4.ID',
        'BaseParamSpecial5.ID',

        // Stats
        'Block',
        'BlockRate',
        'DamageMag',
        'DamagePhys',
        'DefenseMag',
        'DefensePhys',
        'DelayMs',
        'CooldownS',

        // Text
        'Name_%s',
        'Description_%s',
        'ItemUICategory.ID',
        'ItemUICategory.Name_%s',
        'ItemSearchCategory.ID',
        'ItemSearchCategory.Name_%s',
        'ClassJobCategory.ID',
        'ClassJobCategory.Name_%s',
        'ItemKind.ID',
        'ItemKind.Name_%s',

        // Linked content
        'EquipSlotCategory.ID',
        'ItemAction.ID',
        'ItemSearchCategory.ID',
        'ItemSeries.ID',
        'ItemSpecialBonus.ID',
        'ItemSpecialBonusParam.ID',
        'ItemGlamour.ID',
        'ItemRepair.ID',
        

        // Numbers
        'LevelEquip',
        'LevelItem',
        'MateriaSlotCount',
        'MaterializeType',
        'ModelMain',
        'ModelSub',
        'Rarity',
        'StackSize',
        'Stain.ID',

        // Booleans
        'CanBeHq',
        'IsAdvancedMeldingPermitted',
        'IsCollectable',
        'IsCrestWorthy',
        'IsDyeable',
        'IsIndisposable',
        'IsPvP',
        'IsUnique',
        'IsUntradable',
        
        // class job categories
        'ClassJobUse.ID',
        'ClassJobRepair.ID',
        'ClassJobCategory.ID',
        'ClassJobCategory.ACN',
        'ClassJobCategory.ADV',
        'ClassJobCategory.ALC',
        'ClassJobCategory.ARC',
        'ClassJobCategory.ARM',
        'ClassJobCategory.AST',
        'ClassJobCategory.BLM',
        'ClassJobCategory.BRD',
        'ClassJobCategory.BSM',
        'ClassJobCategory.BTN',
        'ClassJobCategory.CNJ',
        'ClassJobCategory.CRP',
        'ClassJobCategory.CUL',
        'ClassJobCategory.DRG',
        'ClassJobCategory.DRK',
        'ClassJobCategory.FSH',
        'ClassJobCategory.GLA',
        'ClassJobCategory.GSM',
        'ClassJobCategory.LNC',
        'ClassJobCategory.LTW',
        'ClassJobCategory.MCH',
        'ClassJobCategory.MIN',
        'ClassJobCategory.MNK',
        'ClassJobCategory.MRD',
        'ClassJobCategory.NIN',
        'ClassJobCategory.PGL',
        'ClassJobCategory.PLD',
        'ClassJobCategory.RDM',
        'ClassJobCategory.ROG',
        'ClassJobCategory.SAM',
        'ClassJobCategory.SCH',
        'ClassJobCategory.SMN',
        'ClassJobCategory.THM',
        'ClassJobCategory.WAR',
        'ClassJobCategory.WHM',
        'ClassJobCategory.WVR',
    ];
}
