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

    public function findNewPvPTeams()
    {
        $filter = [ 'state' => PvPTeam::STATE_ADDING ];
        $order  = [ 'updated' => 'asc' ];
        return $this->findBy($filter, $order, ServiceQueues::TOTAL_PVP_TEAM_UPDATES);
    }

    public function findPvPTeamsToUpdate()
    {
        $filter = [ 'state' => PvPTeam::STATE_CACHED ];
        $order  = [ 'updated' => 'asc' ];
        return $this->findBy($filter, $order, ServiceQueues::TOTAL_PVP_TEAM_UPDATES);
    }
}
