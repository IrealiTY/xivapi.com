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
        $lodestoneId = $character->getId();

        // if the previous state was "adding" then this response means it's
        // a new character and we can request achievements + friends
        if ($character->getState() === Entity::STATE_ADDING) {
            // add their FC too
            if ($data->FreeCompanyId && $em->getRepository(FreeCompany::class)->find($data->FreeCompanyId) === null) {
                $em->persist((new FreeCompany($data->FreeCompanyId))->setState(Entity::STATE_ADDING));
                $em->flush();

                FreeCompanyQueue::request($data->FreeCompanyId, 'free_company_add');
            }
        }

        // convert character data from names to ids
        $data = LodestoneData::convertCharacterData($data);

        LodestoneData::save('character', 'data', $lodestoneId, $data);
        $em->persist($character->setState(Entity::STATE_CACHED)->setUpdated(time()));
    }
}
