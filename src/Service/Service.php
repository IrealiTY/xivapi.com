<?php

namespace App\Service;

use App\Service\Redis\Cache;
use Doctrine\ORM\EntityManagerInterface;

class Service
{
    /** @var EntityManagerInterface */
    public $em;
    /** @var Cache */
    public $cache;

    public function __construct(EntityManagerInterface $em, Cache $cache)
    {
        $this->em    = $em;
        $this->cache = $cache;
    }

    public function persist($object): void
    {
        $this->em->persist($object);
        $this->em->flush();
    }

    public function remove($object): void
    {
        $this->em->remove($object);
        $this->em->flush();
    }

    public function getRepository($class)
    {
        return $this->em->getRepository($class);
    }
}
