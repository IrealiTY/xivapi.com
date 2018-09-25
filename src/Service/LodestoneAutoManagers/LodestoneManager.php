<?php

namespace App\Service\LodestoneAutoManagers;

use App\Service\Redis\Cache;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class LodestoneManager
{
    /** @var Cache */
    protected $cache;
    /** @var EntityManagerInterface */
    protected $em;
    /** @var SymfonyStyle */
    protected $io;
    
    public function __construct(Cache $cache, EntityManagerInterface $em)
    {
        $this->cache = $cache;
        $this->em    = $em;
    }
    
    public function setSymfonyStyle(SymfonyStyle $io)
    {
        $this->io = $io;
        return $this;
    }
    
    public function getQueueObject($queue)
    {
        return (Object)[
            'request'  => (Object)[
                'key'  => $queue .'_req',
                'data' => [],
            ],
            'response' => (Object)[
                'key'  => $queue .'_res',
                'data' => [],
            ]
        ];
    }
    
    public function getResponseData($obj)
    {
        // grab response data and then delete it from the cache
        $obj->data = $this->cache->get($obj->key);
        $this->cache->delete($obj->key);
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
