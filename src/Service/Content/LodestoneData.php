<?php

namespace App\Service\Content;

use App\Service\Common\Arrays;
use App\Service\Common\Language;
use App\Service\Redis\Cache;
use Ramsey\Uuid\Uuid;

class LodestoneData
{
    /** @var Cache */
    private static $cache;

    /**
     * Save some lodestone data
     *
     * @param string $type
     * @param string $filename
     * @param $id
     * @param array $data
     */
    public static function save(string $type, string$filename, $id, $data)
    {
        file_put_contents(
            self::folder($type, $id) .'/'. $filename .'.json',
            json_encode($data)
        );
    }
    
    /**
     * Load some lodestone data
     *
     * @param string $type
     * @param string $filename
     * @param $id
     * @return mixed|null
     */
    public static function load(string $type, string $filename, $id)
    {
        $json = null;
        $jsonFilename = self::folder($type, $id) .'/'. $filename .'.json';

        if (file_exists($jsonFilename)) {
            $json = json_decode(
                file_get_contents($jsonFilename)
            );
        }

        return $json;
    }
    
    /**
     * Check for a storage folder, creates it on runtime.
     *
     * @param string $type
     * @param string $id
     * @return string
     */
    public static function folder(string $type, string $id)
    {
        $mount    = getenv('MOUNT_STORAGE');
        $idFolder = substr($id, -4);
        $folder   = "{$mount}/{$type}/{$idFolder}/{$id}";
        
        if (!is_dir($folder)) {
            mkdir($folder, 0777, true);
        }
        
        return $folder;
    }
    
    #-------------------------------------------------------------------------------------------------------------------
    
    // todo - below should be in its own file
    
    public static function getContent($key)
    {
        if (self::$cache === null) {
            self::$cache = new Cache();
        }

        return self::$cache->get($key) ?: null;
    }
    
    public static function extendCharacterDataHandler($name, $data, $fields)
    {
        if (self::$cache === null) {
            self::$cache = new Cache();
        }

        // grab content and ensure it's an array
        $content = self::$cache->get("xiv_{$name}_". $data->{$name});

        if (!$content) {
            return;
        }

        $data->{$name} = self::extendCharacterDataHandlerSimple($content, $fields);
    }

    public static function extendCharacterDataHandlerSimple($content, $fields)
    {
        $content = json_decode(json_encode($content), true);

        if (!$content) {
            return null;
        }

        // build new array using fields
        $arr = [];
        foreach ($fields as $field) {
            // replace gender and language tags
            $field = str_replace('[LANG]', Language::current(), $field);

            // grab field
            $arr[$field] = Arrays::getArrayValueFromDotNotation($content, $field);

            // replace any _[lang] with non lang ones
            if (substr_count($field, '_') > 0) {
                $value = $arr[$field];
                unset($arr[$field]);
                
                $field = substr($field, 0, -3);
                $arr[$field] = $value;
            }
            
            if (substr_count($field, '.') > 0) {
                Arrays::handleDotNotationToArray($arr, $field, $arr[$field]);
                unset($arr[$field]);
            }
        }
    
        return json_decode(json_encode($arr));
    }
    
    /**
     * - This is not enabled at the moment, may consider deleting
     * Append on API data onto the character
     */
    public static function extendCharacterData($data)
    {
        if ($data == null) {
            return;
        }
        
        self::extendCharacterDataHandler('Title', $data, [
            "ID",
            "Icon",
            "Url",
            "Name_[LANG]",
            "NameFemale_[LANG]"
        ]);

        self::extendCharacterDataHandler('Race', $data, [
            "ID",
            "Url",
            "Name_[LANG]",
            "NameFemale_[LANG]"
        ]);

        self::extendCharacterDataHandler('Tribe', $data, [
            "ID",
            "Icon",
            "Url",
            "Name_[LANG]",
            "NameFemale_[LANG]"
        ]);

        self::extendCharacterDataHandler('Town', $data, [
            "ID",
            "Url",
            "Icon",
            "Name_[LANG]"
        ]);

        self::extendCharacterDataHandler('GuardianDeity', $data, [
            "ID",
            "Url",
            "Icon",
            "Name_[LANG]",
            "GuardianDeity_[LANG]"
        ]);

        //
        // Fix some female specifics
        //
        if ($data->Gender == 2) {
            // replace male with female value
            $data->Title->Name = $data->Title->NameFemale;
            $data->Race->Name  = $data->Race->NameFemale;
            $data->Tribe->Name = $data->Tribe->NameFemale;
        }

        // remove female values
        unset(
            $data->Title->NameFemale,
            $data->Race->NameFemale,
            $data->Tribe->NameFemale
        );

        //
        // Grand Company
        //
        $data->GenderID = $data->Gender;
        $gcGender = $data->Gender == 2 ? 'Female' : 'Male';
        
        $gcRankKeyArray = [
            null,
            "xiv_GCRankLimsa{$gcGender}Text_%s",
            "xiv_GCRankGridania{$gcGender}Text_%s",
            "xiv_GCRankUldah{$gcGender}Text_%s"
        ];
        
        $gcRankIconKeyArray = [
            null,
            "IconMaelstrom",
            "IconSerpents",
            "IconFlames"
        ];

        if (isset($data->GrandCompany->NameID) && !isset($data->GrandCompany->RankID)) {
            throw new \Exception('Fatal error: Grand Company Name ID found but Rank ID not found');
        }

        if (isset($data->GrandCompany->NameID) && isset($data->GrandCompany->RankID)) {
            $gcName = self::extendCharacterDataHandlerSimple(
                self::getContent("xiv_GrandCompany_{$data->GrandCompany->NameID}"),
                [
                    'ID',
                    'Url',
                    'Name_[LANG]',
                ]
            );

            $gcRankName = self::extendCharacterDataHandlerSimple(
                self::getContent(sprintf($gcRankKeyArray[$data->GrandCompany->NameID], $data->GrandCompany->RankID)),
                [
                    'ID',
                    'Url',
                    'Name_[LANG]',
                ]
            );

            $gcRank = self::getContent("xiv_GrandCompanyRank_{$data->GrandCompany->RankID}");
            $gcRankName->Icon = $gcRank->{$gcRankIconKeyArray[$data->GrandCompany->NameID]};
            unset($gcRank);
        }
        
        $data->GrandCompany = [
            'Company' => $gcName ?? null,
            'Rank'    => $gcRankName ?? null
        ];
        
        //
        // Class Jobs
        //
        foreach ($data->ClassJobs as $key => $classJob) {
            $classJob->Class = self::extendCharacterDataHandlerSimple(
                self::getContent("xiv_ClassJob_{$classJob->ClassID}"), [
                    'ID',
                    'Icon',
                    'Url',
                    'Name_[LANG]',
                    'Abbreviation_[LANG]',
                    'ClassJobCategory.ID',
                    'ClassJobCategory.Name_[LANG]',
                ]
            );

            $classJob->Job = self::extendCharacterDataHandlerSimple(
                self::getContent("xiv_ClassJob_{$classJob->JobID}"), [
                    'ID',
                    'Icon',
                    'Url',
                    'Name_[LANG]',
                    'Abbreviation_[LANG]',
                    'ClassJobCategory.ID',
                    'ClassJobCategory.Name_[LANG]',
                ]
            );

            unset($classJob->ClassID, $classJob->JobID);
        }
        
        //
        // Active class job
        //
        $data->ActiveClassJob->Class = self::extendCharacterDataHandlerSimple(
            self::getContent("xiv_ClassJob_{$data->ActiveClassJob->ClassID}"), [
                'ID',
                'Icon',
                'Url',
                'Name_[LANG]',
                'Abbreviation_[LANG]',
                'ClassJobCategory.ID',
                'ClassJobCategory.Name_[LANG]',
            ]
        );
        $data->ActiveClassJob->Job = self::extendCharacterDataHandlerSimple(
            self::getContent("xiv_ClassJob_{$data->ActiveClassJob->JobID}"), [
                'ID',
                'Icon',
                'Url',
                'Name_[LANG]',
                'Abbreviation_[LANG]',
            ]
        );

        unset($data->ActiveClassJob->ClassID, $data->ActiveClassJob->JobID);

        //
        // Gear ClassJob
        //
    
        $data->GearSet->Class = self::extendCharacterDataHandlerSimple(
            self::getContent("xiv_ClassJob_{$data->GearSet->ClassID}"), [
                'ID',
                'Icon',
                'Url',
                'Name_[LANG]',
                'Abbreviation_[LANG]',
            ]
        );
        $data->GearSet->Job = self::extendCharacterDataHandlerSimple(
            self::getContent("xiv_ClassJob_{$data->GearSet->JobID}"), [
                'ID',
                'Icon',
                'Url',
                'Name_[LANG]',
                'Abbreviation_[LANG]',
            ]
        );
        unset(
            $data->GearSet->ClassID,
            $data->GearSet->JobID
        );
        
        //
        // Gear Attributes
        //
        foreach ($data->GearSet->Attributes as $id => $value) {
            $attr = self::extendCharacterDataHandlerSimple(
                self::getContent("xiv_BaseParam_{$id}"),
                [
                    'ID',
                    'Name_[LANG]',
                ]
            );

            $data->GearSet->Attributes[$id] = [
                'Attribute' => $attr,
                'Value' => $value
            ];
        }

        $data->GearSet->Attributes = array_values((array)$data->GearSet->Attributes);

        //
        // Gear Items
        //
        foreach ($data->GearSet->Gear as $slot => $gear) {
            // item
            $gear->Item = self::extendCharacterDataHandlerSimple(
                self::getContent("xiv_Item_{$gear->ID}"),
                [
                    'ID',
                    'Icon',
                    'Name_[LANG]',
                    'LevelEquip',
                    'LevelItem',
                    'Rarity',
                    'ItemUICategory.ID',
                    'ItemUICategory.Name_[LANG]',
                    'ClassJobCategory.ID',
                    'ClassJobCategory.Name_[LANG]',
                ]
            );

            // mirage
            $gear->Mirage = $gear->Mirage ? self::extendCharacterDataHandlerSimple(
                self::getContent("xiv_Item_{$gear->Mirage}"),
                [
                    'ID',
                    'Icon',
                    'Name_[LANG]',
                ]
            ) : null;

            // dyes
            $gear->Dye = $gear->Dye ? self::extendCharacterDataHandlerSimple(
                self::getContent("xiv_Item_{$gear->Dye}"),
                [
                    'ID',
                    'Icon',
                    'Name_[LANG]',
                ]
            ) : null;

            // materia
            foreach ($gear->Materia as $i => $materia) {
                $gear->Materia[$i] = self::extendCharacterDataHandlerSimple(
                    self::getContent("xiv_Item_{$materia}"), [
                        'ID',
                        'Icon',
                        'Url',
                        'Name_[LANG]',
                    ]
                );
            }

            unset($gear->ID);
        }
        
        //
        // Minions and Mounts
        //
        foreach ($data->Minions as $i => $minionId) {
            $data->Minions[$i] = self::extendCharacterDataHandlerSimple(
                self::getContent("xiv_Companion_{$minionId}"), [
                    'ID',
                    'Icon',
                    'IconSmall',
                    'Url',
                    'Name_[LANG]',
                ]
            );
        }
        foreach ($data->Mounts as $i => $mountsId) {
            $data->Mounts[$i] = self::extendCharacterDataHandlerSimple(
                self::getContent("xiv_Mount_{$mountsId}"), [
                    'ID',
                    'Icon',
                    'IconSmall',
                    'Url',
                    'Name_[LANG]',
                ]
            );
        }

        //
        // STATZ
        //

        if (!$totals = self::$cache->get(__METHOD__.'_MIN_MNT_COUNT')) {
            $totalMinions = 0;
            $totalMounts  = 0;
            foreach (self::$cache->get("ids_Companion") as $id) {
                $content = self::$cache->get("xiv_Companion_{$id}");
                if ($content->IconID > 0) {
                    $totalMinions++;
                }
            }
            foreach (self::$cache->get("ids_Mount") as $id) {
                $content = self::$cache->get("xiv_Mount_{$id}");
                if ($content->IconID > 0) {
                    $totalMounts++;
                }
            }

            $totals = [$totalMinions, $totalMounts];
            self::$cache->set(__METHOD__.'_MIN_MNT_COUNT', $totals, (60*60*24));
        }

        $data->MinionsTotal    = $totals[0];
        $data->MinionsCount    = count($data->Minions);
        $data->MinionsProgress = $data->MinionsCount > 0 ? round($data->MinionsCount / $data->MinionsTotal, 3) * 100 : 0;
        $data->MountsTotal     = $totals[1];
        $data->MountsCount     = count($data->Mounts);
        $data->MountsProgress  = $data->MountsCount > 0 ? round($data->MountsCount / $data->MountsTotal, 3) * 100 : 0;
    }
    
    public static function extendAchievementData($achievements)
    {
        if (!isset($achievements->List) || empty($achievements->List)) {
            return null;
        }
        
        foreach ($achievements->List as $i => $achievement) {
            $achievements->List[$i] = self::extendCharacterDataHandlerSimple(
                self::getContent("xiv_Achievement_{$achievement->ID}"),
                [
                    "ID",
                    "Name_[LANG]",
                    "Points",
                    "Icon",
                ]
            );
    
            $achievements->List[$i]->Date = $achievement->Date;
        }
    }
}
