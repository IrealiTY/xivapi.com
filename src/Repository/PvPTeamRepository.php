<?php

namespace App\Repository;

use App\Entity\PvPTeam;
use App\Service\Lodestone\ServiceQueues;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

class PvPTeamRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, PvPTeam::class);
    }

    /**
     * Returns a list of new pvp teams
     */
    public function toAdd($offset = 0)
    {
        $filter = [ 'state' => PvPTeam::STATE_ADDING ];
        $order  = [ 'updated' => 'asc' ];
        $offset = $offset * ServiceQueues::TOTAL_LINKSHELL_UPDATES;

        return $this->findBy($filter, $order, ServiceQueues::TOTAL_PVP_TEAM_UPDATES, $offset);
    }

    /**
     * Returns a list of pvp teams to update
     */
    public function toUpdate($offset = 0, $priority = 0)
    {
        $filter = [ 'state' => PvPTeam::STATE_CACHED, 'priority' => $priority ];
        $order  = [ 'updated' => 'asc' ];
        $offset = $offset * ServiceQueues::TOTAL_LINKSHELL_UPDATES;

        return $this->findBy($filter, $order, ServiceQueues::TOTAL_PVP_TEAM_UPDATES, $offset);
    }
}
