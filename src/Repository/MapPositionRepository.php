<?php

namespace App\Repository;

use App\Entity\MapPosition;
use App\Service\Redis\Cache;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

class MapPositionRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, MapPosition::class);
    }

    public function getTotal()
    {
        $cache = new Cache();

        if ($result = $cache->get(__METHOD__)) {
            return $result;
        }

        $qb = $this->createQueryBuilder('mp');
        $qb->select('COUNT(mp.ID) AS total');

        $total = $qb->getQuery()->getSingleScalarResult();
        $cache->set(__METHOD__, $total, Cache::DEFAULT_TIME);
        return $total;
    }
}
