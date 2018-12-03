<?php

namespace App\Repository;

use App\Entity\Character;
use App\Entity\Entity;
use App\Entity\FreeCompany;
use App\Service\Lodestone\ServiceQueues;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

class FreeCompanyRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, FreeCompany::class);
    }

    /**
     * Returns a list of new free companies
     */
    public function toAdd($offset = 0)
    {
        $filter = [ 'state' => Entity::STATE_ADDING ];
        $order  = [ 'updated' => 'asc' ];
        $offset = $offset * ServiceQueues::TOTAL_FREE_COMPANY_UPDATES;

        return $this->findBy($filter, $order, ServiceQueues::TOTAL_FREE_COMPANY_UPDATES, $offset);
    }

    /**
     * Returns a list of free companies to update
     */
    public function toUpdate($offset = 0, $priority = 0)
    {
        $filter = [ 'state' => Entity::STATE_CACHED, 'priority' => $priority ];
        $order  = [ 'updated' => 'asc' ];
        $offset = $offset * ServiceQueues::TOTAL_FREE_COMPANY_UPDATES;

        return $this->findBy($filter, $order, ServiceQueues::TOTAL_FREE_COMPANY_UPDATES, $offset);
    }
}
