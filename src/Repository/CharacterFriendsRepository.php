<?php

namespace App\Repository;

use App\Entity\CharacterFriends;
use App\Entity\Entity;
use App\Service\Lodestone\ServiceQueues;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

class CharacterFriendsRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, CharacterFriends::class);
    }

    /**
     * Returns a list of character friends to update
     * - This doesn't update the actual friends it updates a characters friend LIST
     */
    public function toUpdate($offset = 0, $priority = 0)
    {
        $filter = [ 'state' => Entity::STATE_CACHED, 'priority' => $priority ];
        $order  = [ 'updated' => 'asc' ];
        $offset = $offset * ServiceQueues::TOTAL_CHARACTER_FRIENDS;

        return $this->findBy($filter, $order, ServiceQueues::TOTAL_CHARACTER_FRIENDS, $offset);
    }
}
