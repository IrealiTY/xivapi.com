<?php

namespace App\Service\Content;

use App\Service\Common\Arrays;
use App\Service\Common\Language;
use App\Service\Redis\Cache;
use Ramsey\Uuid\Uuid;

class LodestoneData
{
    const CACHE_KEY = 'local_character_data';

    /** @var array */
    private static $content = [];
    /** @var Cache */
    private static $cache;
    
    public static function folder(string $type, string $id)
    {
        $folder = implode('/', [
            getenv('MOUNT_STORAGE'),
            $type,
            substr($id, -4),
            $id,
        ]);
        
        if (!is_dir($folder)) {
            mkdir($folder, 0777, true);
        }
        
        return $folder;
    }
    
    #-------------------------------------------------------------------------------------------------------------------
    
    public static function save($type, $filename, $id, $data)
    {
        file_put_contents(self::folder($type, $id) .'/'. $filename .'.json', json_encode($data));
        
        // this is just to update cache
        self::modified($type, $filename, $id);
    }

    public static function delete($type, $filename, $id)
    {
        @unlink(self::folder($type, $id) .'/'. $filename .'.json');
    }
    
    public static function load($type, $filename, $id)
    {
        return [
            json_decode(
                file_get_contents(self::folder($type, $id) .'/'. $filename .'.json') ?: ''
            ),
            self::modified($type, $filename, $id)
        ];
    }
    
    public static function modified($type, $filename, $id)
    {
        $filename = self::folder($type, $id) .'/'. $filename .'.json';
        
        $key      = 'content_modified_'. md5($filename);
        $cache    = new Cache();
        $hash     = sha1(trim(file_get_contents($filename)));
        $updated  = filemtime($filename);
        $modified = $cache->get($key) ?: null;
        
        // if no modified cached or hash has changed
        if ($modified == null || $hash != $modified[0]) {
            $modified = [ $hash, time() ];
            $cache->set($key, $modified, (60*60*24*999));
        }
        
        unset($cache);
        return [ $modified[1], $updated ];
    }
    
    #-------------------------------------------------------------------------------------------------------------------
    
    public static function initContentCache(Cache $cache)
    {
        self::$content = $cache->get(self::CACHE_KEY);
        self::$cache = $cache;
    }
    
    public static function getContent($key)
    {
        if (self::$cache === null) {
            self::$cache = new Cache();
        }

        return self::$cache->get($key);
    }
    
    public static function findContent($category, $string)
    {
        return self::$content->{$category}->{Hash::hash(trim($string))} ?? "[NOT FOUND]";
    }
    
    /**
     * Converts character data down to just raw ids
     */
    public static function convertCharacterData($data)
    {
        self::verification($data);
        
        //
        // ActiveClassJob
        //
        unset($data->ActiveClassJob->ClassName);
        unset($data->ActiveClassJob->JobName);
        
        //
        // Misc
        //
        $data->Gender        = $data->Gender == 'male' ? 1 : 2;
        $data->Town          = self::findContent('Town', $data->Town->Name);
        $data->GuardianDeity = self::findContent('GuardianDeity', $data->GuardianDeity->Name);
        $data->Race          = self::findContent('Race', $data->Race);
        $data->Tribe         = self::findContent('Tribe', $data->Tribe);
        $data->Title         = self::findContent('Title', $data->Title);
        
        //
        // Build gearset
        //
        $set = new \stdClass();
        $set->GearKey    = "{$data->ActiveClassJob->ClassID}_{$data->ActiveClassJob->JobID}";
        $set->ClassID    = $data->ActiveClassJob->ClassID;
        $set->JobID      = $data->ActiveClassJob->JobID;
        $set->Level      = $data->ActiveClassJob->Level;
        $set->Gear       = new \stdClass();
        $set->Attributes = [];
        
        //
        // Attributes
        //
        foreach ($data->Attributes as $attr) {
            $attr->Name = ($attr->Name === 'Critical Hit Rate') ? 'Critical Hit' : $attr->Name;
            $set->Attributes[self::findContent('BaseParam', $attr->Name)] = $attr->Value;
        }
        
        //
        // Gear
        //
        foreach ($data->Gear as $slot => $item) {
            $item->ID = self::findContent('Item', $item->Name);
            
            // has dye?
            if (isset($item->Dye) && $item->Dye) {
                $item->Dye = self::findContent('Item', $item->Dye->Name);
            }
            
            // has mirage?
            if (isset($item->Mirage) && $item->Mirage) {
                $item->Mirage = self::findContent('Item', $item->Mirage->Name);
            }
            
            // has materia?
            if (isset($item->Materia) && $item->Materia) {
                foreach ($item->Materia as $m => $materia) {
                    $item->Materia[$m] = self::findContent('Item', $materia->Name);
                }
            }
            
            // don't need these
            unset($item->Slot);
            unset($item->Name);
            unset($item->Category);
            
            $set->Gear->{$slot} = $item;
        }
        
        unset($data->Attributes);
        unset($data->Gear);
        $data->GearSet = $set;
        
        //
        // ClassJobs
        //
        foreach ($data->ClassJobs as $classJob) {
            unset($classJob->ClassName);
            unset($classJob->JobName);
        }
        
        //
        // Grand Company
        //
        if (isset($data->GrandCompany->Name)) {
            $town = [
                'Maelstrom' => 'GCRankLimsa',
                'Order of the Twin Adder' => 'GCRankGridania',
                'Immortal Flames' => 'GCRankUldah',
            ];
            
            $townSelected   = $town[$data->GrandCompany->Name];
            $genderSelected = $data->Gender == 1 ? 'Male' : 'Female';
            $rankDataSet    = "{$townSelected}{$genderSelected}Text";
            
            $data->GrandCompany->NameID = self::findContent('GrandCompany', $data->GrandCompany->Name);
            $data->GrandCompany->RankID = self::findContent($rankDataSet, $data->GrandCompany->Rank);
            
            unset($data->GrandCompany->Name);
            unset($data->GrandCompany->Rank);
            unset($data->GrandCompany->Icon);
        }
        
        //
        // Minions and Mounts
        //
        foreach ($data->Minions as $m => $minion) {
            $data->Minions[$m] = self::findContent('Companion', $minion->Name);

            // add all minions of light
            if (in_array($data->Minions[$m], [67,68,69,70])) {
                $data->Minions[] = 67;
                $data->Minions[] = 68;
                $data->Minions[] = 69;
                $data->Minions[] = 70;
            }

            // add all wind up leaders
            if (in_array($data->Minions[$m], [71,72,72,74])) {
                $data->Minions[] = 71;
                $data->Minions[] = 72;
                $data->Minions[] = 72;
                $data->Minions[] = 74;
            }
        }
        
        foreach ($data->Mounts as $m => $mount) {
            $data->Mounts[$m] = self::findContent('Mount', $mount->Name);
        }

        $data->Minions = array_values(array_unique($data->Minions));
        $data->Mounts = array_values(array_unique($data->Mounts));
        
        return $data;
    }
    
    /**
     * Add verification data onto a character
     */
    public static function verification($data)
    {
        //
        // Verification stuff, this changes once a day and can be used
        // by developers to verify ownership of a character. Do not leak!
        //
        $seed = 14305;
        $data->VerificationToken = str_ireplace('-', null, Uuid::uuid3(Uuid::NAMESPACE_DNS, $data->ID));
        $data->VerificationToken = date('zH') + $seed . $data->VerificationToken;
        $data->VerificationToken = 'XIV'. strtoupper(substr(sha1($data->VerificationToken), 10, -10)) .'API';
        $data->VerificationTokenPass = stripos($data->Bio, $data->VerificationToken) !== false;
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
            return [];
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
        
        $data->GrandCompany = [
            'Company' => $gcName,
            'Rank'    => $gcRankName
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
                ]
            );

            $classJob->Job = self::extendCharacterDataHandlerSimple(
                self::getContent("xiv_ClassJob_{$classJob->JobID}"), [
                    'ID',
                    'Icon',
                    'Url',
                    'Name_[LANG]',
                    'Abbreviation_[LANG]',
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

            $data->GearSet->Attributes->{$id} = [
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
}
