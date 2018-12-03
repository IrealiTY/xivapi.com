<?php

namespace App\Service\LodestoneQueue;

use App\Entity\Entity;
use App\Entity\PvPTeam;
use App\Service\Content\LodestoneData;
use Doctrine\ORM\EntityManagerInterface;

class PvPTeamQueue
{
    use QueueTrait;

    /**
     * What method to call on the Lodestone Parser API
     */
    const METHOD = LodestoneApi::GET_PVP_TEAM_MEMBERS;

    /**
     * Get entity from database, if it doesn't exist, make one.
     */
    protected static function getEntity(EntityManagerInterface $em, $lodestoneId)
    {
        return $em->getRepository(PvPTeam::class)->find($lodestoneId) ?: new PvPTeam($lodestoneId);
    }

    /**
     * Handle response specific to this queue
     */
    public static function response(EntityManagerInterface $em, PvPTeam $fc, $data): void
    {
        LodestoneData::save('pvpteam', 'data', $fc->getId(), $data);
        $em->persist(
            $fc->setState(Entity::STATE_CACHED)->setUpdated(time())
        );
    }
}
