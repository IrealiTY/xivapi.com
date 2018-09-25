<?php

namespace App\Repository;

use App\Entity\CharacterAchievements;
use App\Service\Lodestone\ServiceQueues;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

class CharacterAchievementRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, CharacterAchievements::class);
    }

    public function findCharacterAchievementsToUpdate()
    {
        $filter = [ 'state' => CharacterAchievements::STATE_CACHED ];
        $order  = [ 'updated' => 'asc' ];
        return $this->findBy($filter, $order, ServiceQueues::TOTAL_ACHIEVEMENT_UPDATES);
    }
}
