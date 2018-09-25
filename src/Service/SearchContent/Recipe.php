<?php

namespace App\Service\SearchContent;

use App\Service\Helpers\ArrayHelper;
use App\Service\Helpers\SearchHelper;

class Recipe
{
    use ArrayHelper;
    use SearchHelper;

    const FIELDS = [
        'Name_%s',

        // Class Job
        'ClassJob.ID',
        'ClassJob.Name_%s',
        'ItemResult.ID',
        
        'AmountResult',
        'CanHq',
        'CanQuickSynth',
        'DifficultyFactor',
        'DurabilityFactor',
        'ExpRewarded',
        'IsSecondary',
        'IsSpecializationRequired',
        'QualityFactor',
        'QuickSynthControl',
        'QuickSynthCraftsmanship',
        'RequiredControl',
        'RequiredCraftsmanship',
        
        // sure this is linked content
        'SecretRecipeBook.ID',
        'SecretRecipeBook.Name_%s',
        'StatusRequired.ID',
        
        // Recipe level table
        'RecipeLevelTable.ClassJobLevel',
        'RecipeLevelTable.Difficulty',
        'RecipeLevelTable.Durability',
        'RecipeLevelTable.Quality',
        'RecipeLevelTable.Stars'
    ];
}
