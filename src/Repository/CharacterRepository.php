<?php

namespace App\Repository;

use App\Entity\Character;
use App\Service\Lodestone\ServiceQueues;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

class CharacterRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Character::class);
    }

    public function getUpdateIds(int $priority = 0, int $page = 0)
    {
        $sql = $this->createQueryBuilder('c');
        $sql->select('c.id')
            ->where("c.priority = :p")
            ->setParameter(':p', $priority)
            ->setMaxResults(ServiceQueues::TOTAL_CHARACTER_UPDATES)
            ->setFirstResult(ServiceQueues::TOTAL_CHARACTER_UPDATES * $page);
        
        return $sql->getQuery()->getResult();
    }
}
