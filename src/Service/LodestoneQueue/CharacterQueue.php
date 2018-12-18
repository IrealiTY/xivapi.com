<?php

namespace App\Service\LodestoneQueue;

use App\Entity\Character;
use App\Entity\Entity;
use App\Entity\FreeCompany;
use App\Service\Content\LodestoneData;
use Doctrine\ORM\EntityManagerInterface;

class CharacterQueue
{
    use QueueTrait;

    /**
     * What method to call on the Lodestone Parser API
     */
    const METHOD = LodestoneApi::GET_CHARACTER;

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
    protected static function handle(EntityManagerInterface $em, Character $character, $data): void
    {
        // convert character data from names to ids
        $data = CharacterConverter::handle($data);

        LodestoneData::save('character', 'data', $character->getId(), $data);
        self::save($em, $character->setStateCached());
    }
}
