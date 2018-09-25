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

    /**
     * Returns a list of new characters
     */
    public function findNewCharacters()
    {
        $filter = [ 'state' => Character::STATE_ADDING ];
        $order  = [ 'updated' => 'asc' ];
        return $this->findBy($filter, $order, ServiceQueues::TOTAL_CHARACTER_UPDATES);
    }

    /**
     * Returns a list of characters to update
     */
    public function findCharactersToUpdate()
    {
        $filter = [ 'state' => Character::STATE_CACHED ];
        $order  = [ 'updated' => 'asc' ];
        return $this->findBy($filter, $order, ServiceQueues::TOTAL_CHARACTER_UPDATES);
    }
}
