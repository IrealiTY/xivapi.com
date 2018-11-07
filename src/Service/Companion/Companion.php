<?php

namespace App\Service\Companion;

use App\Service\Redis\Cache;
use Companion\CompanionApi;

class Companion
{
    /** @var Cache */
    private $cache;
    /** @var CompanionApi */
    private $api;
    
    public function __construct()
    {
        $this->cache = new Cache();
    }
    
    /**
     * Set server for all DC requests
     */
    public function setServer(string $server): Companion
    {
        $server    = ucwords($server);
        $this->api = new CompanionApi("xivapi_{$server}");
        
        return $this;
    }
    
    public function getItemPrices($itemId)
    {
        $item = $this->cache->get("xiv_Item_{$itemId}");
        if (!$item) {
            throw new \Exception("Could not find item: {$itemId}");
        }
        
        $response = $this->api->Market()->getItemMarketListings($itemId);
        
        // build prices
        $prices = [];
        foreach ($response->entries as $row) {
            // Town can link
            $town = $this->cache->get("xiv_Town_{$row->registerTown}");
            unset($town->GameContentLinks);
            unset($town->IconID);
    
            // get real materia
            $materia = [];
            foreach ($row->materia as $mat) {
                $row  = $this->cache->get("xiv_Materia_{$mat->key}");
                $item = $row->{"Item{$mat->grade}"};
                $materia[] = $item;
            }
    
            $prices[] = [
                'ItemID'         => $itemId,
                'Materia'        => $materia,
                'Quantity'       => $row->stack,
                'IsCrafted'      => $row->isCrafted,
                'CraftSignature' => $row->signatureName,
                'IsHQ'           => $row->hq,
                'Stain'          => $row->stain,
                'Price'          => $row->sellPrice,
                'RetainerName'   => $row->sellRetainerName,
                'Town'           => $town,
            ];
        }
        
        // build response
        return [
            'Item' => [
                'ID' => $item->ID,
                'Icon' => $item->Icon,
                'Rarity' => $item->Rarity,
                'Name_en' => $item->Name_en,
                'Name_fr' => $item->Name_fr,
                'Name_de' => $item->Name_de,
                'Name_ja' => $item->Name_ja,
                'Url' => $item->Url,
            ],
            'Lodestone' => [
                'LodestoneId' => $response->eorzeadbItemId,
                'Icon' => $response->icon,
                'IconHq' => $response->iconHq,
            ],
            'Prices' => $prices,
        ];
    }
}
