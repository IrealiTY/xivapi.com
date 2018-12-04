<?php

namespace App\Service\LodestoneQueue;

use App\Entity\Entity;
use App\Entity\Linkshell;
use App\Service\Content\LodestoneData;
use Doctrine\ORM\EntityManagerInterface;

class LinkshellQueue
{
    use QueueTrait;

    /**
     * What method to call on the Lodestone Parser API
     */
    const METHOD = LodestoneApi::GET_LINKSHELL_MEMBERS;

    /**
     * Get entity from database, if it doesn't exist, make one.
     */
    protected static function getEntity(EntityManagerInterface $em, $lodestoneId)
    {
        return $em->getRepository(Linkshell::class)->find($lodestoneId) ?: new Linkshell($lodestoneId);
    }

    /**
     * Handle response specific to this queue
     */
    public static function handle(EntityManagerInterface $em, Linkshell $fc, $data): void
    {
        LodestoneData::save('linkshell', 'data', $fc->getId(), $data);
        $em->persist($fc->setState(Entity::STATE_CACHED)->setUpdated(time()));
    }
}
