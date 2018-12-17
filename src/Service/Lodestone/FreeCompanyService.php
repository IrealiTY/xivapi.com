<?php

namespace App\Service\Lodestone;

use App\Entity\Entity;
use App\Entity\FreeCompany;
use App\Service\Content\LodestoneData;
use App\Service\LodestoneQueue\FreeCompanyQueue;
use App\Service\Service;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;

class FreeCompanyService extends Service
{
    public function get($id)
    {
        /** @var FreeCompany $ent */
        $ent = $this->getRepository(FreeCompany::class)->find($id);
        return $ent ? $this->fetch($ent) : $this->register($id);
    }
    
    public function getMembers($id): array
    {
        /** @var FreeCompany $ent */
        $ent = $this->getRepository(FreeCompany::class)->find($id);
        
        if ($ent && $ent->getState() == Entity::STATE_CACHED) {
            $data = LodestoneData::load('freecompany', 'members', $id);
        }
    
        return [ $ent, $data[0] ?? null, $data[1] ?? null ];
    }
    
    public function fetch(FreeCompany $ent): array
    {
        if ($ent->getState() === FreeCompany::STATE_CACHED) {
            $data = LodestoneData::load('freecompany', 'data', $ent->getId());
        }
        
        return [ $ent, $data[0] ?? null, $data[1] ?? null ];
    }
    
    public function register($id): array
    {
        if (!is_numeric($id) || strlen($id) > 45) {
            throw new NotAcceptableHttpException("Invalid character id: {$id}");
        }
    
        // send a request to rabbit mq to add this character
        FreeCompanyQueue::request($id, 'free_company_add');
        
        return [ new FreeCompany($id), null, null ];
    }
    
    public function delete(FreeCompany $ent)
    {
        $path = LodestoneData::folder('freecompany', $ent->getId());
        @unlink("{$path}/data.json");
        $this->remove($ent);
    }
}
