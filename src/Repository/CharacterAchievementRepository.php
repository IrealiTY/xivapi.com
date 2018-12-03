<?php

namespace App\Repository;

use App\Entity\CharacterAchievements;
use App\Entity\Entity;
use App\Service\Lodestone\ServiceQueues;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

class CharacterAchievementRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, CharacterAchievements::class);
    }

    /**
     * Returns a list of character achievements to update
     */
    public function toUpdate($offset = 0, $priority = 0)
    {
        $filter = [ 'state' => Entity::STATE_CACHED, 'priority' => $priority ];
        $order  = [ 'updated' => 'asc' ];
        $offset = $offset * ServiceQueues::TOTAL_ACHIEVEMENT_UPDATES;

        return $this->findBy($filter, $order, ServiceQueues::TOTAL_ACHIEVEMENT_UPDATES, $offset);
    }
}
