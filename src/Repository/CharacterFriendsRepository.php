<?php

namespace App\Repository;

use App\Entity\CharacterFriends;
use App\Service\Lodestone\ServiceQueues;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

class CharacterFriendsRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, CharacterFriends::class);
    }

    public function findCharacterFriendsToUpdate()
    {
        $filter = [ 'state' => CharacterFriends::STATE_CACHED ];
        $order  = [ 'updated' => 'asc' ];
        return $this->findBy($filter, $order, ServiceQueues::TOTAL_CHARACTER_FRIENDS);
    }
}
