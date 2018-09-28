<?php

namespace App\Service\Companion;

use App\Service\Content\ContentMinified;
use App\Service\Common\Language;
use App\Service\Redis\Cache;

class CompanionMarket extends Companion
{
    /** @var Cache */
    private $cache;

    public function __construct(Cache $cache)
    {
        $this->cache = $cache;
    }
    
    /**
     * Return search categories
     */
    public function getSearchCategories()
    {
        $ItemSearchCategory = [];
        $ItemUICategory = [];
        
        foreach ($this->cache->get('ids_ItemSearchCategory') as $id) {
            $category = $this->cache->get("xiv_ItemSearchCategory_{$id}");
            if (empty($category->Name_en)) {
                continue;
            }
            
            // Don't care much about these bits
            unset($category->ClassJob, $category->GameContentLinks);
            $ItemSearchCategory[] = $category;
        }
    
        foreach ($this->cache->get('ids_ItemUICategory') as $id) {
            $category = $this->cache->get("xiv_ItemUICategory_{$id}");
            if (empty($category->Name_en)) {
                continue;
            }
        
            // Don't care much about these bits
            unset($category->GameContentLinks);
            $ItemUICategory[] = $category;
        }

        return [
            'ItemSearchCategory' => $ItemSearchCategory,
            'ItemUICategory' => $ItemUICategory
        ];
    }

    /**
     * Get the Market Board prices for a specific item
     */
    public function getItemMarketData(int $itemId): ?CompanionResponse
    {
        $key = "xiv_Item_{$itemId}";

        // grab item
        $item = $this->cache->get($key);
        $market = $this->cache->get("{$key}_market");

        if (!$item) {
            return null;
        }

        // if cached
        if ($market) {
            return new CompanionResponse(
                [
                    'Market' => $market,
                    'Item'   => $item,
                ],
                true,
                $this->cache->getTtl("{$key}_market"),
                0,
                0
            );
        }

        // request market data
        [$data, $speed, $attempts] = $this->request(
            new CompanionRequest(Companion::ENDPOINT_DC, "/market/items/catalog/{$itemId}")
        );
        
        // enrich the market response
        $data = $this->enrichMarketResponse($data);

        // cache market data
        $this->cache->set("{$key}_market", $data, Companion::CACHE_TIME);

        // build response
        return new CompanionResponse(
            [
                'Market' => $data,
                'Item'   => $item,
            ],
            false,
            0,
            $speed,
            $attempts
        );
    }
    
    /**
     * Enriches the market response by improving data.
     */
    private function enrichMarketResponse($data)
    {
        $obj = (Object)[
            'Lodestone' => (Object)[
                'ID' => null,
                'Icon' => null,
                'IconHq' => null,
            ],
            'Listings' => []
        ];
        
        $obj->Lodestone->ID = $data['eorzeadbItemId'];
        $obj->Lodestone->Icon = $data['icon'];
        $obj->Lodestone->IconHq = $data['iconHq'];
        
        foreach ($data['entries'] as $row) {
            $quantity = $row['stack'];
            $itemId   = $row['catalogId'];
            $sigName  = $row['signatureName'];
            $isCrafted= $row['isCrafted'];
            $hq       = $row['hq'];
            $stain    = $row['stain'];
            $price    = $row['sellPrice'];
            $retainer = $row['sellRetainerName'];

            // Town can link
            $town = $this->cache->get("xiv_Town_{$row['registerTown']}");
            unset($town->GameContentLinks);
            
            // get real materia
            $materia = [];
            foreach ($row['materia'] as $mat) {
                $id     = $mat['key'];
                $grade  = intval($mat['grade']);
                $row    = $this->cache->get("xiv_Materia_{$id}");
                $item   = $row->{"Item{$grade}"};
                $materia[] = $item;
            }
            
            $obj->Listings[] = [
                'ItemID'         => $itemId,
                'Quantity'       => $quantity,
                'IsCrafted'      => $isCrafted,
                'CraftSignature' => $sigName,
                'IsHQ'           => $hq,
                'Stain'          => $stain,
                'Materia'        => $materia,
                'Price'          => $price,
                'RetainerName'   => $retainer,
                'Town'           => $town,
            ];
        }
        
        return $obj;
    }
}
