<?php

namespace App\Repository;

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

    public function findNewLinkshells()
    {
        $filter = [ 'state' => Linkshell::STATE_ADDING ];
        $order  = [ 'updated' => 'asc' ];
        return $this->findBy($filter, $order, ServiceQueues::TOTAL_LINKSHELL_UPDATES);
    }

    public function findLinkshellsToUpdate()
    {
        $filter = [ 'state' => Linkshell::STATE_CACHED ];
        $order  = [ 'updated' => 'asc' ];
        return $this->findBy($filter, $order, ServiceQueues::TOTAL_LINKSHELL_UPDATES);
    }
}
