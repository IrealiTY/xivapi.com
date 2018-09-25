<?php

namespace App\Service\LodestoneAutoManagers;

use App\Entity\Entity;
use App\Entity\PvPTeam;
use App\Repository\PvPTeamRepository;
use App\Service\Content\LodestoneData;
use App\Service\Lodestone\ServiceQueues;
use Lodestone\Exceptions\GenericException;

class AutoPvpTeamManager extends LodestoneManager
{
    public function handleAddedPvpTeam()
    {
        $this->io->text(__METHOD__);
        
        /** @var PvPTeamRepository $repo */
        $repo = $this->getRepository(PvPTeam::class);
        
        // get queue object and grab response
        $obj = $this->getQueueObject(ServiceQueues::CACHE_PVPTEAM_QUEUE . '_add');
        $this->getResponseData($obj->response);
    
        // process incoming data
        if ($obj->response->data) {
            foreach ($obj->response->data as $row) {
                // todo handle this
                if (!isset($row->data)) {
                    continue;
                }
    
                if ($row->data === GenericException::class) {
                    // todo - idk what to do here ...
                    continue;
                }
    
                /** @var Entity $ent */
                $ent = $repo->find($row->id);
    
                // skip borked stuff
                if (!$ent) {
                    continue;
                };
    
                // save data
                LodestoneData::save('pvpteam', 'data', $row->id, $row->data);
                $this->persist($ent->setState(Entity::STATE_CACHED)->setUpdated(time()));
            }
        }
    
        $this->cache->set($obj->request->key, $repo->findNewPvPTeams(), 300);
        return $this;
    }
    
    public function handleUpdatedPvpTeam()
    {
        $this->io->text(__METHOD__);
        
        /** @var PvPTeamRepository $repo */
        $repo = $this->getRepository(PvPTeam::class);
        
        // set repository, get queue object and grab response
        $obj = $this->getQueueObject(ServiceQueues::CACHE_PVPTEAM_QUEUE);
        $this->getResponseData($obj->response);
    
        // process incoming data
        if ($obj->response->data) {
            foreach ($obj->response->data as $row) {
                // todo handle this
                if (!isset($row->data)) {
                    continue;
                }
    
                if ($row->data === GenericException::class) {
                    // todo - idk what to do here ...
                    continue;
                }
            
                /** @var Entity $ent */
                $ent = $repo->find($row->id);
    
                // skip borked stuff
                if (!$ent) {
                    continue;
                };
    
                // save data
                LodestoneData::save('pvpteam', 'data', $row->id, $row->data);
                $this->persist($ent->setState(Entity::STATE_CACHED)->setUpdated(time()));
            }
        }
    
        $this->cache->set($obj->request->key, $repo->findPvPTeamsToUpdate(), 300);
        return $this;
    }
}
