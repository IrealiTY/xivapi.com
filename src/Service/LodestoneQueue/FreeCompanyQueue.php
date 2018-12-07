<?php

namespace App\Service\LodestoneQueue;

use App\Entity\Entity;
use App\Entity\FreeCompany;
use App\Service\Content\LodestoneData;
use Doctrine\ORM\EntityManagerInterface;

class FreeCompanyQueue
{
    use QueueTrait;

    /**
     * What method to call on the Lodestone Parser API
     */
    const METHOD = LodestoneApi::GET_FREE_COMPANY;

    /**
     * Get entity from database, if it doesn't exist, make one.
     */
    protected static function getEntity(EntityManagerInterface $em, $lodestoneId)
    {
        return $em->getRepository(FreeCompany::class)->find($lodestoneId) ?: new FreeCompany($lodestoneId);
    }

    /**
     * Handle response specific to this queue
     */
    public static function handle(EntityManagerInterface $em, FreeCompany $fc, $data): void
    {
        LodestoneData::save('freecompany', 'data', $fc->getId(), $data);
        $em->persist($fc->setState(Entity::STATE_CACHED)->setUpdated(time()));
    }
}