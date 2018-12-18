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

    public function getRequestTimeStats()
    {
        // SELECT COUNT(*) as total, MAX(added) as finish_time, MIN(added) as start_time, MAX(added)-MIN(added) as duration, COUNT(*)/(MAX(added)-MIN(added)) as req_sec FROM lodestone_statistic;

        $sql = $this->createQueryBuilder('ls');
        $sql->select([
                'ls.cronjob',
                'COUNT(ls.id) as total',
                'MAX(ls.added) as finish_time',
                'MIN(ls.added) as start_time',
                'MAX(ls.added)-MIN(ls.added) as duration',
                'COUNT(ls.id)/(MAX(ls.added)-MIN(ls.added)) as req_sec'
            ])
            ->groupBy('ls.cronjob')
            ->orderBy('ls.finish_time', 'desc')
            ->setMaxResults(100);

        return $sql->getQuery()->getResult();
    }
}
