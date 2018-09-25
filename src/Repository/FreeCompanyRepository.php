<?php

namespace App\Repository;

use App\Entity\Character;
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

    public function findNewFreeCompanies()
    {
        $filter = [ 'state' => FreeCompany::STATE_ADDING ];
        $order  = [ 'updated' => 'asc' ];
        return $this->findBy($filter, $order, ServiceQueues::TOTAL_FREE_COMPANY_UPDATES);
    }

    public function findFreeCompaniesToUpdate()
    {
        $filter = [ 'state' => FreeCompany::STATE_CACHED ];
        $order  = [ 'updated' => 'asc' ];
        return $this->findBy($filter, $order, ServiceQueues::TOTAL_FREE_COMPANY_UPDATES);
    }
}
