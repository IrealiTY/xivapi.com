<?php

namespace App\Service\Lodestone;

use App\Entity\Entity;
use App\Entity\FreeCompany;
use App\Service\Content\LodestoneData;
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
        if (!is_numeric($id)) {
            throw new NotAcceptableHttpException("ID is not numeric: {$id}");
        }
    
        if (strlen($id) > 42) {
            throw new NotAcceptableHttpException("ID length is too long");
        }
        
        $ent = new FreeCompany($id);
        $this->persist($ent);
        return [ $ent, null, null ];
    }
    
    public function delete(FreeCompany $ent)
    {
        $path = LodestoneData::folder('freecompany', $ent->getId());
        @unlink("{$path}/data.json");
        $this->remove($ent);
    }
}
