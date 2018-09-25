<?php

namespace App\Service\LodestoneAutoManagers;

use App\Entity\Entity;
use App\Entity\FreeCompany;
use App\Repository\FreeCompanyRepository;
use App\Service\Content\LodestoneData;
use App\Service\Lodestone\ServiceQueues;
use Lodestone\Exceptions\GenericException;

class AutoFreeCompanyManager extends LodestoneManager
{
    public function handleAddedFreeCompany()
    {
        $this->io->text(__METHOD__);
        
        /** @var FreeCompanyRepository $repo */
        $repo = $this->getRepository(FreeCompany::class);
    
        // get queue object and grab response
        $obj = $this->getQueueObject(ServiceQueues::CACHE_FREECOMPANY_QUEUE . '_add');
        $this->getResponseData($obj->response);
    
        // process incoming data
        if ($obj->response->data) {
            foreach ($obj->response->data as $row) {
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
                LodestoneData::save('freecompany', 'data', $row->id, $row->data);
                $this->persist($ent->setState(Entity::STATE_CACHED)->setUpdated(time()));
            }
        }
    
        $this->cache->set($obj->request->key, $repo->findNewFreeCompanies(), 300);
        return $this;
    }
    
    public function handleUpdatedFreeCompany()
    {
        $this->io->text(__METHOD__);
        
        /** @var FreeCompanyRepository $repo */
        $repo = $this->getRepository(FreeCompany::class);
        
        // get queue object and grab response
        $obj = $this->getQueueObject(ServiceQueues::CACHE_FREECOMPANY_QUEUE);
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
                LodestoneData::save('freecompany', 'data', $row->id, $row->data);
                $this->persist($ent->setState(Entity::STATE_CACHED)->setUpdated(time()));
            }
        }

        $this->cache->set($obj->request->key, $repo->findFreeCompaniesToUpdate());
    
        // get queue object and grab response
        $obj = $this->getQueueObject(ServiceQueues::CACHE_FREECOMPANY_MEMBERS_QUEUE);
        $this->getResponseData($obj->response);
    
        // process incoming members
        if ($obj->response->data) {
            foreach ($obj->response->data as $row) {
                if (!isset($row->data)) {
                    continue;
                }
                
                // save data
                LodestoneData::save('freecompany', 'members', $row->id, $row->data);
            }
        }
    
        $this->cache->set($obj->request->key, $repo->findFreeCompaniesToUpdate(), 300);
        return $this;
    }
}
