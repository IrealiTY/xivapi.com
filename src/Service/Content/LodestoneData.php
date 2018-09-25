<?php

namespace App\Service\Content;

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
        return ContentMinified::mini(
            self::$cache->get($key)
        );
    }
    
    public static function findContent($category, $string)
    {
        return self::$content->{$category}->{Hash::hash($string)} ?? null;
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
                    $item->Materia[$m] = self::findContent('Item', $item->Name);
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
    
    /**
     * @deprecated
     * - This is not enabled at the moment, may consider deleting
     * Append on API data onto the character
     */
    /*
    public static function extendCharacterData($data)
    {
        //
        // Profile data
        //
        $data->Title = self::getContent("xiv_Title_{$data->Title}");
        $data->Race  = self::getContent("xiv_Race_{$data->Race}");
        $data->Tribe = self::getContent("xiv_Tribe_{$data->Tribe}");
        $data->Town  = self::getContent("xiv_Town_{$data->Town}");
        $data->GuardianDeity = self::getContent("xiv_GuardianDeity_{$data->GuardianDeity}");
        
        //
        // Grand Company
        //
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
        
        $gcRankQuestKeyArray = [
            null,
            "QuestMaelstrom",
            "QuestSerpents",
            "QuestFlames"
        ];
        
        $gcName = self::getContent(sprintf('xiv_GrandCompany_%s', $data->GrandCompany->NameID));
        $gcRank = self::getContent(sprintf($gcRankKeyArray[$data->GrandCompany->NameID], $data->GrandCompany->RankID));
        
        // grab correct icon and quest and provide a simplier result
        $gcRank->Icon  = $gcRank[ $gcRankIconKeyArray[$data->GrandCompany->NameID] ];
        $gcRank->Quest = $gcRank[ $gcRankQuestKeyArray[$data->GrandCompany->NameID] ];
        
        $data->GrandCompany = [
            'Company' => $gcName,
            'Rank'    => $gcRank
        ];
        
        //
        // Class Jobs
        //
        foreach ($data->ClassJobs as $key => $classJob) {
            $classJob->Class = self::getContent("xiv_ClassJob_{$classJob->ClassID}");
            $classJob->Job   = self::getContent("xiv_ClassJob_{$classJob->JobID}");
        }
        
        // Active class job
        $data->ActiveClassJob->Class = self::getContent("xiv_ClassJob_{$data->ActiveClassJob->ClassID}");
        $data->ActiveClassJob->Job   = self::getContent("xiv_ClassJob_{$data->ActiveClassJob->JobID}");
        
        //
        // Gear
        //
        foreach ($data->GearSet as $set) {
            $set->Class = self::getContent("xiv_ClassJob_{$set->ClassID}");
            $set->Job   = self::getContent("xiv_ClassJob_{$set->JobID}");
            
            unset($set->ClassID);
            unset($set->JobID);
            
            foreach ($set->Gear as $gear) {
                $gear->Item   = self::getContent("xiv_Item_{$gear->ID}");
                $gear->Mirage = !$gear->Mirage ?: self::getContent("xiv_Item_{$gear->Mirage}");
                $gear->Dye    = !$gear->Dye ?: self::getContent("xiv_Item_{$gear->Dye}");
                
                unset($gear->ID);
                
                if ($gear->Materia) {
                    foreach ($gear->Materia as $i => $materiaId) {
                        $gear->Materia[$i] = self::getContent("xiv_Item_{$materiaId}");
                    }
                }
            }
        }
        
        //
        // Minions and Mounts
        //
        foreach ($data->Minions as $i => $minionId) {
            $data->Minions[$i] = self::getContent("xiv_Companion_{$minionId}");
        }
        foreach ($data->Mounts as $i => $mountsId) {
            $data->Mounts[$i] = self::getContent("xiv_Mount_{$mountsId}");
        }
    }
    */
}
