<?php

namespace App\Repository;

use App\Entity\Entity;
use App\Entity\Linkshell;
use App\Service\Lodestone\ServiceQueues;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

class LinkshellRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Linkshell::class);
    }

    /**
     * Returns a list of new linkshells
     */
    public function toAdd($offset = 0)
    {
        $filter = [ 'state' => Entity::STATE_ADDING ];
        $order  = [ 'updated' => 'asc' ];
        $offset = $offset * ServiceQueues::TOTAL_LINKSHELL_UPDATES;

        return $this->findBy($filter, $order, ServiceQueues::TOTAL_LINKSHELL_UPDATES, $offset);
    }

    /**
     * Returns a list of linkshells to update
     */
    public function toUpdate($offset = 0, $priority = 0)
    {
        $filter = [ 'state' => Entity::STATE_CACHED, 'priority' => $priority ];
        $order  = [ 'updated' => 'asc' ];
        $offset = $offset * ServiceQueues::TOTAL_LINKSHELL_UPDATES;

        return $this->findBy($filter, $order, ServiceQueues::TOTAL_LINKSHELL_UPDATES, $offset);
    }
}
