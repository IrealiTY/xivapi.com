<?php

namespace App\Repository;

use App\Entity\LodestoneStatistic;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

class LodestoneStatisticRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, LodestoneStatistic::class);
    }

    public function removeExpiredRows()
    {
        // 24 hours
        $expiry = time() - (60*60*24);

        $sql = $this->createQueryBuilder('ls');
        $sql->delete()->where('ls.added < :time')->setParameter(':time', $expiry);
        $sql->getQuery()->execute();
    }
}
