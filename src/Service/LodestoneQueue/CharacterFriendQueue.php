<?php

namespace App\Service\LodestoneQueue;

use App\Entity\Character;
use App\Entity\CharacterFriends;
use App\Entity\Entity;
use App\Service\Content\LodestoneData;
use Doctrine\ORM\EntityManagerInterface;

class CharacterFriendQueue
{
    use QueueTrait;

    /**
     * What method to call on the Lodestone Parser API
     */
    const METHOD = LodestoneApi::GET_CHARACTER_FRIENDS;

    /**
     * Get entity from database, if it doesn't exist, make one.
     */
    protected static function getEntity(EntityManagerInterface $em, $lodestoneId)
    {
        return $em->getRepository(Character::class)->find($lodestoneId) ?: new Character($lodestoneId);
    }

    /**
     * Handle response specific to this queue
     */
    protected static function handle(EntityManagerInterface $em, CharacterFriends $ca, $data): void
    {
        LodestoneData::save('character', 'friends', $ca->getId(), $data);
        $em->persist(
            $ca->setState(Entity::STATE_CACHED)->setUpdated(time())
        );
    }
}
