<?php

namespace App\Service\LodestoneAutoManagers;

use App\Entity\Entity;
use App\Entity\Linkshell;
use App\Repository\LinkshellRepository;
use App\Service\Content\LodestoneData;
use App\Service\Lodestone\ServiceQueues;
use Lodestone\Exceptions\GenericException;

class AutoLinkshellManager extends LodestoneManager
{
    public function handleAddedLinkshell()
    {
        $this->io->text(__METHOD__);
        
        /** @var LinkshellRepository $repo */
        $repo = $this->getRepository(Linkshell::class);
    
        // get queue object and grab response
        $obj = $this->getQueueObject(ServiceQueues::CACHE_LINKSHELL_QUEUE . '_add');
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
                LodestoneData::save('linkshell', 'data', $row->id, $row->data);
                $this->persist($ent->setState(Entity::STATE_CACHED)->setUpdated(time()));
            }
        }
    
        $this->cache->set($obj->request->key, $repo->findNewLinkshells(), 300);
        return $this;
    }
    
    public function handleUpdatedLinkshell()
    {
        $this->io->text(__METHOD__);
        
        /** @var LinkshellRepository $repo */
        $repo = $this->getRepository(Linkshell::class);
    
        // set repository, get queue object and grab response
        $obj = $this->getQueueObject(ServiceQueues::CACHE_LINKSHELL_QUEUE);
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
                LodestoneData::save('linkshell', 'data', $row->id, $row->data);
                $this->persist($ent->setState(Entity::STATE_CACHED)->setUpdated(time()));
            }
        }
    
        $this->cache->set($obj->request->key, $repo->findLinkshellsToUpdate(), 300);
        return $this;
    }
}
