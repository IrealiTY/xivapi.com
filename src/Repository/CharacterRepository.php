<?php

namespace App\Repository;

use App\Entity\Character;
use App\Entity\Entity;
use App\Service\Lodestone\ServiceQueues;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

class CharacterRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Character::class);
    }

    /**
     * Returns a list of new characters
     */
    public function toAdd($offset = 0)
    {
        $filter = [ 'state' => Entity::STATE_ADDING ];
        $order  = [ 'updated' => 'asc' ];
        $offset = $offset * ServiceQueues::TOTAL_CHARACTER_UPDATES;

        return $this->findBy($filter, $order, ServiceQueues::TOTAL_CHARACTER_UPDATES, $offset);
    }

    /**
     * Returns a list of characters to update
     */
    public function toUpdate($offset = 0, $priority = 0)
    {
        $filter = [ 'state' => Entity::STATE_CACHED, 'priority' => $priority ];
        $order  = [ 'updated' => 'asc' ];
        $offset = $offset * ServiceQueues::TOTAL_CHARACTER_UPDATES;

        return $this->findBy($filter, $order, ServiceQueues::TOTAL_CHARACTER_UPDATES, $offset);
    }
}
