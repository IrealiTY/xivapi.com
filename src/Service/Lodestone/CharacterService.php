<?php

namespace App\Service\Lodestone;

use App\Entity\Character;
use App\Entity\CharacterAchievements;
use App\Entity\CharacterFriends;
use App\Entity\Entity;
use App\Service\Content\LodestoneData;
use App\Service\Service;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CharacterService extends Service
{
    public function get($id): array
    {
        /** @var Character $ent */
        $ent = $this->getRepository(Character::class)->find($id);
        return $ent ? $this->fetch($ent) : $this->register($id);
    }
    
    public function fetch(Character $ent): array
    {
        if ($ent->getState() === Character::STATE_CACHED) {
            $this->persist($ent->setUpdated($ent->getUpdated()-10));
            $data = LodestoneData::load('character', 'data', $ent->getId());
        }
        
        return [ $ent, $data[0] ?? null, $data[1] ?? null ];
    }
    
    public function register($id): array
    {
        if (!is_numeric($id)) {
            throw new NotAcceptableHttpException("ID is not numeric: {$id}");
        }
        
        if (strlen($id) > 32) {
            throw new NotAcceptableHttpException("ID length is too long");
        }
        
        $ent = new Character($id);
        $this->persist($ent);
        return [ $ent, null, null ];
    }
    
    public function getAchievements($id): array
    {
        /** @var Character $ent */
        $ent = $this->getRepository(CharacterAchievements::class)->find($id);

        if (!$ent) {
            throw new NotFoundHttpException();
        }
        
        if ($ent->getState() == Entity::STATE_CACHED) {
            $data = LodestoneData::load('character', 'achievements', $id);
        }
    
        return [ $ent, $data[0] ?? null, $data[1] ?? null ];
    }

    public function getFriends($id): array
    {
        /** @var Character $ent */
        $ent = $this->getRepository(CharacterFriends::class)->find($id);

        if (!$ent) {
            throw new NotFoundHttpException();
        }
    
        if ($ent->getState() == Entity::STATE_CACHED) {
            $data = LodestoneData::load('character', 'friends', $id);
        }
    
        return [ $ent, $data[0] ?? null, $data[1] ?? null ];
    }
    
    public function delete(Character $ent)
    {
        $path = LodestoneData::folder('character', $ent->getId());
        @unlink("{$path}/data.json");
        @unlink("{$path}/achievements.json");
        @unlink("{$path}/friends.json");
        $this->remove($ent);
    }
}
