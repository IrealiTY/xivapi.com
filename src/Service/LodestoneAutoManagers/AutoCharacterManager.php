<?php

namespace App\Service\LodestoneAutoManagers;

use App\Entity\Character;
use App\Entity\CharacterAchievements;
use App\Entity\CharacterFriends;
use App\Entity\Entity;
use App\Repository\CharacterAchievementRepository;
use App\Repository\CharacterFriendsRepository;
use App\Repository\CharacterRepository;
use App\Service\Content\LodestoneData;
use App\Service\Lodestone\ServiceQueues;
use Lodestone\Exceptions\AchievementsPrivateException;
use Lodestone\Exceptions\GenericException;
use Lodestone\Exceptions\NotFoundException;

class AutoCharacterManager extends LodestoneManager
{
    public function handleAddedCharacters()
    {
        $this->io->text(__METHOD__);
        
        /** @var CharacterRepository $repo */
        $repo = $this->getRepository(Character::class);
        
        // set repository, get queue object and grab response
        $obj = $this->getQueueObject(ServiceQueues::CACHE_CHARACTER_QUEUE . '_add');
        $this->getResponseData($obj->response);
        
        // process incoming data
        if ($obj->response->data) {
            foreach ($obj->response->data as $row) {
                $ent = $repo->find($row->id);
                
                // skip borked stuff
                if (!$ent) {
                    continue;
                };
                
                if ($row->data === GenericException::class) {
                    // todo - idk what to do here ...
                    continue;
                }
                
                // if not found
                if (is_string($row->data) && $row->data === NotFoundException::class) {
                    $this->persist($ent->setState(Entity::STATE_NOT_FOUND)->setUpdated(time()));
                    continue;
                }
                
                // convert and save
                $row->data = LodestoneData::convertCharacterData($row->data);
                LodestoneData::save('character', 'data', $row->id, $row->data);
                $this->persist($ent->setState(Entity::STATE_CACHED)->setUpdated(time()));
                
                // this is a special case, when a character is added
                // Achievements + Friends are also added
                $this->persist((new CharacterAchievements($row->id))->setState(Entity::STATE_CACHED));
                $this->persist((new CharacterFriends($row->id))->setState(Entity::STATE_CACHED));
            }
        }

        $this->io->text("Updated queue: {$obj->request->key}");
        $this->cache->set($obj->request->key, $repo->findNewCharacters(), (60*60));
        return $this;
    }
    
    public function handleUpdatedCharacters()
    {
        $this->io->text(__METHOD__);
        
        /** @var CharacterRepository $repo */
        $repo = $this->getRepository(Character::class);
    
        // set repository, get queue object and grab response
        $obj = $this->getQueueObject(ServiceQueues::CACHE_CHARACTER_QUEUE);
        $this->getResponseData($obj->response);
    
        // process incoming data
        if ($obj->response->data) {
            foreach ($obj->response->data as $row) {
                /** @var Character $character */
                $ent = $repo->find($row->id);
    
                // skip
                if (!$ent
                    || empty($row->data)
                    || $row->data === GenericException::class
                    || $ent->getState() == Entity::STATE_BLACKLISTED)
                {
                    continue;
                }
            
                // if not found
                if (is_string($row->data) && $row->data === NotFoundException::class) {
                    $this->persist($ent->setState(Entity::STATE_NOT_FOUND)->setUpdated(time()));
                    continue;
                }
            
                // convert and save
                $row->data = LodestoneData::convertCharacterData($row->data);
                LodestoneData::save('character', 'data', $row->id, $row->data);
                $this->persist($ent->setState(Entity::STATE_CACHED)->setUpdated(time()));
            }
        }
    
        $this->io->text("Updated queue: {$obj->request->key}");
        $this->cache->set($obj->request->key, $repo->findCharactersToUpdate(), (60*60));
        return $this;
    }
    
    public function handleUpdatedAchievements()
    {
        $this->io->text(__METHOD__);
        
        /** @var CharacterAchievementRepository $repo */
        $repo = $this->getRepository(CharacterAchievements::class);
    
        // set repository, get queue object and grab response
        $obj = $this->getQueueObject(ServiceQueues::CACHE_ACHIEVEMENTS_QUEUE);
        $this->getResponseData($obj->response);
    
        // process incoming data
        if ($obj->response->data) {
            foreach ($obj->response->data as $row) {
                /** @var Entity $ent */
                $ent = $repo->find($row->id);
    
                // skip
                if (!$ent
                    || empty($row->data)
                    || $row->data === GenericException::class
                    || $ent->getState() == Entity::STATE_BLACKLISTED)
                {
                    continue;
                }

                // not found
                if (is_string($row->data) && $row->data === NotFoundException::class) {
                    $this->persist($ent->setState(Entity::STATE_NOT_FOUND)->setUpdated(time()));
                    continue;
                }

                // private
                if (is_string($row->data) && $row->data === AchievementsPrivateException::class) {
                    LodestoneData::delete('character', 'achievements', $row->id);
                    $this->persist($ent->setState(Entity::STATE_PRIVATE)->setUpdated(time()));
                    continue;
                }
            
                // convert and save
                LodestoneData::save('character', 'achievements', $row->id, $row->data);
                $this->persist($ent->setState(Entity::STATE_CACHED)->setUpdated(time()));
            }
        }
    
        $this->io->text("Updated queue: {$obj->request->key}");
        $this->cache->set($obj->request->key, $repo->findCharacterAchievementsToUpdate(), (60*60));
        return $this;
    }
    
    public function handleUpdatedFriends()
    {
        $this->io->text(__METHOD__);
        
        /** @var CharacterFriendsRepository $repo */
        $repo = $this->getRepository(CharacterFriends::class);
    
        // set repository, get queue object and grab response
        $obj = $this->getQueueObject(ServiceQueues::CACHE_FRIENDS_QUEUE);
        $this->getResponseData($obj->response);
    
        // process incoming data
        if ($obj->response->data) {
            foreach ($obj->response->data as $row) {
                /** @var Entity $ent */
                $ent = $repo->find($row->id);
    
                // skip
                if (!$ent
                    || empty($row->data)
                    || $row->data === GenericException::class
                    || $ent->getState() == Entity::STATE_BLACKLISTED)
                {
                    continue;
                }
            
                // not found
                if (is_string($row->data) && $row->data === NotFoundException::class) {
                    $this->persist($ent->setState(Entity::STATE_NOT_FOUND)->setUpdated(time()));
                    continue;
                }
    
                // private
                /*
                if (is_string($row->data) && $row->data === FriendsP::class) {
                    $this->persist($ent->setState(Entity::STATE_NOT_FOUND)->setUpdated(time()));
                    continue;
                }
                */
            
                // convert and save
                LodestoneData::save('character', 'friends', $row->id, $row->data);
                $this->persist($ent->setState(Entity::STATE_CACHED)->setUpdated(time()));
            }
        }
    
        $this->io->text("Updated queue: {$obj->request->key}");
        $this->cache->set($obj->request->key, $repo->findCharacterFriendsToUpdate(), (60*60));
        return $this;
    }
}
