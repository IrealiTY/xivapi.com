<?php

namespace App\Service\Companion;

use App\Service\Redis\Cache;

trait CompanionEnrich
{
    /** @var Cache */
    protected $cache;
    
    public function __construct()
    {
        $this->cache = new Cache();
    }
    
    /**
     * Get better item info
     */
    protected function getEnrichedItem($itemId): array
    {
        $item = $this->cache->get("xiv_Item_{$itemId}");
    
        if (!$item) {
            throw new \Exception("Could not find item: {$itemId}");
        }
        
        return [
            'ID'        => $item->ID,
            'Icon'      => $item->Icon,
            'Rarity'    => $item->Rarity,
            'Name_en'   => $item->Name_en,
            'Name_fr'   => $item->Name_fr,
            'Name_de'   => $item->Name_de,
            'Name_ja'   => $item->Name_ja,
            'Url'       => $item->Url,
        ];
    }
    
    /**
     * Get better town info
     */
    protected function getEnrichedTown($townId)
    {
        $town = $this->cache->get("xiv_Town_{$townId}");
        unset($town->GameContentLinks);
        unset($town->IconID);
 
        return $town;
    }
    
    /**
     * Get better materia data
     */
    protected function getEnrichedMateria(array $materia): array
    {
        $arr = [];
        foreach ($materia as $mat) {
            $row  = $this->cache->get("xiv_Materia_{$mat->key}");
            $item = $row->{"Item{$mat->grade}"};
            $arr[] = $item;
        }
        
        return $arr;
    }
}