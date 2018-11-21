<?php

namespace App\Service\Lodestone;

use App\Entity\Entity;
use App\Entity\PvPTeam;
use App\Service\Content\LodestoneData;
use App\Service\Service;

class PvPTeamService extends Service
{
    public function get($id)
    {
        /** @var PvPTeam $ent */
        $ent = $this->getRepository(PvPTeam::class)->find($id);
        return $ent ? $this->fetch($ent) : $this->register($id);
    }
    
    public function fetch(PvPTeam $ent): array
    {
        if ($ent->getState() === Entity::STATE_CACHED) {
            $data = LodestoneData::load('pvpteam', 'data', $ent->getId());
        }
    
        return [ $ent, $data[0] ?? null, $data[1] ?? null ];
    }
    
    public function register($id): array
    {
        $ent = new PvPTeam($id);
        $this->persist($ent);
        return [ $ent, null ];
    }
    
    public function delete(PvPTeam $ent)
    {
        $path = LodestoneData::folder('pvpteam', $ent->getId());
        @unlink("{$path}/data.json");
        $this->remove($ent);
    }
}
