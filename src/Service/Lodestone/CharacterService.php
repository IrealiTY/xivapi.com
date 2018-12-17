<?php

namespace App\Service\Lodestone;

use App\Entity\Character;
use App\Entity\CharacterAchievements;
use App\Entity\CharacterFriends;
use App\Entity\Entity;
use App\Service\Content\LodestoneData;
use App\Service\LodestoneQueue\CharacterAchievementQueue;
use App\Service\LodestoneQueue\CharacterFriendQueue;
use App\Service\LodestoneQueue\CharacterQueue;
use App\Service\Service;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CharacterService extends Service
{
    /**
     * Get a character; this will add the character if they do not exist
     */
    public function get($id): array
    {
        /** @var Character $ent */
        $ent = $this->getRepository(Character::class)->find($id);
        return $ent ? $this->fetch($ent) : $this->register($id);
    }
    
    /**
     * Fetch an existing character
     */
    public function fetch(Character $ent): array
    {
        if ($ent->getState() === Character::STATE_CACHED) {
            $data = LodestoneData::load('character', 'data', $ent->getId());
        }
        
        return [ $ent, $data[0] ?? null, $data[1] ?? null ];
    }
    
    /**
     * Register a new character to be added to the site
     */
    public function register($id): array
    {
        if (!is_numeric($id) || strlen($id) > 16) {
            throw new NotAcceptableHttpException("Invalid character id: {$id}");
        }
    
        // send a request to rabbit mq to add this character + friends + achievements
        CharacterQueue::request($id, 'character_add');
        CharacterFriendQueue::request($id, 'character_friends_add');
        CharacterAchievementQueue::request($id, 'character_achievements_add');
        
        return [ new Character($id), null, null ];
    }
    
    public function getAchievements($id): array
    {
        /** @var Character $ent */
        $ent = $this->getRepository(CharacterAchievements::class)->find($id);

        if (!$ent) {
            [ null, null, null ];
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
